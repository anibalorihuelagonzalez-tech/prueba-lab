<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$inputData = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_POST['action'] ?? $inputData['action'] ?? '';

// Debug: Ver qué está llegando
error_log("=== obtener-sedes-controller ===");
error_log("POST: " . print_r($_POST, true));
error_log("action: " . $action);

try {
    $db = conexion();

    // =========================================================================
    // MÉTODO 1: LISTAR TODAS LAS SEDES DE UNA CLÍNICA
    // =========================================================================
    if ($action === 'listar_sedes') {
        $clinica_id = $_POST['clinica_id'] ?? $inputData['clinica_id'] ?? null;

        error_log("clinica_id recibido: " . var_export($clinica_id, true));

        if (empty($clinica_id)) {
            echo json_encode(['status' => 'error', 'message' => 'El ID de la clínica es obligatorio.']);
            exit();
        }

        // Verificar que la clínica exista
        $stmtCheck = $db->prepare("SELECT id FROM clinicas WHERE id = ?");
        $stmtCheck->execute([$clinica_id]);
        if (!$stmtCheck->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'La clínica especificada no existe.']);
            exit();
        }

        // Obtener solo las sedes activas de esa clínica
        $stmt = $db->prepare("
            SELECT id, clinica_id, nombre, direccion_fisica, latitud, longitud, principal, estatus
            FROM sedes 
            WHERE clinica_id = ? AND estatus = 1 
            ORDER BY principal DESC, nombre ASC
        ");
        $stmt->execute([$clinica_id]);
        $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("Sedes encontradas: " . count($sedes));

        echo json_encode([
            'status' => 'success',
            'data'   => $sedes
        ]);
        exit();
    }

    // =========================================================================
    // MÉTODO 2: OBTENER UNA SEDE ESPECÍFICA POR ID
    // =========================================================================
    elseif ($action === 'show_sede') {
        $id = $_POST['id'] ?? $inputData['id'] ?? null;

        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'El ID de la sede es obligatorio.']);
            exit();
        }

        $stmt = $db->prepare("
            SELECT id, clinica_id, nombre, direccion_fisica, latitud, longitud, principal, estatus
            FROM sedes 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $sede = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sede) {
            echo json_encode([
                'status' => 'success',
                'sede'   => $sede
            ]);
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'No se encontró la sede especificada.'
            ]);
        }
        exit();
    }

    // =========================================================================
    // ACCIÓN NO VÁLIDA
    // =========================================================================
    else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Acción no válida. Use "listar_sedes" o "show_sede".'
        ]);
        exit();
    }

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