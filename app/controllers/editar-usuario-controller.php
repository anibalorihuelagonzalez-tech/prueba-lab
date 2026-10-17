<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);

header('Content-Type: application/json; charset=utf-8');

// 1. Verificamos que la petición sea estrictamente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido. Utilice POST.'
    ]);
    exit();
}

// 2. Conexión a la base de datos
require_once __DIR__ . '/../config/database.php';

// 3. Leemos datos desde $_POST o desde JSON crudo en php://input
$inputData = json_decode(file_get_contents('php://input'), true) ?: [];

$id = filter_var($_POST['id'] ?? $inputData['id'] ?? null, FILTER_VALIDATE_INT);
$primer_nombre = trim($_POST['primer_nombre'] ?? $inputData['primer_nombre'] ?? '');
$primer_apellido = trim($_POST['primer_apellido'] ?? $inputData['primer_apellido'] ?? '');
$correo_electronico = trim($_POST['correo_electronico'] ?? $inputData['correo_electronico'] ?? '');
$rol_id = filter_var($_POST['rol_id'] ?? $inputData['rol_id'] ?? null, FILTER_VALIDATE_INT);
$contrasena = $_POST['contrasena'] ?? $inputData['contrasena'] ?? null;
if ($contrasena !== null) {
    $contrasena = trim($contrasena);
}

// 4. Validaciones de campos obligatorios
if (!$id || $id <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'El ID del usuario es requerido y debe ser válido.'
    ]);
    exit();
}

if (empty($primer_nombre) || empty($primer_apellido) || empty($correo_electronico) || empty($rol_id)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Todos los campos obligatorios deben ser completados.'
    ]);
    exit();
}

// 5. Validación de formato de email
if (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'El formato del correo electrónico no es válido.'
    ]);
    exit();
}

// 6. Validación opcional de contraseña (si se envía, debe tener mínimo 8 caracteres)
if (!empty($contrasena) && strlen($contrasena) < 8) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'La contraseña debe tener un mínimo de 8 caracteres.'
    ]);
    exit();
}

try {
    $db = conexion();

    // 7. Verificar que el usuario a editar realmente exista
    $stmtUser = $db->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmtUser->execute([$id]);
    if (!$stmtUser->fetch()) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'El usuario que intentas editar no existe.'
        ]);
        exit();
    }

    // 8. Verificar que el rol seleccionado exista
    $stmtRol = $db->prepare("SELECT id FROM roles_usuarios WHERE id = ?");
    $stmtRol->execute([$rol_id]);
    if (!$stmtRol->fetch()) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'El rol seleccionado no es válido.'
        ]);
        exit();
    }

    // 9. Validar email único (que no pertenezca a OTRO usuario)
    $stmtEmail = $db->prepare("SELECT id FROM usuarios WHERE correo_electronico = ? AND id != ?");
    $stmtEmail->execute([$correo_electronico, $id]);
    if ($stmtEmail->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode([
            'status' => 'error',
            'message' => 'El correo electrónico ya está registrado por otro usuario.'
        ]);
        exit();
    }

    // 10. Actualizar registro en la base de datos
    if (!empty($contrasena)) {
        // Se actualiza incluyendo la nueva contraseña hasheada
        $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmtUpdate = $db->prepare("
            UPDATE usuarios 
            SET primer_nombre = ?, 
                primer_apellido = ?, 
                correo_electronico = ?, 
                rol_id = ?, 
                contrasena = ?, 
                fecha_hora_actualizacion = NOW() 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$primer_nombre, $primer_apellido, $correo_electronico, $rol_id, $passwordHash, $id]);
    } else {
        // Se actualizan los datos sin modificar la contraseña existente
        $stmtUpdate = $db->prepare("
            UPDATE usuarios 
            SET primer_nombre = ?, 
                primer_apellido = ?, 
                correo_electronico = ?, 
                rol_id = ?, 
                fecha_hora_actualizacion = NOW() 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$primer_nombre, $primer_apellido, $correo_electronico, $rol_id, $id]);
    }

    // 11. Si el usuario editado es quien está en sesión, actualizar sus datos de sesión
    if (isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] === (int)$id) {
        $_SESSION['usuario_nombre'] = $primer_nombre . ' ' . $primer_apellido;
        $_SESSION['rol_id'] = $rol_id;
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Usuario actualizado correctamente.'
    ]);
    exit();

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => errorSeguro($e)
    ]);
    exit();
}
