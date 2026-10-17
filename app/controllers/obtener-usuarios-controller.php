<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
header('Content-Type: application/json; charset=utf-8');

// 1. Verificamos que la petición sea estrictamente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Utilice POST.']);
    exit();
}

require_once __DIR__ . '/../config/database.php';

// 2. Leemos JSON crudo por si alguna petición lo envía de esa forma
$inputData = json_decode(file_get_contents('php://input'), true) ?: [];

// 3. Obtenemos la acción y las variables dando prioridad a $_POST (clave para que FormData funcione)
$action = $_POST['action'] ?? $inputData['action'] ?? '';
$page   = (int)($_POST['page'] ?? $inputData['page'] ?? 1);
$size   = (int)($_POST['size'] ?? $inputData['size'] ?? 10);
$id     = $_POST['id'] ?? $inputData['id'] ?? null;

try {
    $db = conexion();

    // =========================================================================
    // MÉTODO 1: CONSULTAR TODOS LOS USUARIOS (Usado por Tabulator)
    // =========================================================================
    if ($action === 'listar' || $action === 'list') {
        
        $offset = ($page - 1) * $size;

        // Contamos el total para la paginación
        $stmtTotal = $db->query("SELECT COUNT(*) FROM usuarios");
        $totalRows = $stmtTotal->fetchColumn();

        // Traemos los datos con el nombre del rol
        $query = "SELECT u.id, u.primer_nombre, u.primer_apellido, u.correo_electronico, u.rol_id, r.rol as rol_nombre 
                  FROM usuarios u 
                  INNER JOIN roles_usuarios r ON u.rol_id = r.id 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':limit', $size, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $usuarios = $stmt->fetchall();

        $lastPage = ceil($totalRows / $size);

        // Retornamos el formato exacto que pide Tabulator
        echo json_encode([
            "last_page" => $lastPage > 0 ? $lastPage : 1,
            "data" => $usuarios
        ]);
        exit();

    // =========================================================================
    // MÉTODO 2: CONSULTAR 1 USUARIO EN ESPECÍFICO (Usado al Editar)
    // =========================================================================
    } elseif ($action === 'show') {
        
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'El ID del usuario es requerido.']);
            exit();
        }

        $stmt = $db->prepare("SELECT id, primer_nombre, primer_apellido, correo_electronico, rol_id FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            echo json_encode(['status' => 'success', 'usuario' => $usuario]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado.']);
        }
        exit();

    // =========================================================================
    // ACCIÓN DESCONOCIDA
    // =========================================================================
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => "Acción no válida o no especificada. (Recibido: '$action')"
        ]);
        exit();
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
}