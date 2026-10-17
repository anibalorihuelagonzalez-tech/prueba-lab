<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
if (session_status() === PHP_SESSION_NONE) {
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Utilice POST o GET.']);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

$inputData = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? $inputData['action'] ?? 'listar';
$page   = (int)($_POST['page'] ?? $_GET['page'] ?? $_REQUEST['page'] ?? $inputData['page'] ?? 1);
$size   = (int)($_POST['size'] ?? $_GET['size'] ?? $_REQUEST['size'] ?? $inputData['size'] ?? 10);
$id     = $_POST['id'] ?? $_GET['id'] ?? $_REQUEST['id'] ?? $inputData['id'] ?? null;

try {
    $db = conexion();

    // =========================================================================
    // ACCIÓN: LISTAR TODAS LAS TASAS (Para Tabulator con Paginación y Filtros)
    // =========================================================================
    if ($action === 'listar' || $action === 'list') {
        $offset = ($page - 1) * $size;

        $where = ["tc.deleted_at IS NULL"];
        $params = [];

        // Filtro por fecha de vigencia
        $fecha = trim($_REQUEST['fecha'] ?? $inputData['fecha'] ?? '');
        if (!empty($fecha)) {
            $where[] = "DATE(tc.vigente_desde) = :fecha";
            $params[':fecha'] = $fecha;
        }

        // Filtro por estatus (1 activa, 0 inactiva)
        $estatus = $_REQUEST['estatus'] ?? $inputData['estatus'] ?? null;
        if ($estatus !== null && $estatus !== '') {
            $where[] = "tc.is_active = :estatus";
            $params[':estatus'] = (int)$estatus;
        }

        // Búsqueda general
        $search = trim($_REQUEST['search'] ?? $inputData['search'] ?? '');
        if (!empty($search)) {
            $where[] = "(mo.codigo LIKE :search OR mo.nombre LIKE :search OR md.codigo LIKE :search OR md.nombre LIKE :search OR tc.fuente LIKE :search OR tc.observaciones LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $whereSql = implode(' AND ', $where);

        // Contar el total de registros con filtros aplicados
        $countSql = "SELECT COUNT(*) 
                     FROM tasas_cambio tc
                     INNER JOIN monedas mo ON tc.moneda_origen_id = mo.id
                     INNER JOIN monedas md ON tc.moneda_destino_id = md.id
                     LEFT JOIN usuarios u ON tc.usuario_id = u.id
                     WHERE {$whereSql}";
        $stmtTotal = $db->prepare($countSql);
        foreach ($params as $key => $val) {
            $stmtTotal->bindValue($key, $val);
        }
        $stmtTotal->execute();
        $totalRows = (int)$stmtTotal->fetchColumn();

        // Obtener los datos ordenados por vigencia descendente
        $query = "SELECT 
                    tc.id,
                    tc.moneda_origen_id,
                    tc.moneda_destino_id,
                    tc.usuario_id,
                    tc.tasa,
                    tc.fuente,
                    tc.vigente_desde,
                    tc.vigente_hasta,
                    tc.observaciones,
                    tc.is_active,
                    tc.is_active AS estatus,
                    DATE(tc.vigente_desde) AS fecha_vigencia,
                    tc.created_at,
                    tc.updated_at,
                    tc.created_at AS fecha_hora_creacion,
                    tc.updated_at AS fecha_hora_actualizacion,
                    mo.codigo AS origen_codigo,
                    mo.nombre AS origen_nombre,
                    mo.simbolo AS origen_simbolo,
                    md.codigo AS destino_codigo,
                    md.nombre AS destino_nombre,
                    md.simbolo AS destino_simbolo,
                    CONCAT(mo.codigo, ' / ', md.codigo) AS par,
                    TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS usuario_nombre
                  FROM tasas_cambio tc
                  INNER JOIN monedas mo ON tc.moneda_origen_id = mo.id
                  INNER JOIN monedas md ON tc.moneda_destino_id = md.id
                  LEFT JOIN usuarios u ON tc.usuario_id = u.id
                  WHERE {$whereSql}
                  ORDER BY tc.vigente_desde DESC, tc.id DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $size, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tasas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lastPage = $totalRows > 0 ? (int)ceil($totalRows / $size) : 1;

        echo json_encode([
            "last_page" => $lastPage > 0 ? $lastPage : 1,
            "total" => $totalRows,
            "data" => $tasas
        ]);
        exit();

    // =========================================================================
    // ACCIÓN: VER DETALLES DE UNA TASA ESPECÍFICA (Para Editar o Ver Detalles)
    // =========================================================================
    } elseif ($action === 'show') {
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'El ID de la tasa es requerido.']);
            exit();
        }

        $stmt = $db->prepare("
            SELECT 
                tc.id,
                tc.moneda_origen_id,
                tc.moneda_destino_id,
                tc.usuario_id,
                tc.tasa,
                tc.fuente,
                tc.vigente_desde,
                tc.vigente_hasta,
                tc.observaciones,
                tc.is_active,
                tc.is_active AS estatus,
                DATE(tc.vigente_desde) AS fecha_vigencia,
                tc.created_at,
                tc.updated_at,
                tc.created_at AS fecha_hora_creacion,
                tc.updated_at AS fecha_hora_actualizacion,
                mo.codigo AS origen_codigo,
                mo.nombre AS origen_nombre,
                mo.simbolo AS origen_simbolo,
                md.codigo AS destino_codigo,
                md.nombre AS destino_nombre,
                md.simbolo AS destino_simbolo,
                CONCAT(mo.codigo, ' / ', md.codigo) AS par,
                TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS usuario_nombre
            FROM tasas_cambio tc
            INNER JOIN monedas mo ON tc.moneda_origen_id = mo.id
            INNER JOIN monedas md ON tc.moneda_destino_id = md.id
            LEFT JOIN usuarios u ON tc.usuario_id = u.id
            WHERE tc.id = ? AND tc.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $tasa = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tasa) {
            echo json_encode(['status' => 'success', 'tasa' => $tasa]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Tasa de cambio no encontrada.']);
        }
        exit();

    // =========================================================================
    // ACCIÓN: CATÁLOGO DE MONEDAS ACTIVAS (Para poblar selects en modales)
    // =========================================================================
    } elseif ($action === 'catalogos' || $action === 'monedas') {
        $stmt = $db->query("
            SELECT id, codigo, nombre, simbolo, es_base, es_base AS is_base 
            FROM monedas 
            WHERE is_active = 1 AND deleted_at IS NULL 
            ORDER BY es_base DESC, nombre ASC
        ");
        $monedas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'monedas' => $monedas
        ]);
        exit();

    // =========================================================================
    // ACCIÓN: RESUMEN / KPIS DE TASAS DE CAMBIO
    // =========================================================================
    } elseif ($action === 'kpis' || $action === 'resumen') {
        // Total histórico de tasas
        $totalTasas = (int)$db->query("SELECT COUNT(*) FROM tasas_cambio WHERE deleted_at IS NULL")->fetchColumn();
        
        // Total tasas activas
        $tasasActivas = (int)$db->query("SELECT COUNT(*) FROM tasas_cambio WHERE is_active = 1 AND deleted_at IS NULL")->fetchColumn();

        // Tasa activa principal más reciente (preferiblemente USD a VES o la última registrada activa)
        $stmtPrincipal = $db->query("
            SELECT 
                tc.id,
                tc.tasa,
                tc.fuente,
                tc.vigente_desde,
                tc.vigente_hasta,
                mo.codigo AS origen_codigo,
                mo.simbolo AS origen_simbolo,
                md.codigo AS destino_codigo,
                md.simbolo AS destino_simbolo
            FROM tasas_cambio tc
            INNER JOIN monedas mo ON tc.moneda_origen_id = mo.id
            INNER JOIN monedas md ON tc.moneda_destino_id = md.id
            WHERE tc.is_active = 1 AND tc.deleted_at IS NULL
            ORDER BY (mo.es_base = 1) DESC, tc.vigente_desde DESC, tc.id DESC
            LIMIT 1
        ");
        $tasaPrincipal = $stmtPrincipal->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'kpis' => [
                'total_tasas'     => $totalTasas,
                'tasas_activas'   => $tasasActivas,
                'tasa_principal'  => $tasaPrincipal ?: null
            ]
        ]);
        exit();

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Acción no válida."]);
        exit();
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
    exit();
}