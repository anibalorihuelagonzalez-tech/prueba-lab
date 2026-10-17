<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
// app/controllers/eliminar-despachos-controller.php (la ruta /eliminar-despacho-controller apunta aquí)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php'; // CORREGIDO: antes apuntaba a config/db.php (no existe)
$pdo = conexion();

try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID de envío inválido.']);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Desvincular los casos (cajas pequeñas) para que queden pendientes por despachar
    $stmtLiberar = $pdo->prepare("UPDATE ordenes_trabajo 
                                  SET envio_id = NULL, estatus_actual = 'LISTO PARA DESPACHO' 
                                  WHERE envio_id = :id");
    $stmtLiberar->execute([':id' => $id]);

    // 2. Eliminar registros hijos en historial_envios (por la FK ON DELETE NO ACTION)
    $stmtHist = $pdo->prepare("DELETE FROM historial_envios WHERE envio_id = :id");
    $stmtHist->execute([':id' => $id]);

    // 3. Eliminar la Caja Grande en envios
    $stmtDel = $pdo->prepare("DELETE FROM envios WHERE id = :id");
    $stmtDel->execute([':id' => $id]);

    $pdo->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'El envío fue anulado y los casos de pacientes volvieron a quedar disponibles para despacho.'
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}