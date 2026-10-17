<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = conexion();

    $action = $_POST['action'] ?? $_GET['action'] ?? 'listar_casos';

    // =========================================================================
    // 1. LISTAR TODOS LOS CASOS PARA LA TABLA TABULATOR
    // =========================================================================
    if ($action === 'listar_casos') {
        $stmt = $db->prepare("
            SELECT 
                ot.id,
                ot.codigo_qr,
                ot.clinica_id,
                c.razon_social AS clinica_nombre,
                ot.sede_id,
                COALESCE(s.nombre, 'Sin sede asignada') AS sede_nombre,
                ot.paciente_id,
                TRIM(CONCAT(
                    p.primer_nombre, ' ', 
                    COALESCE(p.segundo_nombre, ''), ' ', 
                    p.primer_apellido, ' ', 
                    COALESCE(p.segundo_apellido, '')
                )) AS paciente_nombre,
                CONCAT(p.tipo_documento, '-', p.numero_documento) AS paciente_documento,
                ot.servicio_id,
                sv.nombre AS servicio_nombre,
                ot.cantidad_piezas_exacta,
                COALESCE(ot.cantidad_protesis, 1) AS cantidad_protesis,
                ot.protesista_id,
                TRIM(CONCAT(prot.primer_nombre, ' ', prot.primer_apellido)) AS protesista_nombre,
                ot.estatus_actual,
                DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y %H:%i') AS fecha_ingreso_fmt,
                od.id AS orden_detalle_id,
                COALESCE(od.etapa_produccion_id, 1) AS etapa_produccion_id,
                COALESCE(ep.nombre, 'TOMA DE IMPRESION') AS etapa_nombre,
                COALESCE(ep.orden, 1) AS etapa_orden,
                ht.usuario_id AS responsable_id,
                TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS responsable_nombre,
                r.rol AS responsable_rol,
                COALESCE(ht.ubicacion, 'RECEPCIÓN LABORATORIO') AS ubicacion_fisica,
                ht.notas AS ultima_nota,
                DATE_FORMAT(ht.fecha_cambio, '%d/%m/%Y %H:%i') AS ultimo_movimiento_fmt,
                env.codigo_qr AS envio_codigo,
                env.estatus_envio AS envio_estatus
            FROM ordenes_trabajo ot
            INNER JOIN clinicas c ON ot.clinica_id = c.id
            LEFT JOIN sedes s ON ot.sede_id = s.id
            INNER JOIN pacientes p ON ot.paciente_id = p.id
            INNER JOIN servicios sv ON ot.servicio_id = sv.id
            LEFT JOIN usuarios prot ON ot.protesista_id = prot.id
            LEFT JOIN ordenes_detalle od ON od.id = (
                SELECT MAX(od2.id) 
                FROM ordenes_detalle od2 
                WHERE od2.orden_trabajo_id = ot.id
            )
            LEFT JOIN etapas_produccion ep ON od.etapa_produccion_id = ep.id
            LEFT JOIN historial_trazabilidad ht ON ht.id = (
                SELECT MAX(ht2.id) 
                FROM historial_trazabilidad ht2 
                WHERE ht2.orden_trabajo_id = ot.id
            )
            LEFT JOIN usuarios u ON ht.usuario_id = u.id
            LEFT JOIN roles_usuarios r ON u.rol_id = r.id
            LEFT JOIN envios env ON ot.envio_id = env.id
            ORDER BY ot.id DESC
        ");

        $stmt->execute();
        $casos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($casos as &$fila) {
            $fila['paciente_nombre'] = preg_replace('/\s+/', ' ', $fila['paciente_nombre']);
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $casos
        ]);
        exit();
    }

    // =========================================================================
    // 2. CARGAR CATÁLOGOS PARA LOS SELECTORES DEL FORMULARIO
    // =========================================================================
    if ($action === 'cargar_catalogos') {
        $stmtClinicas = $db->prepare("
            SELECT id, razon_social, tipo_documento, numero_documento 
            FROM clinicas 
            WHERE estatus = 1 OR estatus IS NULL 
            ORDER BY razon_social ASC
        ");
        $stmtClinicas->execute();
        $clinicas = $stmtClinicas->fetchAll(\PDO::FETCH_ASSOC);

        $stmtSedes = $db->prepare("
            SELECT id, clinica_id, nombre, direccion_fisica, principal 
            FROM sedes 
            WHERE estatus = 1 OR estatus IS NULL 
            ORDER BY clinica_id ASC, principal DESC, nombre ASC
        ");
        $stmtSedes->execute();
        $sedes = $stmtSedes->fetchAll(\PDO::FETCH_ASSOC);

        $stmtPacientes = $db->prepare("
            SELECT 
                p.id, 
                TRIM(CONCAT(p.primer_nombre, ' ', COALESCE(p.segundo_nombre, ''), ' ', p.primer_apellido, ' ', COALESCE(p.segundo_apellido, ''))) AS nombre_completo, 
                p.primer_nombre,
                p.primer_apellido,
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
            ORDER BY p.primer_nombre ASC, p.primer_apellido ASC
        ");
        $stmtPacientes->execute();
        $pacientes = $stmtPacientes->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($pacientes as &$pac) {
            $pac['nombre_completo'] = preg_replace('/\s+/', ' ', $pac['nombre_completo']);
        }

        $stmtServicios = $db->prepare("
            SELECT id, categoria_id, correlativo, nombre, rango_min_piezas, rango_max_piezas, precio 
            FROM servicios 
            WHERE activo = 1 
            ORDER BY correlativo ASC, nombre ASC
        ");
        $stmtServicios->execute();
        $servicios = $stmtServicios->fetchAll(\PDO::FETCH_ASSOC);

        $stmtUsuarios = $db->prepare("
            SELECT 
                u.id, 
                TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS nombre_completo, 
                r.rol,
                u.rol_id
            FROM usuarios u 
            INNER JOIN roles_usuarios r ON u.rol_id = r.id 
            WHERE (u.estatus = 1 OR u.estatus IS NULL)
            ORDER BY u.primer_nombre ASC
        ");
        $stmtUsuarios->execute();
        $usuarios = $stmtUsuarios->fetchAll(\PDO::FETCH_ASSOC);

        // Protesistas obtenidos de la tabla usuarios vinculados con su tabla de roles roles_usuarios
        $stmtProtesistas = $db->prepare("
            SELECT 
                u.id, 
                TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS nombre_completo, 
                r.rol,
                u.rol_id
            FROM usuarios u 
            INNER JOIN roles_usuarios r ON u.rol_id = r.id 
            WHERE (UPPER(r.rol) LIKE '%PROTESISTA%' OR u.rol_id = 4)
              AND (u.estatus = 1 OR u.estatus IS NULL)
            ORDER BY u.primer_nombre ASC
        ");
        $stmtProtesistas->execute();
        $protesistas = $stmtProtesistas->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($protesistas)) {
            $stmtProtesistasFallback = $db->prepare("
                SELECT 
                    u.id, 
                    TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS nombre_completo, 
                    r.rol,
                    u.rol_id
                FROM usuarios u 
                INNER JOIN roles_usuarios r ON u.rol_id = r.id 
                WHERE (UPPER(r.rol) LIKE '%PROTESISTA%' OR u.rol_id = 4)
                ORDER BY u.primer_nombre ASC
            ");
            $stmtProtesistasFallback->execute();
            $protesistas = $stmtProtesistasFallback->fetchAll(\PDO::FETCH_ASSOC);
        }

        $stmtEtapas = $db->prepare("
            SELECT id, nombre, orden 
            FROM etapas_produccion 
            ORDER BY orden ASC
        ");
        $stmtEtapas->execute();
        $etapas = $stmtEtapas->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode([
            'status'      => 'success',
            'clinicas'    => $clinicas,
            'sedes'       => $sedes,
            'pacientes'   => $pacientes,
            'servicios'   => $servicios,
            'usuarios'    => $usuarios,
            'protesistas' => $protesistas,
            'etapas'      => $etapas
        ]);
        exit();
    }

    // =========================================================================
    // 3. OBTENER DETALLE DE UN CASO Y SU BITÁCORA DE TRAZABILIDAD
    // =========================================================================
    if ($action === 'detalle_caso') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'El ID del caso es obligatorio.']);
            exit();
        }

        $stmtCaso = $db->prepare("
            SELECT 
                ot.id,
                ot.codigo_qr,
                ot.clinica_id,
                c.razon_social AS clinica_nombre,
                ot.paciente_id,
                TRIM(CONCAT(p.primer_nombre, ' ', COALESCE(p.segundo_nombre, ''), ' ', p.primer_apellido)) AS paciente_nombre,
                CONCAT(p.tipo_documento, '-', p.numero_documento) AS paciente_documento,
                ot.servicio_id,
                sv.nombre AS servicio_nombre,
                ot.cantidad_piezas_exacta,
                ot.estatus_actual,
                DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y %H:%i') AS fecha_ingreso_fmt,
                COALESCE(od.etapa_produccion_id, 1) AS etapa_produccion_id,
                COALESCE(ep.nombre, 'TOMA DE IMPRESION') AS etapa_nombre,
                ht.usuario_id AS responsable_id,
                TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS responsable_nombre,
                COALESCE(ht.ubicacion, 'RECEPCIÓN LABORATORIO') AS ubicacion_fisica,
                env.id AS envio_id,
                env.codigo_qr AS envio_codigo,
                env.estatus_envio AS envio_estatus
            FROM ordenes_trabajo ot
            INNER JOIN clinicas c ON ot.clinica_id = c.id
            INNER JOIN pacientes p ON ot.paciente_id = p.id
            INNER JOIN servicios sv ON ot.servicio_id = sv.id
            LEFT JOIN ordenes_detalle od ON od.id = (
                SELECT MAX(od2.id) FROM ordenes_detalle od2 WHERE od2.orden_trabajo_id = ot.id
            )
            LEFT JOIN etapas_produccion ep ON od.etapa_produccion_id = ep.id
            LEFT JOIN historial_trazabilidad ht ON ht.id = (
                SELECT MAX(ht2.id) FROM historial_trazabilidad ht2 WHERE ht2.orden_trabajo_id = ot.id
            )
            LEFT JOIN usuarios u ON ht.usuario_id = u.id
            LEFT JOIN envios env ON ot.envio_id = env.id
            WHERE ot.id = ?
            LIMIT 1
        ");
        $stmtCaso->execute([$id]);
        $caso = $stmtCaso->fetch(\PDO::FETCH_ASSOC);

        if (!$caso) {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró el caso solicitado.']);
            exit();
        }

        $stmtHist = $db->prepare("
            SELECT 
                ht.id,
                ht.orden_trabajo_id,
                ht.orden_detalle_id,
                ht.usuario_id,
                TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS usuario_nombre,
                r.rol AS usuario_rol,
                ht.estatus,
                ht.ubicacion,
                ht.notas,
                DATE_FORMAT(ht.fecha_cambio, '%d/%m/%Y %H:%i') AS fecha_cambio
            FROM historial_trazabilidad ht
            INNER JOIN usuarios u ON ht.usuario_id = u.id
            LEFT JOIN roles_usuarios r ON u.rol_id = r.id
            WHERE ht.orden_trabajo_id = ?
            ORDER BY ht.id DESC
        ");
        $stmtHist->execute([$id]);
        $historial = $stmtHist->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode([
            'status'    => 'success',
            'caso'      => $caso,
            'historial' => $historial
        ]);
        exit();
    }

    echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
    exit();

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
}