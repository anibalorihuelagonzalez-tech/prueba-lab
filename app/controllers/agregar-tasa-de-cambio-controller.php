<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
if (session_status() === PHP_SESSION_NONE) {
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Utilice POST.']);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

$inputData = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_POST['action'] ?? $inputData['action'] ?? 'store';

try {
    $db = conexion();

    $id                  = !empty($_POST['id'] ?? ($inputData['id'] ?? null)) ? (int)($_POST['id'] ?? $inputData['id']) : null;
    $moneda_origen_id    = (int)($_POST['moneda_origen_id'] ?? ($inputData['moneda_origen_id'] ?? 0));
    $moneda_destino_id   = (int)($_POST['moneda_destino_id'] ?? ($inputData['moneda_destino_id'] ?? 0));
    $tasaRaw             = $_POST['tasa'] ?? ($inputData['tasa'] ?? null);
    $fuente              = trim($_POST['fuente'] ?? ($inputData['fuente'] ?? 'BCV'));
    $vigente_desde       = trim($_POST['vigente_desde'] ?? ($inputData['vigente_desde'] ?? ''));
    $vigente_hasta       = trim($_POST['vigente_hasta'] ?? ($inputData['vigente_hasta'] ?? ''));
    $observaciones       = trim($_POST['observaciones'] ?? ($inputData['observaciones'] ?? ''));
    $is_active           = isset($_POST['is_active']) ? (int)$_POST['is_active'] : (isset($inputData['is_active']) ? (int)$inputData['is_active'] : 1);
    $desactivar_anteriores = isset($_POST['desactivar_anteriores']) ? (int)$_POST['desactivar_anteriores'] : (isset($inputData['desactivar_anteriores']) ? (int)$inputData['desactivar_anteriores'] : 1);

    // Obtener usuario autenticado si existe
    $usuario_id = Auth::id() ?: ($_SESSION['usuario_id'] ?? null);

    // Normalizar tasa numérica
    $tasa = str_replace(',', '.', (string)$tasaRaw);
    $tasa = (float)$tasa;

    // Formatear fechas
    if (!empty($vigente_desde) && strlen($vigente_desde) === 10) {
        $vigente_desde .= ' 00:00:00';
    }
    if (!empty($vigente_hasta) && strlen($vigente_hasta) === 10) {
        $vigente_hasta .= ' 23:59:59';
    } else if (empty($vigente_hasta)) {
        $vigente_hasta = null;
    }

    // Validaciones básicas
    if ($moneda_origen_id <= 0 || $moneda_destino_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar la moneda de origen y la moneda de destino.']);
        exit();
    }

    if ($moneda_origen_id === $moneda_destino_id) {
        echo json_encode(['status' => 'error', 'message' => 'La moneda de origen y la moneda de destino no pueden ser iguales.']);
        exit();
    }

    if ($tasa <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'El valor de la tasa de cambio debe ser un número mayor a cero.']);
        exit();
    }

    if (empty($vigente_desde)) {
        echo json_encode(['status' => 'error', 'message' => 'La fecha de vigencia es obligatoria.']);
        exit();
    }

    // Verificar que ambas monedas existan en el catálogo
    $checkMonedas = $db->prepare("SELECT id FROM monedas WHERE id IN (?, ?) AND deleted_at IS NULL");
    $checkMonedas->execute([$moneda_origen_id, $moneda_destino_id]);
    if (count($checkMonedas->fetchAll()) < 2) {
        echo json_encode(['status' => 'error', 'message' => 'Una o ambas monedas seleccionadas no son válidas.']);
        exit();
    }

    // =========================================================================
    // CREAR NUEVA TASA
    // =========================================================================
    if ($action === 'store') {

        $db->beginTransaction();

        // Si la nueva tasa se registra activa y se solicita desactivar anteriores del mismo par
        if ($is_active == 1 && $desactivar_anteriores == 1) {
            $stmtDeactivate = $db->prepare("
                UPDATE tasas_cambio 
                SET is_active = 0, updated_at = NOW() 
                WHERE moneda_origen_id = ? AND moneda_destino_id = ? AND is_active = 1 AND deleted_at IS NULL
            ");
            $stmtDeactivate->execute([$moneda_origen_id, $moneda_destino_id]);
        }

        $stmtInsert = $db->prepare("
            INSERT INTO tasas_cambio (
                moneda_origen_id, 
                moneda_destino_id, 
                usuario_id, 
                tasa, 
                fuente, 
                vigente_desde, 
                vigente_hasta, 
                observaciones, 
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([
            $moneda_origen_id,
            $moneda_destino_id,
            $usuario_id,
            $tasa,
            $fuente,
            $vigente_desde,
            $vigente_hasta,
            $observaciones ?: null,
            $is_active
        ]);

        $newId = $db->lastInsertId();
        $db->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Tasa de cambio registrada exitosamente.',
            'id' => $newId
        ]);
        exit();

    // =========================================================================
    // ACTUALIZAR TASA EXISTENTE
    // =========================================================================
    } elseif ($action === 'update') {
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'El ID de la tasa es requerido para actualizar.']);
            exit();
        }

        // Verificar que la tasa exista
        $stmtCheck = $db->prepare("SELECT id FROM tasas_cambio WHERE id = ? AND deleted_at IS NULL");
        $stmtCheck->execute([$id]);
        if (!$stmtCheck->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'La tasa de cambio especificada no existe.']);
            exit();
        }

        $db->beginTransaction();

        // Si se activa esta tasa y se solicita desactivar otras del mismo par
        if ($is_active == 1 && $desactivar_anteriores == 1) {
            $stmtDeactivate = $db->prepare("
                UPDATE tasas_cambio 
                SET is_active = 0, updated_at = NOW() 
                WHERE moneda_origen_id = ? AND moneda_destino_id = ? AND is_active = 1 AND id != ? AND deleted_at IS NULL
            ");
            $stmtDeactivate->execute([$moneda_origen_id, $moneda_destino_id, $id]);
        }

        $stmtUpdate = $db->prepare("
            UPDATE tasas_cambio 
            SET moneda_origen_id  = ?,
                moneda_destino_id = ?,
                usuario_id        = COALESCE(?, usuario_id),
                tasa              = ?,
                fuente            = ?,
                vigente_desde     = ?,
                vigente_hasta     = ?,
                observaciones     = ?,
                is_active         = ?,
                updated_at        = NOW()
            WHERE id = ?
        ");
        $stmtUpdate->execute([
            $moneda_origen_id,
            $moneda_destino_id,
            $usuario_id,
            $tasa,
            $fuente,
            $vigente_desde,
            $vigente_hasta,
            $observaciones ?: null,
            $is_active,
            $id
        ]);

        $db->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Tasa de cambio actualizada correctamente.'
        ]);
        exit();

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        exit();
    }

} catch (\PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
} catch (\Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
}