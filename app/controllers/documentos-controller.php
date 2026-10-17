<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
// app/controllers/documentos-controller.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

$pdo = conexion();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // =========================================================================
        // 1. LISTAR DOCUMENTOS ADMINISTRATIVOS
        // =========================================================================
        case 'listar_documentos':
            $sql = "SELECT 
                        d.id,
                        d.numero_documento,
                        d.tipo_documento,
                        d.clinica_id,
                        c.razon_social AS clinica_nombre,
                        c.tipo_documento AS clinica_tipo_doc,
                        c.numero_documento AS clinica_num_doc,
                        d.sede_id,
                        COALESCE(s.nombre, 'Principal') AS sede_nombre,
                        d.orden_trabajo_id,
                        ot.codigo_qr AS orden_codigo_qr,
                        TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente_nombre,
                        sv.nombre AS servicio_nombre,
                        d.tasa_cambio,
                        d.monto_usd,
                        d.monto_bs,
                        d.estatus,
                        d.observaciones,
                        DATE_FORMAT(d.fecha_emision, '%d/%m/%Y %H:%i') AS fecha_emision_fmt,
                        DATE_FORMAT(d.fecha_emision, '%d/%m/%Y') AS fecha_corta,
                        TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS emitido_por
                    FROM documentos_administrativos d
                    INNER JOIN clinicas c ON d.clinica_id = c.id
                    LEFT JOIN sedes s ON d.sede_id = s.id
                    LEFT JOIN ordenes_trabajo ot ON d.orden_trabajo_id = ot.id
                    LEFT JOIN pacientes p ON ot.paciente_id = p.id
                    LEFT JOIN servicios sv ON ot.servicio_id = sv.id
                    LEFT JOIN usuarios u ON d.usuario_id = u.id
                    ORDER BY d.id DESC";

            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calcular KPIs
            $kpis = [
                'total_documentos' => count($rows),
                'facturado_usd'    => 0.00,
                'facturado_bs'     => 0.00,
                'total_facturas'   => 0,
                'total_garantias'  => 0,
            ];

            foreach ($rows as $r) {
                if ($r['estatus'] !== 'ANULADO') {
                    $kpis['facturado_usd'] += (float)$r['monto_usd'];
                    $kpis['facturado_bs']  += (float)$r['monto_bs'];
                }
                if ($r['tipo_documento'] === 'FACTURA') {
                    $kpis['total_facturas']++;
                } elseif ($r['tipo_documento'] === 'GARANTIA') {
                    $kpis['total_garantias']++;
                }
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $rows,
                'kpis'   => $kpis
            ]);
            break;

        // =========================================================================
        // 2. CARGAR CATÁLOGOS PARA EL GENERADOR DE DOCUMENTOS
        // =========================================================================
        case 'cargar_catalogos':
            // Clínicas
            $clinicas = $pdo->query("SELECT id, razon_social, tipo_documento, numero_documento FROM clinicas WHERE estatus = 1 OR estatus IS NULL ORDER BY razon_social ASC")->fetchAll(\PDO::FETCH_ASSOC);

            // Sedes
            $sedes = $pdo->query("SELECT id, clinica_id, nombre FROM sedes WHERE estatus = 1 OR estatus IS NULL ORDER BY nombre ASC")->fetchAll(\PDO::FETCH_ASSOC);

            // Casos Dentales
            $casos = $pdo->query("
                SELECT 
                    ot.id, 
                    ot.codigo_qr, 
                    ot.clinica_id, 
                    ot.sede_id, 
                    ot.servicio_id, 
                    sv.nombre AS servicio_nombre, 
                    sv.precio AS servicio_precio, 
                    ot.cantidad_piezas_exacta,
                    TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente_nombre
                FROM ordenes_trabajo ot
                INNER JOIN servicios sv ON ot.servicio_id = sv.id
                INNER JOIN pacientes p ON ot.paciente_id = p.id
                ORDER BY ot.id DESC
            ")->fetchAll(\PDO::FETCH_ASSOC);

            // Tasa de cambio vigente más reciente
            $stmtTasa = $pdo->query("SELECT tasa, vigente_desde AS fecha_vigencia FROM tasas_cambio WHERE is_active = 1 AND deleted_at IS NULL ORDER BY vigente_desde DESC, id DESC LIMIT 1");
            $tasaRow = $stmtTasa->fetch(\PDO::FETCH_ASSOC);
            $tasaActual = $tasaRow ? (float)$tasaRow['tasa'] : 1.00;

            echo json_encode([
                'status'      => 'success',
                'clinicas'    => $clinicas,
                'sedes'       => $sedes,
                'casos'       => $casos,
                'tasa_actual' => $tasaActual
            ]);
            break;

        // =========================================================================
        // 2.1 OBTENER CASOS Y AGRUPACIÓN DE SERVICIOS POR CLÍNICA
        // =========================================================================
        case 'obtener_casos_clinica':
            $clinicaId = (int)($_POST['clinica_id'] ?? $_GET['clinica_id'] ?? 0);
            $sedeId    = !empty($_POST['sede_id']) ? (int)$_POST['sede_id'] : null;

            if ($clinicaId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de clínica requerido.']);
                exit;
            }

            // Datos de la clínica
            $stmtC = $pdo->prepare("SELECT id, razon_social, tipo_documento, numero_documento, direccion_fiscal, telefono FROM clinicas WHERE id = ?");
            $stmtC->execute([$clinicaId]);
            $clinica = $stmtC->fetch(\PDO::FETCH_ASSOC);

            // Consultar órdenes de la clínica
            $sqlCasos = "
                SELECT 
                    ot.id,
                    ot.codigo_qr,
                    ot.clinica_id,
                    ot.sede_id,
                    COALESCE(s.nombre, 'Sede Principal') AS sede_nombre,
                    ot.servicio_id,
                    COALESCE(NULLIF(sv.nombre, ''), cs.nombre, 'Servicio Dental') AS servicio_nombre,
                    COALESCE(sv.precio, 0.00) AS servicio_precio,
                    ot.cantidad_piezas_exacta,
                    ot.estatus_actual,
                    DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y') AS fecha_ingreso_fmt,
                    TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente_nombre,
                    CONCAT(p.tipo_documento, '-', p.numero_documento) AS paciente_doc
                FROM ordenes_trabajo ot
                LEFT JOIN servicios sv ON ot.servicio_id = sv.id
                LEFT JOIN categorias_servicios cs ON sv.categoria_id = cs.id
                LEFT JOIN sedes s ON ot.sede_id = s.id
                LEFT JOIN pacientes p ON ot.paciente_id = p.id
                WHERE ot.clinica_id = ?
            ";
            $params = [$clinicaId];
            if ($sedeId) {
                $sqlCasos .= " AND ot.sede_id = ?";
                $params[] = $sedeId;
            }
            $sqlCasos .= " ORDER BY ot.id DESC";

            $stmtCasos = $pdo->prepare($sqlCasos);
            $stmtCasos->execute($params);
            $casos = $stmtCasos->fetchAll(\PDO::FETCH_ASSOC);

            // Agrupar automáticamente por servicio para emisión de factura / relación en lote
            $resumenServicios = [];
            $granTotalUsd = 0.0;
            $totalCasosCount = count($casos);

            foreach ($casos as $c) {
                $srvId = $c['servicio_id'] ?: 0;
                $srvNombre = $c['servicio_nombre'];
                $precio = (float)$c['servicio_precio'];
                $piezas = (int)($c['cantidad_piezas_exacta'] ?: 1);

                if (!isset($resumenServicios[$srvId])) {
                    $resumenServicios[$srvId] = [
                        'servicio_id'     => $srvId,
                        'servicio_nombre' => $srvNombre,
                        'precio_unitario' => $precio,
                        'total_casos'     => 0,
                        'total_piezas'    => 0,
                        'subtotal_usd'    => 0.0,
                        'codigos_casos'   => []
                    ];
                }

                $resumenServicios[$srvId]['total_casos']++;
                $resumenServicios[$srvId]['total_piezas'] += $piezas;
                $resumenServicios[$srvId]['subtotal_usd'] += $precio;
                $resumenServicios[$srvId]['codigos_casos'][] = $c['codigo_qr'];
                $granTotalUsd += $precio;
            }

            echo json_encode([
                'status'            => 'success',
                'clinica'           => $clinica,
                'casos'             => $casos,
                'resumen_servicios' => array_values($resumenServicios),
                'total_casos'       => $totalCasosCount,
                'gran_total_usd'    => round($granTotalUsd, 2)
            ]);
            break;

        // =========================================================================
        // 3. GENERAR NUEVO DOCUMENTO
        // =========================================================================
        case 'generar_documento':
            $tipoDoc       = trim($_POST['tipo_documento'] ?? 'FACTURA');
            $clinicaId     = (int)($_POST['clinica_id'] ?? 0);
            $sedeId        = !empty($_POST['sede_id']) ? (int)$_POST['sede_id'] : null;
            $ordenId       = !empty($_POST['orden_trabajo_id']) ? (int)$_POST['orden_trabajo_id'] : null;
            $montoUsd      = (float)($_POST['monto_usd'] ?? 0.00);
            $tasaCambio    = (float)($_POST['tasa_cambio'] ?? 1.00);
            $observaciones = trim($_POST['observaciones'] ?? '');
            $itemsDetalle  = $_POST['items_detalle'] ?? null;
            if (is_array($itemsDetalle)) {
                $itemsDetalle = json_encode($itemsDetalle, JSON_UNESCAPED_UNICODE);
            }
            $usuarioId     = Auth::id() ?: 1;

            if ($clinicaId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar una clínica receptora.']);
                exit;
            }

            // CAMBIO: las FACTURAS ahora se emiten en el módulo Ventas > Facturación, que calcula el IVA,
            // controla saldos y cobros y genera el asiento contable. Este módulo queda para documentos
            // NO fiscales (presupuestos, garantías, guías de entrega, órdenes de trabajo).
            if ($tipoDoc === 'FACTURA') {
                echo json_encode(['status' => 'error', 'message' => 'Las facturas se emiten en Ventas > Facturación (así quedan con IVA, cobros y contabilidad).']);
                exit;
            }

            if ($montoUsd < 0) {
                echo json_encode(['status' => 'error', 'message' => 'El monto no puede ser negativo.']);
                exit;
            }

            $montoBs = round($montoUsd * $tasaCambio, 2);

            // Generar correlativo
            $prefijo = match ($tipoDoc) {
                'FACTURA'       => 'FAC',
                'ORDEN_TRABAJO' => 'ORD-DOC',
                'PRESUPUESTO'   => 'PRE',
                'GARANTIA'      => 'GAR',
                'GUIA_ENTREGA'  => 'GUI',
                default         => 'DOC'
            };

            // Contar correlativo del año actual
            $year = date('Y');
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM documentos_administrativos WHERE tipo_documento = ?");
            $stmtCount->execute([$tipoDoc]);
            $count = (int)$stmtCount->fetchColumn() + 1;
            $numeroDoc = sprintf("%s-%s-%04d", $prefijo, $year, $count);

            $stmtInsert = $pdo->prepare("
                INSERT INTO documentos_administrativos 
                (numero_documento, tipo_documento, clinica_id, sede_id, orden_trabajo_id, usuario_id, tasa_cambio, monto_usd, monto_bs, estatus, observaciones, items_detalle, fecha_emision)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'EMITIDO', ?, ?, NOW())
            ");
            $stmtInsert->execute([
                $numeroDoc,
                $tipoDoc,
                $clinicaId,
                $sedeId,
                $ordenId,
                $usuarioId,
                $tasaCambio,
                $montoUsd,
                $montoBs,
                $observaciones,
                $itemsDetalle
            ]);

            $nuevoId = (int)$pdo->lastInsertId();

            echo json_encode([
                'status'  => 'success',
                'message' => "Documento {$numeroDoc} generado exitosamente.",
                'id'      => $nuevoId,
                'numero'  => $numeroDoc
            ]);
            break;

        // =========================================================================
        // 4. OBTENER DETALLE COMPLETO PARA VISTA PREVIA IMPRIMIBLE
        // =========================================================================
        case 'ver_detalle':
            $docId = (int)($_POST['id'] ?? 0);
            if ($docId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de documento inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT 
                    d.*,
                    c.razon_social AS clinica_nombre,
                    c.tipo_documento AS clinica_tipo_doc,
                    c.numero_documento AS clinica_num_doc,
                    c.direccion_fiscal AS clinica_direccion,
                    c.telefono AS clinica_telefono,
                    s.nombre AS sede_nombre,
                    s.direccion_fisica AS sede_direccion,
                    ot.codigo_qr AS orden_codigo_qr,
                    ot.cantidad_piezas_exacta,
                    DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y') AS orden_fecha,
                    COALESCE(NULLIF(sv.nombre, ''), 'Servicio Dental General') AS servicio_nombre,
                    sv.precio AS servicio_precio,
                    TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente_nombre,
                    CONCAT(p.tipo_documento, '-', p.numero_documento) AS paciente_doc,
                    TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS usuario_nombre,
                    DATE_FORMAT(d.fecha_emision, '%d/%m/%Y %H:%i') AS fecha_emision_fmt
                FROM documentos_administrativos d
                INNER JOIN clinicas c ON d.clinica_id = c.id
                LEFT JOIN sedes s ON d.sede_id = s.id
                LEFT JOIN ordenes_trabajo ot ON d.orden_trabajo_id = ot.id
                LEFT JOIN servicios sv ON ot.servicio_id = sv.id
                LEFT JOIN pacientes p ON ot.paciente_id = p.id
                LEFT JOIN usuarios u ON d.usuario_id = u.id
                WHERE d.id = ?
                LIMIT 1
            ");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$doc) {
                echo json_encode(['status' => 'error', 'message' => 'Documento no encontrado.']);
                exit;
            }

            // Parsear items_detalle si existe
            if (!empty($doc['items_detalle'])) {
                $doc['items'] = json_decode($doc['items_detalle'], true);
            } else {
                $doc['items'] = null;
            }

            echo json_encode(['status' => 'success', 'documento' => $doc]);
            break;

        // =========================================================================
        // 5. CAMBIAR ESTATUS (PAGADO, ANULADO)
        // =========================================================================
        case 'cambiar_estatus':
            $docId    = (int)($_POST['id'] ?? 0);
            $nuevoEst = strtoupper(trim($_POST['estatus'] ?? ''));

            if ($docId <= 0 || !in_array($nuevoEst, ['EMITIDO', 'PAGADO', 'ANULADO'])) {
                echo json_encode(['status' => 'error', 'message' => 'Estatus o ID inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE documentos_administrativos SET estatus = ? WHERE id = ?");
            $stmt->execute([$nuevoEst, $docId]);

            echo json_encode(['status' => 'success', 'message' => "Estatus actualizado a {$nuevoEst}."]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no permitida.']);
            break;
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}
