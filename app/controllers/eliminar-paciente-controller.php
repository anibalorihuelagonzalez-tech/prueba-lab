<?php
/**
 * app/controllers/eliminar-paciente-controller.php
 * -------------------------------------------------------------------------
 * (ARCHIVO NUEVO) La vista de pacientes ya llamaba a este endpoint, pero el
 * archivo no existía y la ruta fallaba. Ahora:
 *   - Exige sesión (apiGuard).
 *   - NO deja borrar un paciente que tenga casos/órdenes de trabajo, porque en
 *     la base de datos esa FK es ON DELETE CASCADE y se perdería el historial de
 *     trazabilidad y de facturación del caso.
 *   - Si no tiene casos, elimina el paciente (su odontograma se borra en cascada,
 *     que es lo correcto porque le pertenece).
 */
require_once __DIR__ . '/../helpers/api-guard.php';
apiGuard();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['status' => 'error', 'message' => 'Método no permitido.'], 405);
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    responderJson(['status' => 'error', 'message' => 'ID de paciente no proporcionado.']);
}

try {
    $pdo = conexion();

    // ¿Tiene casos asociados? Si sí, no se puede borrar.
    $st = $pdo->prepare("SELECT COUNT(*) FROM ordenes_trabajo WHERE paciente_id = ?");
    $st->execute([$id]);
    if ((int)$st->fetchColumn() > 0) {
        responderJson([
            'status'  => 'error',
            'message' => 'No se puede eliminar: el paciente tiene casos registrados en el laboratorio.',
        ]);
    }

    $del = $pdo->prepare("DELETE FROM pacientes WHERE id = ?");
    $del->execute([$id]);

    if ($del->rowCount() > 0) {
        responderJson(['status' => 'success', 'message' => 'Paciente eliminado correctamente.']);
    }
    responderJson(['status' => 'error', 'message' => 'El paciente no existe o ya fue eliminado.']);

} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)]);
}
