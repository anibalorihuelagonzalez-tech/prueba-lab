<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = conexion();

    $action = $_POST['action'] ?? 'procesar_caso';

    // =========================================================================
    // ELIMINAR CASO
    // =========================================================================
    if ($action === 'eliminar_caso') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de caso inválido.']);
            exit();
        }

        $stmtDel = $db->prepare("DELETE FROM ordenes_trabajo WHERE id = ?");
        $stmtDel->execute([$id]);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Caso eliminado exitosamente.'
        ]);
        exit();
    }

    // =========================================================================
    // PROCESAR CASO (CAMBIAR ETAPA, RESPONSABLE Y UBICACIÓN FÍSICA)
    // =========================================================================
    $orden_trabajo_id    = (int)($_POST['orden_trabajo_id'] ?? 0);
    $etapa_produccion_id = (int)($_POST['etapa_produccion_id'] ?? 0);
    $usuario_id          = (int)($_POST['usuario_id'] ?? 0);
    $ubicacion           = trim($_POST['ubicacion'] ?? '');
    $estatus_actual      = trim($_POST['estatus_actual'] ?? '');
    $notas               = !empty($_POST['notas']) ? substr(trim($_POST['notas']), 0, 255) : null;

    if ($orden_trabajo_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'El ID de la orden de trabajo es obligatorio.']);
        exit();
    }

    if ($etapa_produccion_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar la etapa de producción.']);
        exit();
    }

    if ($usuario_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debe asignar un usuario responsable.']);
        exit();
    }

    if (empty($ubicacion)) {
        echo json_encode(['status' => 'error', 'message' => 'Debe indicar la ubicación física actual del caso.']);
        exit();
    }

    if (empty($estatus_actual)) {
        echo json_encode(['status' => 'error', 'message' => 'El estatus operativo es obligatorio.']);
        exit();
    }

    $db->beginTransaction();

    // 1. Actualizar estatus en ordenes_trabajo
    $stmtUpdOrden = $db->prepare("UPDATE ordenes_trabajo SET estatus_actual = ? WHERE id = ?");
    $stmtUpdOrden->execute([$estatus_actual, $orden_trabajo_id]);

    // 2. Actualizar etapa en ordenes_detalle
    $stmtDet = $db->prepare("SELECT id FROM ordenes_detalle WHERE orden_trabajo_id = ? ORDER BY id DESC LIMIT 1");
    $stmtDet->execute([$orden_trabajo_id]);
    $ordenDetalleId = (int)$stmtDet->fetchColumn();

    if ($ordenDetalleId > 0) {
        $stmtUpdDet = $db->prepare("UPDATE ordenes_detalle SET etapa_produccion_id = ? WHERE id = ?");
        $stmtUpdDet->execute([$etapa_produccion_id, $ordenDetalleId]);
    } else {
        $stmtServ = $db->prepare("SELECT servicio_id FROM ordenes_trabajo WHERE id = ?");
        $stmtServ->execute([$orden_trabajo_id]);
        $servicioId = (int)$stmtServ->fetchColumn();

        $stmtInsDet = $db->prepare("INSERT INTO ordenes_detalle (orden_trabajo_id, servicio_id, etapa_produccion_id) VALUES (?, ?, ?)");
        $stmtInsDet->execute([$orden_trabajo_id, $servicioId, $etapa_produccion_id]);
        $ordenDetalleId = (int)$db->lastInsertId();
    }

    // 3. Consultar nombre de la etapa
    $stmtEtapa = $db->prepare("SELECT nombre FROM etapas_produccion WHERE id = ?");
    $stmtEtapa->execute([$etapa_produccion_id]);
    $nombreEtapa = $stmtEtapa->fetchColumn() ?: 'ETAPA';

    $estatusTrazabilidad = substr($nombreEtapa . ' - ' . $estatus_actual, 0, 45);

    // 4. Insertar registro en historial_trazabilidad
    $stmtHist = $db->prepare("
        INSERT INTO historial_trazabilidad (
            orden_trabajo_id, 
            orden_detalle_id, 
            usuario_id, 
            estatus, 
            ubicacion, 
            notas
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtHist->execute([
        $orden_trabajo_id,
        $ordenDetalleId,
        $usuario_id,
        $estatusTrazabilidad,
        $ubicacion,
        $notas
    ]);

    $db->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Etapa, responsable y ubicación física actualizados exitosamente.'
    ]);
    exit();

} catch (\PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
} catch (\Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
}