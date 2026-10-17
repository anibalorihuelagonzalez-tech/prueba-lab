<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
// controllers/repartidores-controller.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$pdo = conexion();

$action = $_POST['action'] ?? '';

// Verifica si la tabla repartidores ya tiene las columnas extra (tipo_documento, numero_documento, estatus)
function tieneColumnasExtendidas(PDO $pdo): bool {
    static $extendida = null;
    if ($extendida === null) {
        $stmt = $pdo->query("SHOW COLUMNS FROM repartidores LIKE 'numero_documento'");
        $extendida = (bool)$stmt->fetch();
    }
    return $extendida;
}

// Divide "Carlos Alberto Mendoza Pérez" en nombres="Carlos Alberto" y apellidos="Mendoza Pérez"
function dividirNombreCompleto(string $nombreCompleto): array {
    $partes = preg_split('/\s+/', trim($nombreCompleto));
    if (count($partes) === 1) {
        return [$partes[0], ''];
    }
    if (count($partes) === 2) {
        return [$partes[0], $partes[1]];
    }
    $mitad = (int)ceil(count($partes) / 2);
    $nombres = implode(' ', array_slice($partes, 0, $mitad));
    $apellidos = implode(' ', array_slice($partes, $mitad));
    return [$nombres, $apellidos];
}

try {
    $esExtendida = tieneColumnasExtendidas($pdo);

    switch ($action) {

        case 'listar_repartidores':
        case 'listar_repartidores_activos':
            if ($esExtendida) {
                $where = ($action === 'listar_repartidores_activos') ? "WHERE estatus = 1" : "";
                $sql = "SELECT 
                            id,
                            TRIM(CONCAT(nombres, ' ', apellidos)) AS nombre_completo,
                            tipo_documento,
                            numero_documento,
                            telefono,
                            placa_vehiculo AS vehiculo,
                            estatus
                        FROM repartidores
                        $where
                        ORDER BY id DESC";
            } else {
                // Modo compatible con la tabla original sin ALTER TABLE
                $sql = "SELECT 
                            id,
                            TRIM(CONCAT(nombres, ' ', apellidos)) AS nombre_completo,
                            'V' AS tipo_documento,
                            'S/D' AS numero_documento,
                            telefono,
                            placa_vehiculo AS vehiculo,
                            1 AS estatus
                        FROM repartidores
                        ORDER BY id DESC";
            }

            $stmt = $pdo->query($sql);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'store_repartidor':
            $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
            $tipoDoc        = $_POST['tipo_documento'] ?? 'V';
            $numDoc         = trim($_POST['numero_documento'] ?? '');
            $telefono       = trim($_POST['telefono'] ?? '');
            $vehiculo       = trim($_POST['vehiculo'] ?? 'Por asignar');
            $estatus        = (int)($_POST['estatus'] ?? 1);

            if ($nombreCompleto === '' || $telefono === '') {
                echo json_encode(['status' => 'error', 'message' => 'El nombre y el teléfono son obligatorios.']);
                exit;
            }

            [$nombres, $apellidos] = dividirNombreCompleto($nombreCompleto);

            if ($esExtendida) {
                $sql = "INSERT INTO repartidores (nombres, apellidos, tipo_documento, numero_documento, telefono, placa_vehiculo, estatus)
                        VALUES (:nombres, :apellidos, :tipo_doc, :num_doc, :telefono, :vehiculo, :estatus)";
                $params = [
                    ':nombres'   => $nombres,
                    ':apellidos' => $apellidos,
                    ':tipo_doc'  => $tipoDoc,
                    ':num_doc'   => $numDoc,
                    ':telefono'  => $telefono,
                    ':vehiculo'  => $vehiculo !== '' ? $vehiculo : 'Motorizado',
                    ':estatus'   => $estatus
                ];
            } else {
                $sql = "INSERT INTO repartidores (nombres, apellidos, telefono, placa_vehiculo)
                        VALUES (:nombres, :apellidos, :telefono, :vehiculo)";
                $params = [
                    ':nombres'   => $nombres,
                    ':apellidos' => $apellidos,
                    ':telefono'  => $telefono,
                    ':vehiculo'  => $vehiculo !== '' ? $vehiculo : 'Motorizado'
                ];
            }

            $pdo->prepare($sql)->execute($params);
            echo json_encode(['status' => 'success', 'message' => 'Repartidor registrado exitosamente.']);
            break;

        case 'update_repartidor':
            $id             = (int)($_POST['id'] ?? 0);
            $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
            $tipoDoc        = $_POST['tipo_documento'] ?? 'V';
            $numDoc         = trim($_POST['numero_documento'] ?? '');
            $telefono       = trim($_POST['telefono'] ?? '');
            $vehiculo       = trim($_POST['vehiculo'] ?? '');
            $estatus        = (int)($_POST['estatus'] ?? 1);

            if ($id <= 0 || $nombreCompleto === '') {
                echo json_encode(['status' => 'error', 'message' => 'Datos de repartidor inválidos.']);
                exit;
            }

            [$nombres, $apellidos] = dividirNombreCompleto($nombreCompleto);

            if ($esExtendida) {
                $sql = "UPDATE repartidores 
                        SET nombres = :nombres,
                            apellidos = :apellidos,
                            tipo_documento = :tipo_doc,
                            numero_documento = :num_doc,
                            telefono = :telefono,
                            placa_vehiculo = :vehiculo,
                            estatus = :estatus
                        WHERE id = :id";
                $params = [
                    ':nombres'   => $nombres,
                    ':apellidos' => $apellidos,
                    ':tipo_doc'  => $tipoDoc,
                    ':num_doc'   => $numDoc,
                    ':telefono'  => $telefono,
                    ':vehiculo'  => $vehiculo,
                    ':estatus'   => $estatus,
                    ':id'        => $id
                ];
            } else {
                $sql = "UPDATE repartidores 
                        SET nombres = :nombres,
                            apellidos = :apellidos,
                            telefono = :telefono,
                            placa_vehiculo = :vehiculo
                        WHERE id = :id";
                $params = [
                    ':nombres'   => $nombres,
                    ':apellidos' => $apellidos,
                    ':telefono'  => $telefono,
                    ':vehiculo'  => $vehiculo,
                    ':id'        => $id
                ];
            }

            $pdo->prepare($sql)->execute($params);
            echo json_encode(['status' => 'success', 'message' => 'Datos del repartidor actualizados.']);
            break;

        case 'delete_repartidor':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }

            // Verificar si tiene envíos asociados antes de eliminar para evitar error de Foreign Key
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM envios WHERE repartidor_id = :id");
            $stmtCheck->execute([':id' => $id]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'No se puede eliminar este repartidor porque ya tiene envíos históricos asignados. Cámbialo a estatus Inactivo.'
                ]);
                exit;
            }

            $pdo->prepare("DELETE FROM repartidores WHERE id = :id")->execute([':id' => $id]);
            echo json_encode(['status' => 'success', 'message' => 'Repartidor eliminado del directorio.']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción de repartidores no válida.']);
            break;
    }

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}