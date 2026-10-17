<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$pdo = conexion(); 

// Validación opcional de sesión
// if (!isset($_SESSION['usuario_id'])) { 
//     echo json_encode(['status' => 'error', 'message' => 'No autorizado']); exit; 
// }

require_once __DIR__ . '/../config/database.php'; // Ajusta la ruta a tu archivo de conexión PDO ($pdo)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

// Capturamos la acción solicitada por el frontend
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // 1. LISTAR TODAS LAS CLÍNICAS (Para llenar la tabla Tabulator)
        case 'listar_clinicas':
            // Obtenemos todas las clínicas ordenadas por id descendente (las más nuevas primero)
            $stmt = $pdo->prepare("SELECT id, razon_social, tipo_documento, numero_documento, direccion_fiscal, telefono, estatus FROM clinicas WHERE estatus = 1 ORDER BY id DESC");
            $stmt->execute();
            $clinicas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Tabulator espera por defecto un objeto con la propiedad "data" o directamente el array.
            // Para mantener consistencia con la configuración de tu frontend, devolvemos un objeto con "data".
            echo json_encode(['data' => $clinicas]);
            break;

        // 2. OBTENER UNA CLÍNICA ESPECÍFICA (Para llenar el modal de Editar)
        case 'show_clinica':
            $id = $_POST['id'] ?? null;

            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'ID de clínica no proporcionado.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, razon_social, tipo_documento, numero_documento, direccion_fiscal, telefono FROM clinicas WHERE id = ?");
            $stmt->execute([$id]);
            $clinica = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($clinica) {
                echo json_encode(['status' => 'success', 'clinica' => $clinica]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró la clínica solicitada.']);
            }
            break;

        // ACCIÓN NO RECONOCIDA
        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida o no especificada.']);
            break;
    }

} catch (PDOException $e) {
    // Manejo de errores de base de datos
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}