<?php
/**
 * =========================================================================
 * app/views/ventas/documentos.php  —  PANTALLA ÚNICA DE DOCUMENTOS DE VENTA
 * =========================================================================
 * Una sola pantalla sirve para 4 URLs (ver routes.php). El tipo de documento
 * se deduce de la URL:
 *     /ventas/prefacturas    -> PREFACTURA     (cotización previa a facturar)
 *     /ventas/facturas       -> FACTURA        (genera cuenta por cobrar + asiento contable)
 *     /ventas/notas-credito  -> NOTA_CREDITO   (reduce la deuda de una factura)
 *     /ventas/notas-entrega  -> NOTA_ENTREGA   (constancia de entrega, sin efecto contable)
 *     /ventas/facturacion    -> todos los tipos
 *
 * La lógica vive en el componente Alpine "docsApp()" (al final del archivo) y habla
 * con app/controllers/ventas-controller.php.
 *
 * ENVÍO POR WHATSAPP (sin API ni token): el botón abre WhatsApp Web / wa.me con el
 * mensaje ya escrito y el enlace público de la factura digital; tú solo pulsas "enviar".
 */
$rutaActual = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$mapa = [
    '/ventas/prefacturas'   => ['PREFACTURA',   'Prefacturas',          'file-search',   'prefactura'],
    '/ventas/facturas'      => ['FACTURA',      'Facturas',             'receipt',       'factura'],
    '/ventas/notas-credito' => ['NOTA_CREDITO', 'Notas de Crédito',     'file-minus',    'nota de crédito'],
    '/ventas/notas-entrega' => ['NOTA_ENTREGA', 'Notas de Entrega',     'package-check', 'nota de entrega'],
    '/ventas/facturacion'   => ['',             'Facturación y Documentos', 'files',     'documento'],
];
[$tipoVista, $tituloPagina, $iconoPagina, $nombreSingular] = $mapa[$rutaActual] ?? ['', 'Facturación y Documentos', 'files', 'documento'];
$esAdmin = Auth::isAdmin();
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important}</style>

<div x-data="docsApp('<?= $tipoVista ?>', <?= $esAdmin ? 'true' : 'false' ?>)" x-init="init()" x-cloak class="space-y-6">

    <!-- ================= INDICADORES ================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Facturado (vigente)</p>
            <p class="text-xl font-black text-slate-800 mt-1" x-text="usd(kpis.facturado_usd)"></p>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Por cobrar</p>
            <p class="text-xl font-black text-amber-600 mt-1" x-text="usd(kpis.por_cobrar_usd)"></p>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Vencido</p>
            <p class="text-xl font-black text-red-600 mt-1" x-text="usd(kpis.vencido_usd)"></p>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Borradores / Prefacturas abiertas</p>
            <p class="text-xl font-black text-indigo-600 mt-1"><span x-text="kpis.facturas_borrador || 0"></span> / <span x-text="kpis.prefacturas_abiertas || 0"></span></p>
        </div>
    </div>

    <!-- ================= FILTROS + BOTÓN NUEVO ================= -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="text-xs font-semibold text-slate-500">Buscar</label>
                <input type="text" x-model="filtros.q" @keydown.enter="listar()" placeholder="Número o cliente"
                    class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <template x-if="!tipoVista">
                <div>
                    <label class="text-xs font-semibold text-slate-500">Tipo</label>
                    <select x-model="filtros.tipo" @change="listar()" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                        <option value="">Todos</option><option value="PREFACTURA">Prefactura</option><option value="FACTURA">Factura</option>
                        <option value="NOTA_CREDITO">Nota de crédito</option><option value="NOTA_ENTREGA">Nota de entrega</option>
                    </select>
                </div>
            </template>
            <div>
                <label class="text-xs font-semibold text-slate-500">Estatus</label>
                <select x-model="filtros.estatus" @change="listar()" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                    <option value="">Todos</option><option>BORRADOR</option><option>EMITIDO</option><option>PARCIAL</option>
                    <option>PAGADO</option><option>CONVERTIDO</option><option>ANULADO</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">Cliente</label>
                <select x-model="filtros.clinica_id" @change="listar()" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg max-w-[200px]">
                    <option value="">Todos</option>
                    <template x-for="c in cat.clinicas" :key="c.id"><option :value="c.id" x-text="c.razon_social"></option></template>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">Desde</label>
                <input type="date" x-model="filtros.desde" @change="listar()" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">Hasta</label>
                <input type="date" x-model="filtros.hasta" @change="listar()" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
            </div>
            <button @click="listar()" class="px-4 py-2 text-sm font-semibold bg-slate-100 hover:bg-slate-200 rounded-lg">Filtrar</button>

            <!-- Botón NUEVO -->
            <?php if ($tipoVista): ?>
                <button @click="nuevo(tipoVista)" class="px-4 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm">
                    + Nueva <?= htmlspecialchars($nombreSingular) ?>
                </button>
            <?php else: ?>
                <div class="relative" x-data="{o:false}">
                    <button @click="o=!o" class="px-4 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm">+ Nuevo documento ▾</button>
                    <div x-show="o" @click.outside="o=false" class="absolute right-0 mt-1 w-48 bg-white border border-slate-200 rounded-xl shadow-lg z-20 py-1">
                        <button @click="o=false; nuevo('PREFACTURA')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50">Prefactura</button>
                        <button @click="o=false; nuevo('FACTURA')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50">Factura</button>
                        <button @click="o=false; nuevo('NOTA_CREDITO')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50">Nota de crédito</button>
                        <button @click="o=false; nuevo('NOTA_ENTREGA')" class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50">Nota de entrega</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================= TABLA ================= -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-3">Documento</th>
                        <th class="text-left px-4 py-3">Cliente</th>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-right px-4 py-3">Total USD</th>
                        <th class="text-right px-4 py-3">Total Bs</th>
                        <th class="text-right px-4 py-3">Saldo USD</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="r in rows" :key="r.id">
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-800" x-text="r.numero || '(borrador)'"></div>
                                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600" x-text="nombreTipo(r.tipo)"></span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="badge(r)" x-text="r.vencida==1 ? 'VENCIDA' : r.estatus"></span>
                                    <template x-if="parseFloat(r.nc_usd) > 0"><span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-fuchsia-100 text-fuchsia-700" x-text="'NC ' + usd(r.nc_usd)"></span></template>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5" x-show="r.origen_numero" x-text="'Origen: ' + r.origen_numero"></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700" x-text="r.clinica_nombre"></td>
                            <td class="px-4 py-3 text-slate-600">
                                <div x-text="fechaVe(r.fecha_emision)"></div>
                                <div class="text-[11px] text-slate-400" x-show="r.tipo==='FACTURA' && r.condicion_pago==='CREDITO'" x-text="'Vence ' + fechaVe(r.fecha_vencimiento)"></div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold" x-text="usd(r.total_usd)"></td>
                            <td class="px-4 py-3 text-right text-slate-600" x-text="bs(r.total_bs)"></td>
                            <td class="px-4 py-3 text-right font-semibold" :class="parseFloat(r.saldo_usd)>0 ? 'text-amber-600' : 'text-slate-400'"
                                x-text="r.tipo==='FACTURA' ? usd(r.saldo_usd) : '—'"></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button @click="verDetalle(r.id)" class="px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Ver</button>
                                <template x-if="r.estatus==='BORRADOR'">
                                    <span>
                                        <button @click="editar(r.id)" class="px-2.5 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg">Editar</button>
                                        <button @click="emitirBorrador(r)" class="px-2.5 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Emitir</button>
                                        <button @click="eliminarBorrador(r)" class="px-2.5 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg">Borrar</button>
                                    </span>
                                </template>
                                <template x-if="r.estatus!=='BORRADOR' && r.estatus!=='ANULADO'">
                                    <button @click="abrirWhatsApp(r.id)" class="px-2.5 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg" title="Enviar por WhatsApp">WhatsApp</button>
                                </template>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!rows.length && !cargando"><td colspan="7" class="px-4 py-10 text-center text-slate-400">No hay documentos con esos filtros.</td></tr>
                    <tr x-show="cargando"><td colspan="7" class="px-4 py-10 text-center text-slate-400">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- =====================================================================
         MODAL: EDITOR DE DOCUMENTO (crear / editar borrador)
         ===================================================================== -->
    <div x-show="ed.abierto" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl my-6" @click.outside="">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-800" x-text="(ed.id ? 'Editar ' : 'Nueva ') + nombreTipo(ed.tipo).toLowerCase()"></h2>
                <button @click="ed.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>

            <div class="p-6 space-y-5">
                <!-- Cabecera -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-slate-500">Cliente (clínica) *</label>
                        <select x-model="ed.clinica_id" @change="alCambiarClinica()" :disabled="ed.tipo==='NOTA_CREDITO' && ed.documento_origen_id"
                            class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                            <option value="">— Selecciona —</option>
                            <template x-for="c in cat.clinicas" :key="c.id"><option :value="c.id" x-text="c.razon_social + ' (' + c.tipo_documento + '-' + c.numero_documento + ')'"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Sede</label>
                        <select x-model="ed.sede_id" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                            <option value="">— Todas / principal —</option>
                            <template x-for="s in sedesClinica" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Fecha de emisión *</label>
                        <input type="date" x-model="ed.fecha_emision" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Tasa de cambio (Bs por $1) *</label>
                        <input type="number" step="0.0001" min="0" x-model.number="ed.tasa_cambio" :disabled="ed.tipo==='NOTA_CREDITO'"
                            class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                        <p class="text-[11px] mt-1" :class="cat.tasa.exacta ? 'text-slate-400' : 'text-amber-600'"
                           x-text="ed.tipo==='NOTA_CREDITO' ? 'Usa la misma tasa de la factura original.' : (cat.tasa.aviso || (cat.tasa.exacta ? ('Fuente: ' + cat.tasa.fuente) : '⚠ Tasa antigua: actualízala en Configuraciones > Tasa de Cambio'))"></p>
                    </div>
                    <div x-show="ed.tipo==='FACTURA'">
                        <label class="text-xs font-semibold text-slate-500">Condición de pago</label>
                        <div class="flex gap-2 mt-1">
                            <select x-model="ed.condicion_pago" class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                                <option value="CONTADO">Contado</option><option value="CREDITO">Crédito</option>
                            </select>
                            <input type="number" min="0" max="365" x-model.number="ed.dias_credito" x-show="ed.condicion_pago==='CREDITO'" class="w-20 px-2 py-2 text-sm border border-slate-300 rounded-lg" title="Días de crédito">
                        </div>
                    </div>
                    <div x-show="ed.tipo==='FACTURA' || ed.tipo==='NOTA_CREDITO'">
                        <label class="text-xs font-semibold text-slate-500">N° de control (opcional)</label>
                        <input type="text" maxlength="30" x-model="ed.numero_control" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                    </div>
                </div>

                <!-- Nota de crédito: factura de origen -->
                <div x-show="ed.tipo==='NOTA_CREDITO'" class="bg-fuchsia-50 border border-fuchsia-200 rounded-xl p-4">
                    <label class="text-xs font-bold text-fuchsia-700">Factura que se acredita *</label>
                    <select x-model="ed.documento_origen_id" @change="alElegirOrigen()" :disabled="ed.id>0"
                        class="w-full mt-1 px-3 py-2 text-sm border border-fuchsia-300 rounded-lg bg-white">
                        <option value="">— Selecciona la factura —</option>
                        <template x-for="f in facturasOrigen" :key="f.id"><option :value="f.id" x-text="f.numero + ' · saldo ' + usd(f.saldo_usd) + ' · total ' + usd(f.total_usd)"></option></template>
                    </select>
                    <p class="text-xs text-fuchsia-700 mt-2">La nota de crédito no puede superar el saldo pendiente de la factura. Edita cantidades o precios para acreditar solo una parte.</p>
                </div>

                <!-- Líneas -->
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <h3 class="text-sm font-bold text-slate-700">Detalle</h3>
                        <div class="flex gap-2 flex-wrap">
                            <template x-if="ed.tipo!=='NOTA_CREDITO'">
                                <button @click="abrirCasos()" class="px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg">+ Casos del laboratorio</button>
                            </template>
                            <select @change="agregarServicio($event.target.value); $event.target.value=''" class="px-2 py-1.5 text-xs border border-slate-300 rounded-lg">
                                <option value="">+ Servicio del catálogo…</option>
                                <template x-for="s in cat.servicios" :key="s.id"><option :value="s.id" x-text="s.nombre + ' ($' + s.precio + ')'"></option></template>
                            </select>
                            <button @click="agregarLinea()" class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">+ Línea libre</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                <tr><th class="text-left px-3 py-2">Descripción</th><th class="px-2 py-2 w-20">Cant.</th><th class="px-2 py-2 w-28">Precio USD</th>
                                    <th class="px-2 py-2 w-20">Desc. %</th><th class="px-2 py-2 w-14">IVA</th><th class="text-right px-3 py-2 w-28">Total USD</th><th class="w-8"></th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(it, i) in ed.items" :key="i">
                                    <tr>
                                        <td class="px-3 py-1.5"><input type="text" x-model="it.descripcion" maxlength="255" class="w-full px-2 py-1.5 border border-slate-200 rounded-md text-sm"></td>
                                        <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" x-model.number="it.cantidad" class="w-full px-2 py-1.5 border border-slate-200 rounded-md text-sm text-right"></td>
                                        <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" x-model.number="it.precio_unitario_usd" class="w-full px-2 py-1.5 border border-slate-200 rounded-md text-sm text-right"></td>
                                        <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" max="100" x-model.number="it.descuento_pct" class="w-full px-2 py-1.5 border border-slate-200 rounded-md text-sm text-right"></td>
                                        <td class="px-2 py-1.5 text-center"><input type="checkbox" x-model="it.aplica_iva" class="w-4 h-4"></td>
                                        <td class="px-3 py-1.5 text-right font-semibold" x-text="usd(lineaTotal(it))"></td>
                                        <td class="px-1 text-center"><button @click="ed.items.splice(i,1)" class="text-red-500 hover:text-red-700 text-lg leading-none" title="Quitar">&times;</button></td>
                                    </tr>
                                </template>
                                <tr x-show="!ed.items.length"><td colspan="7" class="px-3 py-6 text-center text-slate-400 text-sm">Agrega casos del laboratorio, servicios o líneas libres.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totales + observaciones -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Observaciones</label>
                        <textarea x-model="ed.observaciones" rows="4" maxlength="1000" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></textarea>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-sm space-y-1.5">
                        <div class="flex justify-between"><span class="text-slate-500">Base exenta</span><span x-text="usd(tot.exenta)"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Base imponible</span><span x-text="usd(tot.imponible)"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500" x-text="'IVA ' + ivaPct + '%'"></span><span x-text="usd(tot.iva)"></span></div>
                        <div class="flex justify-between font-black text-base border-t border-slate-200 pt-2"><span>TOTAL USD</span><span x-text="usd(tot.total)"></span></div>
                        <div class="flex justify-between font-bold text-indigo-700"><span>TOTAL Bs</span><span x-text="bs(tot.totalBs)"></span></div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl">
                <button @click="ed.abierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cancelar</button>
                <button @click="guardar(false)" :disabled="ed.guardando" class="px-4 py-2 text-sm font-bold text-indigo-700 bg-indigo-100 hover:bg-indigo-200 rounded-lg disabled:opacity-50">Guardar borrador</button>
                <button @click="guardar(true)" :disabled="ed.guardando" class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg disabled:opacity-50">Guardar y emitir</button>
            </div>
        </div>
    </div>

    <!-- MODAL: CASOS PENDIENTES -->
    <div x-show="casosAbierto" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-bold text-slate-800">Casos pendientes de facturar</h3>
                <button @click="casosAbierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="overflow-y-auto p-4 space-y-2">
                <template x-for="c in casos" :key="c.id">
                    <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" x-model="c._sel" class="mt-1 w-4 h-4">
                        <div class="flex-1 text-sm">
                            <div class="font-semibold text-slate-800" x-text="c.codigo_qr + ' · ' + c.servicio_nombre"></div>
                            <div class="text-xs text-slate-500" x-text="'Paciente: ' + (c.paciente || '—') + ' · Ingreso ' + c.fecha + ' · ' + c.estatus_actual"></div>
                            <div class="text-[11px] text-amber-600" x-show="c.en_prefactura > 0">Ya está en una prefactura / nota de entrega abierta</div>
                        </div>
                        <div class="font-bold text-slate-700" x-text="usd(c.precio)"></div>
                    </label>
                </template>
                <p x-show="!casos.length" class="text-center text-slate-400 py-8 text-sm">Esta clínica no tiene casos pendientes de facturar.</p>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-slate-50 rounded-b-2xl">
                <button @click="casosAbierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cerrar</button>
                <button @click="agregarCasos()" class="px-5 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg">Agregar seleccionados</button>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         MODAL: DETALLE DEL DOCUMENTO
         ===================================================================== -->
    <div x-show="detalle.abierto" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl my-6" x-show="detalle.doc">
            <template x-if="detalle.doc">
                <div>
                    <div class="flex items-start justify-between px-6 py-4 border-b">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800" x-text="nombreTipo(detalle.doc.tipo) + ' ' + (detalle.doc.numero || '(borrador)')"></h2>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="badge(detalle.doc)" x-text="detalle.doc.estatus"></span>
                        </div>
                        <button @click="detalle.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-6 space-y-4 text-sm">
                        <div class="grid grid-cols-2 gap-3">
                            <div><span class="text-slate-400 text-xs">Cliente</span><div class="font-semibold" x-text="detalle.doc.clinica_nombre"></div>
                                <div class="text-xs text-slate-500" x-text="detalle.doc.clinica_tipo_doc + '-' + detalle.doc.clinica_num_doc"></div></div>
                            <div><span class="text-slate-400 text-xs">Fecha / Tasa</span><div class="font-semibold" x-text="fechaVe(detalle.doc.fecha_emision) + ' · Bs ' + fmt(detalle.doc.tasa_cambio, 4)"></div>
                                <div class="text-xs text-slate-500" x-show="detalle.doc.tipo==='FACTURA'" x-text="detalle.doc.condicion_pago==='CREDITO' ? 'Crédito · vence ' + fechaVe(detalle.doc.fecha_vencimiento) : 'Contado'"></div></div>
                        </div>
                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-sm"><thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-3 py-2">Descripción</th><th class="px-2 py-2">Cant.</th><th class="text-right px-3 py-2">Total USD</th></tr></thead>
                                <tbody class="divide-y divide-slate-100"><template x-for="it in detalle.doc.items" :key="it.id">
                                    <tr><td class="px-3 py-2" x-text="it.descripcion"></td><td class="px-2 py-2 text-center" x-text="fmt(it.cantidad)"></td><td class="px-3 py-2 text-right font-semibold" x-text="usd(it.total_usd)"></td></tr>
                                </template></tbody></table>
                        </div>
                        <div class="flex justify-end"><div class="w-full sm:w-72 space-y-1">
                            <div class="flex justify-between"><span class="text-slate-500">Base imponible</span><span x-text="usd(detalle.doc.base_imponible_usd)"></span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Base exenta</span><span x-text="usd(detalle.doc.base_exenta_usd)"></span></div>
                            <div class="flex justify-between"><span class="text-slate-500" x-text="'IVA ' + fmt(detalle.doc.iva_porcentaje,0) + '%'"></span><span x-text="usd(detalle.doc.iva_usd)"></span></div>
                            <div class="flex justify-between font-black text-base border-t pt-1"><span>Total USD</span><span x-text="usd(detalle.doc.total_usd)"></span></div>
                            <div class="flex justify-between font-bold text-indigo-700"><span>Total Bs</span><span x-text="bs(detalle.doc.total_bs)"></span></div>
                            <div class="flex justify-between font-bold text-amber-600" x-show="detalle.doc.tipo==='FACTURA' && detalle.doc.estatus!=='ANULADO'"><span>Saldo pendiente</span><span x-text="usd(detalle.doc.saldo_usd)"></span></div>
                        </div></div>
                        <template x-if="detalle.doc.pagos && detalle.doc.pagos.length">
                            <div><h4 class="text-xs font-bold text-slate-500 uppercase mb-1">Cobros aplicados</h4>
                                <template x-for="p in detalle.doc.pagos" :key="p.numero"><div class="flex justify-between text-xs py-1 border-b border-slate-100">
                                    <span x-text="p.numero + ' · ' + fechaVe(p.fecha_pago) + ' · ' + p.metodo"></span><span class="font-semibold" x-text="usd(p.monto_usd)"></span></div></template></div>
                        </template>
                        <p class="text-xs text-slate-500" x-show="detalle.doc.observaciones" x-text="'Obs.: ' + detalle.doc.observaciones"></p>
                        <p class="text-xs text-red-600" x-show="detalle.doc.motivo_anulacion" x-text="'Anulado: ' + detalle.doc.motivo_anulacion"></p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2 px-6 py-4 border-t bg-slate-50 rounded-b-2xl">
                        <template x-if="detalle.doc.estatus!=='BORRADOR'">
                            <button @click="window.open(detalle.doc.enlace_publico + '?print=1','_blank')" class="px-3 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg">Ver / Imprimir / PDF</button>
                        </template>
                        <template x-if="detalle.doc.estatus!=='BORRADOR' && detalle.doc.estatus!=='ANULADO'">
                            <button @click="abrirWhatsApp(detalle.doc.id)" class="px-3 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg">Enviar por WhatsApp</button>
                        </template>
                        <template x-if="['PREFACTURA','NOTA_ENTREGA'].includes(detalle.doc.tipo) && detalle.doc.estatus==='EMITIDO'">
                            <button @click="convertir(detalle.doc)" class="px-3 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Convertir en factura</button>
                        </template>
                        <template x-if="detalle.doc.tipo==='FACTURA' && ['EMITIDO','PARCIAL'].includes(detalle.doc.estatus)">
                            <span class="flex gap-2">
                                <a :href="'/ventas/cobranzas?clinica=' + detalle.doc.clinica_id" class="px-3 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Registrar cobro</a>
                                <button @click="notaCreditoDe(detalle.doc)" class="px-3 py-2 text-sm font-semibold text-fuchsia-700 bg-fuchsia-50 hover:bg-fuchsia-100 rounded-lg">Nota de crédito</button>
                            </span>
                        </template>
                        <template x-if="esAdmin && !['BORRADOR','ANULADO','CONVERTIDO'].includes(detalle.doc.estatus)">
                            <button @click="anular(detalle.doc)" class="px-3 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg">Anular</button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- =====================================================================
         MODAL: WHATSAPP  (sin API, sin token: abre el chat con el mensaje listo)
         ===================================================================== -->
    <div x-show="wa.abierto" class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/60 p-4">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-bold text-slate-800">Enviar por WhatsApp</h3>
                <button @click="wa.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4 text-sm">
                <p class="text-slate-500"><strong x-text="wa.cliente"></strong> · <span x-text="wa.numero"></span></p>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Teléfono (con código de país, solo números)</label>
                    <input type="text" x-model="wa.tel" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg" placeholder="584121234567">
                    <p class="text-[11px] text-red-500 mt-1" x-show="!wa.tel">Esta clínica no tiene teléfono registrado: escríbelo aquí.</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Mensaje (puedes editarlo)</label>
                    <textarea x-model="wa.msg" rows="9" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg font-mono text-xs"></textarea>
                </div>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg p-3" x-show="wa.local">
                    ⚠ El enlace de la factura apunta a una dirección local (<span x-text="wa.enlace"></span>). El cliente no podrá abrirlo desde su teléfono hasta que
                    el sistema esté en un servidor con dominio. Configura <code>APP_URL</code> en el archivo <code>.env</code>.
                </div>
            </div>
            <div class="flex flex-wrap justify-end gap-2 px-6 py-4 border-t bg-slate-50 rounded-b-2xl">
                <button @click="copiar(wa.enlace, 'Enlace copiado')" class="px-3 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg">Copiar enlace</button>
                <button @click="copiar(wa.msg, 'Mensaje copiado')" class="px-3 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg">Copiar mensaje</button>
                <button @click="abrirWa(true)" :disabled="!wa.tel" class="px-3 py-2 text-sm font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-lg disabled:opacity-50">WhatsApp Web</button>
                <button @click="abrirWa(false)" :disabled="!wa.tel" class="px-4 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-50">Abrir WhatsApp</button>
            </div>
        </div>
    </div>

</div>

<script>
/**
 * Componente Alpine de la pantalla de documentos de venta.
 * @param {string} tipoVista  Tipo fijo de la URL ('' = todos)
 * @param {boolean} esAdmin   Si el usuario puede anular documentos
 */
function docsApp(tipoVista, esAdmin) {
    const API = '/ventas-controller';
    const r2 = (x) => Math.round((Number(x) + Number.EPSILON) * 100) / 100;

    return {
        tipoVista, esAdmin,
        rows: [], kpis: {}, cargando: false,
        filtros: { q: '', tipo: tipoVista, estatus: '', clinica_id: '', desde: '', hasta: '' },
        cat: { clinicas: [], sedes: [], servicios: [], tasa: { tasa: 0, exacta: false }, iva_porcentaje: 16, dias_credito_default: 15, hoy: hoyISO() },
        ed: { abierto: false, guardando: false, id: 0, tipo: 'FACTURA', clinica_id: '', sede_id: '', fecha_emision: '', condicion_pago: 'CONTADO',
              dias_credito: 15, tasa_cambio: 0, documento_origen_id: '', observaciones: '', numero_control: '', items: [], iva_origen: null },
        casos: [], casosAbierto: false, facturasOrigen: [],
        detalle: { abierto: false, doc: null },
        wa: { abierto: false, tel: '', msg: '', enlace: '', local: false, cliente: '', numero: '' },

        // ------------------------------------------------------------------ inicio
        async init() {
            await this.cargarCatalogos();
            await this.listar();
            refrescarIconos();
        },
        async cargarCatalogos() {
            const r = await api(API, { action: 'catalogos' });
            if (r.status === 'success') this.cat = Object.assign(this.cat, r);
            else toast('error', r.message);
        },
        async listar() {
            this.cargando = true;
            const f = Object.assign({ action: 'listar' }, this.filtros);
            const r = await api(API, f);
            this.cargando = false;
            if (r.status === 'success') { this.rows = r.data; this.kpis = r.kpis; }
            else toast('error', r.message);
        },

        // ------------------------------------------------------------------ utilidades de presentación
        nombreTipo(t) { return { PREFACTURA: 'Prefactura', FACTURA: 'Factura', NOTA_CREDITO: 'Nota de Crédito', NOTA_ENTREGA: 'Nota de Entrega' }[t] || t; },
        badge(r) {
            if (r.vencida == 1) return 'bg-red-100 text-red-700';
            return { BORRADOR: 'bg-slate-100 text-slate-600', EMITIDO: 'bg-blue-100 text-blue-700', PARCIAL: 'bg-amber-100 text-amber-700',
                     PAGADO: 'bg-emerald-100 text-emerald-700', ANULADO: 'bg-red-100 text-red-700', CONVERTIDO: 'bg-violet-100 text-violet-700' }[r.estatus] || 'bg-slate-100';
        },

        // ------------------------------------------------------------------ cálculo del editor (solo vista previa; el servidor recalcula)
        get ivaPct() { return (this.ed.tipo === 'NOTA_CREDITO' && this.ed.iva_origen !== null) ? Number(this.ed.iva_origen) : Number(this.cat.iva_porcentaje); },
        lineaTotal(it) { return r2((Number(it.cantidad) || 0) * (Number(it.precio_unitario_usd) || 0) * (1 - (Number(it.descuento_pct) || 0) / 100)); },
        get tot() {
            let ex = 0, im = 0;
            for (const it of this.ed.items) { const t = this.lineaTotal(it); if (it.aplica_iva) im += t; else ex += t; }
            ex = r2(ex); im = r2(im);
            const iva = r2(im * this.ivaPct / 100);
            const tasa = Number(this.ed.tasa_cambio) || 0;
            const exBs = r2(ex * tasa), imBs = r2(im * tasa), ivaBs = r2(imBs * this.ivaPct / 100);
            return { exenta: ex, imponible: im, iva, total: r2(ex + im + iva), totalBs: r2(exBs + imBs + ivaBs) };
        },
        get sedesClinica() { return this.cat.sedes.filter(s => String(s.clinica_id) === String(this.ed.clinica_id)); },

        // ------------------------------------------------------------------ editor
        nuevo(tipo, preset) {
            this.ed = { abierto: true, guardando: false, id: 0, tipo, clinica_id: '', sede_id: '', fecha_emision: this.cat.hoy,
                        condicion_pago: 'CONTADO', dias_credito: this.cat.dias_credito_default, tasa_cambio: this.cat.tasa.tasa || 0,
                        documento_origen_id: '', observaciones: '', numero_control: '', items: [], iva_origen: null };
            this.facturasOrigen = [];
            if (preset) Object.assign(this.ed, preset);
        },
        async editar(id) {
            const r = await api(API, { action: 'ver', id });
            if (r.status !== 'success') return toast('error', r.message);
            const d = r.documento;
            this.nuevo(d.tipo);
            Object.assign(this.ed, {
                id: d.id, clinica_id: String(d.clinica_id), sede_id: d.sede_id ? String(d.sede_id) : '', fecha_emision: d.fecha_emision,
                condicion_pago: d.condicion_pago, dias_credito: Number(d.dias_credito), tasa_cambio: Number(d.tasa_cambio),
                documento_origen_id: d.documento_origen_id ? String(d.documento_origen_id) : '', observaciones: d.observaciones || '',
                numero_control: d.numero_control || '',
                iva_origen: d.tipo === 'NOTA_CREDITO' ? Number(d.iva_porcentaje) : null,
                items: d.items.map(i => ({ orden_trabajo_id: i.orden_trabajo_id, servicio_id: i.servicio_id, descripcion: i.descripcion,
                    cantidad: Number(i.cantidad), precio_unitario_usd: Number(i.precio_unitario_usd), descuento_pct: Number(i.descuento_pct), aplica_iva: i.aplica_iva == 1 })),
            });
            if (d.tipo === 'NOTA_CREDITO') await this.cargarFacturasOrigen();
            this.detalle.abierto = false;
        },
        async alCambiarClinica() {
            this.ed.sede_id = '';
            if (this.ed.tipo === 'NOTA_CREDITO') { this.ed.documento_origen_id = ''; this.ed.items = []; await this.cargarFacturasOrigen(); }
        },
        async cargarFacturasOrigen() {
            this.facturasOrigen = [];
            if (!this.ed.clinica_id) return;
            const r = await api(API, { action: 'listar', tipo: 'FACTURA', clinica_id: this.ed.clinica_id });
            if (r.status === 'success') this.facturasOrigen = r.data.filter(f => ['EMITIDO', 'PARCIAL'].includes(f.estatus) && parseFloat(f.saldo_usd) > 0);
        },
        async alElegirOrigen() {
            if (!this.ed.documento_origen_id) return;
            const r = await api(API, { action: 'ver', id: this.ed.documento_origen_id });
            if (r.status !== 'success') return toast('error', r.message);
            const d = r.documento;
            this.ed.tasa_cambio = Number(d.tasa_cambio);
            this.ed.iva_origen = Number(d.iva_porcentaje);
            this.ed.sede_id = d.sede_id ? String(d.sede_id) : '';
            this.ed.items = d.items.map(i => ({ orden_trabajo_id: null, servicio_id: i.servicio_id, descripcion: i.descripcion,
                cantidad: Number(i.cantidad), precio_unitario_usd: Number(i.precio_unitario_usd), descuento_pct: Number(i.descuento_pct), aplica_iva: i.aplica_iva == 1 }));
        },
        async notaCreditoDe(doc) {
            this.detalle.abierto = false;
            this.nuevo('NOTA_CREDITO', { clinica_id: String(doc.clinica_id) });
            await this.cargarFacturasOrigen();
            this.ed.documento_origen_id = String(doc.id);
            await this.alElegirOrigen();
        },
        agregarLinea() { this.ed.items.push({ orden_trabajo_id: null, servicio_id: null, descripcion: '', cantidad: 1, precio_unitario_usd: 0, descuento_pct: 0, aplica_iva: true }); },
        agregarServicio(id) {
            const s = this.cat.servicios.find(x => String(x.id) === String(id));
            if (!s) return;
            this.ed.items.push({ orden_trabajo_id: null, servicio_id: s.id, descripcion: s.nombre, cantidad: 1, precio_unitario_usd: Number(s.precio), descuento_pct: 0, aplica_iva: true });
        },
        async abrirCasos() {
            if (!this.ed.clinica_id) return toast('warning', 'Primero selecciona la clínica.');
            const r = await api(API, { action: 'casos_facturables', clinica_id: this.ed.clinica_id });
            if (r.status !== 'success') return toast('error', r.message);
            // se ocultan los casos que ya están en este documento
            const usados = new Set(this.ed.items.map(i => i.orden_trabajo_id).filter(Boolean));
            this.casos = r.data.filter(c => !usados.has(c.id)).map(c => Object.assign(c, { _sel: false }));
            this.casosAbierto = true;
        },
        agregarCasos() {
            for (const c of this.casos.filter(x => x._sel)) {
                this.ed.items.push({ orden_trabajo_id: c.id, servicio_id: c.servicio_id,
                    descripcion: c.servicio_nombre + ' — Paciente: ' + (c.paciente || 'N/D') + ' (' + c.codigo_qr + ')',
                    cantidad: 1, precio_unitario_usd: Number(c.precio), descuento_pct: 0, aplica_iva: true });
            }
            this.casosAbierto = false;
        },
        async guardar(emitir) {
            if (!this.ed.clinica_id) return toast('warning', 'Selecciona la clínica.');
            if (!this.ed.items.length) return toast('warning', 'Agrega al menos una línea.');
            if (emitir && !(await confirmar('¿Emitir ' + this.nombreTipo(this.ed.tipo).toLowerCase() + '?',
                'Se le asignará número' + (['FACTURA', 'NOTA_CREDITO'].includes(this.ed.tipo) ? ' y se generará el asiento contable' : '') + '. Ya no podrás editarla.', 'Sí, emitir'))) return;
            this.ed.guardando = true;
            const payload = Object.assign({ action: 'guardar', emitir: emitir ? 1 : 0 }, this.ed, {
                items: this.ed.items.map(i => Object.assign({}, i, { aplica_iva: i.aplica_iva ? 1 : 0 })),
            });
            const r = await api(API, payload);
            this.ed.guardando = false;
            if (r.status !== 'success') return toast('error', r.message);
            toast('success', r.message);
            this.ed.abierto = false;
            await this.listar();
            if (emitir) this.verDetalle(r.id);   // se abre el detalle para poder enviarla por WhatsApp
        },

        // ------------------------------------------------------------------ acciones sobre documentos
        async verDetalle(id) {
            const r = await api(API, { action: 'ver', id });
            if (r.status !== 'success') return toast('error', r.message);
            this.detalle = { abierto: true, doc: r.documento };
        },
        async emitirBorrador(r) {
            if (!(await confirmar('¿Emitir este borrador?', 'Se asignará número y se contabilizará (si aplica).', 'Sí, emitir'))) return;
            const x = await api(API, { action: 'emitir', id: r.id });
            if (x.status === 'success') { toast('success', x.message); await this.listar(); this.verDetalle(r.id); } else toast('error', x.message);
        },
        async eliminarBorrador(r) {
            if (!(await confirmar('¿Eliminar el borrador?', 'Esta acción no se puede deshacer.', 'Sí, eliminar'))) return;
            const x = await api(API, { action: 'eliminar_borrador', id: r.id });
            if (x.status === 'success') { toast('success', x.message); this.listar(); } else toast('error', x.message);
        },
        async convertir(doc) {
            if (!(await confirmar('¿Convertir en factura?', 'Se creará una factura en borrador con las mismas líneas para que la revises.', 'Sí, convertir'))) return;
            const x = await api(API, { action: 'convertir', id: doc.id });
            if (x.status !== 'success') return toast('error', x.message);
            toast('success', x.message);
            this.detalle.abierto = false;
            await this.listar();
            this.editar(x.id);
        },
        async anular(doc) {
            const motivo = await pedirMotivo('Anular ' + this.nombreTipo(doc.tipo).toLowerCase() + ' ' + doc.numero);
            if (!motivo) return;
            const x = await api(API, { action: 'anular', id: doc.id, motivo });
            if (x.status === 'success') { toast('success', x.message); this.detalle.abierto = false; this.listar(); } else toast('error', x.message);
        },

        // ------------------------------------------------------------------ WhatsApp
        async abrirWhatsApp(id) {
            const r = await api(API, { action: 'whatsapp', id });
            if (r.status !== 'success') return toast('error', r.message);
            this.wa = { abierto: true, tel: r.whatsapp.telefono, msg: r.whatsapp.mensaje, enlace: r.whatsapp.enlace,
                        local: r.whatsapp.enlace_local, cliente: r.cliente, numero: r.numero };
        },
        abrirWa(web) {
            const tel = String(this.wa.tel).replace(/\D+/g, '');
            if (!tel) return toast('warning', 'Escribe el teléfono del cliente.');
            const txt = encodeURIComponent(this.wa.msg);
            const url = web ? ('https://web.whatsapp.com/send?phone=' + tel + '&text=' + txt) : ('https://wa.me/' + tel + '?text=' + txt);
            window.open(url, '_blank', 'noopener');
        },
        async copiar(texto, aviso) {
            try { await navigator.clipboard.writeText(texto); toast('success', aviso); }
            catch (e) { window.prompt('Copia el texto (Ctrl+C):', texto); }
        },
    };
}
</script>

<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
