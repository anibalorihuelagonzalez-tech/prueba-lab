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

$primer_nombre = trim($_POST['primer_nombre'] ?? $inputData['primer_nombre'] ?? '');
$primer_apellido = trim($_POST['primer_apellido'] ?? $inputData['primer_apellido'] ?? '');
$correo_electronico = trim($_POST['correo_electronico'] ?? $inputData['correo_electronico'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? $inputData['contrasena'] ?? '');
$rol_id = filter_var($_POST['rol_id'] ?? $inputData['rol_id'] ?? null, FILTER_VALIDATE_INT);

// 4. Validaciones de campos obligatorios
if (empty($primer_nombre) || empty($primer_apellido) || empty($correo_electronico) || empty($contrasena) || empty($rol_id)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Todos los campos son obligatorios para crear un usuario.'
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

// 6. Validación de longitud de contraseña
if (strlen($contrasena) < 8) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'La contraseña debe tener un mínimo de 8 caracteres.'
    ]);
    exit();
}

try {
    $db = conexion();

    // 7. Verificar que el rol seleccionado exista
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

    // 8. Validar email único
    $stmtEmail = $db->prepare("SELECT id FROM usuarios WHERE correo_electronico = ?");
    $stmtEmail->execute([$correo_electronico]);
    if ($stmtEmail->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode([
            'status' => 'error',
            'message' => 'El correo electrónico ya se encuentra registrado.'
        ]);
        exit();
    }

    // 9. Hashear contraseña e insertar usuario
    $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);
    $stmtInsert = $db->prepare("
        INSERT INTO usuarios (primer_nombre, primer_apellido, correo_electronico, contrasena, rol_id, estatus, fecha_hora_creacion)
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmtInsert->execute([$primer_nombre, $primer_apellido, $correo_electronico, $passwordHash, $rol_id]);
    $newId = $db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Usuario creado exitosamente.',
        'id' => $newId
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
