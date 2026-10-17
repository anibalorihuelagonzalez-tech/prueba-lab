<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
header('Content-Type: application/json; charset=utf-8');

// if (!isset($_SESSION['usuario_id'])) { 
//     echo json_encode(['status' => 'error', 'message' => 'No autorizado']); exit; 
// }

require_once __DIR__ . '/../config/database.php'; 

$pdo = conexion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$razon_social     = trim($_POST['razon_social'] ?? '');
$tipo_documento   = trim($_POST['tipo_documento'] ?? '');
$numero_documento = trim($_POST['numero_documento'] ?? '');
$direccion_fiscal = trim($_POST['direccion_fiscal'] ?? '');
$telefono         = trim($_POST['telefono'] ?? '');

// Validación básica
if (empty($razon_social) || empty($tipo_documento) || empty($numero_documento) || empty($direccion_fiscal) || empty($telefono)) {
    echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

try {
    // Verificar si el documento ya existe (UNIQUE KEY uk_clinica_documento)
    $stmtCheck = $pdo->prepare("SELECT id FROM clinicas WHERE numero_documento = ?");
    $stmtCheck->execute([$numero_documento]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Ya existe una clínica con este número de documento.']);
        exit;
    }

    // Insertar la clínica
    $sql = "INSERT INTO clinicas (razon_social, tipo_documento, numero_documento, direccion_fiscal, telefono, estatus) 
            VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$razon_social, $tipo_documento, $numero_documento, $direccion_fiscal, $telefono]);

    echo json_encode(['status' => 'success', 'message' => 'Clínica registrada exitosamente.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}