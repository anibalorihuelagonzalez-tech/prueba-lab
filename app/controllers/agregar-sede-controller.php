<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = conexion();

    // =========================================================================
    // RECOGER DATOS DEL FORMULARIO
    // =========================================================================
    $clinica_id       = $_POST['clinica_id'] ?? null;
    $nombre           = trim($_POST['nombre'] ?? '');
    $direccion_fisica = trim($_POST['direccion_fisica'] ?? '');
    $latitud          = !empty($_POST['latitud']) ? trim($_POST['latitud']) : null;
    $longitud         = !empty($_POST['longitud']) ? trim($_POST['longitud']) : null;
    $principal        = isset($_POST['principal']) ? (int)$_POST['principal'] : 0;

    // =========================================================================
    // VALIDACIONES
    // =========================================================================
    if (empty($clinica_id)) {
        echo json_encode(['status' => 'error', 'message' => 'El ID de la clínica es obligatorio.']);
        exit();
    }

    if (empty($nombre)) {
        echo json_encode(['status' => 'error', 'message' => 'El nombre de la sede es obligatorio.']);
        exit();
    }

    if (empty($direccion_fisica)) {
        echo json_encode(['status' => 'error', 'message' => 'La dirección física es obligatoria.']);
        exit();
    }

    // Verificar que la clínica exista
    $stmtCheck = $db->prepare("SELECT id FROM clinicas WHERE id = ?");
    $stmtCheck->execute([$clinica_id]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'La clínica especificada no existe.']);
        exit();
    }

    // =========================================================================
    // LÓGICA DE SEDE PRINCIPAL
    // Si esta sede se marca como principal, desmarcar las demás de esa clínica
    // =========================================================================
    if ($principal === 1) {
        $stmtReset = $db->prepare("UPDATE sedes SET principal = 0 WHERE clinica_id = ?");
        $stmtReset->execute([$clinica_id]);
    }

    // =========================================================================
    // INSERTAR LA NUEVA SEDE
    // =========================================================================
    $stmt = $db->prepare("
        INSERT INTO sedes (clinica_id, nombre, direccion_fisica, latitud, longitud, principal, estatus) 
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $clinica_id,
        $nombre,
        $direccion_fisica,
        $latitud,
        $longitud,
        $principal
    ]);

    $nuevoId = $db->lastInsertId();

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Sede registrada exitosamente.',
        'sede_id'  => $nuevoId
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