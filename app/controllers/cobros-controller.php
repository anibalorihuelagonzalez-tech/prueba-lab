<?php
/**
 * =========================================================================
 * app/controllers/cobros-controller.php  —  COBRANZAS (cuentas por cobrar y pagos)
 * =========================================================================
 * Acciones (parámetro "action"):
 *
 *   catalogos           Métodos de pago, tasa del día, % de IGTF
 *   pendientes          Facturas con saldo de una clínica (para aplicar un cobro)
 *   registrar           Registra un cobro, lo aplica a facturas y genera el asiento contable
 *   listar_pagos        Historial de cobros con filtros
 *   ver_pago            Detalle de un cobro y a qué facturas se aplicó
 *   anular_pago         Anula un cobro (revierte asiento y devuelve el saldo a las facturas)
 *   conciliar           Marca un cobro como CONCILIADO (ya verificado en el banco)
 *   cxc_resumen         Cuentas por cobrar por clínica con antigüedad de saldos
 *   estado_cuenta       Estado de cuenta de una clínica (facturas, notas de crédito y pagos)
 *   whatsapp_cobranza   Arma el mensaje de WhatsApp para recordar un pago pendiente
 *
 * Reglas del cobro:
 *  - Un cobro nunca puede ser mayor que la deuda pendiente (no se manejan anticipos).
 *  - Si no se indica a qué facturas aplicar, se aplica a las más antiguas primero (FIFO).
 *  - Cobros en divisas con método "aplica IGTF" suman el impuesto sobre el monto cobrado.
 *  - La diferencia entre la tasa de la factura y la del cobro se registra como
 *    ganancia/pérdida por diferencial cambiario.
 */
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
require_once __DIR__ . '/../helpers/finanzas.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * Bolívares "históricos" que quedan pendientes de una factura:
 * total en Bs - cobros ya aplicados - notas de crédito emitidas.
 * Sirve para saldar la cuenta por cobrar exactamente, sin céntimos sobrantes.
 */
function bsPendienteFactura(PDO $db, array $f): float
{
    $st = $db->prepare("SELECT COALESCE(SUM(a.monto_bs_historico),0) FROM ventas_pagos_aplicaciones a
                        INNER JOIN ventas_pagos p ON p.id = a.pago_id AND p.estatus <> 'ANULADO'
                        WHERE a.documento_id = ?");
    $st->execute([$f['id']]);
    $cobrado = (float)$st->fetchColumn();
    $st = $db->prepare("SELECT COALESCE(SUM(total_bs),0) FROM ventas_documentos
                        WHERE documento_origen_id = ? AND tipo = 'NOTA_CREDITO' AND estatus = 'EMITIDO'");
    $st->execute([$f['id']]);
    $credito = (float)$st->fetchColumn();
    return r2((float)$f['total_bs'] - $cobrado - $credito);
}

$in        = entrada();
$accion    = (string)($in['action'] ?? 'listar_pagos');
$db        = conexion();
$usuarioId = (int)Auth::id();

try {
    switch ($accion) {

        // ===================================================================
        case 'catalogos': {
            $metodos = $db->query("SELECT id, nombre, tipo, moneda_codigo, aplica_igtf, requiere_referencia
                                   FROM metodos_pago WHERE is_active = 1 ORDER BY moneda_codigo, nombre")->fetchAll(PDO::FETCH_ASSOC);
            $clinicas = $db->query("SELECT id, razon_social, tipo_documento, numero_documento, telefono
                                    FROM clinicas WHERE estatus = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
            try {
                $tasa = tasaInfo($db, 'USD', 'VES');
            } catch (NegocioException $e) {
                $tasa = ['tasa' => 0, 'fuente' => '', 'desde' => '', 'exacta' => false, 'aviso' => $e->getMessage()];
            }
            responderJson(['status' => 'success', 'metodos' => $metodos, 'clinicas' => $clinicas, 'tasa' => $tasa,
                           'igtf_porcentaje' => (float)configGet($db, 'igtf_porcentaje', '3'), 'hoy' => date('Y-m-d')]);
        }

        // ===================================================================
        case 'pendientes': {
            $st = $db->prepare("SELECT id, numero, fecha_emision, fecha_vencimiento, total_usd, saldo_usd, tasa_cambio, estatus,
                                       GREATEST(0, DATEDIFF(CURDATE(), fecha_vencimiento)) AS dias_mora
                                FROM ventas_documentos
                                WHERE clinica_id = ? AND tipo = 'FACTURA' AND estatus IN ('EMITIDO','PARCIAL') AND saldo_usd > 0
                                ORDER BY fecha_emision ASC, id ASC");
            $st->execute([(int)($in['clinica_id'] ?? 0)]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $total = 0.0;
            foreach ($rows as $r) { $total += (float)$r['saldo_usd']; }
            responderJson(['status' => 'success', 'data' => $rows, 'total_usd' => r2($total)]);
        }

        // ===================================================================
        // REGISTRAR COBRO
        // ===================================================================
        case 'registrar': {
            $res = enTransaccion($db, function () use ($db, $in, $usuarioId) {
                $clinicaId = (int)($in['clinica_id'] ?? 0);
                $st = $db->prepare("SELECT id, razon_social FROM clinicas WHERE id = ?");
                $st->execute([$clinicaId]);
                $clinica = $st->fetch(PDO::FETCH_ASSOC);
                if (!$clinica) {
                    throw new NegocioException('Selecciona la clínica que paga.');
                }
                $st = $db->prepare("SELECT * FROM metodos_pago WHERE id = ? AND is_active = 1");
                $st->execute([(int)($in['metodo_pago_id'] ?? 0)]);
                $metodo = $st->fetch(PDO::FETCH_ASSOC);
                if (!$metodo) {
                    throw new NegocioException('Selecciona un método de pago válido.');
                }
                if (!in_array($metodo['moneda_codigo'], ['USD', 'VES'], true)) {
                    throw new NegocioException('Solo se admiten métodos de pago en USD o VES.');
                }
                $referencia = trim((string)($in['referencia'] ?? ''));
                if ((int)$metodo['requiere_referencia'] && $referencia === '') {
                    throw new NegocioException('Este método de pago requiere el número de referencia.');
                }
                $fecha = fechaValida($in['fecha_pago'] ?? date('Y-m-d'), 'La fecha del pago');
                if ($fecha > date('Y-m-d')) {
                    throw new NegocioException('La fecha del pago no puede ser futura.');
                }
                $monto = r2($in['monto'] ?? 0);
                if ($monto <= 0) {
                    throw new NegocioException('El monto debe ser mayor que cero.');
                }
                $tasa = (float)($in['tasa_cambio'] ?? 0);
                if ($tasa <= 0) {
                    $tasa = tasaVigente($db, 'USD', 'VES', $fecha);
                }

                // --- Equivalencias en USD y Bs, e IGTF ---
                $igtf = 0.0;
                $igtfBs = 0.0;
                if ($metodo['moneda_codigo'] === 'VES') {
                    $montoBs  = $monto;
                    $montoUsd = r2($monto / $tasa);
                } else {
                    $montoUsd = $monto;
                    $montoBs  = r2($monto * $tasa);
                    if ((int)$metodo['aplica_igtf'] && !empty($in['cobrar_igtf'])) {
                        $igtf   = r2($monto * (float)configGet($db, 'igtf_porcentaje', '3') / 100);
                        $igtfBs = r2($igtf * $tasa);
                    }
                }
                if ($montoUsd <= 0) {
                    throw new NegocioException('El monto es demasiado pequeño.');
                }

                // --- Facturas abiertas de la clínica (bloqueadas para que nadie las cobre a la vez) ---
                $st = $db->prepare("SELECT * FROM ventas_documentos
                                    WHERE clinica_id = ? AND tipo = 'FACTURA' AND estatus IN ('EMITIDO','PARCIAL') AND saldo_usd > 0
                                    ORDER BY fecha_emision ASC, id ASC FOR UPDATE");
                $st->execute([$clinicaId]);
                $abiertas = [];
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
                    $abiertas[(int)$f['id']] = $f;
                }
                if (!$abiertas) {
                    throw new NegocioException('Esta clínica no tiene facturas pendientes de pago.');
                }

                // --- Reparto del cobro entre facturas ---
                $reparto = []; // documento_id => monto_usd
                if (!empty($in['aplicaciones']) && is_array($in['aplicaciones'])) {
                    $suma = 0.0;
                    foreach ($in['aplicaciones'] as $a) {
                        $did = (int)($a['documento_id'] ?? 0);
                        $mu  = r2($a['monto_usd'] ?? 0);
                        if ($mu <= 0) { continue; }
                        if (!isset($abiertas[$did])) {
                            throw new NegocioException('Una de las facturas elegidas no está pendiente de pago.');
                        }
                        if ($mu > (float)$abiertas[$did]['saldo_usd'] + 0.004) {
                            throw new NegocioException("Lo aplicado a {$abiertas[$did]['numero']} excede su saldo (" . fmtUsd($abiertas[$did]['saldo_usd']) . ').');
                        }
                        $reparto[$did] = ($reparto[$did] ?? 0) + $mu;
                        $suma += $mu;
                    }
                    if (abs($suma - $montoUsd) > 0.011) {
                        throw new NegocioException('Lo aplicado a facturas (' . fmtUsd($suma) . ') debe ser igual al monto cobrado (' . fmtUsd($montoUsd) . ').');
                    }
                    // Ajuste de centavos por redondeo en la última factura
                    $dif = r2($montoUsd - $suma);
                    if ($dif != 0 && $reparto) {
                        $ult = array_key_last($reparto);
                        $reparto[$ult] = r2($reparto[$ult] + $dif);
                    }
                } else {
                    $resta = $montoUsd;
                    foreach ($abiertas as $did => $f) {
                        if ($resta <= 0.004) { break; }
                        $toma = min($resta, (float)$f['saldo_usd']);
                        $reparto[$did] = r2($toma);
                        $resta = r2($resta - $toma);
                    }
                    if ($resta > 0.011) {
                        throw new NegocioException('El monto cobrado excede la deuda pendiente de la clínica por ' . fmtUsd($resta) . '. No se admiten anticipos.');
                    }
                }
                if (!$reparto) {
                    throw new NegocioException('No hay nada que aplicar con ese monto.');
                }

                // --- Cabecera del cobro ---
                $numero = siguienteNumero($db, 'RECIBO');
                $db->prepare("INSERT INTO ventas_pagos
                    (numero, clinica_id, metodo_pago_id, usuario_id, fecha_pago, moneda_codigo, monto, igtf, tasa_cambio,
                     monto_usd, monto_bs, igtf_bs, referencia, observaciones, estatus)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'REGISTRADO')")
                   ->execute([$numero, $clinicaId, $metodo['id'], $usuarioId, $fecha, $metodo['moneda_codigo'], $monto, $igtf,
                              $tasa, $montoUsd, $montoBs, $igtfBs, $referencia ?: null,
                              trim((string)($in['observaciones'] ?? '')) ?: null]);
                $pagoId = (int)$db->lastInsertId();

                // --- Aplicaciones a facturas ---
                $apls = [];
                $insA = $db->prepare("INSERT INTO ventas_pagos_aplicaciones (pago_id, documento_id, monto_usd, monto_bs_historico) VALUES (?,?,?,?)");
                foreach ($reparto as $did => $mu) {
                    $f = $abiertas[$did];
                    $saldo = (float)$f['saldo_usd'];
                    $bsPend = bsPendienteFactura($db, $f);
                    // Si salda la factura completa, se usa el pendiente exacto; si no, proporcional.
                    $bsHist = (abs($mu - $saldo) < 0.005) ? $bsPend : r2($bsPend * $mu / $saldo);
                    $insA->execute([$pagoId, $did, $mu, $bsHist]);
                    $nuevoSaldo = max(0, r2($saldo - $mu));
                    $db->prepare("UPDATE ventas_documentos SET saldo_usd = ? WHERE id = ?")->execute([$nuevoSaldo, $did]);
                    recalcularEstatusFactura($db, $did);
                    $apls[] = ['documento_id' => $did, 'monto_usd' => $mu, 'monto_bs_historico' => $bsHist];
                }

                // --- Asiento contable del cobro ---
                $asiento = contabilizarCobro($db, [
                    'id' => $pagoId, 'numero' => $numero, 'fecha_pago' => $fecha, 'tasa_cambio' => $tasa,
                    'monto_bs' => $montoBs, 'igtf_bs' => $igtfBs, 'referencia' => $referencia,
                    'clinica_nombre' => $clinica['razon_social'],
                ], $apls, $metodo, $usuarioId);
                $db->prepare("UPDATE ventas_pagos SET asiento_id = ? WHERE id = ?")->execute([$asiento, $pagoId]);

                return ['id' => $pagoId, 'numero' => $numero, 'monto_usd' => $montoUsd, 'igtf' => $igtf];
            });
            responderJson(['status' => 'success', 'id' => $res['id'], 'numero' => $res['numero'],
                           'message' => "Cobro {$res['numero']} registrado por " . fmtUsd($res['monto_usd']) . '.']);
        }

        // ===================================================================
        case 'listar_pagos': {
            $where = [];
            $par = [];
            if (!empty($in['clinica_id'])) { $where[] = 'p.clinica_id = ?'; $par[] = (int)$in['clinica_id']; }
            if (!empty($in['estatus']))    { $where[] = 'p.estatus = ?';    $par[] = (string)$in['estatus']; }
            if (!empty($in['desde']))      { $where[] = 'p.fecha_pago >= ?'; $par[] = fechaValida($in['desde'], 'La fecha "desde"'); }
            if (!empty($in['hasta']))      { $where[] = 'p.fecha_pago <= ?'; $par[] = fechaValida($in['hasta'], 'La fecha "hasta"'); }
            if (!empty($in['q'])) {
                $where[] = '(p.numero LIKE ? OR c.razon_social LIKE ? OR p.referencia LIKE ?)';
                $like = '%' . trim((string)$in['q']) . '%';
                array_push($par, $like, $like, $like);
            }
            $st = $db->prepare("SELECT p.id, p.numero, p.fecha_pago, p.clinica_id, c.razon_social AS clinica_nombre,
                       m.nombre AS metodo, p.moneda_codigo, p.monto, p.igtf, p.tasa_cambio, p.monto_usd, p.monto_bs, p.igtf_bs,
                       p.referencia, p.estatus,
                       (SELECT GROUP_CONCAT(d.numero SEPARATOR ', ') FROM ventas_pagos_aplicaciones a
                          INNER JOIN ventas_documentos d ON d.id = a.documento_id WHERE a.pago_id = p.id) AS facturas
                FROM ventas_pagos p
                INNER JOIN clinicas c ON c.id = p.clinica_id
                INNER JOIN metodos_pago m ON m.id = p.metodo_pago_id"
                . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY p.id DESC LIMIT 500");
            $st->execute($par);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $tu = 0.0; $tb = 0.0; $ti = 0.0;
            foreach ($rows as $r) {
                if ($r['estatus'] !== 'ANULADO') { $tu += (float)$r['monto_usd']; $tb += (float)$r['monto_bs']; $ti += (float)$r['igtf_bs']; }
            }
            responderJson(['status' => 'success', 'data' => $rows,
                           'totales' => ['usd' => r2($tu), 'bs' => r2($tb), 'igtf_bs' => r2($ti)]]);
        }

        // ===================================================================
        case 'ver_pago': {
            $st = $db->prepare("SELECT p.*, c.razon_social AS clinica_nombre, m.nombre AS metodo
                                FROM ventas_pagos p INNER JOIN clinicas c ON c.id = p.clinica_id
                                INNER JOIN metodos_pago m ON m.id = p.metodo_pago_id WHERE p.id = ?");
            $st->execute([(int)($in['id'] ?? 0)]);
            $p = $st->fetch(PDO::FETCH_ASSOC);
            if (!$p) { throw new NegocioException('Cobro no encontrado.'); }
            $st = $db->prepare("SELECT a.monto_usd, a.monto_bs_historico, d.numero, d.fecha_emision
                                FROM ventas_pagos_aplicaciones a INNER JOIN ventas_documentos d ON d.id = a.documento_id
                                WHERE a.pago_id = ?");
            $st->execute([$p['id']]);
            $p['aplicaciones'] = $st->fetchAll(PDO::FETCH_ASSOC);
            responderJson(['status' => 'success', 'pago' => $p]);
        }

        // ===================================================================
        case 'anular_pago': {
            if (!Auth::isAdmin()) {
                responderJson(['status' => 'error', 'message' => 'Solo un administrador puede anular cobros.'], 403);
            }
            enTransaccion($db, function () use ($db, $in, $usuarioId) {
                $motivo = trim((string)($in['motivo'] ?? ''));
                if (mb_strlen($motivo) < 3) { throw new NegocioException('Indica el motivo de la anulación.'); }
                $st = $db->prepare("SELECT * FROM ventas_pagos WHERE id = ? FOR UPDATE");
                $st->execute([(int)($in['id'] ?? 0)]);
                $p = $st->fetch(PDO::FETCH_ASSOC);
                if (!$p) { throw new NegocioException('Cobro no encontrado.'); }
                if ($p['estatus'] === 'ANULADO') { throw new NegocioException('Ese cobro ya está anulado.'); }

                // Revertir el asiento contable
                if ($p['asiento_id']) {
                    anularAsiento($db, (int)$p['asiento_id'], 'Anulación de cobro ' . $p['numero'] . ' - ' . $motivo, $usuarioId);
                }
                // Devolver el saldo a cada factura
                $st = $db->prepare("SELECT documento_id, monto_usd FROM ventas_pagos_aplicaciones WHERE pago_id = ?");
                $st->execute([$p['id']]);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $a) {
                    $db->prepare("UPDATE ventas_documentos SET saldo_usd = saldo_usd + ? WHERE id = ?")->execute([$a['monto_usd'], $a['documento_id']]);
                    recalcularEstatusFactura($db, (int)$a['documento_id']);
                }
                $db->prepare("UPDATE ventas_pagos SET estatus = 'ANULADO', motivo_anulacion = ? WHERE id = ?")->execute([$motivo, $p['id']]);
            });
            responderJson(['status' => 'success', 'message' => 'Cobro anulado. El saldo volvió a las facturas.']);
        }

        // ===================================================================
        case 'conciliar': {
            $st = $db->prepare("UPDATE ventas_pagos SET estatus = 'CONCILIADO' WHERE id = ? AND estatus = 'REGISTRADO'");
            $st->execute([(int)($in['id'] ?? 0)]);
            if ($st->rowCount() === 0) { throw new NegocioException('Solo se pueden conciliar cobros en estado REGISTRADO.'); }
            responderJson(['status' => 'success', 'message' => 'Cobro marcado como conciliado.']);
        }

        // ===================================================================
        // CUENTAS POR COBRAR CON ANTIGÜEDAD DE SALDOS
        // ===================================================================
        case 'cxc_resumen': {
            try { $tasaHoy = tasaVigente($db); } catch (NegocioException $e) { $tasaHoy = 0.0; }
            $rows = $db->query("SELECT c.id AS clinica_id, c.razon_social, c.telefono,
                    COUNT(d.id) AS facturas,
                    SUM(d.saldo_usd) AS saldo_usd,
                    SUM(CASE WHEN d.fecha_vencimiento >= CURDATE() THEN d.saldo_usd ELSE 0 END) AS corriente,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), d.fecha_vencimiento) BETWEEN 1 AND 30 THEN d.saldo_usd ELSE 0 END) AS d1_30,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), d.fecha_vencimiento) BETWEEN 31 AND 60 THEN d.saldo_usd ELSE 0 END) AS d31_60,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), d.fecha_vencimiento) BETWEEN 61 AND 90 THEN d.saldo_usd ELSE 0 END) AS d61_90,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), d.fecha_vencimiento) > 90 THEN d.saldo_usd ELSE 0 END) AS d90_mas,
                    MIN(d.fecha_emision) AS factura_mas_antigua
                FROM ventas_documentos d INNER JOIN clinicas c ON c.id = d.clinica_id
                WHERE d.tipo = 'FACTURA' AND d.estatus IN ('EMITIDO','PARCIAL') AND d.saldo_usd > 0
                GROUP BY c.id, c.razon_social, c.telefono ORDER BY saldo_usd DESC")->fetchAll(PDO::FETCH_ASSOC);
            $tot = ['saldo_usd' => 0, 'corriente' => 0, 'd1_30' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd90_mas' => 0];
            foreach ($rows as $r) { foreach ($tot as $k => $_) { $tot[$k] += (float)$r[$k]; } }
            foreach ($tot as $k => $v) { $tot[$k] = r2($v); }
            responderJson(['status' => 'success', 'data' => $rows, 'totales' => $tot, 'tasa' => $tasaHoy,
                           'total_bs_estimado' => r2($tot['saldo_usd'] * $tasaHoy)]);
        }

        // ===================================================================
        // ESTADO DE CUENTA DE UNA CLÍNICA
        // ===================================================================
        case 'estado_cuenta': {
            $cid = (int)($in['clinica_id'] ?? 0);
            $st = $db->prepare("SELECT id, razon_social, tipo_documento, numero_documento, telefono FROM clinicas WHERE id = ?");
            $st->execute([$cid]);
            $cli = $st->fetch(PDO::FETCH_ASSOC);
            if (!$cli) { throw new NegocioException('Selecciona una clínica.'); }

            $mov = [];
            $st = $db->prepare("SELECT fecha_emision AS fecha, tipo, numero, total_usd FROM ventas_documentos
                                WHERE clinica_id = ? AND ((tipo = 'FACTURA' AND estatus IN ('EMITIDO','PARCIAL','PAGADO'))
                                   OR (tipo = 'NOTA_CREDITO' AND estatus = 'EMITIDO')) ORDER BY fecha_emision, id");
            $st->execute([$cid]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $d) {
                $mov[] = ['fecha' => $d['fecha'], 'orden' => 1, 'tipo' => $d['tipo'], 'numero' => $d['numero'],
                          'detalle' => nombreTipoDocumento($d['tipo']),
                          'cargo' => $d['tipo'] === 'FACTURA' ? (float)$d['total_usd'] : 0.0,
                          'abono' => $d['tipo'] === 'NOTA_CREDITO' ? (float)$d['total_usd'] : 0.0];
            }
            $st = $db->prepare("SELECT p.fecha_pago AS fecha, p.numero, m.nombre AS metodo, p.referencia,
                                       (SELECT COALESCE(SUM(a.monto_usd),0) FROM ventas_pagos_aplicaciones a WHERE a.pago_id = p.id) AS aplicado
                                FROM ventas_pagos p INNER JOIN metodos_pago m ON m.id = p.metodo_pago_id
                                WHERE p.clinica_id = ? AND p.estatus <> 'ANULADO' ORDER BY p.fecha_pago, p.id");
            $st->execute([$cid]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
                $mov[] = ['fecha' => $p['fecha'], 'orden' => 2, 'tipo' => 'PAGO', 'numero' => $p['numero'],
                          'detalle' => 'Pago - ' . $p['metodo'] . ($p['referencia'] ? ' ref. ' . $p['referencia'] : ''),
                          'cargo' => 0.0, 'abono' => (float)$p['aplicado']];
            }
            usort($mov, fn($a, $b) => [$a['fecha'], $a['orden']] <=> [$b['fecha'], $b['orden']]);
            $saldo = 0.0;
            foreach ($mov as &$m) {
                $saldo = r2($saldo + $m['cargo'] - $m['abono']);
                $m['saldo'] = $saldo;
            }
            unset($m);
            responderJson(['status' => 'success', 'clinica' => $cli, 'movimientos' => $mov, 'saldo_usd' => $saldo]);
        }

        // ===================================================================
        // WHATSAPP DE COBRANZA (recordatorio de pago) — sin API ni token
        // ===================================================================
        case 'whatsapp_cobranza': {
            $cid = (int)($in['clinica_id'] ?? 0);
            $st = $db->prepare("SELECT razon_social, telefono FROM clinicas WHERE id = ?");
            $st->execute([$cid]);
            $cli = $st->fetch(PDO::FETCH_ASSOC);
            if (!$cli) { throw new NegocioException('Clínica no encontrada.'); }
            $st = $db->prepare("SELECT numero, saldo_usd, fecha_vencimiento FROM ventas_documentos
                                WHERE clinica_id = ? AND tipo = 'FACTURA' AND estatus IN ('EMITIDO','PARCIAL') AND saldo_usd > 0
                                ORDER BY fecha_emision");
            $st->execute([$cid]);
            $fs = $st->fetchAll(PDO::FETCH_ASSOC);
            if (!$fs) { throw new NegocioException('Esta clínica no tiene saldo pendiente.'); }
            $totUsd = 0.0;
            $det = [];
            foreach ($fs as $f) {
                $totUsd += (float)$f['saldo_usd'];
                $det[] = $f['numero'] . ' (' . fmtUsd($f['saldo_usd']) . ', vence ' . date('d/m/Y', strtotime($f['fecha_vencimiento'])) . ')';
            }
            try { $tasaHoy = tasaVigente($db); } catch (NegocioException $e) { $tasaHoy = 0.0; }
            $cfg = configTodo($db);
            $mensaje = llenarPlantilla($cfg['whatsapp_plantilla_cobranza'] ?? 'Estimado {cliente}, saldo pendiente: {saldo_usd}. {detalle}', [
                'cliente' => $cli['razon_social'], 'empresa' => $cfg['empresa_razon_social'] ?? 'CNSB LAB',
                'saldo_usd' => fmtUsd($totUsd), 'saldo_bs' => $tasaHoy > 0 ? fmtBs($totUsd * $tasaHoy) . ' a la tasa de hoy' : 'N/D',
                'detalle' => implode('; ', $det),
            ]);
            $tel = normalizarTelefono((string)$cli['telefono'], $cfg['codigo_pais_telefono'] ?? '58');
            responderJson(['status' => 'success', 'whatsapp' => [
                'telefono' => $tel, 'mensaje' => $mensaje,
                'url_wa' => 'https://wa.me/' . $tel . '?text=' . rawurlencode($mensaje),
                'url_web' => 'https://web.whatsapp.com/send?phone=' . $tel . '&text=' . rawurlencode($mensaje),
            ], 'cliente' => $cli['razon_social']]);
        }

        default:
            responderJson(['status' => 'error', 'message' => 'Acción no permitida.'], 400);
    }

} catch (NegocioException $e) {
    responderJson(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)], 500);
}
