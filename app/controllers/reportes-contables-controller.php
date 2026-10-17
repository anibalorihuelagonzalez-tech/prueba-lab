<?php
/**
 * =========================================================================
 * app/controllers/reportes-contables-controller.php  —  REPORTES CONTABLES
 * =========================================================================
 * (Reemplaza al archivo anterior, que era una clase suelta, no estaba en las
 *  rutas y consultaba tablas que no existían.)
 *
 * Acciones:
 *   libro_diario          Todos los asientos de un rango con sus líneas
 *   libro_mayor           Movimientos de UNA cuenta con saldo acumulado
 *   balance_comprobacion  Debe, Haber y saldo de cada cuenta en un rango
 *   estado_resultados     Ingresos - Costos - Gastos = Utilidad (rango)
 *   balance_general       Activo = Pasivo + Patrimonio (a una fecha)
 *   libro_ventas          Facturas y notas de crédito con base e IVA (declaración fiscal)
 *   resumen               Indicadores rápidos para el tablero contable
 *
 * Todos los montos están en bolívares (moneda contable). Los asientos anulados
 * se incluyen junto a su asiento inverso, por lo que se compensan solos.
 */
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
require_once __DIR__ . '/../helpers/finanzas.php';
header('Content-Type: application/json; charset=utf-8');

/** Saldo con el signo natural de la cuenta (deudora: debe-haber; acreedora: haber-debe). */
function saldoNatural(string $naturaleza, float $debe, float $haber): float
{
    return r2($naturaleza === 'DEUDORA' ? $debe - $haber : $haber - $debe);
}

/**
 * Saldos por cuenta en un rango de fechas. Si $desde es null suma desde el inicio de los tiempos.
 * Devuelve filas: id, codigo, nombre, tipo, naturaleza, debe, haber.
 */
function saldosPorCuenta(PDO $db, ?string $desde, string $hasta, array $tipos = []): array
{
    $where = ['a.fecha <= ?'];
    $par   = [$hasta];
    if ($desde) {
        $where[] = 'a.fecha >= ?';
        $par[]   = $desde;
    }
    $sql = "SELECT c.id, c.codigo, c.nombre, c.tipo, c.naturaleza, c.nivel,
                   COALESCE(SUM(d.debe),0) AS debe, COALESCE(SUM(d.haber),0) AS haber
            FROM plan_cuentas c
            INNER JOIN asientos_contables_detalles d ON d.cuenta_id = c.id
            INNER JOIN asientos_contables a ON a.id = d.asiento_id AND " . implode(' AND ', $where);
    if ($tipos) {
        $sql .= " WHERE c.tipo IN (" . implode(',', array_fill(0, count($tipos), '?')) . ")";
        $par = array_merge($par, $tipos);
    }
    $sql .= " GROUP BY c.id, c.codigo, c.nombre, c.tipo, c.naturaleza, c.nivel ORDER BY c.codigo";
    $st = $db->prepare($sql);
    $st->execute($par);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

$in     = entrada();
$accion = (string)($in['action'] ?? 'resumen');
$db     = conexion();
$hoy    = date('Y-m-d');

try {
    switch ($accion) {

        // ===================================================================
        case 'libro_diario': {
            $desde = fechaValida($in['desde'] ?? date('Y-m-01'), 'La fecha "desde"');
            $hasta = fechaValida($in['hasta'] ?? $hoy, 'La fecha "hasta"');
            $st = $db->prepare("SELECT id, numero, fecha, tipo, concepto, total_debe, total_haber, estatus
                                FROM asientos_contables WHERE fecha BETWEEN ? AND ? ORDER BY fecha, id");
            $st->execute([$desde, $hasta]);
            $asientos = $st->fetchAll(PDO::FETCH_ASSOC);
            $totD = 0.0; $totH = 0.0;
            if ($asientos) {
                $ids = array_column($asientos, 'id');
                $marcas = implode(',', array_fill(0, count($ids), '?'));
                $st = $db->prepare("SELECT d.asiento_id, c.codigo, c.nombre AS cuenta, d.debe, d.haber, d.descripcion
                                    FROM asientos_contables_detalles d INNER JOIN plan_cuentas c ON c.id = d.cuenta_id
                                    WHERE d.asiento_id IN ({$marcas}) ORDER BY d.asiento_id, d.id");
                $st->execute($ids);
                $porAsiento = [];
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $l) { $porAsiento[$l['asiento_id']][] = $l; }
                foreach ($asientos as &$a) {
                    $a['lineas'] = $porAsiento[$a['id']] ?? [];
                    $totD += (float)$a['total_debe'];
                    $totH += (float)$a['total_haber'];
                }
                unset($a);
            }
            responderJson(['status' => 'success', 'data' => $asientos, 'total_debe' => r2($totD), 'total_haber' => r2($totH)]);
        }

        // ===================================================================
        case 'libro_mayor': {
            $cuentaId = (int)($in['cuenta_id'] ?? 0);
            $desde = fechaValida($in['desde'] ?? date('Y-01-01'), 'La fecha "desde"');
            $hasta = fechaValida($in['hasta'] ?? $hoy, 'La fecha "hasta"');
            $st = $db->prepare("SELECT id, codigo, nombre, tipo, naturaleza FROM plan_cuentas WHERE id = ?");
            $st->execute([$cuentaId]);
            $cta = $st->fetch(PDO::FETCH_ASSOC);
            if (!$cta) { throw new NegocioException('Selecciona una cuenta.'); }

            // Saldo inicial = todo lo anterior a "desde"
            $st = $db->prepare("SELECT COALESCE(SUM(d.debe),0) AS debe, COALESCE(SUM(d.haber),0) AS haber
                                FROM asientos_contables_detalles d INNER JOIN asientos_contables a ON a.id = d.asiento_id
                                WHERE d.cuenta_id = ? AND a.fecha < ?");
            $st->execute([$cuentaId, $desde]);
            $ini = $st->fetch(PDO::FETCH_ASSOC);
            $saldo = saldoNatural($cta['naturaleza'], (float)$ini['debe'], (float)$ini['haber']);
            $saldoInicial = $saldo;

            $st = $db->prepare("SELECT a.id AS asiento_id, a.numero, a.fecha, a.concepto, d.descripcion, d.debe, d.haber
                                FROM asientos_contables_detalles d INNER JOIN asientos_contables a ON a.id = d.asiento_id
                                WHERE d.cuenta_id = ? AND a.fecha BETWEEN ? AND ? ORDER BY a.fecha, a.id, d.id");
            $st->execute([$cuentaId, $desde, $hasta]);
            $movs = $st->fetchAll(PDO::FETCH_ASSOC);
            $totD = 0.0; $totH = 0.0;
            foreach ($movs as &$m) {
                $saldo = r2($saldo + ($cta['naturaleza'] === 'DEUDORA' ? (float)$m['debe'] - (float)$m['haber'] : (float)$m['haber'] - (float)$m['debe']));
                $m['saldo'] = $saldo;
                $totD += (float)$m['debe'];
                $totH += (float)$m['haber'];
            }
            unset($m);
            responderJson(['status' => 'success', 'cuenta' => $cta, 'saldo_inicial' => $saldoInicial, 'movimientos' => $movs,
                           'total_debe' => r2($totD), 'total_haber' => r2($totH), 'saldo_final' => $saldo]);
        }

        // ===================================================================
        case 'balance_comprobacion': {
            $desde = !empty($in['desde']) ? fechaValida($in['desde'], 'La fecha "desde"') : null;
            $hasta = fechaValida($in['hasta'] ?? $hoy, 'La fecha "hasta"');
            $filas = saldosPorCuenta($db, $desde, $hasta);
            $totD = 0.0; $totH = 0.0; $sD = 0.0; $sH = 0.0;
            foreach ($filas as &$f) {
                $neto = r2((float)$f['debe'] - (float)$f['haber']);
                $f['saldo_deudor']   = $neto > 0 ? $neto : 0.0;
                $f['saldo_acreedor'] = $neto < 0 ? abs($neto) : 0.0;
                $totD += (float)$f['debe']; $totH += (float)$f['haber'];
                $sD += $f['saldo_deudor']; $sH += $f['saldo_acreedor'];
            }
            unset($f);
            responderJson(['status' => 'success', 'data' => $filas, 'total_debe' => r2($totD), 'total_haber' => r2($totH),
                           'total_saldo_deudor' => r2($sD), 'total_saldo_acreedor' => r2($sH),
                           'cuadra' => abs($sD - $sH) < 0.01]);
        }

        // ===================================================================
        case 'estado_resultados': {
            $desde = fechaValida($in['desde'] ?? date('Y-01-01'), 'La fecha "desde"');
            $hasta = fechaValida($in['hasta'] ?? $hoy, 'La fecha "hasta"');
            $filas = saldosPorCuenta($db, $desde, $hasta, ['INGRESO', 'COSTO', 'GASTO']);
            $grupos = ['INGRESO' => [], 'COSTO' => [], 'GASTO' => []];
            $tot = ['INGRESO' => 0.0, 'COSTO' => 0.0, 'GASTO' => 0.0];
            foreach ($filas as $f) {
                $s = saldoNatural($f['naturaleza'], (float)$f['debe'], (float)$f['haber']);
                if ($s == 0) { continue; }
                $grupos[$f['tipo']][] = ['codigo' => $f['codigo'], 'nombre' => $f['nombre'], 'monto' => $s];
                $tot[$f['tipo']] += $s;
            }
            $utilBruta = r2($tot['INGRESO'] - $tot['COSTO']);
            $utilNeta  = r2($utilBruta - $tot['GASTO']);
            responderJson(['status' => 'success', 'desde' => $desde, 'hasta' => $hasta,
                'ingresos' => $grupos['INGRESO'], 'costos' => $grupos['COSTO'], 'gastos' => $grupos['GASTO'],
                'total_ingresos' => r2($tot['INGRESO']), 'total_costos' => r2($tot['COSTO']), 'total_gastos' => r2($tot['GASTO']),
                'utilidad_bruta' => $utilBruta, 'utilidad_neta' => $utilNeta]);
        }

        // ===================================================================
        case 'balance_general': {
            $hasta = fechaValida($in['hasta'] ?? $hoy, 'La fecha "al"');
            $filas = saldosPorCuenta($db, null, $hasta);
            $grupos = ['ACTIVO' => [], 'PASIVO' => [], 'PATRIMONIO' => []];
            $tot = ['ACTIVO' => 0.0, 'PASIVO' => 0.0, 'PATRIMONIO' => 0.0];
            $resultadoAcum = 0.0; // ingresos - costos - gastos aún NO cerrados en el patrimonio
            foreach ($filas as $f) {
                $s = saldoNatural($f['naturaleza'], (float)$f['debe'], (float)$f['haber']);
                if (isset($grupos[$f['tipo']])) {
                    if ($s == 0) { continue; }
                    $grupos[$f['tipo']][] = ['codigo' => $f['codigo'], 'nombre' => $f['nombre'], 'monto' => $s];
                    $tot[$f['tipo']] += $s;
                } else {
                    $neto = (float)$f['haber'] - (float)$f['debe']; // resultado: positivo = utilidad
                    $resultadoAcum += $neto;
                }
            }
            $resultadoAcum = r2($resultadoAcum);
            $patrimonioTotal = r2($tot['PATRIMONIO'] + $resultadoAcum);
            $pasivoMasPat = r2($tot['PASIVO'] + $patrimonioTotal);
            responderJson(['status' => 'success', 'hasta' => $hasta,
                'activos' => $grupos['ACTIVO'], 'pasivos' => $grupos['PASIVO'], 'patrimonio' => $grupos['PATRIMONIO'],
                'total_activo' => r2($tot['ACTIVO']), 'total_pasivo' => r2($tot['PASIVO']),
                'resultado_periodo_no_cerrado' => $resultadoAcum, 'total_patrimonio' => $patrimonioTotal,
                'total_pasivo_patrimonio' => $pasivoMasPat, 'cuadra' => abs(r2($tot['ACTIVO']) - $pasivoMasPat) < 0.01]);
        }

        // ===================================================================
        // LIBRO DE VENTAS (base para la declaración del IVA)
        // ===================================================================
        case 'libro_ventas': {
            $desde = fechaValida($in['desde'] ?? date('Y-m-01'), 'La fecha "desde"');
            $hasta = fechaValida($in['hasta'] ?? $hoy, 'La fecha "hasta"');
            $st = $db->prepare("SELECT d.id, d.tipo, d.numero, d.numero_control, d.fecha_emision, d.estatus, d.iva_porcentaje,
                                       c.razon_social, CONCAT(c.tipo_documento, '-', c.numero_documento) AS rif,
                                       d.base_exenta_bs, d.base_imponible_bs, d.iva_bs, d.total_bs
                                FROM ventas_documentos d INNER JOIN clinicas c ON c.id = d.clinica_id
                                WHERE d.tipo IN ('FACTURA','NOTA_CREDITO') AND d.fecha_emision BETWEEN ? AND ?
                                  AND d.estatus <> 'BORRADOR' ORDER BY d.fecha_emision, d.id");
            $st->execute([$desde, $hasta]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            $t = ['exenta' => 0.0, 'imponible' => 0.0, 'iva' => 0.0, 'total' => 0.0];
            foreach ($rows as &$r) {
                // Anuladas: se listan pero no suman. Las notas de crédito restan.
                $signo = $r['estatus'] === 'ANULADO' ? 0 : ($r['tipo'] === 'NOTA_CREDITO' ? -1 : 1);
                $r['signo'] = $signo;
                $t['exenta']    += $signo * (float)$r['base_exenta_bs'];
                $t['imponible'] += $signo * (float)$r['base_imponible_bs'];
                $t['iva']       += $signo * (float)$r['iva_bs'];
                $t['total']     += $signo * (float)$r['total_bs'];
            }
            unset($r);
            foreach ($t as $k => $v) { $t[$k] = r2($v); }
            // IVA crédito fiscal de compras en el mismo rango (informativo)
            $st = $db->prepare("SELECT COALESCE(SUM(iva_bs),0) FROM gastos WHERE estatus <> 'ANULADO' AND fecha BETWEEN ? AND ?");
            $st->execute([$desde, $hasta]);
            $ivaCredito = r2($st->fetchColumn());
            responderJson(['status' => 'success', 'data' => $rows, 'totales' => $t,
                           'iva_credito_compras' => $ivaCredito, 'iva_por_pagar' => r2($t['iva'] - $ivaCredito)]);
        }

        // ===================================================================
        // RESUMEN para el tablero (mes actual)
        // ===================================================================
        case 'resumen': {
            $d = date('Y-m-01');
            $filas = saldosPorCuenta($db, $d, $hoy, ['INGRESO', 'COSTO', 'GASTO']);
            $ing = 0.0; $egr = 0.0;
            foreach ($filas as $f) {
                $s = saldoNatural($f['naturaleza'], (float)$f['debe'], (float)$f['haber']);
                if ($f['tipo'] === 'INGRESO') { $ing += $s; } else { $egr += $s; }
            }
            $st = $db->prepare("SELECT COUNT(*) FROM asientos_contables WHERE fecha >= ?");
            $st->execute([$d]);
            responderJson(['status' => 'success', 'ingresos_mes' => r2($ing), 'egresos_mes' => r2($egr),
                           'utilidad_mes' => r2($ing - $egr), 'asientos_mes' => (int)$st->fetchColumn()]);
        }

        default:
            responderJson(['status' => 'error', 'message' => 'Acción no permitida.'], 400);
    }

} catch (NegocioException $e) {
    responderJson(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)], 500);
}
