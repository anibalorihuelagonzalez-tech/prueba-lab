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

// 3. Leemos el ID desde $_POST o desde JSON crudo en php://input
$inputData = json_decode(file_get_contents('php://input'), true) ?: [];
$id = filter_var($_POST['id'] ?? $inputData['id'] ?? null, FILTER_VALIDATE_INT);

// 4. Validación de ID
if (!$id || $id <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'El ID del usuario es requerido y debe ser válido.'
    ]);
    exit();
}

// 5. Prevenir que el usuario en sesión se dé de baja a sí mismo
if (isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] === (int)$id) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'No puedes dar de baja tu propia cuenta mientras mantienes la sesión activa.'
    ]);
    exit();
}

try {
    $db = conexion();

    // 6. Verificar que el usuario exista
    $stmtCheck = $db->prepare("SELECT id, primer_nombre, primer_apellido FROM usuarios WHERE id = ?");
    $stmtCheck->execute([$id]);
    $usuario = $stmtCheck->fetch();

    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'El usuario no existe o ya ha sido dado de baja.'
        ]);
        exit();
    }

    // 7. Ejecutar baja del usuario
    // Nota: Si el esquema de base de datos contara con una columna 'estado' o 'activo', se realizaría
    // un UPDATE para baja lógica. Como la tabla 'usuarios' actual no cuenta con dicha columna,
    // se ejecuta la eliminación (baja física) del registro.
    $stmtDelete = $db->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmtDelete->execute([$id]);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => "El usuario {$usuario['primer_nombre']} {$usuario['primer_apellido']} ha sido dado de baja exitosamente."
    ]);
    exit();

} catch (\PDOException $e) {
    http_response_code(500);
    // Si hay restricciones de clave foránea u otro error SQL
    if ($e->getCode() == '23000') {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se puede dar de baja al usuario porque tiene registros vinculados en el sistema.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => errorSeguro($e)
        ]);
    }
    exit();
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => errorSeguro($e)
    ]);
    exit();
}
