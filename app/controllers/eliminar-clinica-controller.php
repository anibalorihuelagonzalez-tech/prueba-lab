<?php
/**
 * app/controllers/eliminar-clinica-controller.php
 * -------------------------------------------------------------------------
 * CORREGIDO: la versión anterior usaba la variable $pdo sin crearla (error fatal
 * "Call to a member function prepare() on null"), y además borraba en CASCADA
 * toda la información de la clínica (casos, pacientes, envíos...).
 *
 * Ahora solo se elimina una clínica que NO tenga casos, pacientes ni documentos
 * (facturas, cobros). Si tiene movimientos se recomienda desactivarla.
 */
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['status' => 'error', 'message' => 'Método no permitido.'], 405);
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    responderJson(['status' => 'error', 'message' => 'ID de clínica no proporcionado.']);
}

try {
    $pdo = conexion();

    // Tablas que "cuelgan" de la clínica. Si alguna tiene registros, se bloquea el borrado.
    $dependencias = [
        'ordenes_trabajo'            => 'casos del laboratorio',
        'pacientes'                  => 'pacientes',
        'documentos_administrativos' => 'documentos administrativos',
        'ventas_documentos'          => 'facturas / documentos de venta',
        'ventas_pagos'               => 'cobros',
    ];
    foreach ($dependencias as $tabla => $descripcion) {
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM {$tabla} WHERE clinica_id = ?");
            $st->execute([$id]);
            if ((int)$st->fetchColumn() > 0) {
                responderJson([
                    'status'  => 'error',
                    'message' => "No se puede eliminar: la clínica tiene {$descripcion} asociados. Puedes desactivarla en su lugar.",
                ]);
            }
        } catch (PDOException $e) {
            // La tabla puede no existir todavía (migración pendiente): se ignora esa comprobación.
            continue;
        }
    }

    // Sin movimientos: se elimina (sus sedes y teléfonos se borran en cascada, es lo esperado).
    $stmt = $pdo->prepare("DELETE FROM clinicas WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        responderJson(['status' => 'success', 'message' => 'Clínica y sus sedes eliminadas.']);
    }
    responderJson(['status' => 'error', 'message' => 'La clínica no existe o ya fue eliminada.']);

} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)]);
}
