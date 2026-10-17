<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
// app/controllers/recepcion-controller.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit;
}

$pdo = conexion();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // =========================================================================
        // 1. LISTAR RECEPCIONES (CASOS Y ENVÍOS)
        // =========================================================================
        case 'listar_recepciones':
            $filtroTipo = trim($_POST['tipo_recepcion'] ?? $_GET['tipo_recepcion'] ?? ''); // 'LABORATORIO', 'CLINICA' o vacío

            $sql = "
                SELECT 
                    ot.id,
                    ot.codigo_qr,
                    ot.clinica_id,
                    c.razon_social AS clinica_nombre,
                    COALESCE(s.nombre, 'Sede Principal') AS sede_nombre,
                    ot.servicio_id,
                    COALESCE(NULLIF(sv.nombre, ''), cs.nombre, 'Prótesis Dental') AS servicio_nombre,
                    ot.cantidad_piezas_exacta,
                    ot.estatus_actual,
                    DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y %H:%i') AS fecha_ingreso_fmt,
                    DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y') AS fecha_corta,
                    TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente_nombre,
                    ot.envio_id,
                    e.codigo_qr AS envio_codigo_qr,
                    e.direccion_movimiento,
                    e.estatus_envio,
                    DATE_FORMAT(e.fecha_hora_recepcion, '%d/%m/%Y %H:%i') AS fecha_recepcion_fmt
                FROM ordenes_trabajo ot
                INNER JOIN clinicas c ON ot.clinica_id = c.id
                LEFT JOIN sedes s ON ot.sede_id = s.id
                LEFT JOIN servicios sv ON ot.servicio_id = sv.id
                LEFT JOIN categorias_servicios cs ON sv.categoria_id = cs.id
                LEFT JOIN pacientes p ON ot.paciente_id = p.id
                LEFT JOIN envios e ON ot.envio_id = e.id
                ORDER BY ot.id DESC
            ";

            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // KPIs
            $kpis = [
                'total_casos'         => count($rows),
                'recibidos_lab'       => 0,
                'recibidos_clinica'   => 0,
                'pendientes'          => 0,
                'recibidos_hoy'       => 0
            ];

            $hoy = date('Y-m-d');
            $filtrados = [];

            foreach ($rows as $r) {
                $esRecibidoLab = stripos($r['estatus_actual'], 'Recibido en Laboratorio') !== false;
                $esRecibidoClinica = stripos($r['estatus_actual'], 'Recibido en Clínica') !== false || stripos($r['estatus_actual'], 'Entregado') !== false;

                if ($esRecibidoLab) {
                    $kpis['recibidos_lab']++;
                }
                if ($esRecibidoClinica) {
                    $kpis['recibidos_clinica']++;
                }
                if (!$esRecibidoLab && !$esRecibidoClinica) {
                    $kpis['pendientes']++;
                }

                // Filtrar según pestaña si aplica
                if ($filtroTipo === 'LABORATORIO') {
                    // Muestra los que entran al laboratorio
                    if ($r['direccion_movimiento'] === 'ENTRADA' || empty($r['direccion_movimiento'])) {
                        $filtrados[] = $r;
                    }
                } elseif ($filtroTipo === 'CLINICA') {
                    // Muestra los que se entregan a clínicas
                    if ($r['direccion_movimiento'] === 'SALIDA' || !empty($r['envio_id'])) {
                        $filtrados[] = $r;
                    }
                } else {
                    $filtrados[] = $r;
                }
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $filtrados,
                'kpis'   => $kpis
            ]);
            break;

        // =========================================================================
        // 2. BUSCAR CASO O ENVÍO POR CÓDIGO QR / TRACKING
        // =========================================================================
        case 'buscar_por_qr':
            $codigo = strtoupper(trim($_POST['codigo_qr'] ?? ''));

            if (empty($codigo)) {
                echo json_encode(['status' => 'error', 'message' => 'Código de búsqueda vacío.']);
                exit;
            }

            // Buscar en ordenes de trabajo o envios
            $stmt = $pdo->prepare("
                SELECT 
                    ot.id,
                    ot.codigo_qr,
                    ot.clinica_id,
                    c.razon_social AS clinica_nombre,
                    COALESCE(s.nombre, 'Sede Principal') AS sede_nombre,
                    COALESCE(NULLIF(sv.nombre, ''), 'Prótesis Dental') AS servicio_nombre,
                    ot.cantidad_piezas_exacta,
                    ot.estatus_actual,
                    DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y %H:%i') AS fecha_ingreso_fmt,
                    TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente_nombre,
                    ot.envio_id,
                    e.codigo_qr AS envio_codigo_qr,
                    e.estatus_envio
                FROM ordenes_trabajo ot
                INNER JOIN clinicas c ON ot.clinica_id = c.id
                LEFT JOIN sedes s ON ot.sede_id = s.id
                LEFT JOIN servicios sv ON ot.servicio_id = sv.id
                LEFT JOIN pacientes p ON ot.paciente_id = p.id
                LEFT JOIN envios e ON ot.envio_id = e.id
                WHERE UPPER(ot.codigo_qr) = ? OR UPPER(e.codigo_qr) = ?
                LIMIT 1
            ");
            $stmt->execute([$codigo, $codigo]);
            $caso = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$caso) {
                echo json_encode(['status' => 'error', 'message' => "No se encontró ningún caso con el código '{$codigo}'."]);
                exit;
            }

            echo json_encode(['status' => 'success', 'data' => $caso]);
            break;

        // =========================================================================
        // 3. REGISTRAR RECEPCIÓN (LABORATORIO O CLÍNICA)
        // =========================================================================
        case 'registrar_recepcion':
            $casoId        = (int)($_POST['caso_id'] ?? 0);
            $tipoRecepcion = strtoupper(trim($_POST['tipo_recepcion'] ?? 'LABORATORIO')); // 'LABORATORIO' o 'CLINICA'
            $nombreReceptor = trim($_POST['nombre_receptor'] ?? Auth::name() ?? 'Recepcionista');
            $observaciones = trim($_POST['observaciones'] ?? '');
            $usuarioId     = Auth::id() ?: 1;

            if ($casoId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de caso inválido.']);
                exit;
            }

            // Consultar datos del caso
            $stmtCaso = $pdo->prepare("SELECT ot.*, c.razon_social AS clinica_nombre FROM ordenes_trabajo ot INNER JOIN clinicas c ON ot.clinica_id = c.id WHERE ot.id = ?");
            $stmtCaso->execute([$casoId]);
            $caso = $stmtCaso->fetch(\PDO::FETCH_ASSOC);

            if (!$caso) {
                echo json_encode(['status' => 'error', 'message' => 'Caso no encontrado.']);
                exit;
            }

            $nuevoEstatus = '';
            $ubicacion = '';

            if ($tipoRecepcion === 'CLINICA') {
                $nuevoEstatus = 'RECEPCIÓN: Recibido en Clínica';
                $ubicacion = $caso['clinica_nombre'];
                $notaFmt = "Recepcionado en clínica por: {$nombreReceptor}. " . ($observaciones ? "Notas: {$observaciones}" : '');
            } else {
                $nuevoEstatus = 'RECEPCIÓN: Recibido en Laboratorio';
                $ubicacion = 'Laboratorio Central CNSB';
                $notaFmt = "Recepcionado en laboratorio por: {$nombreReceptor}. " . ($observaciones ? "Notas: {$observaciones}" : '');
            }

            // Actualizar caso
            $stmtUpd = $pdo->prepare("UPDATE ordenes_trabajo SET estatus_actual = ? WHERE id = ?");
            $stmtUpd->execute([$nuevoEstatus, $casoId]);

            // Actualizar envío si existe
            if (!empty($caso['envio_id'])) {
                $stmtEnvio = $pdo->prepare("UPDATE envios SET estatus_envio = 'RECIBIDO', fecha_hora_recepcion = NOW() WHERE id = ?");
                $stmtEnvio->execute([$caso['envio_id']]);
            }

            // Registrar en trazabilidad
            $stmtTraz = $pdo->prepare("
                INSERT INTO historial_trazabilidad (orden_trabajo_id, usuario_id, estatus, ubicacion, notas, fecha_cambio)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmtTraz->execute([
                $casoId,
                $usuarioId,
                $nuevoEstatus,
                $ubicacion,
                substr($notaFmt, 0, 255)
            ]);

            echo json_encode([
                'status'  => 'success',
                'message' => "Recepción confirmada con éxito para el caso {$caso['codigo_qr']}.",
                'nuevo_estatus' => $nuevoEstatus
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no permitida.']);
            break;
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}
