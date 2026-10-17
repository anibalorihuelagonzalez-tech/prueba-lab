<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
// app/controllers/recursos-humanos-controller.php
if (session_status() === PHP_SESSION_NONE) {
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

$pdo = conexion();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // =========================================================================
        // 1. LISTAR TODO EL PERSONAL
        // =========================================================================
        case 'listar_personal':
            $sql = "SELECT 
                        e.id,
                        e.usuario_id,
                        e.cargo_id,
                        e.estatus_empleado_id,
                        e.contrato_id,
                        e.primer_nombre,
                        e.segundo_nombre,
                        e.primer_apellido,
                        e.segundo_apellido,
                        TRIM(CONCAT(e.primer_nombre, ' ', COALESCE(e.primer_apellido, ''))) AS nombre_completo,
                        e.tipo_documento,
                        e.numero_documento,
                        IF(e.numero_documento LIKE CONCAT(e.tipo_documento, '-%'), e.numero_documento, CONCAT(e.tipo_documento, '-', e.numero_documento)) AS documento_completo,
                        COALESCE(e.correo_electronico, u.correo_electronico, 'Sin correo') AS correo,
                        e.telefono,
                        e.direccion,
                        COALESCE(e.foto, u.foto, '') AS foto,
                        DATE_FORMAT(e.fecha_nacimiento, '%d/%m/%Y') AS fecha_nacimiento_fmt,
                        DATE_FORMAT(e.fecha_ingreso, '%d/%m/%Y') AS fecha_ingreso_fmt,
                        e.fecha_ingreso,
                        e.salario_base_ves,
                        e.is_active,
                        COALESCE(c.nombre, 'Sin Cargo') AS cargo_nombre,
                        COALESCE(c.departamento, 'General') AS departamento,
                        COALESCE(ee.nombre, IF(e.is_active = 1, 'Activo', 'Inactivo')) AS estatus_nombre,
                        COALESCE(ct.tipo_contrato, 'INDETERMINADO') AS tipo_contrato
                    FROM empleados e
                    LEFT JOIN cargos c ON e.cargo_id = c.id
                    LEFT JOIN estatus_empleado ee ON e.estatus_empleado_id = ee.id
                    LEFT JOIN contratos ct ON e.contrato_id = ct.id
                    LEFT JOIN usuarios u ON e.usuario_id = u.id
                    WHERE e.deleted_at IS NULL
                    ORDER BY e.is_active DESC, e.primer_nombre ASC";

            $stmt = $pdo->query($sql);
            $personal = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Obtener última tasa activa para calcular salario en USD
            $tasaRow = $pdo->query("SELECT tasa FROM tasas_cambio WHERE is_active = 1 AND deleted_at IS NULL ORDER BY vigente_desde DESC, id DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
            $tasaActual = $tasaRow ? (float)$tasaRow['tasa'] : 1.0;

            foreach ($personal as &$p) {
                $salarioVes = (float)$p['salario_base_ves'];
                $p['salario_usd'] = $tasaActual > 0 ? round($salarioVes / $tasaActual, 2) : 0.00;
            }

            // Calcular KPIs
            $kpis = [
                'total_personal'  => count($personal),
                'protesistas'     => 0,
                'logistica'       => 0,
                'administracion'  => 0,
                'activos'         => 0
            ];

            foreach ($personal as $p) {
                if ($p['is_active'] == 1) $kpis['activos']++;
                $depto = strtoupper($p['departamento']);
                $cargo = strtoupper($p['cargo_nombre']);
                if (str_contains($cargo, 'PROTESISTA') || str_contains($depto, 'LABORATORIO')) {
                    $kpis['protesistas']++;
                } elseif (str_contains($cargo, 'REPARTIDOR') || str_contains($depto, 'LOGÍSTICA') || str_contains($depto, 'LOGISTICA')) {
                    $kpis['logistica']++;
                } elseif (str_contains($cargo, 'ADMIN') || str_contains($depto, 'FINANZAS') || str_contains($depto, 'ADMINISTRACIÓN')) {
                    $kpis['administracion']++;
                }
            }

            echo json_encode([
                'status'      => 'success',
                'data'        => $personal,
                'kpis'        => $kpis,
                'tasa_actual' => $tasaActual
            ]);
            break;

        // =========================================================================
        // 2. CARGAR CATÁLOGOS (CARGOS, DEPARTAMENTOS, CONTRATOS, ESTATUS)
        // =========================================================================
        case 'cargar_catalogos':
            $cargos = $pdo->query("SELECT id, nombre, departamento FROM cargos WHERE is_active = 1 ORDER BY departamento ASC, nombre ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $estatusList = $pdo->query("SELECT id, nombre FROM estatus_empleado ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $contratos = $pdo->query("SELECT id, tipo_contrato, fecha_inicio, fecha_fin FROM contratos ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $roles = $pdo->query("SELECT id, rol FROM roles_usuarios ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'status'    => 'success',
                'cargos'    => $cargos,
                'estatus'   => $estatusList,
                'contratos' => $contratos,
                'roles'     => $roles
            ]);
            break;

        // =========================================================================
        // 3. REGISTRAR NUEVO EMPLEADO / COLABORADOR (CON FOTO)
        // =========================================================================
        case 'crear_empleado':
            $primerNombre   = trim($_POST['primer_nombre'] ?? '');
            $segundoNombre  = trim($_POST['segundo_nombre'] ?? '');
            $primerApellido = trim($_POST['primer_apellido'] ?? '');
            $segundoApellido= trim($_POST['segundo_apellido'] ?? '');
            $tipoDoc        = trim($_POST['tipo_documento'] ?? 'V');
            $numDoc         = trim($_POST['numero_documento'] ?? '');
            $correo         = trim($_POST['correo_electronico'] ?? '');
            $telefono       = trim($_POST['telefono'] ?? '');
            $direccion      = trim($_POST['direccion'] ?? '');
            $cargoId        = !empty($_POST['cargo_id']) ? (int)$_POST['cargo_id'] : null;
            $estatusId      = !empty($_POST['estatus_empleado_id']) ? (int)$_POST['estatus_empleado_id'] : 1;
            $tipoContrato   = trim($_POST['tipo_contrato'] ?? 'INDETERMINADO');
            $fechaIngreso   = trim($_POST['fecha_ingreso'] ?? date('Y-m-d'));
            $fechaNac       = !empty($_POST['fecha_nacimiento']) ? trim($_POST['fecha_nacimiento']) : null;
            $salarioVes     = (float)str_replace(',', '.', $_POST['salario_base_ves'] ?? '0');
            $crearUsuario   = !empty($_POST['crear_usuario']);
            $contrasena     = trim($_POST['contrasena'] ?? '');
            $rolId          = !empty($_POST['rol_id']) ? (int)$_POST['rol_id'] : 3;

            if (empty($primerNombre) || empty($primerApellido) || empty($numDoc)) {
                echo json_encode(['status' => 'error', 'message' => 'Nombres, apellidos y cédula de identidad son obligatorios.']);
                exit;
            }

            // Evitar duplicidad de registros (Validar si ya existe este documento)
            $stmtCheckDup = $pdo->prepare("SELECT id FROM empleados WHERE tipo_documento = ? AND numero_documento = ? AND deleted_at IS NULL LIMIT 1");
            $stmtCheckDup->execute([$tipoDoc, $numDoc]);
            if ($stmtCheckDup->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "Ya existe un colaborador registrado con el documento {$tipoDoc}-{$numDoc}."]);
                exit;
            }

            // Procesar foto si fue subida
            $fotoPath = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && !empty($_FILES['foto']['tmp_name'])) {
                $fileTmp = $_FILES['foto']['tmp_name'];
                $fileName = $_FILES['foto']['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (!in_array($fileExt, $allowed)) {
                    echo json_encode(['status' => 'error', 'message' => 'Formato de imagen inválido. Solo se admiten JPG, PNG, WEBP o GIF.']);
                    exit;
                }

                $uploadsDir = __DIR__ . '/../../public/uploads/personal';
                if (!is_dir($uploadsDir)) {
                    @mkdir($uploadsDir, 0777, true);
                }

                $newFileName = 'emp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $targetFile = $uploadsDir . '/' . $newFileName;

                if (move_uploaded_file($fileTmp, $targetFile)) {
                    $fotoPath = '/uploads/personal/' . $newFileName;
                }
            }

            $pdo->beginTransaction();

            // Gestionar Contrato
            $stmtCt = $pdo->prepare("INSERT INTO contratos (tipo_contrato, fecha_inicio) VALUES (?, ?)");
            $stmtCt->execute([$tipoContrato, $fechaIngreso]);
            $contratoId = $pdo->lastInsertId();

            // Opcional: Crear cuenta de usuario en sistema si se solicitó
            $usuarioId = null;
            if ($crearUsuario && !empty($correo) && !empty($contrasena)) {
                $stmtCheckU = $pdo->prepare("SELECT id FROM usuarios WHERE correo_electronico = ?");
                $stmtCheckU->execute([$correo]);
                if ($stmtCheckU->fetch()) {
                    $pdo->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya pertenece a un usuario existente.']);
                    exit;
                }

                $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmtU = $pdo->prepare("INSERT INTO usuarios (primer_nombre, primer_apellido, foto, correo_electronico, contrasena, rol_id, estatus) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmtU->execute([$primerNombre, $primerApellido, $fotoPath, $correo, $hash, $rolId]);
                $usuarioId = $pdo->lastInsertId();
            }

            // Insertar Empleado
            $stmtEmp = $pdo->prepare("
                INSERT INTO empleados (
                    usuario_id, cargo_id, estatus_empleado_id, contrato_id,
                    primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
                    tipo_documento, numero_documento, correo_electronico, telefono, direccion,
                    foto, fecha_nacimiento, fecha_ingreso, salario_base_ves, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmtEmp->execute([
                $usuarioId,
                $cargoId,
                $estatusId,
                $contratoId,
                $primerNombre,
                $segundoNombre ?: null,
                $primerApellido,
                $segundoApellido ?: null,
                $tipoDoc,
                $numDoc,
                $correo ?: null,
                $telefono ?: null,
                $direccion ?: null,
                $fotoPath,
                $fechaNac ?: null,
                $fechaIngreso,
                $salarioVes
            ]);

            $empId = $pdo->lastInsertId();
            $pdo->commit();

            echo json_encode([
                'status'  => 'success',
                'message' => 'Colaborador registrado exitosamente con su fotografía y expediente.',
                'id'      => $empId
            ]);
            break;

        // =========================================================================
        // 4. ACTUALIZAR COLABORADOR (DATOS Y FOTO)
        // =========================================================================
        case 'editar_empleado':
            $empId          = (int)($_POST['id'] ?? 0);
            $primerNombre   = trim($_POST['primer_nombre'] ?? '');
            $segundoNombre  = trim($_POST['segundo_nombre'] ?? '');
            $primerApellido = trim($_POST['primer_apellido'] ?? '');
            $segundoApellido= trim($_POST['segundo_apellido'] ?? '');
            $tipoDoc        = trim($_POST['tipo_documento'] ?? 'V');
            $numDoc         = trim($_POST['numero_documento'] ?? '');
            $correo         = trim($_POST['correo_electronico'] ?? '');
            $telefono       = trim($_POST['telefono'] ?? '');
            $direccion      = trim($_POST['direccion'] ?? '');
            $cargoId        = !empty($_POST['cargo_id']) ? (int)$_POST['cargo_id'] : null;
            $estatusId      = !empty($_POST['estatus_empleado_id']) ? (int)$_POST['estatus_empleado_id'] : 1;
            $salarioVes     = (float)str_replace(',', '.', $_POST['salario_base_ves'] ?? '0');
            $isActive       = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if ($empId <= 0 || empty($primerNombre) || empty($primerApellido)) {
                echo json_encode(['status' => 'error', 'message' => 'ID, nombres y apellidos son requeridos.']);
                exit;
            }

            // Validar que la cédula no esté asignada a otro empleado
            if (!empty($numDoc)) {
                $stmtCheckDup = $pdo->prepare("SELECT id FROM empleados WHERE tipo_documento = ? AND numero_documento = ? AND id != ? AND deleted_at IS NULL LIMIT 1");
                $stmtCheckDup->execute([$tipoDoc, $numDoc, $empId]);
                if ($stmtCheckDup->fetch()) {
                    echo json_encode(['status' => 'error', 'message' => "El documento {$tipoDoc}-{$numDoc} ya pertenece a otro colaborador."]);
                    exit;
                }
            }

            // Consultar foto actual
            $stmtCurr = $pdo->prepare("SELECT foto, usuario_id FROM empleados WHERE id = ?");
            $stmtCurr->execute([$empId]);
            $currentData = $stmtCurr->fetch(\PDO::FETCH_ASSOC);
            if (!$currentData) {
                echo json_encode(['status' => 'error', 'message' => 'El colaborador especificado no existe.']);
                exit;
            }

            $fotoPath = $currentData['foto'];

            // Si se subió una nueva foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && !empty($_FILES['foto']['tmp_name'])) {
                $fileTmp = $_FILES['foto']['tmp_name'];
                $fileName = $_FILES['foto']['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (in_array($fileExt, $allowed)) {
                    $uploadsDir = __DIR__ . '/../../public/uploads/personal';
                    if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0777, true);
                    $newFileName = 'emp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                    $targetFile = $uploadsDir . '/' . $newFileName;
                    if (move_uploaded_file($fileTmp, $targetFile)) {
                        $fotoPath = '/uploads/personal/' . $newFileName;
                    }
                }
            }

            $stmtUp = $pdo->prepare("
                UPDATE empleados 
                SET primer_nombre = ?, segundo_nombre = ?, primer_apellido = ?, segundo_apellido = ?,
                    tipo_documento = ?, numero_documento = ?, correo_electronico = ?, telefono = ?,
                    direccion = ?, cargo_id = ?, estatus_empleado_id = ?, salario_base_ves = ?,
                    foto = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmtUp->execute([
                $primerNombre,
                $segundoNombre ?: null,
                $primerApellido,
                $segundoApellido ?: null,
                $tipoDoc,
                $numDoc,
                $correo ?: null,
                $telefono ?: null,
                $direccion ?: null,
                $cargoId,
                $estatusId,
                $salarioVes,
                $fotoPath,
                $isActive,
                $empId
            ]);

            // Sincronizar foto en usuario vinculado si existe
            if (!empty($currentData['usuario_id']) && $fotoPath) {
                $pdo->prepare("UPDATE usuarios SET foto = ?, primer_nombre = ?, primer_apellido = ? WHERE id = ?")
                    ->execute([$fotoPath, $primerNombre, $primerApellido, $currentData['usuario_id']]);
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Expediente del colaborador actualizado correctamente.',
                'foto'    => $fotoPath
            ]);
            break;

        // =========================================================================
        // 5. ALTERNAR ESTATUS (ACTIVAR / DESACTIVAR)
        // =========================================================================
        case 'cambiar_estatus':
            $id = (int)($_POST['id'] ?? 0);
            $nuevoEstatus = (int)($_POST['estatus'] ?? 1);

            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE empleados SET is_active = ?, estatus_empleado_id = IF(? = 1, 1, 4), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nuevoEstatus, $nuevoEstatus, $id]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Estatus del colaborador actualizado exitosamente.'
            ]);
            break;

        // =========================================================================
        // 6. ELIMINAR COLABORADOR (SOFT DELETE)
        // =========================================================================
        case 'eliminar_empleado':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE empleados SET is_active = 0, deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Colaborador archivado del sistema exitosamente.'
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
