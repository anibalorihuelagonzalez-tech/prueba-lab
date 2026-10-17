<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
if (session_status() === PHP_SESSION_NONE) {
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Utilice POST.']);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

$inputData = json_decode(file_get_contents('php://input'), true) ?: [];
$id = !empty($_POST['id'] ?? $inputData['id']) ? (int)($_POST['id'] ?? $inputData['id']) : null;

if (empty($id)) {
    echo json_encode(['status' => 'error', 'message' => 'El ID de la tasa es requerido.']);
    exit();
}

try {
    $db = conexion();

    // Verificar si existe y no ha sido eliminada
    $check = $db->prepare("SELECT id FROM tasas_cambio WHERE id = ? AND deleted_at IS NULL");
    $check->execute([$id]);
    if (!$check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró la tasa de cambio especificada o ya fue eliminada.']);
        exit();
    }

    // Soft delete: actualizar deleted_at y desactivar
    $stmt = $db->prepare("UPDATE tasas_cambio SET is_active = 0, deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'Tasa de cambio eliminada exitosamente.']);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
}