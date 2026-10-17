<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
// app/controllers/departamentos-controller.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit;
}

$pdo = conexion();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // =========================================================================
        // 1. LISTAR DEPARTAMENTOS
        // =========================================================================
        case 'listar':
            $sql = "
                SELECT 
                    d.id,
                    d.nombre,
                    d.descripcion,
                    d.is_active,
                    DATE_FORMAT(d.created_at, '%d/%m/%Y') AS fecha_creacion,
                    (SELECT COUNT(*) FROM cargos c WHERE c.departamento_id = d.id OR c.departamento = d.nombre) AS total_cargos,
                    (SELECT COUNT(*) FROM empleados e 
                     INNER JOIN cargos c2 ON e.cargo_id = c2.id 
                     WHERE c2.departamento_id = d.id OR c2.departamento = d.nombre) AS total_empleados
                FROM departamentos d
                ORDER BY d.id ASC
            ";
            $stmt = $pdo->query($sql);
            $departamentos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $kpis = [
                'total_departamentos' => count($departamentos),
                'activos'             => 0,
                'inactivos'           => 0,
                'total_cargos'        => 0
            ];

            foreach ($departamentos as $d) {
                if ((int)$d['is_active'] === 1) {
                    $kpis['activos']++;
                } else {
                    $kpis['inactivos']++;
                }
                $kpis['total_cargos'] += (int)$d['total_cargos'];
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $departamentos,
                'kpis'   => $kpis
            ]);
            break;

        // =========================================================================
        // 2. OBTENER DETALLE DE UN DEPARTAMENTO
        // =========================================================================
        case 'show':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de departamento inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, nombre, descripcion, is_active FROM departamentos WHERE id = ?");
            $stmt->execute([$id]);
            $depto = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$depto) {
                echo json_encode(['status' => 'error', 'message' => 'Departamento no encontrado.']);
                exit;
            }

            echo json_encode(['status' => 'success', 'data' => $depto]);
            break;

        // =========================================================================
        // 3. REGISTRAR DEPARTAMENTO
        // =========================================================================
        case 'store':
            $nombre      = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $isActive    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if (empty($nombre)) {
                echo json_encode(['status' => 'error', 'message' => 'El nombre del departamento es obligatorio.']);
                exit;
            }

            // Validar duplicado
            $stmtCheck = $pdo->prepare("SELECT id FROM departamentos WHERE LOWER(nombre) = LOWER(?)");
            $stmtCheck->execute([$nombre]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Ya existe un departamento con ese nombre.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO departamentos (nombre, descripcion, is_active, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$nombre, $descripcion, $isActive]);

            echo json_encode([
                'status'  => 'success',
                'message' => "Departamento '{$nombre}' registrado con éxito."
            ]);
            break;

        // =========================================================================
        // 4. ACTUALIZAR DEPARTAMENTO
        // =========================================================================
        case 'update':
            $id          = (int)($_POST['id'] ?? 0);
            $nombre      = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $isActive    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if ($id <= 0 || empty($nombre)) {
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para actualizar.']);
                exit;
            }

            // Validar duplicado en otro ID
            $stmtCheck = $pdo->prepare("SELECT id FROM departamentos WHERE LOWER(nombre) = LOWER(?) AND id != ?");
            $stmtCheck->execute([$nombre, $id]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Ya existe otro departamento con ese nombre.']);
                exit;
            }

            // Obtener nombre anterior para sincronizar cargos
            $stmtOld = $pdo->prepare("SELECT nombre FROM departamentos WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldNombre = $stmtOld->fetchColumn();

            $stmt = $pdo->prepare("UPDATE departamentos SET nombre = ?, descripcion = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $isActive, $id]);

            // Sincronizar nombre en cargos
            if ($oldNombre && $oldNombre !== $nombre) {
                $stmtSync = $pdo->prepare("UPDATE cargos SET departamento = ? WHERE departamento_id = ? OR departamento = ?");
                $stmtSync->execute([$nombre, $id, $oldNombre]);
            }

            echo json_encode([
                'status'  => 'success',
                'message' => "Departamento actualizado con éxito."
            ]);
            break;

        // =========================================================================
        // 5. CAMBIAR ESTATUS
        // =========================================================================
        case 'toggle_status':
            $id       = (int)($_POST['id'] ?? 0);
            $isActive = (int)($_POST['is_active'] ?? 1);

            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE departamentos SET is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$isActive, $id]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Estatus del departamento actualizado correctamente.'
            ]);
            break;

        // =========================================================================
        // 6. ELIMINAR DEPARTAMENTO
        // =========================================================================
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }

            // Verificar si tiene cargos asociados
            $stmtCargos = $pdo->prepare("SELECT COUNT(*) FROM cargos WHERE departamento_id = ?");
            $stmtCargos->execute([$id]);
            if ((int)$stmtCargos->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar el departamento porque tiene cargos vinculados. Reasigna o desactiva el departamento.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM departamentos WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Departamento eliminado correctamente.'
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no permitida.']);
            break;
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}
