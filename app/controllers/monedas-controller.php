<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
// app/controllers/monedas-controller.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Utilice POST.']);
    exit();
}

$inputData = json_decode(file_get_contents('php://input'), true) ?: [];
$action    = $_POST['action'] ?? $inputData['action'] ?? 'listar';
$id        = $_POST['id'] ?? $inputData['id'] ?? null;

try {
    $db = conexion();

    switch ($action) {

        // =========================================================================
        // 1. LISTAR TODAS LAS MONEDAS
        // =========================================================================
        case 'listar':
        case 'list':
            $stmt = $db->query("
                SELECT 
                    id, 
                    codigo, 
                    nombre, 
                    simbolo, 
                    decimales, 
                    es_base, 
                    is_active, 
                    DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS fecha_creacion_fmt,
                    DATE_FORMAT(updated_at, '%d/%m/%Y %H:%i') AS fecha_actualizacion_fmt
                FROM monedas 
                WHERE deleted_at IS NULL 
                ORDER BY es_base DESC, codigo ASC
            ");
            $monedas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular KPIs
            $kpis = [
                'total_monedas'      => count($monedas),
                'moneda_base'        => 'No definida',
                'monedas_activas'    => 0,
                'monedas_secundarias'=> 0
            ];

            foreach ($monedas as $m) {
                if ((int)$m['es_base'] === 1) {
                    $kpis['moneda_base'] = "{$m['codigo']} ({$m['simbolo']})";
                } else {
                    $kpis['monedas_secundarias']++;
                }

                if ((int)$m['is_active'] === 1) {
                    $kpis['monedas_activas']++;
                }
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $monedas,
                'kpis'   => $kpis
            ]);
            break;

        // =========================================================================
        // 2. OBTENER DETALLE DE 1 MONEDA (Para editar)
        // =========================================================================
        case 'show':
            if (empty($id)) {
                echo json_encode(['status' => 'error', 'message' => 'El ID de la moneda es requerido.']);
                exit();
            }

            $stmt = $db->prepare("SELECT * FROM monedas WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$id]);
            $moneda = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($moneda) {
                echo json_encode(['status' => 'success', 'moneda' => $moneda]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Moneda no encontrada.']);
            }
            break;

        // =========================================================================
        // 3. REGISTRAR NUEVA MONEDA
        // =========================================================================
        case 'store':
            $codigo    = strtoupper(trim($_POST['codigo'] ?? $inputData['codigo'] ?? ''));
            $nombre    = trim($_POST['nombre'] ?? $inputData['nombre'] ?? '');
            $simbolo   = trim($_POST['simbolo'] ?? $inputData['simbolo'] ?? '');
            $decimales = (int)($_POST['decimales'] ?? $inputData['decimales'] ?? 2);
            $es_base   = (int)($_POST['es_base'] ?? $inputData['es_base'] ?? 0);
            $is_active = (int)($_POST['is_active'] ?? $inputData['is_active'] ?? 1);

            if (empty($codigo) || empty($nombre) || empty($simbolo)) {
                echo json_encode(['status' => 'error', 'message' => 'Código, nombre y símbolo son obligatorios.']);
                exit();
            }

            if (strlen($codigo) !== 3) {
                echo json_encode(['status' => 'error', 'message' => 'El código de moneda debe tener exactamente 3 caracteres ISO (ej. USD, VES).']);
                exit();
            }

            // Validar código único
            $stmtCheck = $db->prepare("SELECT id FROM monedas WHERE codigo = ? AND deleted_at IS NULL LIMIT 1");
            $stmtCheck->execute([$codigo]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "El código de moneda {$codigo} ya se encuentra registrado."]);
                exit();
            }

            $db->beginTransaction();

            // Si se marca como base, desactivar base en las demás
            if ($es_base === 1) {
                $db->exec("UPDATE monedas SET es_base = 0");
            }

            $stmtIns = $db->prepare("
                INSERT INTO monedas (codigo, nombre, simbolo, decimales, es_base, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtIns->execute([$codigo, $nombre, $simbolo, $decimales, $es_base, $is_active]);
            $newId = $db->lastInsertId();

            $db->commit();

            echo json_encode([
                'status'  => 'success',
                'message' => "Moneda {$codigo} registrada exitosamente.",
                'id'      => $newId
            ]);
            break;

        // =========================================================================
        // 4. ACTUALIZAR MONEDA EXISTENTE
        // =========================================================================
        case 'update':
            $monedaId  = (int)($_POST['id'] ?? $inputData['id'] ?? 0);
            $codigo    = strtoupper(trim($_POST['codigo'] ?? $inputData['codigo'] ?? ''));
            $nombre    = trim($_POST['nombre'] ?? $inputData['nombre'] ?? '');
            $simbolo   = trim($_POST['simbolo'] ?? $inputData['simbolo'] ?? '');
            $decimales = (int)($_POST['decimales'] ?? $inputData['decimales'] ?? 2);
            $es_base   = (int)($_POST['es_base'] ?? $inputData['es_base'] ?? 0);
            $is_active = (int)($_POST['is_active'] ?? $inputData['is_active'] ?? 1);

            if ($monedaId <= 0 || empty($codigo) || empty($nombre) || empty($simbolo)) {
                echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
                exit();
            }

            if (strlen($codigo) !== 3) {
                echo json_encode(['status' => 'error', 'message' => 'El código debe tener exactamente 3 caracteres.']);
                exit();
            }

            // Validar código único que no pertenezca a otra moneda
            $stmtCheck = $db->prepare("SELECT id FROM monedas WHERE codigo = ? AND id != ? AND deleted_at IS NULL LIMIT 1");
            $stmtCheck->execute([$codigo, $monedaId]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "El código {$codigo} ya pertenece a otra moneda."]);
                exit();
            }

            $db->beginTransaction();

            if ($es_base === 1) {
                $db->exec("UPDATE monedas SET es_base = 0");
            }

            $stmtUp = $db->prepare("
                UPDATE monedas 
                SET codigo = ?, nombre = ?, simbolo = ?, decimales = ?, es_base = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmtUp->execute([$codigo, $nombre, $simbolo, $decimales, $es_base, $is_active, $monedaId]);

            $db->commit();

            echo json_encode([
                'status'  => 'success',
                'message' => "Moneda {$codigo} actualizada correctamente."
            ]);
            break;

        // =========================================================================
        // 5. ESTABLECER COMO MONEDA BASE
        // =========================================================================
        case 'set_base':
            $monedaId = (int)($_POST['id'] ?? $inputData['id'] ?? 0);
            if ($monedaId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de moneda inválido.']);
                exit();
            }

            $db->beginTransaction();
            $db->exec("UPDATE monedas SET es_base = 0");
            $stmt = $db->prepare("UPDATE monedas SET es_base = 1, is_active = 1 WHERE id = ?");
            $stmt->execute([$monedaId]);
            $db->commit();

            echo json_encode([
                'status'  => 'success',
                'message' => 'Moneda base del sistema actualizada exitosamente.'
            ]);
            break;

        // =========================================================================
        // 6. CAMBIAR ESTATUS (ACTIVAR / DESACTIVAR)
        // =========================================================================
        case 'toggle_status':
            $monedaId     = (int)($_POST['id'] ?? $inputData['id'] ?? 0);
            $nuevoEstatus = (int)($_POST['is_active'] ?? $inputData['is_active'] ?? 1);

            if ($monedaId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de moneda inválido.']);
                exit();
            }

            // No permitir desactivar la moneda base
            $checkBase = $db->prepare("SELECT es_base FROM monedas WHERE id = ?");
            $checkBase->execute([$monedaId]);
            $esBase = (int)$checkBase->fetchColumn();

            if ($esBase === 1 && $nuevoEstatus === 0) {
                echo json_encode(['status' => 'error', 'message' => 'No puedes desactivar la moneda base del sistema.']);
                exit();
            }

            $stmt = $db->prepare("UPDATE monedas SET is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nuevoEstatus, $monedaId]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Estatus de la moneda actualizado exitosamente.'
            ]);
            break;

        // =========================================================================
        // 7. ELIMINAR MONEDA (SOFT DELETE)
        // =========================================================================
        case 'delete':
            $monedaId = (int)($_POST['id'] ?? $inputData['id'] ?? 0);
            if ($monedaId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de moneda inválido.']);
                exit();
            }

            // Verificar si es base
            $check = $db->prepare("SELECT codigo, es_base FROM monedas WHERE id = ?");
            $check->execute([$monedaId]);
            $mon = $check->fetch(PDO::FETCH_ASSOC);

            if (!$mon) {
                echo json_encode(['status' => 'error', 'message' => 'Moneda no encontrada.']);
                exit();
            }

            if ((int)$mon['es_base'] === 1) {
                echo json_encode(['status' => 'error', 'message' => 'No puedes eliminar la moneda base del sistema.']);
                exit();
            }

            // Verificar si tiene tasas vinculadas
            $checkTasas = $db->prepare("SELECT COUNT(*) FROM tasas_cambio WHERE (moneda_origen_id = ? OR moneda_destino_id = ?) AND deleted_at IS NULL");
            $checkTasas->execute([$monedaId, $monedaId]);
            if ((int)$checkTasas->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar la moneda porque tiene tasas de cambio vinculadas. Puedes desactivarla en su lugar.']);
                exit();
            }

            $stmtDel = $db->prepare("UPDATE monedas SET deleted_at = NOW(), is_active = 0 WHERE id = ?");
            $stmtDel->execute([$monedaId]);

            echo json_encode([
                'status'  => 'success',
                'message' => "Moneda {$mon['codigo']} eliminada correctamente."
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "Acción '{$action}' no válida."]);
            break;
    }

} catch (\Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}
