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
    // MÉTODO 1: LISTAR TODOS LOS PACIENTES
    // =========================================================================
    if ($action === 'listar_pacientes') {
        $stmt = $db->prepare("
            SELECT 
                p.id, 
                p.primer_nombre, 
                p.segundo_nombre, 
                p.primer_apellido, 
                p.segundo_apellido, 
                p.tipo_documento, 
                p.numero_documento, 
                p.sexo,
                p.clinica_id,
                p.sede_id,
                c.razon_social AS clinica_nombre,
                s.nombre AS sede_nombre
            FROM pacientes p
            LEFT JOIN clinicas c ON p.clinica_id = c.id
            LEFT JOIN sedes s ON p.sede_id = s.id
            ORDER BY p.primer_apellido ASC, p.primer_nombre ASC
        ");
        $stmt->execute();
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data'   => $pacientes
        ]);
        exit();
    }

    // =========================================================================
    // MÉTODO 2: OBTENER UN PACIENTE ESPECÍFICO POR ID
    // =========================================================================
    elseif ($action === 'show_paciente') {
        $id = $_POST['id'] ?? $inputData['id'] ?? null;

        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'El ID del paciente es obligatorio.']);
            exit();
        }

        $stmt = $db->prepare("
            SELECT 
                p.id, 
                p.primer_nombre, 
                p.segundo_nombre, 
                p.primer_apellido, 
                p.segundo_apellido, 
                p.tipo_documento, 
                p.numero_documento, 
                p.sexo,
                p.clinica_id,
                p.sede_id,
                c.razon_social AS clinica_nombre,
                s.nombre AS sede_nombre
            FROM pacientes p
            LEFT JOIN clinicas c ON p.clinica_id = c.id
            LEFT JOIN sedes s ON p.sede_id = s.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($paciente) {
            echo json_encode([
                'status'   => 'success',
                'paciente' => $paciente
            ]);
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'No se encontró el paciente especificado.'
            ]);
        }
        exit();
    }

    // =========================================================================
    // ACCIÓN NO VÁLIDA
    // =========================================================================
    else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Acción no válida. Use "listar_pacientes" o "show_paciente".'
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