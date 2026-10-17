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
    $id               = $_POST['id'] ?? null;
    $primer_nombre    = trim($_POST['primer_nombre'] ?? '');
    $segundo_nombre   = !empty($_POST['segundo_nombre']) ? trim($_POST['segundo_nombre']) : null;
    $primer_apellido  = trim($_POST['primer_apellido'] ?? '');
    $segundo_apellido = !empty($_POST['segundo_apellido']) ? trim($_POST['segundo_apellido']) : null;
    $tipo_documento   = $_POST['tipo_documento'] ?? null;
    $numero_documento = trim($_POST['numero_documento'] ?? '');
    $sexo             = $_POST['sexo'] ?? null;
    $clinica_id       = !empty($_POST['clinica_id']) ? (int)$_POST['clinica_id'] : null;
    $sede_id          = !empty($_POST['sede_id']) ? (int)$_POST['sede_id'] : null;

    // =========================================================================
    // VALIDACIONES
    // =========================================================================
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'El ID del paciente es obligatorio.']);
        exit();
    }

    if (empty($primer_nombre)) {
        echo json_encode(['status' => 'error', 'message' => 'El primer nombre es obligatorio.']);
        exit();
    }

    if (empty($primer_apellido)) {
        echo json_encode(['status' => 'error', 'message' => 'El primer apellido es obligatorio.']);
        exit();
    }

    if (empty($tipo_documento) || !in_array($tipo_documento, ['V', 'E'])) {
        echo json_encode(['status' => 'error', 'message' => 'El tipo de documento debe ser V o E.']);
        exit();
    }

    if (empty($numero_documento)) {
        echo json_encode(['status' => 'error', 'message' => 'El número de documento es obligatorio.']);
        exit();
    }

    if (strlen($numero_documento) > 8) {
        echo json_encode(['status' => 'error', 'message' => 'El número de documento no puede superar los 8 caracteres.']);
        exit();
    }

    if (empty($sexo) || !in_array($sexo, ['H', 'M'])) {
        echo json_encode(['status' => 'error', 'message' => 'El sexo debe ser H (Hombre) o M (Mujer).']);
        exit();
    }

    // Verificar que el paciente exista
    $stmtCheck = $db->prepare("SELECT id FROM pacientes WHERE id = ?");
    $stmtCheck->execute([$id]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'El paciente no existe.']);
        exit();
    }

    // Verificar que el número de documento no esté ya registrado en OTRO paciente
    $stmtDup = $db->prepare("SELECT id FROM pacientes WHERE numero_documento = ? AND id != ?");
    $stmtDup->execute([$numero_documento, $id]);
    if ($stmtDup->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Ya existe otro paciente con ese número de documento.']);
        exit();
    }

    // =========================================================================
    // ACTUALIZAR PACIENTE
    // =========================================================================
    $stmt = $db->prepare("
        UPDATE pacientes 
        SET 
            primer_nombre    = ?, 
            segundo_nombre   = ?, 
            primer_apellido  = ?, 
            segundo_apellido = ?, 
            tipo_documento   = ?, 
            numero_documento = ?, 
            sexo             = ?,
            clinica_id       = ?,
            sede_id          = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $primer_nombre,
        $segundo_nombre,
        $primer_apellido,
        $segundo_apellido,
        $tipo_documento,
        $numero_documento,
        $sexo,
        $clinica_id,
        $sede_id,
        $id
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Paciente actualizado correctamente.'
    ]);
    exit();

} catch (\PDOException $e) {
    http_response_code(500);
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Ya existe un paciente con ese documento.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    }
    exit();
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
}