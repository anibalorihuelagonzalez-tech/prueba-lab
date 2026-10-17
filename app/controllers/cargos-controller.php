<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
// app/controllers/cargos-controller.php
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
        // 1. LISTAR CARGOS
        // =========================================================================
        case 'listar':
            $sql = "
                SELECT 
                    c.id,
                    c.nombre,
                    c.descripcion,
                    c.departamento,
                    c.departamento_id,
                    COALESCE(d.nombre, c.departamento, 'Sin Departamento') AS departamento_nombre,
                    c.is_active,
                    DATE_FORMAT(c.created_at, '%d/%m/%Y') AS fecha_creacion,
                    (SELECT COUNT(*) FROM empleados e WHERE e.cargo_id = c.id) AS total_empleados
                FROM cargos c
                LEFT JOIN departamentos d ON c.departamento_id = d.id
                ORDER BY c.id ASC
            ";
            $stmt = $pdo->query($sql);
            $cargos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $kpis = [
                'total_cargos'    => count($cargos),
                'activos'         => 0,
                'inactivos'       => 0,
                'total_empleados' => 0
            ];

            foreach ($cargos as $c) {
                if ((int)$c['is_active'] === 1) {
                    $kpis['activos']++;
                } else {
                    $kpis['inactivos']++;
                }
                $kpis['total_empleados'] += (int)$c['total_empleados'];
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $cargos,
                'kpis'   => $kpis
            ]);
            break;

        // =========================================================================
        // 2. OBTENER DETALLE DE UN CARGO
        // =========================================================================
        case 'show':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de cargo inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, nombre, descripcion, departamento, departamento_id, is_active FROM cargos WHERE id = ?");
            $stmt->execute([$id]);
            $cargo = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$cargo) {
                echo json_encode(['status' => 'error', 'message' => 'Cargo no encontrado.']);
                exit;
            }

            echo json_encode(['status' => 'success', 'data' => $cargo]);
            break;

        // =========================================================================
        // 3. OBTENER DEPARTAMENTOS ACTIVOS (PARA SELECT)
        // =========================================================================
        case 'obtener_departamentos':
            $stmt = $pdo->query("SELECT id, nombre FROM departamentos WHERE is_active = 1 ORDER BY nombre ASC");
            $deptos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data'   => $deptos
            ]);
            break;

        // =========================================================================
        // 4. REGISTRAR CARGO
        // =========================================================================
        case 'store':
            $nombre         = trim($_POST['nombre'] ?? '');
            $descripcion    = trim($_POST['descripcion'] ?? '');
            $departamentoId = !empty($_POST['departamento_id']) ? (int)$_POST['departamento_id'] : null;
            $isActive       = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if (empty($nombre)) {
                echo json_encode(['status' => 'error', 'message' => 'El nombre del cargo es obligatorio.']);
                exit;
            }

            // Validar duplicado
            $stmtCheck = $pdo->prepare("SELECT id FROM cargos WHERE LOWER(nombre) = LOWER(?)");
            $stmtCheck->execute([$nombre]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Ya existe un cargo con ese nombre.']);
                exit;
            }

            // Nombre del departamento
            $deptoNombre = 'General';
            if ($departamentoId) {
                $stmtD = $pdo->prepare("SELECT nombre FROM departamentos WHERE id = ?");
                $stmtD->execute([$departamentoId]);
                $deptoNombre = $stmtD->fetchColumn() ?: 'General';
            }

            $stmt = $pdo->prepare("INSERT INTO cargos (nombre, descripcion, departamento, departamento_id, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$nombre, $descripcion, $deptoNombre, $departamentoId, $isActive]);

            echo json_encode([
                'status'  => 'success',
                'message' => "Cargo '{$nombre}' registrado con éxito."
            ]);
            break;

        // =========================================================================
        // 5. ACTUALIZAR CARGO
        // =========================================================================
        case 'update':
            $id             = (int)($_POST['id'] ?? 0);
            $nombre         = trim($_POST['nombre'] ?? '');
            $descripcion    = trim($_POST['descripcion'] ?? '');
            $departamentoId = !empty($_POST['departamento_id']) ? (int)$_POST['departamento_id'] : null;
            $isActive       = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if ($id <= 0 || empty($nombre)) {
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para actualizar.']);
                exit;
            }

            // Validar duplicado en otro ID
            $stmtCheck = $pdo->prepare("SELECT id FROM cargos WHERE LOWER(nombre) = LOWER(?) AND id != ?");
            $stmtCheck->execute([$nombre, $id]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Ya existe otro cargo con ese nombre.']);
                exit;
            }

            // Nombre del departamento
            $deptoNombre = 'General';
            if ($departamentoId) {
                $stmtD = $pdo->prepare("SELECT nombre FROM departamentos WHERE id = ?");
                $stmtD->execute([$departamentoId]);
                $deptoNombre = $stmtD->fetchColumn() ?: 'General';
            }

            $stmt = $pdo->prepare("UPDATE cargos SET nombre = ?, descripcion = ?, departamento = ?, departamento_id = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $deptoNombre, $departamentoId, $isActive, $id]);

            echo json_encode([
                'status'  => 'success',
                'message' => "Cargo actualizado con éxito."
            ]);
            break;

        // =========================================================================
        // 6. CAMBIAR ESTATUS
        // =========================================================================
        case 'toggle_status':
            $id       = (int)($_POST['id'] ?? 0);
            $isActive = (int)($_POST['is_active'] ?? 1);

            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE cargos SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $id]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Estatus del cargo actualizado correctamente.'
            ]);
            break;

        // =========================================================================
        // 7. ELIMINAR CARGO
        // =========================================================================
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }

            // Verificar si hay empleados con este cargo
            $stmtEmp = $pdo->prepare("SELECT COUNT(*) FROM empleados WHERE cargo_id = ?");
            $stmtEmp->execute([$id]);
            if ((int)$stmtEmp->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar el cargo porque tiene trabajadores asignados. Reasigna a los empleados primero o desactiva el cargo.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM cargos WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Cargo eliminado correctamente.'
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
