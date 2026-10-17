<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = conexion();

    $sede_id = $_POST['id'] ?? null;

    // =========================================================================
    // VALIDACIONES
    // =========================================================================
    if (empty($sede_id)) {
        echo json_encode(['status' => 'error', 'message' => 'El ID de la sede es obligatorio.']);
        exit();
    }

    // Verificar que la sede exista y esté activa
    $stmtCheck = $db->prepare("SELECT id, estatus FROM sedes WHERE id = ?");
    $stmtCheck->execute([$sede_id]);
    $sede = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$sede) {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró la sede especificada.']);
        exit();
    }

    if ((int)$sede['estatus'] === 0) {
        echo json_encode(['status' => 'error', 'message' => 'La sede ya se encuentra inactiva.']);
        exit();
    }

    // =========================================================================
    // DESACTIVAR LA SEDE (Soft Delete)
    // =========================================================================
    $stmt = $db->prepare("UPDATE sedes SET estatus = 0, principal = 0 WHERE id = ?");
    $stmt->execute([$sede_id]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Sede desactivada correctamente.'
    ]);
    exit();

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => errorSeguro($e)
    ]);
    exit();
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => errorSeguro($e)
    ]);
    exit();
}