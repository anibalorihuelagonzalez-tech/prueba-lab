<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
// controllers/agregar-despacho-controller.php
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
    // Según los INSERT iniciales de historial_envios_estatus: 1=CREADO, 2=RECOLECTADO, 3=EN TRANSITO, 4=ENTREGADO
    return match ($estatusVista) {
        'Pendiente' => 1,
        'En Ruta'   => 3,
        'Entregado' => 4,
        default     => 1,
    };
}

try {
    $sedeId        = (int)($_POST['sede_id'] ?? 0);
    $repartidorId  = (int)($_POST['repartidor_id'] ?? 0);
    $fechaSalida   = $_POST['fecha_salida'] ?? date('Y-m-d');
    $estatusVista  = $_POST['estatus'] ?? 'Pendiente';
    $observaciones = trim($_POST['observaciones'] ?? '');
    $casosIds      = $_POST['casos_ids'] ?? [];

    if ($sedeId <= 0 || $repartidorId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar la sede de destino y el repartidor.']);
        exit;
    }

    if (empty($casosIds) || !is_array($casosIds)) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar al menos un caso (caja pequeña) para el envío.']);
        exit;
    }

    $estatusBd    = mapViewToEstatusBd($estatusVista);
    $cantidadCasos = count($casosIds);
    $codigoQrTemp = 'ENV-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
    $ahora        = date('Y-m-d H:i:s');
    $fechaProg    = $fechaSalida . ' ' . date('H:i:s');

    $pdo->beginTransaction();

    // 1. Insertar la Caja Grande en envios
    $sqlInsert = "INSERT INTO envios (
                    sede_id,
                    repartidor_id,
                    estatus_envio,
                    cantidad_casos,
                    codigo_qr,
                    direccion_movimiento,
                    fecha_hora_recepcion,
                    fecha_hora_despacho,
                    fecha_hora_creacion,
                    fecha_hora_programada,
                    notas_cancelacion
                  ) VALUES (
                    :sede_id,
                    :repartidor_id,
                    :estatus_envio,
                    :cantidad_casos,
                    :codigo_qr,
                    'SALIDA',
                    :fecha_recepcion,
                    :fecha_despacho,
                    :fecha_creacion,
                    :fecha_programada,
                    :notas
                  )";

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([
        ':sede_id'          => $sedeId,
        ':repartidor_id'    => $repartidorId,
        ':estatus_envio'    => $estatusBd,
        ':cantidad_casos'   => $cantidadCasos,
        ':codigo_qr'        => $codigoQrTemp,
        ':fecha_recepcion'  => $ahora,
        ':fecha_despacho'   => $fechaProg,
        ':fecha_creacion'   => $ahora,
        ':fecha_programada' => $fechaProg,
        ':notas'            => $observaciones !== '' ? $observaciones : null
    ]);

    $envioId = (int)$pdo->lastInsertId();

    // Actualizar el código QR con formato limpio #ENV-0001
    $codigoQrFinal = 'ENV-' . str_pad((string)$envioId, 4, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE envios SET codigo_qr = :qr WHERE id = :id")
        ->execute([':qr' => $codigoQrFinal, ':id' => $envioId]);

    // 2. Vincular las Cajas Pequeñas (ordenes_trabajo) al Envío
    $idsLimpiados = array_map('intval', $casosIds);
    $placeholders = implode(',', array_fill(0, count($idsLimpiados), '?'));

    $sqlUpdateOrdenes = "UPDATE ordenes_trabajo 
                         SET envio_id = ?, estatus_actual = ? 
                         WHERE id IN ($placeholders)";
    $paramsOrdenes = array_merge([$envioId, "EN DESPACHO ($estatusVista)"], $idsLimpiados);
    $pdo->prepare($sqlUpdateOrdenes)->execute($paramsOrdenes);

    // 3. Registrar en historial_envios (si existe un usuario en sesión o existe al menos 1 usuario en BD)
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    if (!$usuarioId) {
        $stmtUser = $pdo->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1");
        $usuarioId = $stmtUser->fetchColumn();
    }

    if ($usuarioId) {
        $stmtHist = $pdo->prepare("INSERT INTO historial_envios (envio_id, usuario_id, historial_envios_estatus_id, notas) 
                                   VALUES (:envio_id, :usuario_id, :estatus_id, :notas)");
        $stmtHist->execute([
            ':envio_id'   => $envioId,
            ':usuario_id' => $usuarioId,
            ':estatus_id' => mapViewToHistorialId($estatusVista),
            ':notas'      => $observaciones !== '' ? $observaciones : 'Envío creado y empacado.'
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => "Envío #$codigoQrFinal creado con $cantidadCasos caso(s) empacado(s)."
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}