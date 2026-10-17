<?php
/**
 * =========================================================================
 * app/controllers/contabilidad-controller.php  —  NÚCLEO CONTABLE
 * =========================================================================
 * Acciones (parámetro "action"):
 *
 *  PLAN DE CUENTAS : cuentas_listar | cuenta_guardar | cuenta_alternar | cuenta_eliminar
 *  ASIENTOS        : asientos_listar | asiento_ver | asiento_crear | asiento_anular
 *  PERÍODOS        : periodos_listar | periodo_cerrar | periodo_reabrir
 *  CIERRE ANUAL    : cierre_ejercicio
 *  CONFIGURACIÓN   : config_get | config_set
 *
 * Principios contables aplicados:
 *  - Partida doble: todo asiento cuadra (Debe = Haber).
 *  - Un asiento NO se edita ni se borra: se corrige anulándolo (asiento inverso).
 *  - Un período (mes) CERRADO no admite movimientos.
 */
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
require_once __DIR__ . '/../helpers/finanzas.php';
header('Content-Type: application/json; charset=utf-8');

$in        = entrada();
$accion    = (string)($in['action'] ?? 'cuentas_listar');
$db        = conexion();
$usuarioId = (int)Auth::id();

try {
    switch ($accion) {

        // ===================================================================
        // PLAN DE CUENTAS
        // ===================================================================
        case 'cuentas_listar': {
            $rows = $db->query("SELECT c.id, c.codigo, c.nombre, c.tipo, c.naturaleza, c.nivel, c.padre_id,
                                       c.permite_movimiento, c.es_sistema, c.is_active,
                                       COALESCE(m.debe,0) AS debe, COALESCE(m.haber,0) AS haber, COALESCE(m.movs,0) AS movimientos
                                FROM plan_cuentas c
                                LEFT JOIN (SELECT cuenta_id, SUM(debe) AS debe, SUM(haber) AS haber, COUNT(*) AS movs
                                           FROM asientos_contables_detalles GROUP BY cuenta_id) m ON m.cuenta_id = c.id
                                ORDER BY c.codigo")->fetchAll(PDO::FETCH_ASSOC);
            responderJson(['status' => 'success', 'data' => $rows]);
        }

        case 'cuenta_guardar': {
            $id     = (int)($in['id'] ?? 0);
            $codigo = trim((string)($in['codigo'] ?? ''));
            $nombre = trim((string)($in['nombre'] ?? ''));
            $tipo   = strtoupper(trim((string)($in['tipo'] ?? '')));
            $nat    = strtoupper(trim((string)($in['naturaleza'] ?? '')));
            $padre  = !empty($in['padre_id']) ? (int)$in['padre_id'] : null;
            $perm   = !empty($in['permite_movimiento']) ? 1 : 0;

            if (!preg_match('/^\d+(\.\d+)*$/', $codigo) || strlen($codigo) > 30) {
                throw new NegocioException('El código debe ser numérico con puntos, por ejemplo 1.1.01.006.');
            }
            if ($nombre === '' || mb_strlen($nombre) > 150) {
                throw new NegocioException('El nombre es obligatorio (máximo 150 caracteres).');
            }
            if (!in_array($tipo, ['ACTIVO', 'PASIVO', 'PATRIMONIO', 'INGRESO', 'COSTO', 'GASTO'], true)) {
                throw new NegocioException('Tipo de cuenta no válido.');
            }
            if (!in_array($nat, ['DEUDORA', 'ACREEDORA'], true)) {
                throw new NegocioException('Naturaleza no válida.');
            }
            // Código único
            $st = $db->prepare("SELECT id FROM plan_cuentas WHERE codigo = ? AND id <> ?");
            $st->execute([$codigo, $id]);
            if ($st->fetchColumn()) {
                throw new NegocioException("Ya existe una cuenta con el código {$codigo}.");
            }
            // Coherencia con la cuenta padre
            if ($padre) {
                $st = $db->prepare("SELECT codigo, tipo FROM plan_cuentas WHERE id = ?");
                $st->execute([$padre]);
                $p = $st->fetch(PDO::FETCH_ASSOC);
                if (!$p) { throw new NegocioException('La cuenta padre no existe.'); }
                if (strpos($codigo, $p['codigo'] . '.') !== 0) {
                    throw new NegocioException("El código debe comenzar con el de la cuenta padre ({$p['codigo']}.).");
                }
                if ($p['tipo'] !== $tipo) {
                    throw new NegocioException("El tipo debe ser el mismo que el de la cuenta padre ({$p['tipo']}).");
                }
                if ($padre === $id) { throw new NegocioException('Una cuenta no puede ser su propio padre.'); }
            }
            $nivel = substr_count($codigo, '.') + 1;

            if ($id > 0) {
                $st = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM asientos_contables_detalles d WHERE d.cuenta_id = c.id) AS movs,
                                           (SELECT COUNT(*) FROM plan_cuentas h WHERE h.padre_id = c.id) AS hijos
                                    FROM plan_cuentas c WHERE c.id = ?");
                $st->execute([$id]);
                $a = $st->fetch(PDO::FETCH_ASSOC);
                if (!$a) { throw new NegocioException('La cuenta no existe.'); }
                if ((int)$a['es_sistema'] && $a['codigo'] !== $codigo) {
                    throw new NegocioException('Esta cuenta la usa el sistema automáticamente: no se puede cambiar su código.');
                }
                if ((int)$a['movs'] > 0 && ($a['tipo'] !== $tipo || $a['naturaleza'] !== $nat || $a['codigo'] !== $codigo)) {
                    throw new NegocioException('La cuenta ya tiene movimientos: solo puedes cambiar su nombre.');
                }
                if ((int)$a['movs'] > 0 && !$perm) {
                    throw new NegocioException('La cuenta ya tiene movimientos: debe seguir permitiéndolos.');
                }
                if ((int)$a['hijos'] > 0 && $perm) {
                    throw new NegocioException('Una cuenta con subcuentas es de grupo y no puede recibir movimientos.');
                }
                $db->prepare("UPDATE plan_cuentas SET codigo=?, nombre=?, tipo=?, naturaleza=?, nivel=?, padre_id=?, permite_movimiento=? WHERE id=?")
                   ->execute([$codigo, $nombre, $tipo, $nat, $nivel, $padre, $perm, $id]);
            } else {
                $db->prepare("INSERT INTO plan_cuentas (codigo, nombre, tipo, naturaleza, nivel, padre_id, permite_movimiento) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$codigo, $nombre, $tipo, $nat, $nivel, $padre, $perm]);
            }
            responderJson(['status' => 'success', 'message' => 'Cuenta guardada.']);
        }

        case 'cuenta_alternar': {
            $st = $db->prepare("SELECT es_sistema FROM plan_cuentas WHERE id = ?");
            $st->execute([(int)($in['id'] ?? 0)]);
            $es = $st->fetchColumn();
            if ($es === false) { throw new NegocioException('La cuenta no existe.'); }
            if ((int)$es) { throw new NegocioException('Las cuentas de sistema no se pueden desactivar.'); }
            $db->prepare("UPDATE plan_cuentas SET is_active = 1 - is_active WHERE id = ?")->execute([(int)$in['id']]);
            responderJson(['status' => 'success', 'message' => 'Estado actualizado.']);
        }

        case 'cuenta_eliminar': {
            $id = (int)($in['id'] ?? 0);
            $st = $db->prepare("SELECT codigo, es_sistema FROM plan_cuentas WHERE id = ?");
            $st->execute([$id]);
            $c = $st->fetch(PDO::FETCH_ASSOC);
            if (!$c) { throw new NegocioException('La cuenta no existe.'); }
            if ((int)$c['es_sistema']) { throw new NegocioException('Las cuentas de sistema no se pueden eliminar.'); }
            $checks = [
                ["SELECT COUNT(*) FROM asientos_contables_detalles WHERE cuenta_id = ?", 'Tiene movimientos contables.'],
                ["SELECT COUNT(*) FROM plan_cuentas WHERE padre_id = ?", 'Tiene subcuentas.'],
                ["SELECT COUNT(*) FROM metodos_pago WHERE cuenta_contable_id = ?", 'La usa un método de pago.'],
                ["SELECT COUNT(*) FROM gastos WHERE cuenta_gasto_id = ? OR cuenta_pago_id = ?", 'La usa un gasto.'],
            ];
            foreach ($checks as [$sql, $msg]) {
                $st = $db->prepare($sql);
                $st->execute(substr_count($sql, '?') === 2 ? [$id, $id] : [$id]);
                if ((int)$st->fetchColumn() > 0) { throw new NegocioException('No se puede eliminar: ' . $msg); }
            }
            foreach (configTodo($db) as $k => $v) {
                if (strpos($k, 'cta_') === 0 && $v === $c['codigo']) {
                    throw new NegocioException('No se puede eliminar: está configurada como cuenta automática (' . $k . ').');
                }
            }
            $db->prepare("DELETE FROM plan_cuentas WHERE id = ?")->execute([$id]);
            responderJson(['status' => 'success', 'message' => 'Cuenta eliminada.']);
        }

        // ===================================================================
        // ASIENTOS
        // ===================================================================
        case 'asientos_listar': {
            $where = [];
            $par = [];
            if (!empty($in['desde'])) { $where[] = 'a.fecha >= ?'; $par[] = fechaValida($in['desde'], 'La fecha "desde"'); }
            if (!empty($in['hasta'])) { $where[] = 'a.fecha <= ?'; $par[] = fechaValida($in['hasta'], 'La fecha "hasta"'); }
            if (!empty($in['tipo']))    { $where[] = 'a.tipo = ?';    $par[] = (string)$in['tipo']; }
            if (!empty($in['estatus'])) { $where[] = 'a.estatus = ?'; $par[] = (string)$in['estatus']; }
            if (!empty($in['q'])) {
                $where[] = '(a.numero LIKE ? OR a.concepto LIKE ?)';
                $like = '%' . trim((string)$in['q']) . '%';
                array_push($par, $like, $like);
            }
            $st = $db->prepare("SELECT a.id, a.numero, a.fecha, a.tipo, a.concepto, a.total_debe, a.total_haber, a.estatus,
                                       a.referencia_tabla, a.referencia_id, a.asiento_original_id,
                                       TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS usuario
                                FROM asientos_contables a INNER JOIN usuarios u ON u.id = a.usuario_id"
                . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY a.fecha DESC, a.id DESC LIMIT 500");
            $st->execute($par);
            responderJson(['status' => 'success', 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        }

        case 'asiento_ver': {
            $st = $db->prepare("SELECT a.*, TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS usuario,
                                       o.numero AS original_numero, r.numero AS reverso_numero
                                FROM asientos_contables a INNER JOIN usuarios u ON u.id = a.usuario_id
                                LEFT JOIN asientos_contables o ON o.id = a.asiento_original_id
                                LEFT JOIN asientos_contables r ON r.id = a.asiento_reverso_id
                                WHERE a.id = ?");
            $st->execute([(int)($in['id'] ?? 0)]);
            $a = $st->fetch(PDO::FETCH_ASSOC);
            if (!$a) { throw new NegocioException('Asiento no encontrado.'); }
            $st = $db->prepare("SELECT d.id, d.cuenta_id, c.codigo, c.nombre AS cuenta, d.debe, d.haber, d.descripcion
                                FROM asientos_contables_detalles d INNER JOIN plan_cuentas c ON c.id = d.cuenta_id
                                WHERE d.asiento_id = ? ORDER BY d.id");
            $st->execute([$a['id']]);
            $a['lineas'] = $st->fetchAll(PDO::FETCH_ASSOC);
            responderJson(['status' => 'success', 'asiento' => $a]);
        }

        case 'asiento_crear': {
            $tipo = in_array(($in['tipo'] ?? 'MANUAL'), ['MANUAL', 'AJUSTE', 'APERTURA'], true) ? $in['tipo'] : 'MANUAL';
            $concepto = trim((string)($in['concepto'] ?? ''));
            if (mb_strlen($concepto) < 3) { throw new NegocioException('Escribe el concepto del asiento.'); }
            $id = crearAsiento($db, [
                'fecha' => $in['fecha'] ?? '', 'tipo' => $tipo, 'concepto' => $concepto,
                'tasa_cambio' => !empty($in['tasa_cambio']) ? (float)$in['tasa_cambio'] : 1, 'usuario_id' => $usuarioId,
            ], is_array($in['lineas'] ?? null) ? $in['lineas'] : []);
            responderJson(['status' => 'success', 'id' => $id, 'message' => 'Asiento registrado.']);
        }

        case 'asiento_anular': {
            $motivo = trim((string)($in['motivo'] ?? ''));
            if (mb_strlen($motivo) < 3) { throw new NegocioException('Indica el motivo de la anulación.'); }
            // Los asientos automáticos nacen de una factura, cobro o gasto: deben anularse desde su documento
            $st = $db->prepare("SELECT tipo FROM asientos_contables WHERE id = ?");
            $st->execute([(int)($in['id'] ?? 0)]);
            $t = $st->fetchColumn();
            if ($t === false) { throw new NegocioException('Asiento no encontrado.'); }
            if (in_array($t, ['VENTA', 'NOTA_CREDITO', 'COBRO', 'GASTO', 'PAGO_GASTO'], true)) {
                throw new NegocioException('Este asiento fue generado automáticamente. Anula el documento (factura, cobro o gasto) que lo originó.');
            }
            $nuevo = anularAsiento($db, (int)$in['id'], $motivo, $usuarioId);
            responderJson(['status' => 'success', 'id' => $nuevo, 'message' => 'Asiento anulado (se generó el asiento inverso).']);
        }

        // ===================================================================
        // PERÍODOS
        // ===================================================================
        case 'periodos_listar': {
            $anio = (int)($in['anio'] ?? date('Y'));
            $st = $db->prepare("SELECT p.mes, p.estatus, p.fecha_cierre,
                                       (SELECT COUNT(*) FROM asientos_contables a WHERE a.periodo_id = p.id) AS asientos
                                FROM periodos_contables p WHERE p.anio = ?");
            $st->execute([$anio]);
            $existentes = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) { $existentes[(int)$p['mes']] = $p; }
            $meses = [];
            for ($m = 1; $m <= 12; $m++) {
                $meses[] = $existentes[$m] ?? ['mes' => $m, 'estatus' => 'ABIERTO', 'fecha_cierre' => null, 'asientos' => 0];
            }
            $st = $db->prepare("SELECT COUNT(*) FROM asientos_contables WHERE tipo = 'CIERRE' AND referencia_tabla = 'cierre_ejercicio' AND referencia_id = ? AND estatus = 'ACTIVO'");
            $st->execute([$anio]);
            responderJson(['status' => 'success', 'anio' => $anio, 'meses' => $meses, 'ejercicio_cerrado' => (int)$st->fetchColumn() > 0]);
        }

        case 'periodo_cerrar':
        case 'periodo_reabrir': {
            $anio = (int)($in['anio'] ?? 0);
            $mes  = (int)($in['mes'] ?? 0);
            if ($anio < 2000 || $mes < 1 || $mes > 12) { throw new NegocioException('Período no válido.'); }
            $p = periodoDeFecha($db, sprintf('%04d-%02d-01', $anio, $mes));
            if ($accion === 'periodo_cerrar') {
                $db->prepare("UPDATE periodos_contables SET estatus = 'CERRADO', fecha_cierre = NOW(), usuario_cierre_id = ? WHERE id = ?")
                   ->execute([$usuarioId, $p['id']]);
                $msg = 'Período cerrado. Ya no admite movimientos.';
            } else {
                $db->prepare("UPDATE periodos_contables SET estatus = 'ABIERTO', fecha_cierre = NULL, usuario_cierre_id = NULL WHERE id = ?")->execute([$p['id']]);
                $msg = 'Período reabierto.';
            }
            responderJson(['status' => 'success', 'message' => $msg]);
        }

        // ===================================================================
        // CIERRE DE EJERCICIO ANUAL
        // Lleva a cero Ingresos, Costos y Gastos del año contra "Resultado del Ejercicio".
        // ===================================================================
        case 'cierre_ejercicio': {
            $anio = (int)($in['anio'] ?? 0);
            if ($anio < 2000 || $anio > (int)date('Y')) { throw new NegocioException('Año no válido.'); }
            $id = enTransaccion($db, function () use ($db, $anio, $usuarioId) {
                $st = $db->prepare("SELECT COUNT(*) FROM asientos_contables WHERE tipo = 'CIERRE' AND referencia_tabla = 'cierre_ejercicio'
                                    AND referencia_id = ? AND estatus = 'ACTIVO'");
                $st->execute([$anio]);
                if ((int)$st->fetchColumn() > 0) {
                    throw new NegocioException("El ejercicio {$anio} ya fue cerrado. Si necesitas rehacerlo, anula primero su asiento de cierre.");
                }
                $st = $db->prepare("SELECT c.id, SUM(d.debe) AS debe, SUM(d.haber) AS haber
                                    FROM asientos_contables_detalles d
                                    INNER JOIN asientos_contables a ON a.id = d.asiento_id
                                    INNER JOIN plan_cuentas c ON c.id = d.cuenta_id
                                    WHERE a.fecha BETWEEN ? AND ? AND c.tipo IN ('INGRESO','COSTO','GASTO')
                                    GROUP BY c.id");
                $st->execute(["{$anio}-01-01", "{$anio}-12-31"]);
                $lineas = [];
                $totD = 0.0; $totH = 0.0;
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) {
                    $neto = r2((float)$c['debe'] - (float)$c['haber']);
                    if ($neto > 0)      { $lineas[] = ['cuenta_id' => $c['id'], 'debe' => 0, 'haber' => $neto, 'descripcion' => 'Cierre de ejercicio']; $totH += $neto; }
                    elseif ($neto < 0)  { $lineas[] = ['cuenta_id' => $c['id'], 'debe' => abs($neto), 'haber' => 0, 'descripcion' => 'Cierre de ejercicio']; $totD += abs($neto); }
                }
                if (!$lineas) { throw new NegocioException("No hay saldos de resultados en {$anio} para cerrar."); }
                $res = r2($totD - $totH); // >0 utilidad, <0 pérdida
                $cuentaRes = cuentaSistema($db, 'cta_resultado_ejercicio');
                if ($res > 0)      { $lineas[] = ['cuenta_id' => $cuentaRes, 'debe' => 0, 'haber' => $res, 'descripcion' => "Utilidad del ejercicio {$anio}"]; }
                elseif ($res < 0)  { $lineas[] = ['cuenta_id' => $cuentaRes, 'debe' => abs($res), 'haber' => 0, 'descripcion' => "Pérdida del ejercicio {$anio}"]; }
                return crearAsiento($db, [
                    'fecha' => "{$anio}-12-31", 'tipo' => 'CIERRE', 'concepto' => "Cierre del ejercicio {$anio}",
                    'referencia_tabla' => 'cierre_ejercicio', 'referencia_id' => $anio, 'usuario_id' => $usuarioId,
                ], $lineas);
            });
            responderJson(['status' => 'success', 'id' => $id, 'message' => "Ejercicio {$anio} cerrado. Los resultados quedaron en la cuenta de Resultado del Ejercicio."]);
        }

        // ===================================================================
        // CONFIGURACIÓN (empresa, impuestos, cuentas automáticas, plantillas WhatsApp)
        // ===================================================================
        case 'config_get': {
            $cuentas = $db->query("SELECT codigo, nombre, tipo FROM plan_cuentas WHERE permite_movimiento = 1 AND is_active = 1 ORDER BY codigo")->fetchAll(PDO::FETCH_ASSOC);
            $filas = $db->query("SELECT clave, valor, descripcion FROM configuracion ORDER BY clave")->fetchAll(PDO::FETCH_ASSOC);
            responderJson(['status' => 'success', 'config' => $filas, 'cuentas' => $cuentas]);
        }

        case 'config_set': {
            $valores = $in['valores'] ?? [];
            if (!is_array($valores) || !$valores) { throw new NegocioException('No hay datos para guardar.'); }
            enTransaccion($db, function () use ($db, $valores) {
                $existentes = array_keys(configTodo($db, true));
                foreach ($valores as $clave => $valor) {
                    $clave = (string)$clave;
                    if (!in_array($clave, $existentes, true)) { continue; } // solo claves conocidas
                    $valor = trim((string)$valor);
                    if (in_array($clave, ['iva_porcentaje', 'igtf_porcentaje'], true)) {
                        if (!is_numeric($valor) || $valor < 0 || $valor > 100) { throw new NegocioException('Los porcentajes deben estar entre 0 y 100.'); }
                    }
                    if ($clave === 'dias_credito_default' && (!ctype_digit($valor) || (int)$valor > 365)) {
                        throw new NegocioException('Los días de crédito deben ser un número entre 0 y 365.');
                    }
                    if (strpos($clave, 'cta_') === 0) { cuentaIdPorCodigo($db, $valor); } // la cuenta debe existir
                    configSet($db, $clave, $valor);
                }
            });
            responderJson(['status' => 'success', 'message' => 'Configuración guardada.']);
        }

        default:
            responderJson(['status' => 'error', 'message' => 'Acción no permitida.'], 400);
    }

} catch (NegocioException $e) {
    responderJson(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)], 500);
}
