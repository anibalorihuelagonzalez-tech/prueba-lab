<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
$pdo = conexion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$id               = $_POST['id'] ?? null;
$razon_social     = trim($_POST['razon_social'] ?? '');
$tipo_documento   = trim($_POST['tipo_documento'] ?? '');
$numero_documento = trim($_POST['numero_documento'] ?? '');
$direccion_fiscal = trim($_POST['direccion_fiscal'] ?? '');
$telefono         = trim($_POST['telefono'] ?? '');

if (!$id || empty($razon_social) || empty($tipo_documento) || empty($numero_documento) || empty($direccion_fiscal) || empty($telefono)) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios para actualizar.']);
    exit;
}

try {
    // Verificar si el documento ya existe en OTRA clínica distinta a la que estamos editando
    $stmtCheck = $pdo->prepare("SELECT id FROM clinicas WHERE numero_documento = ? AND id != ?");
    $stmtCheck->execute([$numero_documento, $id]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'El número de documento pertenece a otra clínica.']);
        exit;
    }

    $sql = "UPDATE clinicas 
            SET razon_social = ?, tipo_documento = ?, numero_documento = ?, direccion_fiscal = ?, telefono = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$razon_social, $tipo_documento, $numero_documento, $direccion_fiscal, $telefono, $id]);

    // Verificar si realmente se hizo algún cambio (opcional, pero útil)
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Clínica actualizada exitosamente.']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'No se realizaron cambios en la clínica.']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}