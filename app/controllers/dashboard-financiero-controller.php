<?php
/**
 * =========================================================================
 * app/controllers/dashboard-financiero-controller.php  —  TABLERO FINANCIERO
 * =========================================================================
 * REESCRITO: antes leía los "documentos administrativos" sueltos y el botón
 * "cobro rápido" marcaba una factura como PAGADA sin registrar dinero, método de
 * pago, tasa ni asiento contable (la deuda desaparecía sin rastro).
 *
 * Ahora las métricas salen de los módulos reales:
 *   - Facturado     = facturas vigentes (ventas_documentos)
 *   - Cobrado       = cobros registrados y no anulados (ventas_pagos)
 *   - Por cobrar    = saldo pendiente de las facturas
 * y los cobros se registran SOLO en Ventas > Cobranzas.
 * (La estructura del JSON se mantiene igual para que la vista siga funcionando.)
 */
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
require_once __DIR__ . '/../helpers/finanzas.php';
header('Content-Type: application/json; charset=utf-8');

$in     = entrada();
$accion = (string)($in['action'] ?? '');
$pdo    = conexion();

try {
    switch ($accion) {

        case 'obtener_metricas': {
            try { $tasa = tasaVigente($pdo); } catch (NegocioException $e) { $tasa = 0.0; }

            $f = $pdo->query("SELECT
                    COALESCE(SUM(CASE WHEN estatus IN ('EMITIDO','PARCIAL','PAGADO') THEN total_usd END),0) AS fact_usd,
                    COALESCE(SUM(CASE WHEN estatus IN ('EMITIDO','PARCIAL','PAGADO') THEN total_bs END),0)  AS fact_bs,
                    COALESCE(SUM(CASE WHEN estatus IN ('EMITIDO','PARCIAL') THEN saldo_usd END),0)          AS pend_usd,
                    COALESCE(SUM(CASE WHEN estatus = 'ANULADO' THEN total_usd END),0)                       AS anul_usd
                FROM ventas_documentos WHERE tipo = 'FACTURA'")->fetch(PDO::FETCH_ASSOC);
            $c = $pdo->query("SELECT COALESCE(SUM(monto_usd),0) AS usd, COALESCE(SUM(monto_bs),0) AS bs
                              FROM ventas_pagos WHERE estatus <> 'ANULADO'")->fetch(PDO::FETCH_ASSOC);

            $clinicas = $pdo->query("SELECT c.id, c.razon_social, c.tipo_documento, c.numero_documento, c.telefono,
                    COALESCE(SUM(CASE WHEN d.estatus IN ('EMITIDO','PARCIAL','PAGADO') THEN d.total_usd END),0) AS total_facturado,
                    COALESCE(SUM(CASE WHEN d.estatus IN ('EMITIDO','PARCIAL','PAGADO') THEN d.total_usd - d.saldo_usd END),0) AS total_cobrado,
                    COALESCE(SUM(CASE WHEN d.estatus IN ('EMITIDO','PARCIAL') THEN d.saldo_usd END),0) AS saldo_pendiente,
                    COUNT(d.id) AS total_documentos
                FROM clinicas c LEFT JOIN ventas_documentos d ON d.clinica_id = c.id AND d.tipo = 'FACTURA'
                GROUP BY c.id, c.razon_social, c.tipo_documento, c.numero_documento, c.telefono
                ORDER BY saldo_pendiente DESC, total_facturado DESC")->fetchAll(PDO::FETCH_ASSOC);

            $distribucion = $pdo->query("SELECT COALESCE(cs.nombre, 'General / Varios') AS categoria_nombre,
                    COUNT(i.id) AS total_casos, COALESCE(SUM(i.total_usd),0) AS subtotal_usd
                FROM ventas_items i
                INNER JOIN ventas_documentos d ON d.id = i.documento_id AND d.tipo = 'FACTURA' AND d.estatus IN ('EMITIDO','PARCIAL','PAGADO')
                LEFT JOIN servicios sv ON sv.id = i.servicio_id
                LEFT JOIN categorias_servicios cs ON cs.id = sv.categoria_id
                GROUP BY cs.id, cs.nombre ORDER BY subtotal_usd DESC")->fetchAll(PDO::FETCH_ASSOC);

            $pendientes = $pdo->query("SELECT d.id, d.numero AS numero_documento, d.tipo AS tipo_documento, d.clinica_id,
                    c.razon_social AS clinica_nombre, d.saldo_usd AS monto_usd, ROUND(d.saldo_usd * " . (float)$tasa . ", 2) AS monto_bs,
                    DATE_FORMAT(d.fecha_emision, '%d/%m/%Y') AS fecha_emision_fmt,
                    DATEDIFF(CURDATE(), d.fecha_emision) AS dias_antiguedad
                FROM ventas_documentos d INNER JOIN clinicas c ON c.id = d.clinica_id
                WHERE d.tipo = 'FACTURA' AND d.estatus IN ('EMITIDO','PARCIAL') AND d.saldo_usd > 0
                ORDER BY d.fecha_emision ASC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

            responderJson([
                'status' => 'success',
                'resumen' => [
                    'facturado_usd'  => r2($f['fact_usd']),
                    'facturado_bs'   => r2($f['fact_bs']),
                    'cobrado_usd'    => r2($c['usd']),
                    'cobrado_bs'     => r2($c['bs']),
                    'por_cobrar_usd' => r2($f['pend_usd']),
                    'por_cobrar_bs'  => r2($f['pend_usd'] * $tasa),
                    'anulado_usd'    => r2($f['anul_usd']),
                    'tasa_bcv'       => $tasa,
                ],
                'clinicas'               => $clinicas,
                'distribucion_servicios' => $distribucion,
                'cuentas_pendientes'     => $pendientes,
            ]);
        }

        // El "cobro rápido" ya no marca facturas como pagadas sin dinero de por medio.
        case 'marcar_cobrado': {
            responderJson(['status' => 'error',
                'message' => 'Los cobros se registran en Ventas > Cobranzas (con método de pago, tasa y referencia) para que queden en el libro contable.']);
        }

        default:
            responderJson(['status' => 'error', 'message' => 'Acción no permitida.'], 400);
    }

} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)], 500);
}
