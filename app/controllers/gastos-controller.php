<?php
/**
 * =========================================================================
 * app/controllers/gastos-controller.php  —  GASTOS Y COMPRAS (egresos)
 * =========================================================================
 * Registra lo que el laboratorio paga o debe a proveedores y lo contabiliza.
 *
 *  CONTADO (se paga al momento):
 *      Debe  Cuenta de gasto/costo/activo elegida ... base
 *      Debe  IVA Crédito Fiscal ..................... IVA
 *      Haber Caja/Banco elegido ..................... total
 *  A CRÉDITO (queda por pagar):
 *      Debe  Gasto + IVA crédito   /   Haber Proveedores por Pagar
 *      (al pagarlo:  Debe Proveedores por Pagar / Haber Caja-Banco)
 *
 * Los gastos se llevan en bolívares (moneda contable).
 * Acciones: listar | catalogos | guardar | pagar | anular
 */
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
require_once __DIR__ . '/../helpers/finanzas.php';
header('Content-Type: application/json; charset=utf-8');

$in        = entrada();
$accion    = (string)($in['action'] ?? 'listar');
$db        = conexion();
$usuarioId = (int)Auth::id();

try {
    switch ($accion) {

        case 'listar': {
            $where = [];
            $par = [];
            if (!empty($in['desde'])) { $where[] = 'g.fecha >= ?'; $par[] = fechaValida($in['desde'], 'La fecha "desde"'); }
            if (!empty($in['hasta'])) { $where[] = 'g.fecha <= ?'; $par[] = fechaValida($in['hasta'], 'La fecha "hasta"'); }
            if (!empty($in['estatus'])) { $where[] = 'g.estatus = ?'; $par[] = (string)$in['estatus']; }
            if (!empty($in['q'])) {
                $where[] = '(g.proveedor LIKE ? OR g.concepto LIKE ? OR g.numero LIKE ?)';
                $like = '%' . trim((string)$in['q']) . '%';
                array_push($par, $like, $like, $like);
            }
            $st = $db->prepare("SELECT g.id, g.numero, g.fecha, g.proveedor, g.proveedor_rif, g.nro_factura_proveedor, g.concepto,
                                       g.base_bs, g.iva_bs, g.total_bs, g.forma_pago, g.estatus, g.fecha_pago,
                                       c.codigo AS cuenta_codigo, c.nombre AS cuenta_nombre
                                FROM gastos g INNER JOIN plan_cuentas c ON c.id = g.cuenta_gasto_id"
                . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY g.fecha DESC, g.id DESC LIMIT 500");
            $st->execute($par);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $tot = 0.0; $pend = 0.0;
            foreach ($rows as $r) {
                if ($r['estatus'] !== 'ANULADO') { $tot += (float)$r['total_bs']; }
                if ($r['estatus'] === 'PENDIENTE') { $pend += (float)$r['total_bs']; }
            }
            responderJson(['status' => 'success', 'data' => $rows, 'total_bs' => r2($tot), 'pendiente_bs' => r2($pend)]);
        }

        case 'catalogos': {
            $gasto = $db->query("SELECT id, codigo, nombre FROM plan_cuentas
                                 WHERE tipo IN ('GASTO','COSTO') AND permite_movimiento = 1 AND is_active = 1 ORDER BY codigo")->fetchAll(PDO::FETCH_ASSOC);
            $pago = $db->query("SELECT id, codigo, nombre FROM plan_cuentas
                                WHERE tipo = 'ACTIVO' AND permite_movimiento = 1 AND is_active = 1 AND codigo LIKE '1.1.01.%' ORDER BY codigo")->fetchAll(PDO::FETCH_ASSOC);
            responderJson(['status' => 'success', 'cuentas_gasto' => $gasto, 'cuentas_pago' => $pago,
                           'iva_porcentaje' => (float)configGet($db, 'iva_porcentaje', '16'), 'hoy' => date('Y-m-d')]);
        }

        case 'guardar': {
            $res = enTransaccion($db, function () use ($db, $in, $usuarioId) {
                $fecha = fechaValida($in['fecha'] ?? '', 'La fecha del gasto');
                $prov = trim((string)($in['proveedor'] ?? ''));
                $concepto = trim((string)($in['concepto'] ?? ''));
                if ($prov === '' || mb_strlen($prov) > 150) { throw new NegocioException('Indica el proveedor.'); }
                if ($concepto === '' || mb_strlen($concepto) > 255) { throw new NegocioException('Indica el concepto del gasto.'); }
                $base = r2($in['base_bs'] ?? 0);
                $iva  = r2($in['iva_bs'] ?? 0);
                if ($base <= 0) { throw new NegocioException('La base del gasto debe ser mayor que cero.'); }
                if ($iva < 0) { throw new NegocioException('El IVA no puede ser negativo.'); }
                $total = r2($base + $iva);
                $forma = (($in['forma_pago'] ?? 'CONTADO') === 'CREDITO') ? 'CREDITO' : 'CONTADO';

                $st = $db->prepare("SELECT id FROM plan_cuentas WHERE id = ? AND tipo IN ('GASTO','COSTO') AND permite_movimiento = 1 AND is_active = 1");
                $st->execute([(int)($in['cuenta_gasto_id'] ?? 0)]);
                $cuentaGasto = (int)$st->fetchColumn();
                if (!$cuentaGasto) { throw new NegocioException('Selecciona la cuenta de gasto.'); }

                $cuentaPago = null;
                if ($forma === 'CONTADO') {
                    $st = $db->prepare("SELECT id FROM plan_cuentas WHERE id = ? AND tipo = 'ACTIVO' AND permite_movimiento = 1 AND is_active = 1");
                    $st->execute([(int)($in['cuenta_pago_id'] ?? 0)]);
                    $cuentaPago = (int)$st->fetchColumn();
                    if (!$cuentaPago) { throw new NegocioException('Selecciona la caja o banco con el que se pagó.'); }
                }

                $numero = siguienteNumero($db, 'GASTO');
                $db->prepare("INSERT INTO gastos (numero, fecha, proveedor, proveedor_rif, nro_factura_proveedor, concepto, cuenta_gasto_id,
                                base_bs, iva_bs, total_bs, forma_pago, cuenta_pago_id, estatus, fecha_pago, usuario_id, observaciones)
                              VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$numero, $fecha, $prov, trim((string)($in['proveedor_rif'] ?? '')) ?: null,
                              trim((string)($in['nro_factura_proveedor'] ?? '')) ?: null, $concepto, $cuentaGasto,
                              $base, $iva, $total, $forma, $cuentaPago, $forma === 'CONTADO' ? 'PAGADO' : 'PENDIENTE',
                              $forma === 'CONTADO' ? $fecha : null, $usuarioId, trim((string)($in['observaciones'] ?? '')) ?: null]);
                $gastoId = (int)$db->lastInsertId();

                $lineas = [['cuenta_id' => $cuentaGasto, 'debe' => $base, 'haber' => 0, 'descripcion' => $concepto]];
                if ($iva > 0) {
                    $lineas[] = ['cuenta_id' => cuentaSistema($db, 'cta_iva_credito'), 'debe' => $iva, 'haber' => 0, 'descripcion' => 'IVA crédito fiscal'];
                }
                $lineas[] = ['cuenta_id' => $forma === 'CONTADO' ? $cuentaPago : cuentaSistema($db, 'cta_cxp'),
                             'debe' => 0, 'haber' => $total, 'descripcion' => $prov];
                $asiento = crearAsiento($db, [
                    'fecha' => $fecha, 'tipo' => 'GASTO', 'concepto' => "Gasto {$numero} - {$prov}: {$concepto}",
                    'referencia_tabla' => 'gastos', 'referencia_id' => $gastoId, 'usuario_id' => $usuarioId,
                ], $lineas);
                $db->prepare("UPDATE gastos SET asiento_id = ? WHERE id = ?")->execute([$asiento, $gastoId]);
                return $numero;
            });
            responderJson(['status' => 'success', 'message' => "Gasto {$res} registrado."]);
        }

        // Pago de un gasto que quedó a crédito
        case 'pagar': {
            enTransaccion($db, function () use ($db, $in, $usuarioId) {
                $st = $db->prepare("SELECT * FROM gastos WHERE id = ? FOR UPDATE");
                $st->execute([(int)($in['id'] ?? 0)]);
                $g = $st->fetch(PDO::FETCH_ASSOC);
                if (!$g || $g['estatus'] !== 'PENDIENTE') { throw new NegocioException('Solo se pueden pagar gastos PENDIENTES.'); }
                $fecha = fechaValida($in['fecha_pago'] ?? date('Y-m-d'), 'La fecha de pago');
                $st = $db->prepare("SELECT id FROM plan_cuentas WHERE id = ? AND tipo = 'ACTIVO' AND permite_movimiento = 1 AND is_active = 1");
                $st->execute([(int)($in['cuenta_pago_id'] ?? 0)]);
                $cuentaPago = (int)$st->fetchColumn();
                if (!$cuentaPago) { throw new NegocioException('Selecciona la caja o banco con el que pagas.'); }
                $asiento = crearAsiento($db, [
                    'fecha' => $fecha, 'tipo' => 'PAGO_GASTO', 'concepto' => "Pago del gasto {$g['numero']} - {$g['proveedor']}",
                    'referencia_tabla' => 'gastos', 'referencia_id' => (int)$g['id'], 'usuario_id' => $usuarioId,
                ], [
                    ['cuenta_id' => cuentaSistema($db, 'cta_cxp'), 'debe' => $g['total_bs'], 'haber' => 0, 'descripcion' => $g['proveedor']],
                    ['cuenta_id' => $cuentaPago, 'debe' => 0, 'haber' => $g['total_bs'], 'descripcion' => 'Pago a proveedor'],
                ]);
                $db->prepare("UPDATE gastos SET estatus = 'PAGADO', fecha_pago = ?, cuenta_pago_id = ?, asiento_pago_id = ? WHERE id = ?")
                   ->execute([$fecha, $cuentaPago, $asiento, $g['id']]);
            });
            responderJson(['status' => 'success', 'message' => 'Gasto pagado.']);
        }

        case 'anular': {
            enTransaccion($db, function () use ($db, $in, $usuarioId) {
                $motivo = trim((string)($in['motivo'] ?? ''));
                if (mb_strlen($motivo) < 3) { throw new NegocioException('Indica el motivo de la anulación.'); }
                $st = $db->prepare("SELECT * FROM gastos WHERE id = ? FOR UPDATE");
                $st->execute([(int)($in['id'] ?? 0)]);
                $g = $st->fetch(PDO::FETCH_ASSOC);
                if (!$g || $g['estatus'] === 'ANULADO') { throw new NegocioException('El gasto no existe o ya está anulado.'); }
                // Primero se revierte el pago (si lo hubo en un momento posterior) y luego el registro original
                if ($g['asiento_pago_id']) { anularAsiento($db, (int)$g['asiento_pago_id'], "Anulación gasto {$g['numero']} - {$motivo}", $usuarioId); }
                if ($g['asiento_id'])      { anularAsiento($db, (int)$g['asiento_id'], "Anulación gasto {$g['numero']} - {$motivo}", $usuarioId); }
                $db->prepare("UPDATE gastos SET estatus = 'ANULADO', observaciones = ? WHERE id = ?")
                   ->execute([mb_substr('ANULADO: ' . $motivo, 0, 255), $g['id']]);
            });
            responderJson(['status' => 'success', 'message' => 'Gasto anulado.']);
        }

        default:
            responderJson(['status' => 'error', 'message' => 'Acción no permitida.'], 400);
    }

} catch (NegocioException $e) {
    responderJson(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)], 500);
}
