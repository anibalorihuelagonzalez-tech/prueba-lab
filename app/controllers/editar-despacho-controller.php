<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
// controllers/editar-despacho-controller.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
$pdo = conexion();

function mapViewToEstatusBd(string $estatusVista): string {
    return match ($estatusVista) {
        'Pendiente' => 'PREPARADO',
        'En Ruta'   => 'TRANSITO',
        'Entregado' => 'RECIBIDO',
        default     => 'PREPARADO',
    };
}

function mapViewToHistorialId(string $estatusVista): int {
    return match ($estatusVista) {
        'Pendiente' => 1,
        'En Ruta'   => 3,
        'Entregado' => 4,
        default     => 1,
    };
}

$action = $_POST['action'] ?? 'editar_completo';

try {
    // Obtener usuario para auditoría en historial_envios
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    if (!$usuarioId) {
        $usuarioId = $pdo->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn();
    }

    // =========================================================================
    // CASO A: CAMBIO RÁPIDO DE ESTATUS DESDE EL MODAL DE MANIFIESTO
    // =========================================================================
    if ($action === 'cambiar_estatus') {
        $id           = (int)($_POST['id'] ?? 0);
        $nuevoEstatus = $_POST['estatus'] ?? 'Pendiente';

        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de envío no válido.']);
            exit;
        }

        $estatusBd = mapViewToEstatusBd($nuevoEstatus);
        $pdo->beginTransaction();

        // Actualizar envios (si se marca Entregado, actualizamos fecha_hora_recepcion)
        if ($estatusBd === 'RECIBIDO') {
            $stmt = $pdo->prepare("UPDATE envios SET estatus_envio = :est, fecha_hora_recepcion = NOW() WHERE id = :id");
        } elseif ($estatusBd === 'TRANSITO') {
            $stmt = $pdo->prepare("UPDATE envios SET estatus_envio = :est, fecha_hora_despacho = NOW() WHERE id = :id");
        } else {
            $stmt = $pdo->prepare("UPDATE envios SET estatus_envio = :est WHERE id = :id");
        }
        $stmt->execute([':est' => $estatusBd, ':id' => $id]);

        // Actualizar estatus de las órdenes contenidas
        $pdo->prepare("UPDATE ordenes_trabajo SET estatus_actual = :est_orden WHERE envio_id = :id")
            ->execute([':est_orden' => "DESPACHO: $nuevoEstatus", ':id' => $id]);

        // Guardar trazabilidad
        if ($usuarioId) {
            $pdo->prepare("INSERT INTO historial_envios (envio_id, usuario_id, historial_envios_estatus_id, notas) VALUES (?, ?, ?, ?)")
                ->execute([$id, $usuarioId, mapViewToHistorialId($nuevoEstatus), "Cambio rápido de estatus a: $nuevoEstatus"]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => "Estatus actualizado a $nuevoEstatus."]);
        exit;
    }

    // =========================================================================
    // CASO B: EDICIÓN COMPLETA DEL ENVÍO (FORMULARIO PRINCIPAL)
    // =========================================================================
    $envioId       = (int)($_POST['id'] ?? 0);
    $sedeId        = (int)($_POST['sede_id'] ?? 0);
    $repartidorId  = (int)($_POST['repartidor_id'] ?? 0);
    $fechaSalida   = $_POST['fecha_salida'] ?? date('Y-m-d');
    $estatusVista  = $_POST['estatus'] ?? 'Pendiente';
    $observaciones = trim($_POST['observaciones'] ?? '');
    $casosIds      = $_POST['casos_ids'] ?? [];

    if ($envioId <= 0 || $sedeId <= 0 || $repartidorId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Datos de envío incompletos.']);
        exit;
    }

    if (empty($casosIds) || !is_array($casosIds)) {
        echo json_encode(['status' => 'error', 'message' => 'El envío debe contener al menos un caso.']);
        exit;
    }

    $estatusBd     = mapViewToEstatusBd($estatusVista);
    $cantidadCasos = count($casosIds);
    $fechaProg     = $fechaSalida . ' ' . date('H:i:s');

    $pdo->beginTransaction();

    // 1. Actualizar cabecera en envios
    $sqlUpdate = "UPDATE envios 
                  SET sede_id = :sede_id,
                      repartidor_id = :repartidor_id,
                      estatus_envio = :estatus_envio,
                      cantidad_casos = :cantidad_casos,
                      fecha_hora_programada = :fecha_programada,
                      notas_cancelacion = :notas
                  WHERE id = :id";

    $pdo->prepare($sqlUpdate)->execute([
        ':sede_id'          => $sedeId,
        ':repartidor_id'    => $repartidorId,
        ':estatus_envio'    => $estatusBd,
        ':cantidad_casos'   => $cantidadCasos,
        ':fecha_programada' => $fechaProg,
        ':notas'            => $observaciones !== '' ? $observaciones : null,
        ':id'               => $envioId
    ]);

    // 2. Liberar las órdenes que estaban antes en este envío (volverlas a dejar pendientes)
    $pdo->prepare("UPDATE ordenes_trabajo SET envio_id = NULL, estatus_actual = 'LISTO PARA DESPACHO' WHERE envio_id = :envio_id")
        ->execute([':envio_id' => $envioId]);

    // 3. Asignar la nueva selección de órdenes al envío
    $idsLimpiados = array_map('intval', $casosIds);
    $placeholders = implode(',', array_fill(0, count($idsLimpiados), '?'));

    $sqlAsignar = "UPDATE ordenes_trabajo 
                   SET envio_id = ?, estatus_actual = ? 
                   WHERE id IN ($placeholders)";
    $paramsAsignar = array_merge([$envioId, "EN DESPACHO ($estatusVista)"], $idsLimpiados);
    $pdo->prepare($sqlAsignar)->execute($paramsAsignar);

    // 4. Registrar en historial_envios
    if ($usuarioId) {
        $pdo->prepare("INSERT INTO historial_envios (envio_id, usuario_id, historial_envios_estatus_id, notas) VALUES (?, ?, ?, ?)")
            ->execute([$envioId, $usuarioId, mapViewToHistorialId($estatusVista), "Envío editado ($cantidadCasos casos). " . $observaciones]);
    }

    $pdo->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Envío actualizado correctamente.'
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}