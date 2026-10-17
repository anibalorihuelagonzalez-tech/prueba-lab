<?php
/**
 * =========================================================================
 * app/helpers/finanzas.php  —  NÚCLEO DE LÓGICA DE VENTAS Y CONTABILIDAD
 * =========================================================================
 * Aquí vive todo lo que NO es pantalla: cálculos de impuestos, tasas de cambio,
 * numeración de documentos y, sobre todo, la CONTABILIZACIÓN AUTOMÁTICA
 * (cada factura, nota de crédito y cobro genera su asiento contable).
 *
 * Convenciones del sistema:
 *  - Los precios de los servicios están en USD (así están en la tabla servicios).
 *  - La contabilidad se lleva en BOLÍVARES (VES). Cada documento guarda la tasa
 *    de cambio del día (Bs por 1 USD) y sus totales en ambas monedas.
 *  - Todas las funciones que escriben datos deben ejecutarse dentro de una
 *    transacción (use enTransaccion()). Si algo falla, no se guarda NADA.
 *  - Los errores "de negocio" (validaciones) se lanzan con NegocioException:
 *    su mensaje SÍ se muestra al usuario. Cualquier otro error se oculta
 *    (ver errorSeguro() en seguridad.php).
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/seguridad.php';

/** Error de validación / regla de negocio: el mensaje es apto para mostrarse al usuario. */
if (!class_exists('NegocioException')) {
    class NegocioException extends Exception {}
}

// ===========================================================================
// 1. UTILIDADES NUMÉRICAS Y DE FORMATO
// ===========================================================================

/** Redondea a 2 decimales (dinero). */
function r2($n): float { return round((float)$n, 2); }

/** Formatea un número como dinero con separador de miles: 1.234,56 (estilo venezolano). */
function fmtNum($n, int $dec = 2): string { return number_format((float)$n, $dec, ',', '.'); }

/** "$ 1.234,56" */
function fmtUsd($n): string { return '$ ' . fmtNum($n); }

/** "Bs. 1.234,56" */
function fmtBs($n): string { return 'Bs. ' . fmtNum($n); }

/** Escapa texto para imprimirlo en HTML de forma segura (evita XSS). */
function h($t): string { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); }

/** Valida una fecha 'Y-m-d'. Devuelve la fecha o lanza NegocioException. */
function fechaValida(?string $f, string $nombre = 'La fecha'): string
{
    $f = trim((string)$f);
    $d = DateTime::createFromFormat('Y-m-d', $f);
    if (!$d || $d->format('Y-m-d') !== $f) {
        throw new NegocioException("{$nombre} no es válida (formato AAAA-MM-DD).");
    }
    return $f;
}

/**
 * Lee los datos que envía el navegador: mezcla GET, POST y el cuerpo JSON
 * (las pantallas nuevas envían JSON porque llevan listas de ítems).
 */
function entrada(): array
{
    static $e = null;
    if ($e === null) {
        $e = array_merge($_GET, $_POST);
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $j = json_decode($raw, true);
            if (is_array($j)) {
                $e = array_merge($e, $j);
            }
        }
    }
    return $e;
}

/** Ejecuta $fn dentro de una transacción (commit si todo sale bien, rollback si algo falla). */
function enTransaccion(PDO $db, callable $fn)
{
    if ($db->inTransaction()) {
        return $fn();
    }
    $db->beginTransaction();
    try {
        $r = $fn();
        $db->commit();
        return $r;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

// ===========================================================================
// 2. CONFIGURACIÓN (tabla `configuracion`, clave/valor)
// ===========================================================================

/** Devuelve todas las configuraciones como arreglo clave => valor (con caché por petición). */
function configTodo(PDO $db, bool $recargar = false): array
{
    static $cache = null;
    if ($cache === null || $recargar) {
        $cache = [];
        foreach ($db->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $cache[$f['clave']] = $f['valor'];
        }
    }
    return $cache;
}

function configGet(PDO $db, string $clave, ?string $defecto = null): ?string
{
    $c = configTodo($db);
    return array_key_exists($clave, $c) && $c[$clave] !== null ? $c[$clave] : $defecto;
}

function configSet(PDO $db, string $clave, string $valor): void
{
    $st = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $st->execute([$clave, $valor]);
    configTodo($db, true); // refresca la caché
}

// ===========================================================================
// 3. TASA DE CAMBIO
// ===========================================================================

/**
 * Busca la tasa (cuántos Bs vale 1 USD) para una fecha.
 * Orden de búsqueda:
 *   1) tasa activa cuya vigencia cubre la fecha,
 *   2) última tasa activa registrada (aunque su vigencia ya venció),
 *   3) la inversa (VES->USD) si solo existe esa.
 * Devuelve ['tasa'=>float, 'fuente'=>string, 'desde'=>string, 'exacta'=>bool].
 */
function tasaInfo(PDO $db, string $origen = 'USD', string $destino = 'VES', ?string $fecha = null): array
{
    if ($origen === $destino) {
        return ['tasa' => 1.0, 'fuente' => 'N/A', 'desde' => '', 'exacta' => true];
    }
    $fecha = $fecha ?: date('Y-m-d');

    $ids = [];
    foreach ($db->query("SELECT id, codigo FROM monedas WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC) as $m) {
        $ids[strtoupper($m['codigo'])] = (int)$m['id'];
    }
    if (!isset($ids[$origen]) || !isset($ids[$destino])) {
        throw new NegocioException("No existen las monedas {$origen}/{$destino} en el sistema (Configuraciones > Monedas).");
    }

    $buscar = function (int $o, int $d, bool $exacta) use ($db, $fecha) {
        if ($exacta) {
            $sql = "SELECT tasa, fuente, vigente_desde FROM tasas_cambio
                    WHERE moneda_origen_id = ? AND moneda_destino_id = ? AND is_active = 1 AND deleted_at IS NULL
                      AND vigente_desde <= ? AND (vigente_hasta IS NULL OR vigente_hasta >= ?)
                    ORDER BY vigente_desde DESC, id DESC LIMIT 1";
            $p = [$o, $d, $fecha . ' 23:59:59', $fecha . ' 00:00:00'];
        } else {
            $sql = "SELECT tasa, fuente, vigente_desde FROM tasas_cambio
                    WHERE moneda_origen_id = ? AND moneda_destino_id = ? AND is_active = 1 AND deleted_at IS NULL
                    ORDER BY vigente_desde DESC, id DESC LIMIT 1";
            $p = [$o, $d];
        }
        $st = $db->prepare($sql);
        $st->execute($p);
        return $st->fetch(PDO::FETCH_ASSOC);
    };

    $o = $ids[$origen];
    $d = $ids[$destino];

    foreach ([true, false] as $exacta) {
        $f = $buscar($o, $d, $exacta);
        if ($f && (float)$f['tasa'] > 0) {
            return ['tasa' => (float)$f['tasa'], 'fuente' => (string)$f['fuente'], 'desde' => (string)$f['vigente_desde'], 'exacta' => $exacta];
        }
    }
    // Inversa
    $f = $buscar($d, $o, false);
    if ($f && (float)$f['tasa'] > 0) {
        return ['tasa' => round(1 / (float)$f['tasa'], 8), 'fuente' => (string)$f['fuente'] . ' (inversa)', 'desde' => (string)$f['vigente_desde'], 'exacta' => false];
    }
    throw new NegocioException("No hay tasa de cambio {$origen}→{$destino} registrada. Regístrala en Configuraciones > Tasa de Cambio.");
}

function tasaVigente(PDO $db, string $origen = 'USD', string $destino = 'VES', ?string $fecha = null): float
{
    return tasaInfo($db, $origen, $destino, $fecha)['tasa'];
}

// ===========================================================================
// 4. CORRELATIVOS (numeración)
// ===========================================================================

/**
 * Devuelve el siguiente número de una serie (ej: 'FACTURA' -> 'FAC-000012').
 * El incremento es atómico (LAST_INSERT_ID) y, dentro de una transacción, la fila
 * queda bloqueada hasta el commit: dos usuarios nunca obtienen el mismo número.
 */
function siguienteNumero(PDO $db, string $clave): string
{
    $up = $db->prepare("UPDATE correlativos SET ultimo = LAST_INSERT_ID(ultimo + 1) WHERE clave = ?");
    $up->execute([$clave]);
    if ($up->rowCount() === 0) {
        throw new NegocioException("La serie de numeración '{$clave}' no existe. Ejecuta la migración database/01_ventas_contabilidad.sql.");
    }
    $n = (int)$db->query("SELECT LAST_INSERT_ID()")->fetchColumn();
    $st = $db->prepare("SELECT prefijo, digitos FROM correlativos WHERE clave = ?");
    $st->execute([$clave]);
    $c = $st->fetch(PDO::FETCH_ASSOC);
    return $c['prefijo'] . '-' . str_pad((string)$n, (int)$c['digitos'], '0', STR_PAD_LEFT);
}

// ===========================================================================
// 5. PERÍODOS Y CUENTAS CONTABLES
// ===========================================================================

/** Obtiene (y crea si no existe) el período contable del mes de la fecha. */
function periodoDeFecha(PDO $db, string $fecha): array
{
    $anio = (int)substr($fecha, 0, 4);
    $mes  = (int)substr($fecha, 5, 2);
    $st = $db->prepare("SELECT * FROM periodos_contables WHERE anio = ? AND mes = ?");
    $st->execute([$anio, $mes]);
    $p = $st->fetch(PDO::FETCH_ASSOC);
    if (!$p) {
        $db->prepare("INSERT IGNORE INTO periodos_contables (anio, mes, estatus) VALUES (?, ?, 'ABIERTO')")->execute([$anio, $mes]);
        $st->execute([$anio, $mes]);
        $p = $st->fetch(PDO::FETCH_ASSOC);
    }
    return $p;
}

/** Exige que el mes de la fecha esté ABIERTO. Devuelve el id del período. */
function exigirPeriodoAbierto(PDO $db, string $fecha): int
{
    $p = periodoDeFecha($db, $fecha);
    if ($p['estatus'] !== 'ABIERTO') {
        $mm = str_pad((string)$p['mes'], 2, '0', STR_PAD_LEFT);
        throw new NegocioException("El período contable {$mm}/{$p['anio']} está CERRADO. No se pueden registrar movimientos en esa fecha.");
    }
    return (int)$p['id'];
}

/** Id de una cuenta a partir de su código (ej: '1.1.02.001'). */
function cuentaIdPorCodigo(PDO $db, string $codigo): int
{
    $st = $db->prepare("SELECT id FROM plan_cuentas WHERE codigo = ? LIMIT 1");
    $st->execute([$codigo]);
    $id = $st->fetchColumn();
    if (!$id) {
        throw new NegocioException("La cuenta contable con código {$codigo} no existe en el Plan de Cuentas.");
    }
    return (int)$id;
}

/**
 * Id de una "cuenta de sistema" (las que el programa usa en los asientos automáticos).
 * Claves: cta_cxc, cta_ingresos, cta_devoluciones, cta_iva_debito, cta_iva_credito,
 *         cta_igtf, cta_dif_ganancia, cta_dif_perdida, cta_cxp, cta_resultado_ejercicio...
 * Los códigos se configuran en Contabilidad > Configuración.
 */
function cuentaSistema(PDO $db, string $clave): int
{
    $codigo = configGet($db, $clave);
    if (!$codigo) {
        throw new NegocioException("Falta configurar la cuenta de sistema '{$clave}' (Contabilidad > Configuración).");
    }
    return cuentaIdPorCodigo($db, $codigo);
}

// ===========================================================================
// 6. ASIENTOS CONTABLES
// ===========================================================================

/**
 * Crea un asiento contable validado y devuelve su id.
 *
 * @param array $cab    fecha (Y-m-d), tipo, concepto, usuario_id, [referencia_tabla], [referencia_id],
 *                      [tasa_cambio], [asiento_original_id]
 * @param array $lineas cada una: ['cuenta_id'=>int, 'debe'=>float, 'haber'=>float, 'descripcion'=>string]
 *
 * Reglas (partida doble): Debe total = Haber total; mínimo 2 líneas; cada línea
 * solo Debe o solo Haber; solo cuentas activas que permitan movimientos;
 * el período contable debe estar abierto.
 */
function crearAsiento(PDO $db, array $cab, array $lineas): int
{
    return enTransaccion($db, function () use ($db, $cab, $lineas) {
        $fecha = fechaValida($cab['fecha'] ?? '', 'La fecha del asiento');

        // 1) Limpiar y validar líneas
        $limpias = [];
        $totD = 0.0;
        $totH = 0.0;
        foreach ($lineas as $l) {
            $d = r2($l['debe'] ?? 0);
            $hh = r2($l['haber'] ?? 0);
            if ($d < 0 || $hh < 0) {
                throw new NegocioException('Los montos del asiento no pueden ser negativos.');
            }
            if ($d == 0 && $hh == 0) {
                continue; // línea vacía: se ignora
            }
            if ($d > 0 && $hh > 0) {
                throw new NegocioException('Una línea del asiento no puede tener Debe y Haber a la vez.');
            }
            if (empty($l['cuenta_id'])) {
                throw new NegocioException('Todas las líneas del asiento deben tener una cuenta.');
            }
            $limpias[] = ['cuenta_id' => (int)$l['cuenta_id'], 'debe' => $d, 'haber' => $hh, 'descripcion' => mb_substr((string)($l['descripcion'] ?? ''), 0, 255)];
            $totD += $d;
            $totH += $hh;
        }
        if (count($limpias) < 2) {
            throw new NegocioException('Un asiento necesita al menos dos líneas con monto.');
        }
        $totD = r2($totD);
        $totH = r2($totH);
        if (abs($totD - $totH) > 0.004) {
            throw new NegocioException('El asiento no cuadra: Debe ' . fmtNum($totD) . ' ≠ Haber ' . fmtNum($totH) . '.');
        }

        // 2) Validar cuentas (existen, activas, permiten movimiento)
        $ids = array_values(array_unique(array_column($limpias, 'cuenta_id')));
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $st = $db->prepare("SELECT id, codigo, nombre, permite_movimiento, is_active FROM plan_cuentas WHERE id IN ({$marcas})");
        $st->execute($ids);
        $cuentas = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $cuentas[(int)$c['id']] = $c;
        }
        foreach ($ids as $id) {
            if (!isset($cuentas[$id])) {
                throw new NegocioException('Una de las cuentas del asiento no existe.');
            }
            $c = $cuentas[$id];
            if (!(int)$c['is_active']) {
                throw new NegocioException("La cuenta {$c['codigo']} - {$c['nombre']} está inactiva.");
            }
            if (!(int)$c['permite_movimiento']) {
                throw new NegocioException("La cuenta {$c['codigo']} - {$c['nombre']} es una cuenta de grupo y no admite movimientos. Usa una subcuenta.");
            }
        }

        // 3) Período abierto + número + inserción
        $periodoId = exigirPeriodoAbierto($db, $fecha);
        $numero = siguienteNumero($db, 'ASIENTO');

        $ins = $db->prepare("INSERT INTO asientos_contables
            (numero, fecha, periodo_id, tipo, concepto, referencia_tabla, referencia_id, tasa_cambio,
             total_debe, total_haber, estatus, asiento_original_id, usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?, 'ACTIVO', ?, ?)");
        $ins->execute([
            $numero, $fecha, $periodoId, $cab['tipo'] ?? 'MANUAL', mb_substr((string)$cab['concepto'], 0, 255),
            $cab['referencia_tabla'] ?? null, $cab['referencia_id'] ?? null, (float)($cab['tasa_cambio'] ?? 1),
            $totD, $totH, $cab['asiento_original_id'] ?? null, (int)$cab['usuario_id'],
        ]);
        $asientoId = (int)$db->lastInsertId();

        $insD = $db->prepare("INSERT INTO asientos_contables_detalles (asiento_id, cuenta_id, debe, haber, descripcion) VALUES (?,?,?,?,?)");
        foreach ($limpias as $l) {
            $insD->execute([$asientoId, $l['cuenta_id'], $l['debe'], $l['haber'], $l['descripcion']]);
        }
        return $asientoId;
    });
}

/**
 * Anula un asiento creando su asiento INVERSO (nunca se borra nada: queda rastro).
 * El original pasa a ANULADO y apunta al reverso.
 */
function anularAsiento(PDO $db, int $asientoId, string $motivo, int $usuarioId, ?string $fecha = null): int
{
    return enTransaccion($db, function () use ($db, $asientoId, $motivo, $usuarioId, $fecha) {
        $st = $db->prepare("SELECT * FROM asientos_contables WHERE id = ? FOR UPDATE");
        $st->execute([$asientoId]);
        $a = $st->fetch(PDO::FETCH_ASSOC);
        if (!$a) {
            throw new NegocioException('El asiento no existe.');
        }
        if ($a['estatus'] === 'ANULADO' || $a['asiento_original_id']) {
            throw new NegocioException('Ese asiento ya está anulado o es un asiento de anulación.');
        }
        $det = $db->prepare("SELECT cuenta_id, debe, haber, descripcion FROM asientos_contables_detalles WHERE asiento_id = ?");
        $det->execute([$asientoId]);
        $lineas = [];
        foreach ($det->fetchAll(PDO::FETCH_ASSOC) as $l) {
            // Se invierten Debe y Haber
            $lineas[] = ['cuenta_id' => $l['cuenta_id'], 'debe' => $l['haber'], 'haber' => $l['debe'], 'descripcion' => $l['descripcion']];
        }
        $nuevo = crearAsiento($db, [
            'fecha'               => $fecha ?: date('Y-m-d'),
            'tipo'                => 'ANULACION',
            'concepto'            => 'ANULACIÓN del asiento ' . $a['numero'] . ': ' . $motivo,
            'referencia_tabla'    => $a['referencia_tabla'],
            'referencia_id'       => $a['referencia_id'],
            'tasa_cambio'         => $a['tasa_cambio'],
            'asiento_original_id' => $asientoId,
            'usuario_id'          => $usuarioId,
        ], $lineas);
        $db->prepare("UPDATE asientos_contables SET estatus = 'ANULADO', asiento_reverso_id = ? WHERE id = ?")->execute([$nuevo, $asientoId]);
        return $nuevo;
    });
}

// ===========================================================================
// 7. CÁLCULO DE DOCUMENTOS DE VENTA
// ===========================================================================

/**
 * Calcula líneas y totales de un documento de venta.
 *
 * @param array $items   cada uno: descripcion, cantidad, precio_unitario_usd, descuento_pct, aplica_iva,
 *                       [orden_trabajo_id], [servicio_id]
 * @param float $ivaPct  alícuota de IVA (ej. 16)
 * @param float $tasa    Bs por 1 USD
 * @return array         'items' normalizados + subtotales/IVA/total en USD y en Bs
 */
function calcularDocumento(array $items, float $ivaPct, float $tasa): array
{
    if ($tasa <= 0) {
        throw new NegocioException('La tasa de cambio debe ser mayor que cero.');
    }
    $norm = [];
    $exentaUsd = 0.0;
    $imponibleUsd = 0.0;

    foreach ($items as $it) {
        $desc = trim((string)($it['descripcion'] ?? ''));
        $cant = (float)($it['cantidad'] ?? 0);
        $prec = (float)($it['precio_unitario_usd'] ?? 0);
        $dpct = (float)($it['descuento_pct'] ?? 0);
        if ($desc === '') {
            throw new NegocioException('Cada línea del documento necesita una descripción.');
        }
        if ($cant <= 0) {
            throw new NegocioException("La cantidad de \"{$desc}\" debe ser mayor que cero.");
        }
        if ($prec < 0) {
            throw new NegocioException("El precio de \"{$desc}\" no puede ser negativo.");
        }
        if ($dpct < 0 || $dpct > 100) {
            throw new NegocioException("El descuento de \"{$desc}\" debe estar entre 0 y 100%.");
        }
        $total = r2($cant * $prec * (1 - $dpct / 100));
        $aplicaIva = !empty($it['aplica_iva']) ? 1 : 0;

        $norm[] = [
            'orden_trabajo_id'    => !empty($it['orden_trabajo_id']) ? (int)$it['orden_trabajo_id'] : null,
            'servicio_id'         => !empty($it['servicio_id']) ? (int)$it['servicio_id'] : null,
            'descripcion'         => mb_substr($desc, 0, 255),
            'cantidad'            => $cant,
            'precio_unitario_usd' => r2($prec),
            'descuento_pct'       => $dpct,
            'aplica_iva'          => $aplicaIva,
            'total_usd'           => $total,
        ];
        if ($aplicaIva) { $imponibleUsd += $total; } else { $exentaUsd += $total; }
    }
    if (!$norm) {
        throw new NegocioException('El documento debe tener al menos una línea.');
    }

    $exentaUsd    = r2($exentaUsd);
    $imponibleUsd = r2($imponibleUsd);
    $ivaUsd       = r2($imponibleUsd * $ivaPct / 100);
    $totalUsd     = r2($exentaUsd + $imponibleUsd + $ivaUsd);

    // En bolívares el IVA se calcula sobre la base en Bs (así se declara fiscalmente).
    $exentaBs    = r2($exentaUsd * $tasa);
    $imponibleBs = r2($imponibleUsd * $tasa);
    $ivaBs       = r2($imponibleBs * $ivaPct / 100);
    $totalBs     = r2($exentaBs + $imponibleBs + $ivaBs);

    return [
        'items'              => $norm,
        'subtotal_usd'       => r2($exentaUsd + $imponibleUsd),
        'base_exenta_usd'    => $exentaUsd,
        'base_imponible_usd' => $imponibleUsd,
        'iva_porcentaje'     => $ivaPct,
        'iva_usd'            => $ivaUsd,
        'total_usd'          => $totalUsd,
        'base_exenta_bs'     => $exentaBs,
        'base_imponible_bs'  => $imponibleBs,
        'iva_bs'             => $ivaBs,
        'total_bs'           => $totalBs,
    ];
}

/** Recalcula el estatus de una FACTURA según su saldo (EMITIDO / PARCIAL / PAGADO). */
function recalcularEstatusFactura(PDO $db, int $docId): void
{
    $st = $db->prepare("SELECT estatus, total_usd, saldo_usd FROM ventas_documentos WHERE id = ? AND tipo = 'FACTURA'");
    $st->execute([$docId]);
    $d = $st->fetch(PDO::FETCH_ASSOC);
    if (!$d || in_array($d['estatus'], ['BORRADOR', 'ANULADO'], true)) {
        return;
    }
    $saldo = (float)$d['saldo_usd'];
    if ($saldo <= 0.004) {
        $nuevo = 'PAGADO';
    } elseif ($saldo < (float)$d['total_usd'] - 0.004) {
        $nuevo = 'PARCIAL';
    } else {
        $nuevo = 'EMITIDO';
    }
    $db->prepare("UPDATE ventas_documentos SET estatus = ? WHERE id = ?")->execute([$nuevo, $docId]);
}

// ===========================================================================
// 8. CONTABILIZACIÓN AUTOMÁTICA
// ===========================================================================

/**
 * FACTURA emitida:
 *      Debe  Cuentas por Cobrar Clientes ........ total Bs
 *      Haber Ingresos por Servicios ............. base Bs (exenta + imponible)
 *      Haber IVA Débito Fiscal .................. IVA Bs
 */
function contabilizarFactura(PDO $db, array $doc, int $usuarioId): int
{
    $cxc  = cuentaSistema($db, 'cta_cxc');
    $ing  = cuentaSistema($db, 'cta_ingresos');
    $lineas = [
        ['cuenta_id' => $cxc, 'debe' => $doc['total_bs'], 'haber' => 0, 'descripcion' => 'Factura ' . $doc['numero']],
        ['cuenta_id' => $ing, 'debe' => 0, 'haber' => r2($doc['base_exenta_bs'] + $doc['base_imponible_bs']), 'descripcion' => 'Servicios facturados'],
    ];
    if ((float)$doc['iva_bs'] > 0) {
        $lineas[] = ['cuenta_id' => cuentaSistema($db, 'cta_iva_debito'), 'debe' => 0, 'haber' => $doc['iva_bs'], 'descripcion' => 'IVA débito fiscal'];
    }
    return crearAsiento($db, [
        'fecha' => $doc['fecha_emision'], 'tipo' => 'VENTA',
        'concepto' => 'Factura ' . $doc['numero'] . ' - ' . ($doc['clinica_nombre'] ?? ''),
        'referencia_tabla' => 'ventas_documentos', 'referencia_id' => (int)$doc['id'],
        'tasa_cambio' => $doc['tasa_cambio'], 'usuario_id' => $usuarioId,
    ], $lineas);
}

/**
 * NOTA DE CRÉDITO emitida (reduce la deuda de la clínica):
 *      Debe  Devoluciones y Descuentos en Ventas . base Bs
 *      Debe  IVA Débito Fiscal ................... IVA Bs
 *      Haber Cuentas por Cobrar Clientes ......... total Bs
 */
function contabilizarNotaCredito(PDO $db, array $doc, int $usuarioId): int
{
    $lineas = [
        ['cuenta_id' => cuentaSistema($db, 'cta_devoluciones'), 'debe' => r2($doc['base_exenta_bs'] + $doc['base_imponible_bs']), 'haber' => 0, 'descripcion' => 'Devolución / descuento'],
        ['cuenta_id' => cuentaSistema($db, 'cta_cxc'), 'debe' => 0, 'haber' => $doc['total_bs'], 'descripcion' => 'Nota de crédito ' . $doc['numero']],
    ];
    if ((float)$doc['iva_bs'] > 0) {
        $lineas[] = ['cuenta_id' => cuentaSistema($db, 'cta_iva_debito'), 'debe' => $doc['iva_bs'], 'haber' => 0, 'descripcion' => 'IVA débito reversado'];
    }
    return crearAsiento($db, [
        'fecha' => $doc['fecha_emision'], 'tipo' => 'NOTA_CREDITO',
        'concepto' => 'Nota de crédito ' . $doc['numero'] . ' - ' . ($doc['clinica_nombre'] ?? ''),
        'referencia_tabla' => 'ventas_documentos', 'referencia_id' => (int)$doc['id'],
        'tasa_cambio' => $doc['tasa_cambio'], 'usuario_id' => $usuarioId,
    ], $lineas);
}

/**
 * COBRO (ingreso de dinero):
 *      Debe  Caja/Banco del método de pago ....... (monto + IGTF) en Bs
 *      Haber Cuentas por Cobrar Clientes ......... deuda saldada, valuada a la tasa de la FACTURA
 *      Haber IGTF por Pagar ...................... IGTF en Bs
 *      Haber/Debe Diferencial cambiario .......... la diferencia (ganancia o pérdida por cambio de tasa)
 *
 * Ejemplo: factura de $100 emitida con tasa 36 (CxC = 3.600 Bs). Se cobra con tasa 40
 * en efectivo Bs = 4.000 Bs -> Haber CxC 3.600 y Haber Ganancia cambiaria 400.
 */
function contabilizarCobro(PDO $db, array $pago, array $aplicaciones, array $metodo, int $usuarioId): int
{
    $cxcBs = 0.0;
    foreach ($aplicaciones as $a) {
        $cxcBs += (float)$a['monto_bs_historico'];
    }
    $cxcBs   = r2($cxcBs);
    $igtfBs  = r2($pago['igtf_bs']);
    $debeBs  = r2((float)$pago['monto_bs'] + $igtfBs);   // lo que realmente entró a caja/banco
    $dif     = r2($debeBs - $cxcBs - $igtfBs);           // >0 ganancia, <0 pérdida

    $lineas = [
        ['cuenta_id' => (int)$metodo['cuenta_contable_id'], 'debe' => $debeBs, 'haber' => 0, 'descripcion' => $metodo['nombre'] . ($pago['referencia'] ? ' ref. ' . $pago['referencia'] : '')],
        ['cuenta_id' => cuentaSistema($db, 'cta_cxc'), 'debe' => 0, 'haber' => $cxcBs, 'descripcion' => 'Cobro ' . $pago['numero']],
    ];
    if ($igtfBs > 0) {
        $lineas[] = ['cuenta_id' => cuentaSistema($db, 'cta_igtf'), 'debe' => 0, 'haber' => $igtfBs, 'descripcion' => 'IGTF cobrado'];
    }
    if ($dif > 0.004) {
        $lineas[] = ['cuenta_id' => cuentaSistema($db, 'cta_dif_ganancia'), 'debe' => 0, 'haber' => $dif, 'descripcion' => 'Ganancia por diferencial cambiario'];
    } elseif ($dif < -0.004) {
        $lineas[] = ['cuenta_id' => cuentaSistema($db, 'cta_dif_perdida'), 'debe' => abs($dif), 'haber' => 0, 'descripcion' => 'Pérdida por diferencial cambiario'];
    }
    return crearAsiento($db, [
        'fecha' => $pago['fecha_pago'], 'tipo' => 'COBRO',
        'concepto' => 'Cobro ' . $pago['numero'] . ' - ' . ($pago['clinica_nombre'] ?? ''),
        'referencia_tabla' => 'ventas_pagos', 'referencia_id' => (int)$pago['id'],
        'tasa_cambio' => $pago['tasa_cambio'], 'usuario_id' => $usuarioId,
    ], $lineas);
}

// ===========================================================================
// 9. LECTURA COMPLETA DE UN DOCUMENTO (usada por "ver", WhatsApp y la factura digital)
// ===========================================================================

/** Carga un documento con su clínica, sede, ítems y pagos aplicados. Devuelve null si no existe. */
function cargarDocumento(PDO $db, ?int $id = null, ?string $codigoPublico = null): ?array
{
    if ($id) {
        $st = $db->prepare("SELECT d.*, c.razon_social AS clinica_nombre, c.tipo_documento AS clinica_tipo_doc,
                   c.numero_documento AS clinica_num_doc, c.direccion_fiscal AS clinica_direccion, c.telefono AS clinica_telefono,
                   s.nombre AS sede_nombre, o.numero AS origen_numero, o.tipo AS origen_tipo
            FROM ventas_documentos d
            INNER JOIN clinicas c ON c.id = d.clinica_id
            LEFT JOIN sedes s ON s.id = d.sede_id
            LEFT JOIN ventas_documentos o ON o.id = d.documento_origen_id
            WHERE d.id = ?");
        $st->execute([$id]);
    } else {
        $st = $db->prepare("SELECT d.*, c.razon_social AS clinica_nombre, c.tipo_documento AS clinica_tipo_doc,
                   c.numero_documento AS clinica_num_doc, c.direccion_fiscal AS clinica_direccion, c.telefono AS clinica_telefono,
                   s.nombre AS sede_nombre, o.numero AS origen_numero, o.tipo AS origen_tipo
            FROM ventas_documentos d
            INNER JOIN clinicas c ON c.id = d.clinica_id
            LEFT JOIN sedes s ON s.id = d.sede_id
            LEFT JOIN ventas_documentos o ON o.id = d.documento_origen_id
            WHERE d.codigo_publico = ?");
        $st->execute([(string)$codigoPublico]);
    }
    $doc = $st->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        return null;
    }
    $it = $db->prepare("SELECT i.*, ot.codigo_qr AS orden_codigo,
                               TRIM(CONCAT(p.primer_nombre, ' ', p.primer_apellido)) AS paciente
                        FROM ventas_items i
                        LEFT JOIN ordenes_trabajo ot ON ot.id = i.orden_trabajo_id
                        LEFT JOIN pacientes p ON p.id = ot.paciente_id
                        WHERE i.documento_id = ? ORDER BY i.id");
    $it->execute([$doc['id']]);
    $doc['items'] = $it->fetchAll(PDO::FETCH_ASSOC);

    $pg = $db->prepare("SELECT p.numero, p.fecha_pago, p.moneda_codigo, p.monto, a.monto_usd, m.nombre AS metodo
                        FROM ventas_pagos_aplicaciones a
                        INNER JOIN ventas_pagos p ON p.id = a.pago_id AND p.estatus <> 'ANULADO'
                        INNER JOIN metodos_pago m ON m.id = p.metodo_pago_id
                        WHERE a.documento_id = ? ORDER BY p.fecha_pago, p.id");
    $pg->execute([$doc['id']]);
    $doc['pagos'] = $pg->fetchAll(PDO::FETCH_ASSOC);
    return $doc;
}

// ===========================================================================
// 10. WHATSAPP (sin API ni token: solo se arma el enlace wa.me que abre el chat)
// ===========================================================================

/** Convierte un teléfono local (04129989473) a formato internacional sin '+' (584129989473). */
function normalizarTelefono(string $tel, string $codigoPais = '58'): string
{
    $d = preg_replace('/\D+/', '', $tel);
    if ($d === '') {
        return '';
    }
    if (strpos($d, '00') === 0) {          // 0058412...
        $d = substr($d, 2);
    }
    if (strpos($d, $codigoPais) === 0 && strlen($d) >= 11) {   // ya trae el código de país
        return $d;
    }
    if ($d[0] === '0') {                   // 0412... -> 412...
        $d = substr($d, 1);
    }
    return $codigoPais . $d;
}

/** URL base pública del sistema (APP_URL del .env; si no existe, se deduce de la petición). */
function urlBase(): string
{
    $v = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: '';
    if ($v !== '') {
        return rtrim($v, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/** Enlace público (sin sesión) de la factura digital. */
function enlacePublicoDocumento(string $codigoPublico): string
{
    return urlBase() . '/factura-digital/' . $codigoPublico;
}

/** Reemplaza {variables} en una plantilla de mensaje. */
function llenarPlantilla(string $plantilla, array $vars): string
{
    $map = [];
    foreach ($vars as $k => $v) {
        $map['{' . $k . '}'] = (string)$v;
    }
    return strtr($plantilla, $map);
}

/** Etiqueta legible del tipo de documento. */
function nombreTipoDocumento(string $tipo): string
{
    return [
        'PREFACTURA' => 'Prefactura', 'FACTURA' => 'Factura',
        'NOTA_CREDITO' => 'Nota de Crédito', 'NOTA_ENTREGA' => 'Nota de Entrega',
    ][$tipo] ?? $tipo;
}

/**
 * Arma todo lo necesario para el botón de WhatsApp de un documento:
 * teléfono del cliente, mensaje y las URLs wa.me / WhatsApp Web.
 * NO envía nada: el usuario pulsa el botón y envía el mensaje él mismo desde WhatsApp Web.
 */
function datosWhatsappDocumento(PDO $db, array $doc): array
{
    $cfg = configTodo($db);
    $tel = normalizarTelefono((string)$doc['clinica_telefono'], $cfg['codigo_pais_telefono'] ?? '58');
    $plantilla = $cfg['whatsapp_plantilla_documento'] ?? 'Hola {cliente}, le enviamos su {tipo_documento} {documento}: {enlace}';
    $enlace = enlacePublicoDocumento($doc['codigo_publico']);
    $mensaje = llenarPlantilla($plantilla, [
        'cliente'        => $doc['clinica_nombre'],
        'empresa'        => $cfg['empresa_razon_social'] ?? 'CNSB LAB',
        'tipo_documento' => mb_strtolower(nombreTipoDocumento($doc['tipo'])),
        'documento'      => $doc['numero'] ?: '(borrador)',
        'fecha'          => date('d/m/Y', strtotime($doc['fecha_emision'])),
        'total_usd'      => fmtUsd($doc['total_usd']),
        'total_bs'       => fmtBs($doc['total_bs']),
        'enlace'         => $enlace,
    ]);
    return [
        'telefono'      => $tel,
        'mensaje'       => $mensaje,
        'enlace'        => $enlace,
        'url_wa'        => 'https://wa.me/' . $tel . '?text=' . rawurlencode($mensaje),
        'url_web'       => 'https://web.whatsapp.com/send?phone=' . $tel . '&text=' . rawurlencode($mensaje),
        'enlace_local'  => (bool)preg_match('~^https?://(localhost|127\.|192\.168\.|10\.)~i', $enlace),
    ];
}
