<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
// controllers/obtener-despachos-controller.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$pdo = conexion();

$action = $_POST['action'] ?? '';

// Traductor de ENUM de BD a Etiquetas del Frontend
function mapEstatusToView(string $estatusBd): string {
    return match ($estatusBd) {
        'PREPARADO' => 'Pendiente',
        'TRANSITO'  => 'En Ruta',
        'RECIBIDO'  => 'Entregado',
        default     => 'Pendiente',
    };
}

try {
    switch ($action) {
        
        // 1. LISTAR TODOS LOS ENVÍOS PARA LA TABLA PRINCIPAL
        case 'listar_envios':
            $sql = "SELECT 
                        e.id,
                        e.codigo_qr AS codigo,
                        e.sede_id,
                        s.nombre AS sede_nombre,
                        s.clinica_id,
                        c.razon_social AS clinica_nombre,
                        e.repartidor_id,
                        TRIM(CONCAT(r.nombres, ' ', r.apellidos)) AS repartidor_nombre,
                        r.telefono AS repartidor_telefono,
                        e.cantidad_casos,
                        DATE(COALESCE(e.fecha_hora_programada, e.fecha_hora_despacho, e.fecha_hora_creacion)) AS fecha_salida,
                        e.estatus_envio,
                        e.notas_cancelacion AS observaciones,
                        (SELECT COUNT(*) FROM ordenes_trabajo ot WHERE ot.envio_id = e.id) AS total_real_casos
                    FROM envios e
                    INNER JOIN sedes s ON e.sede_id = s.id
                    INNER JOIN clinicas c ON s.clinica_id = c.id
                    INNER JOIN repartidores r ON e.repartidor_id = r.id
                    ORDER BY e.id DESC";

            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll();

            $data = array_map(function ($row) {
                $row['estatus'] = mapEstatusToView($row['estatus_envio']);
                // Usa el conteo real de órdenes vinculadas o el campo cantidad_casos como respaldo
                $row['total_cajas_pequenas'] = $row['total_real_casos'] > 0 ? (int)$row['total_real_casos'] : (int)$row['cantidad_casos'];
                return $row;
            }, $rows);

            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        // 2. OBTENER CASOS (CAJAS PEQUEÑAS) PENDIENTES POR SEDE
        case 'obtener_casos_por_sede':
            $sedeId  = (int)($_POST['sede_id'] ?? 0);
            $envioId = !empty($_POST['envio_id']) ? (int)$_POST['envio_id'] : null;

            if ($sedeId <= 0) {
                echo json_encode(['status' => 'error', 'casos' => [], 'message' => 'Sede inválida.']);
                exit;
            }

            // Obtenemos el clinica_id al que pertenece esta sede
            // (Ya que ordenes_trabajo se relaciona con clinicas en el esquema SQL)
            $stmtSede = $pdo->prepare("SELECT clinica_id FROM sedes WHERE id = :sede_id LIMIT 1");
            $stmtSede->execute([':sede_id' => $sedeId]);
            $sedeRow = $stmtSede->fetch();

            if (!$sedeRow) {
                echo json_encode(['status' => 'error', 'casos' => [], 'message' => 'La sede no existe.']);
                exit;
            }

            $clinicaId = (int)$sedeRow['clinica_id'];

            // Trae las órdenes de trabajo de esa clínica que NO tengan envío asignado (envio_id IS NULL)
            // O que pertenezcan al envío actual que se está editando
            $sqlCasos = "SELECT 
                            ot.id,
                            ot.codigo_qr AS codigo_orden,
                            ot.cantidad_piezas_exacta AS piezas_dentales,
                            ot.estatus_actual,
                            sv.nombre AS servicio_nombre,
                            TRIM(CONCAT(p.primer_nombre, ' ', COALESCE(p.segundo_nombre, ''), ' ', p.primer_apellido)) AS paciente_nombre,
                            CONCAT(p.tipo_documento, '-', p.numero_documento) AS paciente_documento,
                            CASE WHEN (:envio_id_check IS NOT NULL AND ot.envio_id = :envio_id_val) THEN 1 ELSE 0 END AS asignado_a_este_envio
                         FROM ordenes_trabajo ot
                         INNER JOIN servicios sv ON ot.servicio_id = sv.id
                         INNER JOIN pacientes p ON ot.paciente_id = p.id
                         WHERE ot.clinica_id = :clinica_id
                           AND (ot.envio_id IS NULL OR (:envio_id_filter IS NOT NULL AND ot.envio_id = :envio_id_filter_val))
                         ORDER BY asignado_a_este_envio DESC, ot.id DESC";

            $stmtCasos = $pdo->prepare($sqlCasos);
            $stmtCasos->execute([
                ':envio_id_check'      => $envioId,
                ':envio_id_val'        => $envioId,
                ':clinica_id'          => $clinicaId,
                ':envio_id_filter'     => $envioId,
                ':envio_id_filter_val' => $envioId
            ]);

            echo json_encode([
                'status' => 'success',
                'casos'  => $stmtCasos->fetchAll()
            ]);
            break;

        // 3. VER DETALLE / MANIFIESTO O CARGAR DATOS PARA EDITAR ENVÍO
        case 'show_envio':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de envío inválido.']);
                exit;
            }

            $sqlEnvio = "SELECT 
                            e.id,
                            e.sede_id,
                            s.clinica_id,
                            s.nombre AS sede_nombre,
                            s.direccion_fisica AS sede_direccion,
                            c.razon_social AS clinica_nombre,
                            e.repartidor_id,
                            TRIM(CONCAT(r.nombres, ' ', r.apellidos)) AS repartidor_nombre,
                            r.telefono AS repartidor_telefono,
                            DATE(COALESCE(e.fecha_hora_programada, e.fecha_hora_despacho, e.fecha_hora_creacion)) AS fecha_salida,
                            e.estatus_envio,
                            e.notas_cancelacion AS observaciones
                         FROM envios e
                         INNER JOIN sedes s ON e.sede_id = s.id
                         INNER JOIN clinicas c ON s.clinica_id = c.id
                         INNER JOIN repartidores r ON e.repartidor_id = r.id
                         WHERE e.id = :id
                         LIMIT 1";

            $stmt = $pdo->prepare($sqlEnvio);
            $stmt->execute([':id' => $id]);
            $envio = $stmt->fetch();

            if (!$envio) {
                echo json_encode(['status' => 'error', 'message' => 'Envío no encontrado.']);
                exit;
            }

            $envio['estatus'] = mapEstatusToView($envio['estatus_envio']);

            // Obtener las cajas pequeñas (ordenes_trabajo) empacadas dentro de este envío
            $sqlCajas = "SELECT 
                            ot.id,
                            ot.codigo_qr AS codigo_orden,
                            ot.cantidad_piezas_exacta AS piezas_dentales,
                            sv.nombre AS servicio_nombre,
                            TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente_nombre,
                            CONCAT(p.tipo_documento, '-', p.numero_documento) AS paciente_documento
                         FROM ordenes_trabajo ot
                         INNER JOIN servicios sv ON ot.servicio_id = sv.id
                         INNER JOIN pacientes p ON ot.paciente_id = p.id
                         WHERE ot.envio_id = :envio_id
                         ORDER BY ot.id ASC";

            $stmtCajas = $pdo->prepare($sqlCajas);
            $stmtCajas->execute([':envio_id' => $id]);
            $cajasPequenas = $stmtCajas->fetchAll();

            echo json_encode([
                'status'         => 'success',
                'envio'          => $envio,
                'cajas_pequenas' => $cajasPequenas
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida en obtener-despachos-controller.']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}