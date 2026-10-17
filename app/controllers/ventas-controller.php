<?php
/**
 * =========================================================================
 * app/controllers/ventas-controller.php  —  MÓDULO DE VENTAS (documentos)
 * =========================================================================
 * Endpoint AJAX (responde JSON) para prefacturas, facturas, notas de crédito
 * y notas de entrega. Acciones (parámetro "action"):
 *
 *   listar             Lista documentos con filtros + indicadores (KPIs)
 *   catalogos          Datos para los formularios (clínicas, servicios, tasa, IVA...)
 *   casos_facturables  Casos (órdenes) de una clínica que aún no se han facturado
 *   ver                Detalle completo de un documento (+ datos de WhatsApp)
 *   guardar            Crea o edita un documento (BORRADOR) y opcionalmente lo emite
 *   emitir             Emite un borrador: asigna número y genera el asiento contable
 *   convertir          Convierte prefactura / nota de entrega en una factura (borrador)
 *   anular             Anula un documento emitido (revierte el asiento contable)
 *   eliminar_borrador  Borra un documento que todavía es BORRADOR
 *   whatsapp           Arma el enlace de WhatsApp para enviar el documento al cliente
 *   actualizar_control Guarda el número de control fiscal en un documento emitido
 *
 * Ciclo de vida:  BORRADOR -> EMITIDO -> (PARCIAL) -> PAGADO   |  ANULADO
 *                 PREFACTURA / NOTA_ENTREGA emitidas pasan a CONVERTIDO al facturarlas.
 */
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR', 'RECEPCIONISTA']);
require_once __DIR__ . '/../helpers/finanzas.php';
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------------------------------------
// Funciones de apoyo de este controlador
// ---------------------------------------------------------------------------

/**
 * Devuelve los códigos de los casos que ya están en OTRA factura emitida
 * (para impedir facturar dos veces el mismo trabajo).
 */
function casosYaFacturados(PDO $db, array $items, int $docIdActual): array
{
    $ids = [];
    foreach ($items as $it) {
        if (!empty($it['orden_trabajo_id'])) {
            $ids[] = (int)$it['orden_trabajo_id'];
        }
    }
    if (!$ids) {
        return [];
    }
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT DISTINCT ot.codigo_qr
            FROM ventas_items vi
            INNER JOIN ventas_documentos vd ON vd.id = vi.documento_id
            INNER JOIN ordenes_trabajo ot ON ot.id = vi.orden_trabajo_id
            WHERE vi.orden_trabajo_id IN ({$marcas})
              AND vd.tipo = 'FACTURA' AND vd.estatus IN ('EMITIDO','PARCIAL','PAGADO')
              AND vd.id <> ?";
    $st = $db->prepare($sql);
    $st->execute(array_merge($ids, [$docIdActual]));
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Crea o actualiza un documento en estado BORRADOR. Devuelve el id.
 * Aquí se validan cliente, fechas, tasa, líneas y (para notas de crédito) la factura de origen.
 */
function guardarDocumento(PDO $db, array $in, int $usuarioId): int
{
    $tipo = strtoupper(trim((string)($in['tipo'] ?? '')));
    if (!in_array($tipo, ['PREFACTURA', 'FACTURA', 'NOTA_CREDITO', 'NOTA_ENTREGA'], true)) {
        throw new NegocioException('Tipo de documento no válido.');
    }
    $id = (int)($in['id'] ?? 0);

    // --- Cliente (clínica) y sede ---
    $clinicaId = (int)($in['clinica_id'] ?? 0);
    if ($clinicaId <= 0) {
        throw new NegocioException('Debes seleccionar la clínica (cliente).');
    }
    $st = $db->prepare("SELECT id FROM clinicas WHERE id = ?");
    $st->execute([$clinicaId]);
    if (!$st->fetchColumn()) {
        throw new NegocioException('La clínica seleccionada no existe.');
    }
    $sedeId = !empty($in['sede_id']) ? (int)$in['sede_id'] : null;
    if ($sedeId) {
        $st = $db->prepare("SELECT id FROM sedes WHERE id = ? AND clinica_id = ?");
        $st->execute([$sedeId, $clinicaId]);
        if (!$st->fetchColumn()) {
            throw new NegocioException('La sede no pertenece a la clínica seleccionada.');
        }
    }

    $fecha  = fechaValida($in['fecha_emision'] ?? date('Y-m-d'), 'La fecha de emisión');
    $cfg    = configTodo($db);
    $ivaPct = (float)($cfg['iva_porcentaje'] ?? 16);
    $tasa   = (float)($in['tasa_cambio'] ?? 0);
    if ($tasa <= 0) {
        $tasa = tasaVigente($db, 'USD', 'VES', $fecha);
    }

    // --- Nota de crédito: debe apuntar a una factura emitida de la misma clínica ---
    $origenId = !empty($in['documento_origen_id']) ? (int)$in['documento_origen_id'] : null;
    $origen = null;
    if ($tipo === 'NOTA_CREDITO') {
        if (!$origenId) {
            throw new NegocioException('La nota de crédito debe estar asociada a una factura.');
        }
        $st = $db->prepare("SELECT * FROM ventas_documentos WHERE id = ? AND tipo = 'FACTURA'");
        $st->execute([$origenId]);
        $origen = $st->fetch(PDO::FETCH_ASSOC);
        if (!$origen || !in_array($origen['estatus'], ['EMITIDO', 'PARCIAL', 'PAGADO'], true)) {
            throw new NegocioException('La factura de origen no existe o no está vigente.');
        }
        if ((int)$origen['clinica_id'] !== $clinicaId) {
            throw new NegocioException('La factura de origen pertenece a otra clínica.');
        }
        // La nota de crédito usa la MISMA tasa e IVA de la factura original.
        $tasa   = (float)$origen['tasa_cambio'];
        $ivaPct = (float)$origen['iva_porcentaje'];
    }

    $calc = calcularDocumento($in['items'] ?? [], $ivaPct, $tasa);

    if ($tipo === 'NOTA_CREDITO' && $calc['total_usd'] > (float)$origen['saldo_usd'] + 0.004) {
        throw new NegocioException('La nota de crédito (' . fmtUsd($calc['total_usd']) . ') excede el saldo pendiente de la factura '
            . $origen['numero'] . ' (' . fmtUsd($origen['saldo_usd']) . '). Si ya fue cobrada, primero anula el cobro.');
    }

    // --- Condición de pago (solo las facturas pueden ser a crédito) ---
    $cond = (($in['condicion_pago'] ?? 'CONTADO') === 'CREDITO' && $tipo === 'FACTURA') ? 'CREDITO' : 'CONTADO';
    $dias = $cond === 'CREDITO' ? max(0, (int)($in['dias_credito'] ?? ($cfg['dias_credito_default'] ?? 15))) : 0;
    $venc = date('Y-m-d', strtotime($fecha . " +{$dias} days"));

    $campos = [
        'clinica_id'          => $clinicaId,
        'sede_id'             => $sedeId,
        'documento_origen_id' => $origenId ?: (!empty($in['documento_origen_id']) ? (int)$in['documento_origen_id'] : null),
        'fecha_emision'       => $fecha,
        'fecha_vencimiento'   => $venc,
        'condicion_pago'      => $cond,
        'dias_credito'        => $dias,
        'tasa_cambio'         => $tasa,
        'subtotal_usd'        => $calc['subtotal_usd'],
        'base_exenta_usd'     => $calc['base_exenta_usd'],
        'base_imponible_usd'  => $calc['base_imponible_usd'],
        'iva_porcentaje'      => $calc['iva_porcentaje'],
        'iva_usd'             => $calc['iva_usd'],
        'total_usd'           => $calc['total_usd'],
        'base_exenta_bs'      => $calc['base_exenta_bs'],
        'base_imponible_bs'   => $calc['base_imponible_bs'],
        'iva_bs'              => $calc['iva_bs'],
        'total_bs'            => $calc['total_bs'],
        'observaciones'       => trim((string)($in['observaciones'] ?? '')) ?: null,
        'numero_control'      => trim((string)($in['numero_control'] ?? '')) ?: null,
    ];

    if ($id > 0) {
        $st = $db->prepare("SELECT tipo, estatus FROM ventas_documentos WHERE id = ? FOR UPDATE");
        $st->execute([$id]);
        $d = $st->fetch(PDO::FETCH_ASSOC);
        if (!$d) {
            throw new NegocioException('El documento no existe.');
        }
        if ($d['estatus'] !== 'BORRADOR') {
            throw new NegocioException('Solo se pueden modificar documentos en BORRADOR.');
        }
        if ($d['tipo'] !== $tipo) {
            throw new NegocioException('No se puede cambiar el tipo de un documento.');
        }
        $sql = "UPDATE ventas_documentos SET " . implode('=?, ', array_keys($campos)) . "=? WHERE id = ?";
        $db->prepare($sql)->execute(array_merge(array_values($campos), [$id]));
        $db->prepare("DELETE FROM ventas_items WHERE documento_id = ?")->execute([$id]);
    } else {
        $campos['tipo']           = $tipo;
        $campos['usuario_id']     = $usuarioId;
        $campos['codigo_publico'] = bin2hex(random_bytes(16)); // código aleatorio del enlace público
        $campos['saldo_usd']      = 0;
        $sql = "INSERT INTO ventas_documentos (" . implode(',', array_keys($campos)) . ") VALUES ("
             . implode(',', array_fill(0, count($campos), '?')) . ")";
        $db->prepare($sql)->execute(array_values($campos));
        $id = (int)$db->lastInsertId();
    }

    $insI = $db->prepare("INSERT INTO ventas_items
        (documento_id, orden_trabajo_id, servicio_id, descripcion, cantidad, precio_unitario_usd, descuento_pct, aplica_iva, total_usd)
        VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($calc['items'] as $it) {
        $insI->execute([$id, $it['orden_trabajo_id'], $it['servicio_id'], $it['descripcion'], $it['cantidad'],
                        $it['precio_unitario_usd'], $it['descuento_pct'], $it['aplica_iva'], $it['total_usd']]);
    }
    return $id;
}

/**
 * Emite un BORRADOR: asigna número, deja el saldo por cobrar (facturas) y
 * genera el asiento contable (facturas y notas de crédito).
 */
function emitirDocumento(PDO $db, int $id, int $usuarioId): array
{
    $st = $db->prepare("SELECT * FROM ventas_documentos WHERE id = ? FOR UPDATE");
    $st->execute([$id]);
    $d = $st->fetch(PDO::FETCH_ASSOC);
    if (!$d) {
        throw new NegocioException('El documento no existe.');
    }
    if ($d['estatus'] !== 'BORRADOR') {
        throw new NegocioException('Ese documento ya fue emitido.');
    }
    $items = $db->prepare("SELECT orden_trabajo_id FROM ventas_items WHERE documento_id = ?");
    $items->execute([$id]);
    $lineas = $items->fetchAll(PDO::FETCH_ASSOC);
    if (!$lineas) {
        throw new NegocioException('El documento no tiene líneas.');
    }

    $tipo = $d['tipo'];

    // Una factura no puede incluir casos ya facturados en otra factura vigente
    if ($tipo === 'FACTURA') {
        $dup = casosYaFacturados($db, $lineas, $id);
        if ($dup) {
            throw new NegocioException('Estos casos ya están en otra factura emitida: ' . implode(', ', $dup) . '. Quítalos de la factura.');
        }
    }

    // Nota de crédito: reduce el saldo de la factura de origen
    if ($tipo === 'NOTA_CREDITO') {
        $st = $db->prepare("SELECT * FROM ventas_documentos WHERE id = ? FOR UPDATE");
        $st->execute([$d['documento_origen_id']]);
        $o = $st->fetch(PDO::FETCH_ASSOC);
        if (!$o || !in_array($o['estatus'], ['EMITIDO', 'PARCIAL', 'PAGADO'], true)) {
            throw new NegocioException('La factura de origen ya no está vigente.');
        }
        if ((float)$d['total_usd'] > (float)$o['saldo_usd'] + 0.004) {
            throw new NegocioException('La nota de crédito excede el saldo pendiente de la factura ' . $o['numero'] . '.');
        }
    }

    $numero = siguienteNumero($db, $tipo);
    $saldo  = ($tipo === 'FACTURA') ? $d['total_usd'] : 0;
    $db->prepare("UPDATE ventas_documentos SET numero = ?, estatus = 'EMITIDO', saldo_usd = ? WHERE id = ?")
       ->execute([$numero, $saldo, $id]);

    $doc = cargarDocumento($db, $id);

    if ($tipo === 'FACTURA') {
        $asiento = contabilizarFactura($db, $doc, $usuarioId);
        $db->prepare("UPDATE ventas_documentos SET asiento_id = ? WHERE id = ?")->execute([$asiento, $id]);
        // Si la factura viene de una prefactura / nota de entrega, esa queda marcada como CONVERTIDA
        if (!empty($d['documento_origen_id'])) {
            $db->prepare("UPDATE ventas_documentos SET estatus = 'CONVERTIDO'
                          WHERE id = ? AND tipo IN ('PREFACTURA','NOTA_ENTREGA') AND estatus = 'EMITIDO'")
               ->execute([$d['documento_origen_id']]);
        }
    } elseif ($tipo === 'NOTA_CREDITO') {
        $asiento = contabilizarNotaCredito($db, $doc, $usuarioId);
        $db->prepare("UPDATE ventas_documentos SET asiento_id = ? WHERE id = ?")->execute([$asiento, $id]);
        $db->prepare("UPDATE ventas_documentos SET saldo_usd = saldo_usd - ? WHERE id = ?")
           ->execute([$d['total_usd'], $d['documento_origen_id']]);
        recalcularEstatusFactura($db, (int)$d['documento_origen_id']);
    }
    return ['id' => $id, 'numero' => $numero];
}

// ---------------------------------------------------------------------------
// Enrutado de acciones
// ---------------------------------------------------------------------------
$in        = entrada();
$accion    = (string)($in['action'] ?? 'listar');
$db        = conexion();
$usuarioId = (int)Auth::id();

try {
    switch ($accion) {

        // ===================================================================
        // LISTAR + KPIs
        // ===================================================================
        case 'listar': {
            $where = [];
            $par   = [];
            if (!empty($in['tipo']))       { $where[] = 'd.tipo = ?';        $par[] = (string)$in['tipo']; }
            if (!empty($in['estatus']))    { $where[] = 'd.estatus = ?';     $par[] = (string)$in['estatus']; }
            if (!empty($in['clinica_id'])) { $where[] = 'd.clinica_id = ?';  $par[] = (int)$in['clinica_id']; }
            if (!empty($in['desde']))      { $where[] = 'd.fecha_emision >= ?'; $par[] = fechaValida($in['desde'], 'La fecha "desde"'); }
            if (!empty($in['hasta']))      { $where[] = 'd.fecha_emision <= ?'; $par[] = fechaValida($in['hasta'], 'La fecha "hasta"'); }
            if (!empty($in['q'])) {
                $where[] = '(d.numero LIKE ? OR c.razon_social LIKE ?)';
                $like = '%' . trim((string)$in['q']) . '%';
                $par[] = $like;
                $par[] = $like;
            }
            $sql = "SELECT d.id, d.tipo, d.numero, d.numero_control, d.estatus, d.clinica_id, c.razon_social AS clinica_nombre,
                           d.fecha_emision, d.fecha_vencimiento, d.condicion_pago, d.tasa_cambio,
                           d.total_usd, d.total_bs, d.saldo_usd, d.documento_origen_id, o.numero AS origen_numero,
                           (SELECT COALESCE(SUM(n.total_usd),0) FROM ventas_documentos n
                             WHERE n.documento_origen_id = d.id AND n.tipo = 'NOTA_CREDITO' AND n.estatus = 'EMITIDO') AS nc_usd,
                           CASE WHEN d.tipo = 'FACTURA' AND d.estatus IN ('EMITIDO','PARCIAL')
                                 AND d.saldo_usd > 0 AND d.fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END AS vencida
                    FROM ventas_documentos d
                    INNER JOIN clinicas c ON c.id = d.clinica_id
                    LEFT JOIN ventas_documentos o ON o.id = d.documento_origen_id"
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . " ORDER BY d.id DESC LIMIT 500";
            $st = $db->prepare($sql);
            $st->execute($par);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);

            $k = $db->query("SELECT
                    COALESCE(SUM(CASE WHEN tipo='FACTURA' AND estatus IN ('EMITIDO','PARCIAL','PAGADO') THEN total_usd END),0) AS facturado_usd,
                    COALESCE(SUM(CASE WHEN tipo='FACTURA' AND estatus IN ('EMITIDO','PARCIAL') THEN saldo_usd END),0) AS por_cobrar_usd,
                    COALESCE(SUM(CASE WHEN tipo='FACTURA' AND estatus IN ('EMITIDO','PARCIAL') AND saldo_usd>0 AND fecha_vencimiento < CURDATE() THEN saldo_usd END),0) AS vencido_usd,
                    COALESCE(SUM(CASE WHEN tipo='FACTURA' AND estatus='BORRADOR' THEN 1 END),0) AS facturas_borrador,
                    COALESCE(SUM(CASE WHEN tipo='PREFACTURA' AND estatus='EMITIDO' THEN 1 END),0) AS prefacturas_abiertas
                FROM ventas_documentos")->fetch(PDO::FETCH_ASSOC);

            responderJson(['status' => 'success', 'data' => $rows, 'kpis' => $k]);
        }

        // ===================================================================
        // CATÁLOGOS PARA LOS FORMULARIOS
        // ===================================================================
        case 'catalogos': {
            $cfg = configTodo($db);
            $clinicas = $db->query("SELECT id, razon_social, tipo_documento, numero_documento, telefono
                                    FROM clinicas WHERE estatus = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
            $sedes = $db->query("SELECT id, clinica_id, nombre FROM sedes WHERE estatus = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
            $servicios = $db->query("SELECT id, nombre, precio FROM servicios WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
            try {
                $tasa = tasaInfo($db, 'USD', 'VES');
            } catch (NegocioException $e) {
                $tasa = ['tasa' => 0, 'fuente' => '', 'desde' => '', 'exacta' => false, 'aviso' => $e->getMessage()];
            }
            responderJson([
                'status' => 'success', 'clinicas' => $clinicas, 'sedes' => $sedes, 'servicios' => $servicios,
                'tasa' => $tasa,
                'iva_porcentaje' => (float)($cfg['iva_porcentaje'] ?? 16),
                'dias_credito_default' => (int)($cfg['dias_credito_default'] ?? 15),
                'hoy' => date('Y-m-d'),
            ]);
        }

        // ===================================================================
        // CASOS DE UNA CLÍNICA PENDIENTES DE FACTURAR
        // ===================================================================
        case 'casos_facturables': {
            $clinicaId = (int)($in['clinica_id'] ?? 0);
            if ($clinicaId <= 0) {
                throw new NegocioException('Selecciona una clínica.');
            }
            $st = $db->prepare("SELECT ot.id, ot.codigo_qr, ot.sede_id, ot.servicio_id, ot.cantidad_piezas_exacta, ot.estatus_actual,
                       DATE_FORMAT(ot.fecha_ingreso, '%d/%m/%Y') AS fecha,
                       COALESCE(sv.nombre, 'Servicio dental') AS servicio_nombre, COALESCE(sv.precio, 0) AS precio,
                       TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente,
                       (SELECT COUNT(*) FROM ventas_items vi2 INNER JOIN ventas_documentos vd2 ON vd2.id = vi2.documento_id
                         WHERE vi2.orden_trabajo_id = ot.id AND vd2.tipo IN ('PREFACTURA','NOTA_ENTREGA')
                           AND vd2.estatus IN ('BORRADOR','EMITIDO')) AS en_prefactura
                FROM ordenes_trabajo ot
                LEFT JOIN servicios sv ON sv.id = ot.servicio_id
                LEFT JOIN pacientes p ON p.id = ot.paciente_id
                WHERE ot.clinica_id = ?
                  AND NOT EXISTS (SELECT 1 FROM ventas_items vi INNER JOIN ventas_documentos vd ON vd.id = vi.documento_id
                                  WHERE vi.orden_trabajo_id = ot.id AND vd.tipo = 'FACTURA'
                                    AND vd.estatus IN ('EMITIDO','PARCIAL','PAGADO'))
                ORDER BY ot.id DESC");
            $st->execute([$clinicaId]);
            responderJson(['status' => 'success', 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        }

        // ===================================================================
        // VER DETALLE
        // ===================================================================
        case 'ver': {
            $doc = cargarDocumento($db, (int)($in['id'] ?? 0));
            if (!$doc) {
                throw new NegocioException('Documento no encontrado.');
            }
            $st = $db->prepare("SELECT COALESCE(SUM(total_usd),0) FROM ventas_documentos
                                WHERE documento_origen_id = ? AND tipo = 'NOTA_CREDITO' AND estatus = 'EMITIDO'");
            $st->execute([$doc['id']]);
            $doc['nc_usd'] = (float)$st->fetchColumn();
            $doc['enlace_publico'] = enlacePublicoDocumento($doc['codigo_publico']);
            responderJson(['status' => 'success', 'documento' => $doc]);
        }

        // ===================================================================
        // GUARDAR (crear / editar borrador) y opcionalmente EMITIR
        // ===================================================================
        case 'guardar': {
            $res = enTransaccion($db, function () use ($db, $in, $usuarioId) {
                $id = guardarDocumento($db, $in, $usuarioId);
                $numero = null;
                if (!empty($in['emitir'])) {
                    $numero = emitirDocumento($db, $id, $usuarioId)['numero'];
                }
                return ['id' => $id, 'numero' => $numero];
            });
            responderJson(['status' => 'success', 'id' => $res['id'], 'numero' => $res['numero'],
                'message' => $res['numero'] ? "Documento {$res['numero']} emitido correctamente." : 'Borrador guardado.']);
        }

        // ===================================================================
        // EMITIR UN BORRADOR EXISTENTE
        // ===================================================================
        case 'emitir': {
            $res = enTransaccion($db, fn() => emitirDocumento($db, (int)($in['id'] ?? 0), $usuarioId));
            responderJson(['status' => 'success', 'id' => $res['id'], 'numero' => $res['numero'],
                'message' => "Documento {$res['numero']} emitido correctamente."]);
        }

        // ===================================================================
        // CONVERTIR PREFACTURA / NOTA DE ENTREGA EN FACTURA (queda en borrador para revisar)
        // ===================================================================
        case 'convertir': {
            $res = enTransaccion($db, function () use ($db, $in, $usuarioId) {
                $id = (int)($in['id'] ?? 0);
                $doc = cargarDocumento($db, $id);
                if (!$doc || !in_array($doc['tipo'], ['PREFACTURA', 'NOTA_ENTREGA'], true)) {
                    throw new NegocioException('Solo se pueden convertir prefacturas y notas de entrega.');
                }
                if ($doc['estatus'] !== 'EMITIDO') {
                    throw new NegocioException('Solo se puede convertir un documento EMITIDO (no anulado ni ya convertido).');
                }
                // Si ya existe un borrador de factura creado desde este documento, se reutiliza
                $st = $db->prepare("SELECT id FROM ventas_documentos WHERE documento_origen_id = ? AND tipo = 'FACTURA' AND estatus = 'BORRADOR' LIMIT 1");
                $st->execute([$id]);
                $existente = $st->fetchColumn();
                if ($existente) {
                    return (int)$existente;
                }
                $items = [];
                foreach ($doc['items'] as $it) {
                    $items[] = [
                        'orden_trabajo_id' => $it['orden_trabajo_id'], 'servicio_id' => $it['servicio_id'],
                        'descripcion' => $it['descripcion'], 'cantidad' => $it['cantidad'],
                        'precio_unitario_usd' => $it['precio_unitario_usd'], 'descuento_pct' => $it['descuento_pct'],
                        'aplica_iva' => $it['aplica_iva'],
                    ];
                }
                return guardarDocumento($db, [
                    'tipo' => 'FACTURA', 'clinica_id' => $doc['clinica_id'], 'sede_id' => $doc['sede_id'],
                    'documento_origen_id' => $id, 'fecha_emision' => date('Y-m-d'), 'items' => $items,
                    'observaciones' => 'Generada desde ' . nombreTipoDocumento($doc['tipo']) . ' ' . $doc['numero'],
                ], $usuarioId);
            });
            responderJson(['status' => 'success', 'id' => $res, 'message' => 'Se creó la factura en borrador. Revísala y emítela.']);
        }

        // ===================================================================
        // ANULAR (solo administradores)
        // ===================================================================
        case 'anular': {
            if (!Auth::isAdmin()) {
                responderJson(['status' => 'error', 'message' => 'Solo un administrador puede anular documentos.'], 403);
            }
            enTransaccion($db, function () use ($db, $in, $usuarioId) {
                $id = (int)($in['id'] ?? 0);
                $motivo = trim((string)($in['motivo'] ?? ''));
                if (mb_strlen($motivo) < 3) {
                    throw new NegocioException('Indica el motivo de la anulación.');
                }
                $st = $db->prepare("SELECT * FROM ventas_documentos WHERE id = ? FOR UPDATE");
                $st->execute([$id]);
                $d = $st->fetch(PDO::FETCH_ASSOC);
                if (!$d) {
                    throw new NegocioException('El documento no existe.');
                }
                if ($d['estatus'] === 'BORRADOR') {
                    throw new NegocioException('Un borrador no se anula: se elimina.');
                }
                if (in_array($d['estatus'], ['ANULADO', 'CONVERTIDO'], true)) {
                    throw new NegocioException('Este documento ya está ' . strtolower($d['estatus']) . '.');
                }

                if ($d['tipo'] === 'FACTURA') {
                    // No se puede anular si tiene cobros o notas de crédito vigentes
                    $st = $db->prepare("SELECT COUNT(*) FROM ventas_pagos_aplicaciones a
                                        INNER JOIN ventas_pagos p ON p.id = a.pago_id AND p.estatus <> 'ANULADO'
                                        WHERE a.documento_id = ?");
                    $st->execute([$id]);
                    if ((int)$st->fetchColumn() > 0) {
                        throw new NegocioException('La factura tiene cobros aplicados. Anula primero esos cobros.');
                    }
                    $st = $db->prepare("SELECT COUNT(*) FROM ventas_documentos WHERE documento_origen_id = ? AND tipo = 'NOTA_CREDITO' AND estatus = 'EMITIDO'");
                    $st->execute([$id]);
                    if ((int)$st->fetchColumn() > 0) {
                        throw new NegocioException('La factura tiene notas de crédito vigentes. Anúlalas primero.');
                    }
                    if ($d['asiento_id']) {
                        anularAsiento($db, (int)$d['asiento_id'], 'Anulación de factura ' . $d['numero'] . ' - ' . $motivo, $usuarioId);
                    }
                    // Si venía de una prefactura/nota de entrega, esa vuelve a quedar disponible
                    if (!empty($d['documento_origen_id'])) {
                        $db->prepare("UPDATE ventas_documentos SET estatus = 'EMITIDO' WHERE id = ? AND estatus = 'CONVERTIDO'")
                           ->execute([$d['documento_origen_id']]);
                    }
                    $db->prepare("UPDATE ventas_documentos SET estatus = 'ANULADO', saldo_usd = 0, motivo_anulacion = ? WHERE id = ?")
                       ->execute([$motivo, $id]);

                } elseif ($d['tipo'] === 'NOTA_CREDITO') {
                    // Devuelve el saldo a la factura de origen
                    $db->prepare("UPDATE ventas_documentos SET saldo_usd = saldo_usd + ? WHERE id = ? AND estatus <> 'ANULADO'")
                       ->execute([$d['total_usd'], $d['documento_origen_id']]);
                    recalcularEstatusFactura($db, (int)$d['documento_origen_id']);
                    if ($d['asiento_id']) {
                        anularAsiento($db, (int)$d['asiento_id'], 'Anulación de nota de crédito ' . $d['numero'] . ' - ' . $motivo, $usuarioId);
                    }
                    $db->prepare("UPDATE ventas_documentos SET estatus = 'ANULADO', motivo_anulacion = ? WHERE id = ?")->execute([$motivo, $id]);

                } else { // PREFACTURA / NOTA_ENTREGA (no tienen asiento contable)
                    $db->prepare("UPDATE ventas_documentos SET estatus = 'ANULADO', motivo_anulacion = ? WHERE id = ?")->execute([$motivo, $id]);
                }
            });
            responderJson(['status' => 'success', 'message' => 'Documento anulado correctamente.']);
        }

        // ===================================================================
        // ELIMINAR BORRADOR
        // ===================================================================
        case 'eliminar_borrador': {
            $st = $db->prepare("DELETE FROM ventas_documentos WHERE id = ? AND estatus = 'BORRADOR'");
            $st->execute([(int)($in['id'] ?? 0)]);
            if ($st->rowCount() === 0) {
                throw new NegocioException('Solo se pueden eliminar documentos en BORRADOR.');
            }
            responderJson(['status' => 'success', 'message' => 'Borrador eliminado.']);
        }

        // ===================================================================
        // WHATSAPP: enlace para enviar el documento (SIN API, SIN TOKEN)
        // ===================================================================
        case 'whatsapp': {
            $doc = cargarDocumento($db, (int)($in['id'] ?? 0));
            if (!$doc) {
                throw new NegocioException('Documento no encontrado.');
            }
            if (in_array($doc['estatus'], ['BORRADOR', 'ANULADO'], true)) {
                throw new NegocioException('Solo se pueden enviar documentos emitidos y vigentes.');
            }
            responderJson(['status' => 'success', 'whatsapp' => datosWhatsappDocumento($db, $doc),
                           'cliente' => $doc['clinica_nombre'], 'numero' => $doc['numero']]);
        }

        // ===================================================================
        // NÚMERO DE CONTROL FISCAL (lo asigna la imprenta digital / máquina fiscal)
        // ===================================================================
        case 'actualizar_control': {
            $st = $db->prepare("UPDATE ventas_documentos SET numero_control = ? WHERE id = ? AND estatus <> 'BORRADOR'");
            $st->execute([trim((string)($in['numero_control'] ?? '')) ?: null, (int)($in['id'] ?? 0)]);
            responderJson(['status' => 'success', 'message' => 'Número de control actualizado.']);
        }

        default:
            responderJson(['status' => 'error', 'message' => 'Acción no permitida.'], 400);
    }

} catch (NegocioException $e) {
    responderJson(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)], 500);
}
