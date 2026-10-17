<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$inputData = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_POST['action'] ?? $inputData['action'] ?? '';

try {
    $db = conexion();

    // =========================================================================
    // GUARDAR / ACTUALIZAR ODONTOGRAMA
    // =========================================================================
    if ($action === 'guardar_odontograma') {
        $paciente_id   = $_POST['paciente_id'] ?? $inputData['paciente_id'] ?? null;
        $datos_json    = $_POST['datos_json'] ?? $inputData['datos_json'] ?? null;
        $observaciones = trim($_POST['observaciones'] ?? $inputData['observaciones'] ?? '');

        // =====================================================================
        // VALIDACIONES
        // =====================================================================
        if (empty($paciente_id)) {
            echo json_encode(['status' => 'error', 'message' => 'El ID del paciente es obligatorio.']);
            exit();
        }

        if (empty($datos_json)) {
            echo json_encode(['status' => 'error', 'message' => 'Los datos del odontograma son obligatorios.']);
            exit();
        }

        // Verificar que el paciente exista
        $stmtCheck = $db->prepare("SELECT id FROM pacientes WHERE id = ?");
        $stmtCheck->execute([$paciente_id]);
        if (!$stmtCheck->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El paciente especificado no existe.']);
            exit();
        }

        // Decodificar el JSON de los dientes
        $datosDientes = json_decode($datos_json, true);
        if (!is_array($datosDientes)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato de los datos del odontograma es inválido.']);
            exit();
        }

        // Mapeo de estados: Frontend → DB
        $mapaEstados = [
            'sano'       => 'SANO',
            'caries'     => 'CON CARIE',
            'restaurado' => 'SANO',
            'protesis'   => 'SANO',
            'ausente'    => 'AUSENTE'
        ];

        // =====================================================================
        // INICIAR TRANSACCIÓN
        // =====================================================================
        $db->beginTransaction();

        try {
            // 1. Eliminar los registros previos del odontograma de este paciente
            $stmtDelete = $db->prepare("DELETE FROM pacientes_odontogramas WHERE paciente_id = ?");
            $stmtDelete->execute([$paciente_id]);

            // 2. Insertar los nuevos registros
            $stmtInsert = $db->prepare("
                INSERT INTO pacientes_odontogramas 
                    (paciente_id, diente_numero, estado_diente, observaciones) 
                VALUES (?, ?, ?, ?)
            ");

            $totalGuardados = 0;

            foreach ($datosDientes as $dienteNum => $estadoFrontend) {
                // Validar que el número de diente sea válido (FDI: 11-48)
                $dienteNum = (int)$dienteNum;
                if ($dienteNum < 11 || $dienteNum > 48) {
                    continue;
                }

                // Convertir estado al ENUM de la DB
                $estadoDB = $mapaEstados[$estadoFrontend] ?? 'SANO';

                // Guardar solo dientes que NO estén sanos (para ahorrar espacio)
                // Si quieres guardar TODOS los dientes (incluyendo sanos), comenta esta línea:
                if ($estadoDB === 'SANO') continue;

                $stmtInsert->execute([
                    $paciente_id,
                    $dienteNum,
                    $estadoDB,
                    $observaciones ?: null
                ]);

                $totalGuardados++;
            }

            // Confirmar la transacción
            $db->commit();

            echo json_encode([
                'status'         => 'success',
                'message'        => 'Odontograma guardado correctamente.',
                'total_dientes'  => $totalGuardados
            ]);
            exit();

        } catch (\Exception $e) {
            // Revertir en caso de error
            $db->rollBack();
            throw $e;
        }
    }

    // =========================================================================
    // ACCIÓN NO VÁLIDA
    // =========================================================================
    else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Acción no válida. Use "guardar_odontograma".'
        ]);
        exit();
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => errorSeguro($e)
    ]);
    exit();
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => errorSeguro($e)
    ]);
    exit();
}