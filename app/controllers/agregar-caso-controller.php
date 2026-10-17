<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = conexion();

    // =========================================================================
    // RECOGER DATOS DEL FORMULARIO
    // =========================================================================
    $clinica_id             = (int)($_POST['clinica_id'] ?? 0);
    $sede_id                = !empty($_POST['sede_id']) ? (int)$_POST['sede_id'] : null;
    $paciente_id            = (int)($_POST['paciente_id'] ?? 0);
    $servicio_id            = (int)($_POST['servicio_id'] ?? 0);
    $cantidad_protesis      = max(1, (int)($_POST['cantidad_protesis'] ?? 1));
    $protesista_id          = !empty($_POST['protesista_id']) ? (int)$_POST['protesista_id'] : null;
    $cantidad_piezas_exacta = (int)($_POST['cantidad_piezas_exacta'] ?? 1);
    $etapa_produccion_id    = (int)($_POST['etapa_produccion_id'] ?? 1);
    $estatus_actual         = trim($_POST['estatus_actual'] ?? 'RECIBIDO EN LAB');
    $usuario_id             = (int)($_POST['usuario_id'] ?? ($_SESSION['usuario_id'] ?? ($protesista_id ?? 0)));
    $ubicacion              = trim($_POST['ubicacion'] ?? 'RECEPCIÓN LABORATORIO');
    $notas                  = !empty($_POST['notas']) ? substr(trim($_POST['notas']), 0, 255) : 'Ingreso inicial del caso.';

    // =========================================================================
    // VALIDACIONES
    // =========================================================================
    if ($clinica_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar una clínica solicitante.']);
        exit();
    }

    if ($paciente_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar un paciente.']);
        exit();
    }

    if ($servicio_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar el tipo de prótesis o servicio.']);
        exit();
    }

    if ($cantidad_piezas_exacta < 1 || $cantidad_piezas_exacta > 32) {
        echo json_encode(['status' => 'error', 'message' => 'La cantidad de piezas debe estar entre 1 y 32.']);
        exit();
    }

    if ($usuario_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debe asignar un usuario responsable para el caso.']);
        exit();
    }

    if (empty($ubicacion)) {
        echo json_encode(['status' => 'error', 'message' => 'La ubicación física es obligatoria.']);
        exit();
    }

    // =========================================================================
    // INSERTAR ORDEN, DETALLE DE ETAPA Y TRAZABILIDAD (TRANSACCIÓN)
    // =========================================================================
    $db->beginTransaction();

    $qrTemporal = 'ORD-' . strtoupper(bin2hex(random_bytes(3))) . '-' . time();

    // 1. Insertar en ordenes_trabajo
    $stmtOrden = $db->prepare("
        INSERT INTO ordenes_trabajo (
            clinica_id, 
            sede_id,
            servicio_id, 
            paciente_id, 
            codigo_qr, 
            cantidad_protesis,
            protesista_id,
            cantidad_piezas_exacta, 
            estatus_actual
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtOrden->execute([
        $clinica_id,
        $sede_id,
        $servicio_id,
        $paciente_id,
        $qrTemporal,
        $cantidad_protesis,
        $protesista_id,
        $cantidad_piezas_exacta,
        $estatus_actual
    ]);

    $ordenTrabajoId = (int)$db->lastInsertId();
    $codigoQrFinal  = 'ORD-' . str_pad((string)$ordenTrabajoId, 4, '0', STR_PAD_LEFT);

    $stmtUpdateQr = $db->prepare("UPDATE ordenes_trabajo SET codigo_qr = ? WHERE id = ?");
    $stmtUpdateQr->execute([$codigoQrFinal, $ordenTrabajoId]);

    // 2. Insertar en ordenes_detalle
    $stmtDetalle = $db->prepare("
        INSERT INTO ordenes_detalle (
            orden_trabajo_id, 
            servicio_id, 
            etapa_produccion_id
        ) VALUES (?, ?, ?)
    ");
    $stmtDetalle->execute([
        $ordenTrabajoId,
        $servicio_id,
        $etapa_produccion_id
    ]);

    $ordenDetalleId = (int)$db->lastInsertId();

    // Obtener nombre de la etapa
    $stmtEtapa = $db->prepare("SELECT nombre FROM etapas_produccion WHERE id = ?");
    $stmtEtapa->execute([$etapa_produccion_id]);
    $nombreEtapa = $stmtEtapa->fetchColumn() ?: 'TOMA DE IMPRESION';

    $estatusTrazabilidad = substr($nombreEtapa . ' - ' . $estatus_actual, 0, 45);

    // 3. Insertar en historial_trazabilidad
    $stmtTrazabilidad = $db->prepare("
        INSERT INTO historial_trazabilidad (
            orden_trabajo_id, 
            orden_detalle_id, 
            usuario_id, 
            estatus, 
            ubicacion, 
            notas
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtTrazabilidad->execute([
        $ordenTrabajoId,
        $ordenDetalleId,
        $usuario_id,
        $estatusTrazabilidad,
        $ubicacion,
        $notas
    ]);

    $db->commit();

    echo json_encode([
        'status'           => 'success',
        'message'          => "Caso #$codigoQrFinal registrado y asignado exitosamente.",
        'orden_trabajo_id' => $ordenTrabajoId
    ]);
    exit();

} catch (\PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Conflicto de llaves o código QR duplicado.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    }
    exit();
} catch (\Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
}