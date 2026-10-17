<?php
/**
 * =========================================================================
 * app/views/ventas/factura-digital.php  —  FACTURA DIGITAL (página pública imprimible)
 * =========================================================================
 * Es la página que se abre desde el enlace que se envía por WhatsApp:
 *        https://tu-dominio/factura-digital/<codigo>
 *
 *  - NO requiere iniciar sesión: el <codigo> son 32 caracteres aleatorios (imposibles de adivinar).
 *    Es un código del documento, NO un token de WhatsApp ni de ninguna API.
 *  - Sirve para facturas, prefacturas, notas de crédito y notas de entrega ya emitidas.
 *  - Está pensada para imprimirse o "Guardar como PDF" desde el navegador (botón superior).
 *  - Los borradores no se pueden ver por este enlace.
 *  - Con ?print=1 abre directamente el cuadro de impresión.
 * La variable $codigo la crea el router (routes.php) a partir de la URL.
 */
require_once __DIR__ . '/../../helpers/finanzas.php';

header('X-Robots-Tag: noindex, nofollow');   // que Google no indexe facturas

$codigo = (string)($codigo ?? '');
$doc = null;
if (preg_match('/^[a-f0-9]{32}$/', $codigo)) {
    try {
        $db  = conexion();
        $doc = cargarDocumento($db, null, $codigo);
        $cfg = configTodo($db);
    } catch (Throwable $e) {
        error_log('[CNSB_LAB] factura-digital: ' . $e->getMessage());
        $doc = null;
    }
}
if (!$doc || $doc['estatus'] === 'BORRADOR') {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    return;
}

$esFactura  = $doc['tipo'] === 'FACTURA';
$anulado    = $doc['estatus'] === 'ANULADO';
$pagado     = $doc['estatus'] === 'PAGADO';
$sello      = $anulado ? 'ANULADO' : ($pagado ? 'PAGADO' : ($doc['estatus'] === 'CONVERTIDO' ? 'CONVERTIDO' : ''));
$empresa    = $cfg['empresa_razon_social'] ?? 'CNSB LAB';
$autoPrint  = isset($_GET['print']) && $_GET['print'] === '1';
$titulo     = nombreTipoDocumento($doc['tipo']) . ' ' . $doc['numero'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= h($titulo) ?> · <?= h($empresa) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f7; font-family: "Segoe UI", system-ui, -apple-system, sans-serif; color: #1e293b; font-size: 14px; }
        .barra { max-width: 820px; margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-end; padding: 0 12px; }
        .btn { background: #4f46e5; color: #fff; border: 0; padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .btn.sec { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .hoja { position: relative; max-width: 820px; margin: 12px auto 32px; background: #fff; padding: 36px 40px; border-radius: 12px; box-shadow: 0 4px 24px rgba(15,23,42,.08); overflow: hidden; }
        .cab { display: flex; justify-content: space-between; gap: 20px; border-bottom: 3px solid #4f46e5; padding-bottom: 16px; }
        .cab h1 { margin: 0; font-size: 22px; color: #4f46e5; letter-spacing: .5px; }
        .cab .doc { text-align: right; }
        .cab .doc .tipo { font-size: 13px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px; }
        .cab .doc .num { font-size: 24px; font-weight: 800; }
        .peq { font-size: 12px; color: #64748b; line-height: 1.5; }
        .cliente { margin: 20px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .caja { background: #f8fafc; border-radius: 8px; padding: 12px 14px; }
        .caja b { display: block; font-size: 11px; text-transform: uppercase; color: #94a3b8; letter-spacing: .6px; margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #4f46e5; color: #fff; text-align: left; padding: 8px 10px; font-size: 12px; text-transform: uppercase; }
        td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .r { text-align: right; } .c { text-align: center; }
        .totales { margin: 18px 0 0 auto; width: 320px; }
        .totales div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totales .gran { border-top: 2px solid #1e293b; margin-top: 4px; padding-top: 8px; font-size: 17px; font-weight: 800; }
        .totales .bs { color: #4f46e5; font-weight: 700; }
        .pagos { margin-top: 20px; }
        .pagos h3 { font-size: 12px; text-transform: uppercase; color: #64748b; margin: 0 0 6px; }
        .sello { position: absolute; top: 120px; right: 50px; transform: rotate(-14deg); border: 4px solid; padding: 6px 22px; font-size: 34px; font-weight: 900; letter-spacing: 3px; opacity: .22; }
        .sello.anulado { color: #dc2626; border-color: #dc2626; } .sello.pagado { color: #16a34a; border-color: #16a34a; } .sello.conv { color: #7c3aed; border-color: #7c3aed; }
        .pie { margin-top: 28px; padding-top: 12px; border-top: 1px dashed #cbd5e1; text-align: center; }
        @media (max-width: 640px) { .hoja { padding: 20px 16px; } .cliente { grid-template-columns: 1fr; } .cab { flex-direction: column; } .cab .doc { text-align: left; } .totales { width: 100%; } }
        @media print { body { background: #fff; } .barra { display: none; } .hoja { box-shadow: none; margin: 0; max-width: none; padding: 0; border-radius: 0; } }
    </style>
</head>
<body>
    <div class="barra">
        <button class="btn sec" onclick="window.print()">Imprimir</button>
        <button class="btn" onclick="window.print()">Guardar como PDF</button>
    </div>

    <div class="hoja">
        <?php if ($sello): ?>
            <div class="sello <?= $anulado ? 'anulado' : ($pagado ? 'pagado' : 'conv') ?>"><?= h($sello) ?></div>
        <?php endif; ?>

        <div class="cab">
            <div>
                <h1><?= h($empresa) ?></h1>
                <div class="peq">
                    <?php if (!empty($cfg['empresa_rif'])): ?>RIF: <?= h($cfg['empresa_rif']) ?><br><?php endif; ?>
                    <?php if (!empty($cfg['empresa_direccion'])): ?><?= h($cfg['empresa_direccion']) ?><br><?php endif; ?>
                    <?php if (!empty($cfg['empresa_telefono'])): ?>Tel: <?= h($cfg['empresa_telefono']) ?><?php endif; ?>
                    <?php if (!empty($cfg['empresa_email'])): ?> · <?= h($cfg['empresa_email']) ?><?php endif; ?>
                </div>
            </div>
            <div class="doc">
                <div class="tipo"><?= h(nombreTipoDocumento($doc['tipo'])) ?></div>
                <div class="num"><?= h($doc['numero']) ?></div>
                <?php if (!empty($doc['numero_control'])): ?><div class="peq">N° de control: <?= h($doc['numero_control']) ?></div><?php endif; ?>
                <div class="peq">Fecha: <?= h(date('d/m/Y', strtotime($doc['fecha_emision']))) ?></div>
                <?php if ($esFactura && $doc['condicion_pago'] === 'CREDITO'): ?>
                    <div class="peq">Vence: <?= h(date('d/m/Y', strtotime($doc['fecha_vencimiento']))) ?> (crédito <?= (int)$doc['dias_credito'] ?> días)</div>
                <?php elseif ($esFactura): ?><div class="peq">Condición: contado</div><?php endif; ?>
                <?php if (!empty($doc['origen_numero'])): ?><div class="peq">Ref.: <?= h(nombreTipoDocumento($doc['origen_tipo'])) ?> <?= h($doc['origen_numero']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="cliente">
            <div class="caja"><b>Cliente</b><?= h($doc['clinica_nombre']) ?><br>
                <span class="peq"><?= h($doc['clinica_tipo_doc']) ?>-<?= h($doc['clinica_num_doc']) ?><?php if ($doc['sede_nombre']): ?> · Sede: <?= h($doc['sede_nombre']) ?><?php endif; ?></span></div>
            <div class="caja"><b>Dirección</b><span class="peq"><?= h($doc['clinica_direccion'] ?: '—') ?></span></div>
        </div>

        <table>
            <thead><tr><th>Descripción</th><th class="c">Cant.</th><th class="r">Precio USD</th><th class="c">Desc.</th><th class="r">Total USD</th></tr></thead>
            <tbody>
            <?php foreach ($doc['items'] as $it): ?>
                <tr>
                    <td><?= h($it['descripcion']) ?><?php if (!(int)$it['aplica_iva']): ?> <span class="peq">(exento)</span><?php endif; ?></td>
                    <td class="c"><?= h(rtrim(rtrim(number_format((float)$it['cantidad'], 2, ',', '.'), '0'), ',')) ?></td>
                    <td class="r"><?= h(fmtNum($it['precio_unitario_usd'])) ?></td>
                    <td class="c"><?= (float)$it['descuento_pct'] > 0 ? h(fmtNum($it['descuento_pct'], 0)) . '%' : '—' ?></td>
                    <td class="r"><?= h(fmtNum($it['total_usd'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totales">
            <?php if ((float)$doc['base_exenta_usd'] > 0): ?><div><span>Base exenta</span><span><?= h(fmtUsd($doc['base_exenta_usd'])) ?></span></div><?php endif; ?>
            <div><span>Base imponible</span><span><?= h(fmtUsd($doc['base_imponible_usd'])) ?></span></div>
            <div><span>IVA (<?= h(fmtNum($doc['iva_porcentaje'], 0)) ?>%)</span><span><?= h(fmtUsd($doc['iva_usd'])) ?></span></div>
            <div class="gran"><span>TOTAL USD</span><span><?= h(fmtUsd($doc['total_usd'])) ?></span></div>
            <div class="bs"><span>TOTAL Bs. (tasa <?= h(fmtNum($doc['tasa_cambio'], 4)) ?>)</span><span><?= h(fmtBs($doc['total_bs'])) ?></span></div>
            <?php if ($esFactura && !$anulado): ?>
                <div style="margin-top:6px;font-weight:700;color:<?= (float)$doc['saldo_usd'] > 0.004 ? '#b45309' : '#16a34a' ?>">
                    <span>Saldo pendiente</span><span><?= h(fmtUsd($doc['saldo_usd'])) ?></span></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($doc['pagos'])): ?>
            <div class="pagos"><h3>Pagos recibidos</h3>
                <table><tbody>
                <?php foreach ($doc['pagos'] as $p): ?>
                    <tr><td><?= h($p['numero']) ?></td><td><?= h(date('d/m/Y', strtotime($p['fecha_pago']))) ?></td><td><?= h($p['metodo']) ?></td><td class="r"><?= h(fmtUsd($p['monto_usd'])) ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
        <?php endif; ?>

        <?php if (!empty($doc['observaciones'])): ?><p class="peq" style="margin-top:16px"><b>Observaciones:</b> <?= nl2br(h($doc['observaciones'])) ?></p><?php endif; ?>

        <div class="pie peq"><?= h($cfg['leyenda_documento'] ?? '') ?></div>
    </div>

    <?php if ($autoPrint): ?><script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script><?php endif; ?>
</body>
</html>
