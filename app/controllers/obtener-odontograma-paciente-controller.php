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
    // OBTENER ODONTOGRAMA DE UN PACIENTE
    // =========================================================================
    if ($action === 'obtener_odontograma') {
        $paciente_id = $_POST['paciente_id'] ?? $inputData['paciente_id'] ?? null;

        if (empty($paciente_id)) {
            echo json_encode(['status' => 'error', 'message' => 'El ID del paciente es obligatorio.']);
            exit();
        }

        // Verificar que el paciente exista
        $stmtCheck = $db->prepare("SELECT id FROM pacientes WHERE id = ?");
        $stmtCheck->execute([$paciente_id]);
        if (!$stmtCheck->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El paciente especificado no existe.']);
            exit();
        }

        // Obtener todos los registros del odontograma de ese paciente
        $stmt = $db->prepare("
            SELECT diente_numero, estado_diente, observaciones, fecha_registro
            FROM pacientes_odontogramas 
            WHERE paciente_id = ?
            ORDER BY diente_numero ASC
        ");
        $stmt->execute([$paciente_id]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mapeo inverso: DB → Frontend
        $mapaInverso = [
            'SANO'      => 'sano',
            'CON CARIE' => 'caries',
            'A EXTRAER' => 'ausente',
            'AUSENTE'   => 'ausente'
        ];

        // Construir el JSON de dientes
        $datosDientes = [];
        $observaciones = '';

        foreach ($registros as $reg) {
            $estadoFrontend = $mapaInverso[$reg['estado_diente']] ?? 'sano';
            $datosDientes[$reg['diente_numero']] = $estadoFrontend;

            if (!empty($reg['observaciones'])) {
                $observaciones = $reg['observaciones'];
            }
        }

        // Si no hay registros guardados, devolver todos los dientes como sanos
        if (empty($datosDientes)) {
            $dientesFDI = [
                18,17,16,15,14,13,12,11,
                21,22,23,24,25,26,27,28,
                48,47,46,45,44,43,42,41,
                31,32,33,34,35,36,37,38
            ];
            foreach ($dientesFDI as $num) {
                $datosDientes[$num] = 'sano';
            }
        }

        echo json_encode([
            'status' => 'success',
            'odontograma' => [
                'paciente_id'     => $paciente_id,
                'datos_json'      => json_encode($datosDientes),
                'observaciones'   => $observaciones,
                'total_registros' => count($registros)
            ]
        ]);
        exit();
    }

    // =========================================================================
    // ACCIÓN NO VÁLIDA
    // =========================================================================
    else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Acción no válida. Use "obtener_odontograma".'
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