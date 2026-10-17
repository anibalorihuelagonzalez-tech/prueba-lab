<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard();
// app/controllers/tracking-controller.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

$pdo = conexion();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Mapeo amigable de estatus
function formatoEstatus(string $estBd): array {
    return match (strtoupper($estBd)) {
        'PREPARADO', 'CREADO' => [
            'label' => 'Preparado en Lab',
            'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
            'step'  => 1
        ],
        'RECOLECTADO' => [
            'label' => 'Asignado / Recolectado',
            'badge' => 'bg-sky-50 text-sky-700 border-sky-200',
            'step'  => 2
        ],
        'TRANSITO', 'EN TRANSITO', 'EN RUTA' => [
            'label' => 'En Tránsito',
            'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
            'step'  => 3
        ],
        'RECIBIDO', 'ENTREGADO' => [
            'label' => 'Entregado en Clínica',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'step'  => 4
        ],
        default => [
            'label' => $estBd,
            'badge' => 'bg-slate-100 text-slate-700 border-slate-200',
            'step'  => 1
        ]
    };
}

try {
    switch ($action) {

        // =========================================================================
        // 1. LISTAR ENVÍOS CON MÉTRICAS DE TRACKING PARA TABULATOR Y KPIS
        // =========================================================================
        case 'listar_tracking':
            $sql = "SELECT 
                        e.id,
                        e.codigo_qr AS codigo,
                        e.sede_id,
                        s.nombre AS sede_nombre,
                        s.direccion_fisica AS sede_direccion,
                        s.clinica_id,
                        c.razon_social AS clinica_nombre,
                        e.repartidor_id,
                        TRIM(CONCAT(r.nombres, ' ', r.apellidos)) AS repartidor_nombre,
                        r.telefono AS repartidor_telefono,
                        r.placa_vehiculo,
                        e.cantidad_casos,
                        e.direccion_movimiento,
                        e.estatus_envio,
                        DATE_FORMAT(e.fecha_hora_creacion, '%d/%m/%Y %H:%i') AS fecha_creacion_fmt,
                        DATE_FORMAT(e.fecha_hora_despacho, '%d/%m/%Y %H:%i') AS fecha_despacho_fmt,
                        DATE_FORMAT(e.fecha_hora_recepcion, '%d/%m/%Y %H:%i') AS fecha_recepcion_fmt,
                        DATE_FORMAT(COALESCE(e.fecha_hora_programada, e.fecha_hora_despacho, e.fecha_hora_creacion), '%d/%m/%Y %H:%i') AS fecha_ruta_fmt,
                        e.notas_cancelacion AS observaciones,
                        (SELECT COUNT(*) FROM ordenes_trabajo ot WHERE ot.envio_id = e.id) AS total_real_casos
                    FROM envios e
                    INNER JOIN sedes s ON e.sede_id = s.id
                    INNER JOIN clinicas c ON s.clinica_id = c.id
                    INNER JOIN repartidores r ON e.repartidor_id = r.id
                    ORDER BY e.id DESC";

            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $kpis = [
                'total_envios'   => count($rows),
                'en_transito'    => 0,
                'preparados'     => 0,
                'entregados'     => 0,
                'total_casos'    => 0,
            ];

            $data = array_map(function ($row) use (&$kpis) {
                $info = formatoEstatus($row['estatus_envio']);
                $row['estatus_label'] = $info['label'];
                $row['estatus_badge'] = $info['badge'];
                $row['estatus_step']  = $info['step'];
                $row['total_cajas']   = $row['total_real_casos'] > 0 ? (int)$row['total_real_casos'] : (int)$row['cantidad_casos'];

                if ($info['step'] === 3) {
                    $kpis['en_transito']++;
                } elseif ($info['step'] === 4) {
                    $kpis['entregados']++;
                } else {
                    $kpis['preparados']++;
                }
                $kpis['total_casos'] += $row['total_cajas'];

                return $row;
            }, $rows);

            echo json_encode([
                'status' => 'success',
                'data'   => $data,
                'kpis'   => $kpis
            ]);
            break;

        // =========================================================================
        // 2. OBTENER TRAZABILIDAD DETALLADA DE UN ENVÍO (LÍNEA DE TIEMPO + CASOS)
        // =========================================================================
        case 'obtener_trazabilidad':
            $envioId = !empty($_POST['envio_id']) ? (int)$_POST['envio_id'] : 0;
            $terminoBusqueda = trim($_POST['codigo_qr'] ?? '');

            // Si se busca por QR (sea de la caja de envío 'ENV-' o de un caso dental 'ORD-')
            if ($envioId <= 0 && !empty($terminoBusqueda)) {
                // Primero intentar buscar por código directo de envío
                $stmtQREnv = $pdo->prepare("SELECT id FROM envios WHERE codigo_qr = ? OR id = ? LIMIT 1");
                $stmtQREnv->execute([$terminoBusqueda, (int)$terminoBusqueda]);
                $envioFound = $stmtQREnv->fetch();

                if ($envioFound) {
                    $envioId = (int)$envioFound['id'];
                } else {
                    // Intentar buscar si el QR corresponde a una orden de trabajo contenida en un envío
                    $stmtQROrd = $pdo->prepare("SELECT envio_id FROM ordenes_trabajo WHERE codigo_qr = ? AND envio_id IS NOT NULL LIMIT 1");
                    $stmtQROrd->execute([$terminoBusqueda]);
                    $ordFound = $stmtQROrd->fetch();
                    if ($ordFound && !empty($ordFound['envio_id'])) {
                        $envioId = (int)$ordFound['envio_id'];
                    }
                }
            }

            if ($envioId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se encontró ningún despacho o caso asociado a ese código.'
                ]);
                exit;
            }

            // Datos principales del envío
            $sqlEnvio = "SELECT 
                            e.id,
                            e.codigo_qr AS codigo,
                            e.sede_id,
                            s.nombre AS sede_nombre,
                            s.direccion_fisica AS sede_direccion,
                            s.clinica_id,
                            c.razon_social AS clinica_nombre,
                            c.telefono AS clinica_telefono,
                            c.tipo_documento,
                            c.numero_documento,
                            e.repartidor_id,
                            TRIM(CONCAT(r.nombres, ' ', r.apellidos)) AS repartidor_nombre,
                            r.telefono AS repartidor_telefono,
                            r.placa_vehiculo,
                            e.cantidad_casos,
                            e.direccion_movimiento,
                            e.estatus_envio,
                            DATE_FORMAT(e.fecha_hora_creacion, '%d/%m/%Y %H:%i') AS fecha_creacion_fmt,
                            DATE_FORMAT(e.fecha_hora_despacho, '%d/%m/%Y %H:%i') AS fecha_despacho_fmt,
                            DATE_FORMAT(e.fecha_hora_recepcion, '%d/%m/%Y %H:%i') AS fecha_recepcion_fmt,
                            e.notas_cancelacion AS observaciones
                         FROM envios e
                         INNER JOIN sedes s ON e.sede_id = s.id
                         INNER JOIN clinicas c ON s.clinica_id = c.id
                         INNER JOIN repartidores r ON e.repartidor_id = r.id
                         WHERE e.id = :id
                         LIMIT 1";

            $stmtEnv = $pdo->prepare($sqlEnvio);
            $stmtEnv->execute([':id' => $envioId]);
            $envio = $stmtEnv->fetch(\PDO::FETCH_ASSOC);

            if (!$envio) {
                echo json_encode(['status' => 'error', 'message' => 'Despacho no encontrado.']);
                exit;
            }

            $info = formatoEstatus($envio['estatus_envio']);
            $envio['estatus_label'] = $info['label'];
            $envio['estatus_badge'] = $info['badge'];
            $envio['estatus_step']  = $info['step'];

            // 1. Historial de eventos / Bitácora registrada
            $sqlHistorial = "SELECT 
                                h.id,
                                h.historial_envios_estatus_id,
                                hes.nombre AS estatus_nombre,
                                h.notas,
                                DATE_FORMAT(h.fecha_hora, '%d/%m/%Y %H:%i') AS fecha_hora_fmt,
                                TRIM(CONCAT(u.primer_nombre, ' ', u.primer_apellido)) AS usuario_nombre,
                                ru.rol AS usuario_rol
                             FROM historial_envios h
                             LEFT JOIN historial_envios_estatus hes ON h.historial_envios_estatus_id = hes.id
                             LEFT JOIN usuarios u ON h.usuario_id = u.id
                             LEFT JOIN roles_usuarios ru ON u.rol_id = ru.id
                             WHERE h.envio_id = :envio_id
                             ORDER BY h.id ASC";

            $stmtHist = $pdo->prepare($sqlHistorial);
            $stmtHist->execute([':envio_id' => $envioId]);
            $eventos = $stmtHist->fetchAll(\PDO::FETCH_ASSOC);

            // 2. Casos / Cajas pequeñas contenidas en el despacho
            $sqlCasos = "SELECT 
                            ot.id,
                            ot.codigo_qr,
                            ot.cantidad_piezas_exacta,
                            COALESCE(ot.cantidad_protesis, 1) AS cantidad_protesis,
                            ot.estatus_actual,
                            DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y %H:%i') AS fecha_ingreso_fmt,
                            sv.nombre AS servicio_nombre,
                            TRIM(CONCAT(p.primer_nombre, ' ', COALESCE(p.segundo_nombre, ''), ' ', p.primer_apellido)) AS paciente_nombre,
                            CONCAT(p.tipo_documento, '-', p.numero_documento) AS paciente_documento,
                            TRIM(CONCAT(prot.primer_nombre, ' ', prot.primer_apellido)) AS protesista_nombre
                         FROM ordenes_trabajo ot
                         INNER JOIN servicios sv ON ot.servicio_id = sv.id
                         INNER JOIN pacientes p ON ot.paciente_id = p.id
                         LEFT JOIN usuarios prot ON ot.protesista_id = prot.id
                         WHERE ot.envio_id = :envio_id
                         ORDER BY ot.id ASC";

            $stmtCasos = $pdo->prepare($sqlCasos);
            $stmtCasos->execute([':envio_id' => $envioId]);
            $casos = $stmtCasos->fetchAll(\PDO::FETCH_ASSOC);

            // Construir línea de tiempo estándar con 4 hitos
            $timeline = [
                [
                    'paso'       => 1,
                    'titulo'     => 'Preparado en Laboratorio',
                    'descripcion'=> 'Lote empacado y sellado en el laboratorio dental.',
                    'completado' => $envio['estatus_step'] >= 1,
                    'actual'     => $envio['estatus_step'] === 1,
                    'fecha'      => $envio['fecha_creacion_fmt'],
                    'icono'      => 'package-check'
                ],
                [
                    'paso'       => 2,
                    'titulo'     => 'Recolectado por Repartidor',
                    'descripcion'=> "Asignado a {$envio['repartidor_nombre']} ({$envio['placa_vehiculo']}).",
                    'completado' => $envio['estatus_step'] >= 2,
                    'actual'     => $envio['estatus_step'] === 2,
                    'fecha'      => $envio['fecha_despacho_fmt'] ?: ($envio['estatus_step'] >= 2 ? $envio['fecha_creacion_fmt'] : null),
                    'icono'      => 'bike'
                ],
                [
                    'paso'       => 3,
                    'titulo'     => 'En Tránsito hacia la Clínica',
                    'descripcion'=> "En ruta hacia {$envio['clinica_nombre']} - {$envio['sede_nombre']}.",
                    'completado' => $envio['estatus_step'] >= 3,
                    'actual'     => $envio['estatus_step'] === 3,
                    'fecha'      => $envio['fecha_despacho_fmt'],
                    'icono'      => 'truck'
                ],
                [
                    'paso'       => 4,
                    'titulo'     => 'Recibido en Clínica / Sede',
                    'descripcion'=> 'Entrega confirmada y recibida por el personal odontológico.',
                    'completado' => $envio['estatus_step'] >= 4,
                    'actual'     => $envio['estatus_step'] === 4,
                    'fecha'      => $envio['fecha_recepcion_fmt'],
                    'icono'      => 'map-pin'
                ],
            ];

            echo json_encode([
                'status'    => 'success',
                'envio'     => $envio,
                'timeline'  => $timeline,
                'eventos'   => $eventos,
                'casos'     => $casos
            ]);
            break;

        // =========================================================================
        // 3. ACTUALIZAR ESTATUS DEL ENVÍO Y REGISTRAR EN BITÁCORA
        // =========================================================================
        case 'actualizar_estatus':
            $envioId   = (int)($_POST['envio_id'] ?? 0);
            $nuevoEst  = strtoupper(trim($_POST['nuevo_estatus'] ?? ''));
            $notas     = trim($_POST['notas'] ?? '');
            $usuarioId = Auth::id() ?: 1;

            if ($envioId <= 0 || !in_array($nuevoEst, ['PREPARADO', 'TRANSITO', 'RECIBIDO'])) {
                echo json_encode(['status' => 'error', 'message' => 'Parámetros no válidos.']);
                exit;
            }

            $pdo->beginTransaction();

            $sqlUp = "UPDATE envios SET estatus_envio = :est ";
            $params = [':est' => $nuevoEst, ':id' => $envioId];

            if ($nuevoEst === 'TRANSITO') {
                $sqlUp .= ", fecha_hora_despacho = NOW() ";
            } elseif ($nuevoEst === 'RECIBIDO') {
                $sqlUp .= ", fecha_hora_recepcion = NOW() ";
            }
            $sqlUp .= " WHERE id = :id";

            $stmtUp = $pdo->prepare($sqlUp);
            $stmtUp->execute($params);

            // Mapeo id estatus historial
            $histEstatusId = match ($nuevoEst) {
                'PREPARADO' => 1,
                'TRANSITO'  => 3,
                'RECIBIDO'  => 4,
                default     => 1
            };

            $stmtHist = $pdo->prepare("
                INSERT INTO historial_envios (envio_id, usuario_id, historial_envios_estatus_id, notas, fecha_hora)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $notaRegistro = !empty($notas) ? $notas : "Cambio de estatus del envío a {$nuevoEst}";
            $stmtHist->execute([$envioId, $usuarioId, $histEstatusId, $notaRegistro]);

            // Si se marcó como recibido, actualizar las órdenes de trabajo contenidas
            if ($nuevoEst === 'RECIBIDO') {
                $stmtOrd = $pdo->prepare("
                    UPDATE ordenes_trabajo 
                    SET estatus_actual = 'ENTREGADO EN CLÍNICA' 
                    WHERE envio_id = ?
                ");
                $stmtOrd->execute([$envioId]);
            }

            $pdo->commit();

            echo json_encode([
                'status'  => 'success',
                'message' => "Estatus actualizado a {$nuevoEst} exitosamente."
            ]);
            break;

        // =========================================================================
        // 4. ESCANEAR CÓDIGO QR (DETECTA CAJA PRINCIPAL O CAJA PEQUEÑA)
        // =========================================================================
        case 'escanear_qr':
            $rawCode = trim($_POST['codigo_qr'] ?? '');
            if (empty($rawCode)) {
                echo json_encode(['status' => 'error', 'message' => 'Debe ingresar o escanear un código QR.']);
                exit;
            }

            // Normalizar código
            $cleanCode = strtoupper(trim($rawCode));

            // A) Verificar si es una CAJA PRINCIPAL (ENVÍO)
            $stmtEnv = $pdo->prepare("
                SELECT 
                    e.id, e.codigo_qr, e.estatus_envio, e.cantidad_casos,
                    s.nombre AS sede_nombre, s.direccion_fisica AS sede_direccion,
                    c.razon_social AS clinica_nombre,
                    TRIM(CONCAT(r.nombres, ' ', r.apellidos)) AS repartidor_nombre,
                    r.telefono AS repartidor_telefono, r.placa_vehiculo,
                    DATE_FORMAT(e.fecha_hora_creacion, '%d/%m/%Y %H:%i') AS fecha_creacion_fmt,
                    DATE_FORMAT(e.fecha_hora_despacho, '%d/%m/%Y %H:%i') AS fecha_despacho_fmt,
                    DATE_FORMAT(e.fecha_hora_recepcion, '%d/%m/%Y %H:%i') AS fecha_recepcion_fmt
                FROM envios e
                INNER JOIN sedes s ON e.sede_id = s.id
                INNER JOIN clinicas c ON s.clinica_id = c.id
                INNER JOIN repartidores r ON e.repartidor_id = r.id
                WHERE e.codigo_qr = ? OR e.codigo_qr = ? OR (e.id = ? AND e.id > 0)
                LIMIT 1
            ");
            $idNum = ctype_digit($cleanCode) ? (int)$cleanCode : 0;
            $stmtEnv->execute([$cleanCode, $rawCode, $idNum]);
            $envio = $stmtEnv->fetch(\PDO::FETCH_ASSOC);

            if ($envio) {
                // Obtener casos/cajas pequeñas contenidas
                $stmtCasos = $pdo->prepare("
                    SELECT 
                        ot.id, ot.codigo_qr, ot.cantidad_piezas_exacta, ot.estatus_actual,
                        sv.nombre AS servicio_nombre,
                        TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente_nombre
                    FROM ordenes_trabajo ot
                    INNER JOIN servicios sv ON ot.servicio_id = sv.id
                    INNER JOIN pacientes p ON ot.paciente_id = p.id
                    WHERE ot.envio_id = ?
                ");
                $stmtCasos->execute([$envio['id']]);
                $casos = $stmtCasos->fetchAll(\PDO::FETCH_ASSOC);

                echo json_encode([
                    'status' => 'success',
                    'tipo'   => 'CAJA_PRINCIPAL',
                    'titulo' => 'Caja Principal (Despacho Dental)',
                    'envio'  => $envio,
                    'casos'  => $casos
                ]);
                exit;
            }

            // B) Verificar si es una CAJA PEQUEÑA (CASO / ORDEN DE TRABAJO INDIVIDUAL)
            $stmtOrd = $pdo->prepare("
                SELECT 
                    ot.id, ot.codigo_qr, ot.cantidad_piezas_exacta, ot.estatus_actual,
                    DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y %H:%i') AS fecha_ingreso_fmt,
                    sv.nombre AS servicio_nombre,
                    c.razon_social AS clinica_nombre,
                    TRIM(CONCAT(p.primer_nombre, ' ', COALESCE(p.segundo_nombre, ''), ' ', p.primer_apellido)) AS paciente_nombre,
                    CONCAT(p.tipo_documento, '-', p.numero_documento) AS paciente_documento,
                    TRIM(CONCAT(prot.primer_nombre, ' ', prot.primer_apellido)) AS protesista_nombre,
                    ot.envio_id,
                    e.codigo_qr AS envio_codigo_qr,
                    e.estatus_envio
                FROM ordenes_trabajo ot
                INNER JOIN servicios sv ON ot.servicio_id = sv.id
                INNER JOIN clinicas c ON ot.clinica_id = c.id
                INNER JOIN pacientes p ON ot.paciente_id = p.id
                LEFT JOIN usuarios prot ON ot.protesista_id = prot.id
                LEFT JOIN envios e ON ot.envio_id = e.id
                WHERE ot.codigo_qr = ? OR ot.codigo_qr = ? OR (ot.id = ? AND ot.id > 0)
                LIMIT 1
            ");
            $stmtOrd->execute([$cleanCode, $rawCode, $idNum]);
            $caso = $stmtOrd->fetch(\PDO::FETCH_ASSOC);

            if ($caso) {
                echo json_encode([
                    'status' => 'success',
                    'tipo'   => 'CAJA_PEQUENA',
                    'titulo' => 'Caja Pequeña (Caso Dental / Prótesis)',
                    'caso'   => $caso
                ]);
                exit;
            }

            echo json_encode([
                'status'  => 'not_found',
                'message' => "No se encontró ningún paquete ni caso dental registrado con el código '{$rawCode}'."
            ]);
            break;

        // =========================================================================
        // 5. ACTUALIZAR ESTATUS MEDIANTE ESCANEO (ABRIR, RECIBIR, ENTREGAR)
        // =========================================================================
        case 'actualizar_estatus_escaneo':
            $tipo        = trim($_POST['tipo'] ?? '');
            $id          = (int)($_POST['id'] ?? 0);
            $nuevoEstatus= trim($_POST['nuevo_estatus'] ?? '');
            $notas       = trim($_POST['notas'] ?? '');
            $usuarioId   = Auth::id() ?: 1;

            if ($id <= 0 || empty($nuevoEstatus) || empty($tipo)) {
                echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos para actualizar el paquete.']);
                exit;
            }

            $pdo->beginTransaction();

            if ($tipo === 'CAJA_PRINCIPAL') {
                // Actualizar Envío Principal
                $estEnvio = match ($nuevoEstatus) {
                    'RECIBIDO', 'ABIERTO EN CLÍNICA' => 'RECIBIDO',
                    'EN RUTA', 'TRANSITO'            => 'TRANSITO',
                    default                           => 'PREPARADO'
                };

                $sqlUp = "UPDATE envios SET estatus_envio = ? ";
                if ($estEnvio === 'RECIBIDO') {
                    $sqlUp .= ", fecha_hora_recepcion = NOW() ";
                } elseif ($estEnvio === 'TRANSITO') {
                    $sqlUp .= ", fecha_hora_despacho = NOW() ";
                }
                $sqlUp .= " WHERE id = ?";
                $pdo->prepare($sqlUp)->execute([$estEnvio, $id]);

                // Registrar en historial de envíos
                $stmtHist = $pdo->prepare("
                    INSERT INTO historial_envios (envio_id, usuario_id, historial_envios_estatus_id, notas, fecha_hora)
                    VALUES (?, ?, 4, ?, NOW())
                ");
                $notaHist = !empty($notas) ? $notas : "Escaneo QR: {$nuevoEstatus}";
                $stmtHist->execute([$id, $usuarioId, $notaHist]);

                // Si se abrió o recibió la caja principal, actualizar las cajas pequeñas dentro
                if ($nuevoEstatus === 'ABIERTO EN CLÍNICA' || $nuevoEstatus === 'RECIBIDO') {
                    $pdo->prepare("UPDATE ordenes_trabajo SET estatus_actual = ? WHERE envio_id = ?")
                        ->execute([$nuevoEstatus === 'ABIERTO EN CLÍNICA' ? 'Caja Abierta por Odontólogo' : 'Entregado en Clínica', $id]);
                }

                $pdo->commit();
                echo json_encode([
                    'status'  => 'success',
                    'message' => "Caja Principal actualizada a '{$nuevoEstatus}' exitosamente."
                ]);
                exit;

            } elseif ($tipo === 'CAJA_PEQUENA') {
                // Actualizar Caso / Orden individual
                $pdo->prepare("UPDATE ordenes_trabajo SET estatus_actual = ? WHERE id = ?")
                    ->execute([$nuevoEstatus, $id]);

                // Registrar en historial de trazabilidad
                $stmtHT = $pdo->prepare("
                    INSERT INTO historial_trazabilidad (orden_trabajo_id, usuario_id, estatus, ubicacion, notas, fecha_cambio)
                    VALUES (?, ?, ?, 'Verificación QR', ?, NOW())
                ");
                $notaHT = !empty($notas) ? $notas : "Actualización mediante escaneo QR: {$nuevoEstatus}";
                $stmtHT->execute([$id, $usuarioId, $nuevoEstatus, $notaHT]);

                $pdo->commit();
                echo json_encode([
                    'status'  => 'success',
                    'message' => "Caja Pequeña / Caso actualizado a '{$nuevoEstatus}' exitosamente."
                ]);
                exit;
            } else {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Tipo de paquete no reconocido.']);
                exit;
            }
            break;

        // =========================================================================
        // 6. DETALLES PARA ETIQUETA QR DE CAJA PRINCIPAL (IMPRESIÓN)
        // =========================================================================
        case 'etiqueta_caja_principal':
            $envioId = (int)($_POST['envio_id'] ?? 0);
            if ($envioId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de despacho requerido.']);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT 
                    e.id, e.codigo_qr, e.estatus_envio, e.cantidad_casos,
                    s.nombre AS sede_nombre, s.direccion_fisica AS sede_direccion,
                    c.razon_social AS clinica_nombre, c.telefono AS clinica_telefono,
                    TRIM(CONCAT(r.nombres, ' ', r.apellidos)) AS repartidor_nombre,
                    r.telefono AS repartidor_telefono, r.placa_vehiculo,
                    DATE_FORMAT(e.fecha_hora_creacion, '%d/%m/%Y %H:%i') AS fecha_creacion_fmt,
                    DATE_FORMAT(COALESCE(e.fecha_hora_programada, e.fecha_hora_despacho, e.fecha_hora_creacion), '%d/%m/%Y') AS fecha_salida_fmt
                FROM envios e
                INNER JOIN sedes s ON e.sede_id = s.id
                INNER JOIN clinicas c ON s.clinica_id = c.id
                INNER JOIN repartidores r ON e.repartidor_id = r.id
                WHERE e.id = ?
            ");
            $stmt->execute([$envioId]);
            $envio = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$envio) {
                echo json_encode(['status' => 'error', 'message' => 'Despacho no encontrado.']);
                exit;
            }

            // Casos contenidos
            $stmtCasos = $pdo->prepare("
                SELECT 
                    ot.id, ot.codigo_qr, ot.cantidad_piezas_exacta,
                    sv.nombre AS servicio_nombre,
                    TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente_nombre
                FROM ordenes_trabajo ot
                INNER JOIN servicios sv ON ot.servicio_id = sv.id
                INNER JOIN pacientes p ON ot.paciente_id = p.id
                WHERE ot.envio_id = ?
            ");
            $stmtCasos->execute([$envioId]);
            $casos = $stmtCasos->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'envio'  => $envio,
                'casos'  => $casos
            ]);
            break;

        // =========================================================================
        // 7. DETALLES PARA ETIQUETA QR DE CAJA PEQUEÑA (IMPRESIÓN)
        // =========================================================================
        case 'etiqueta_caja_pequena':
            $ordenId = (int)($_POST['orden_id'] ?? 0);
            if ($ordenId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de orden requerido.']);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT 
                    ot.id, ot.codigo_qr, ot.cantidad_piezas_exacta, ot.estatus_actual,
                    DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y') AS fecha_ingreso_fmt,
                    sv.nombre AS servicio_nombre, sv.correlativo,
                    c.razon_social AS clinica_nombre,
                    TRIM(CONCAT(p.primer_nombre, ' ', COALESCE(p.segundo_nombre, ''), ' ', p.primer_apellido)) AS paciente_nombre,
                    CONCAT(p.tipo_documento, '-', p.numero_documento) AS paciente_documento,
                    TRIM(CONCAT(prot.primer_nombre, ' ', prot.primer_apellido)) AS protesista_nombre,
                    e.codigo_qr AS master_box_qr
                FROM ordenes_trabajo ot
                INNER JOIN servicios sv ON ot.servicio_id = sv.id
                INNER JOIN clinicas c ON ot.clinica_id = c.id
                INNER JOIN pacientes p ON ot.paciente_id = p.id
                LEFT JOIN usuarios prot ON ot.protesista_id = prot.id
                LEFT JOIN envios e ON ot.envio_id = e.id
                WHERE ot.id = ?
            ");
            $stmt->execute([$ordenId]);
            $caso = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$caso) {
                echo json_encode(['status' => 'error', 'message' => 'Caso dental no encontrado.']);
                exit;
            }

            echo json_encode([
                'status' => 'success',
                'caso'   => $caso
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
            break;
    }

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => errorSeguro($e)]);
}
