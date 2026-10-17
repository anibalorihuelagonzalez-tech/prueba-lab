<?php
/**
 * =========================================================================
 * app/views/ventas/cobranzas.php  —  COBRANZAS Y CUENTAS POR COBRAR
 * =========================================================================
 * Pestañas:
 *   1) Cuentas por cobrar : deuda por clínica con antigüedad (corriente / 1-30 / 31-60 / 61-90 / +90 días),
 *                           estado de cuenta y recordatorio por WhatsApp (sin API, solo botón).
 *   2) Cobros registrados : historial de pagos; permite conciliar y anular.
 * Botón "Registrar cobro": aplica el dinero a las facturas (las más antiguas primero, o las que tú elijas)
 * y genera el asiento contable (incluye IGTF y diferencial cambiario).
 * Habla con app/controllers/cobros-controller.php.
 * Acepta ?clinica=ID o ?factura=ID para abrir directo el formulario de cobro.
 */
$tituloPagina = 'Cobranzas y Cuentas por Cobrar';
$iconoPagina  = 'hand-coins';
$esAdmin = Auth::isAdmin();
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important}</style>

<div x-data="cobrosApp(<?= $esAdmin ? 'true' : 'false' ?>)" x-init="init()" x-cloak class="space-y-6">

    <!-- Tarjetas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total por cobrar</p>
            <p class="text-xl font-black text-amber-600 mt-1" x-text="usd(cxc.totales.saldo_usd)"></p>
            <p class="text-[11px] text-slate-400" x-text="'≈ ' + bs(cxc.total_bs_estimado) + ' (tasa de hoy)'"></p></div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Corriente (no vencido)</p>
            <p class="text-xl font-black text-emerald-600 mt-1" x-text="usd(cxc.totales.corriente)"></p></div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Vencido</p>
            <p class="text-xl font-black text-red-600 mt-1" x-text="usd(vencidoTotal)"></p></div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-center">
            <button @click="nuevoCobro()" class="px-5 py-3 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm">+ Registrar cobro</button></div>
    </div>

    <!-- Pestañas -->
    <div class="flex gap-2 border-b border-slate-200">
        <button @click="tab='cxc'" :class="tab==='cxc' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500'" class="px-4 py-2 text-sm font-bold border-b-2">Cuentas por cobrar</button>
        <button @click="tab='pagos'; listarPagos()" :class="tab==='pagos' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500'" class="px-4 py-2 text-sm font-bold border-b-2">Cobros registrados</button>
    </div>

    <!-- ============ CUENTAS POR COBRAR ============ -->
    <div x-show="tab==='cxc'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr>
                <th class="text-left px-4 py-3">Clínica</th><th class="text-right px-3 py-3">Corriente</th><th class="text-right px-3 py-3">1-30</th>
                <th class="text-right px-3 py-3">31-60</th><th class="text-right px-3 py-3">61-90</th><th class="text-right px-3 py-3">+90</th>
                <th class="text-right px-3 py-3">Total USD</th><th class="text-right px-4 py-3">Acciones</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <template x-for="r in cxc.data" :key="r.clinica_id">
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-3"><div class="font-semibold text-slate-800" x-text="r.razon_social"></div><div class="text-[11px] text-slate-400" x-text="r.facturas + ' factura(s) · desde ' + fechaVe(r.factura_mas_antigua)"></div></td>
                        <td class="px-3 py-3 text-right" x-text="usd(r.corriente)"></td>
                        <td class="px-3 py-3 text-right" x-text="usd(r.d1_30)"></td>
                        <td class="px-3 py-3 text-right text-amber-600" x-text="usd(r.d31_60)"></td>
                        <td class="px-3 py-3 text-right text-orange-600" x-text="usd(r.d61_90)"></td>
                        <td class="px-3 py-3 text-right text-red-600 font-semibold" x-text="usd(r.d90_mas)"></td>
                        <td class="px-3 py-3 text-right font-black" x-text="usd(r.saldo_usd)"></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button @click="nuevoCobro(r.clinica_id)" class="px-2.5 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Cobrar</button>
                            <button @click="estadoCuenta(r.clinica_id)" class="px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Estado de cuenta</button>
                            <button @click="recordatorio(r.clinica_id)" class="px-2.5 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Recordar</button>
                        </td>
                    </tr>
                </template>
                <tr x-show="!cxc.data.length"><td colspan="8" class="px-4 py-10 text-center text-slate-400">No hay deudas pendientes. 🎉</td></tr>
            </tbody>
            <tfoot x-show="cxc.data.length" class="bg-slate-50 font-bold text-sm"><tr>
                <td class="px-4 py-3">TOTAL</td><td class="px-3 py-3 text-right" x-text="usd(cxc.totales.corriente)"></td><td class="px-3 py-3 text-right" x-text="usd(cxc.totales.d1_30)"></td>
                <td class="px-3 py-3 text-right" x-text="usd(cxc.totales.d31_60)"></td><td class="px-3 py-3 text-right" x-text="usd(cxc.totales.d61_90)"></td>
                <td class="px-3 py-3 text-right" x-text="usd(cxc.totales.d90_mas)"></td><td class="px-3 py-3 text-right" x-text="usd(cxc.totales.saldo_usd)"></td><td></td></tr></tfoot>
        </table>
    </div>

    <!-- ============ COBROS REGISTRADOS ============ -->
    <div x-show="tab==='pagos'" class="space-y-3">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]"><label class="text-xs font-semibold text-slate-500">Buscar</label>
                <input type="text" x-model="fp.q" @keydown.enter="listarPagos()" placeholder="Recibo, clínica o referencia" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div><label class="text-xs font-semibold text-slate-500">Desde</label><input type="date" x-model="fp.desde" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div><label class="text-xs font-semibold text-slate-500">Hasta</label><input type="date" x-model="fp.hasta" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div><label class="text-xs font-semibold text-slate-500">Estatus</label>
                <select x-model="fp.estatus" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"><option value="">Todos</option><option>REGISTRADO</option><option>CONCILIADO</option><option>ANULADO</option></select></div>
            <button @click="listarPagos()" class="px-4 py-2 text-sm font-semibold bg-slate-100 hover:bg-slate-200 rounded-lg">Filtrar</button>
            <div class="ml-auto text-right text-xs text-slate-500">Total cobrado: <strong x-text="usd(pagosTot.usd)"></strong> · <strong x-text="bs(pagosTot.bs)"></strong> · IGTF <strong x-text="bs(pagosTot.igtf_bs)"></strong></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-4 py-3">Recibo</th><th class="text-left px-3 py-3">Clínica</th><th class="text-left px-3 py-3">Método</th>
                    <th class="text-right px-3 py-3">Monto</th><th class="text-right px-3 py-3">Equiv. USD</th><th class="text-right px-3 py-3">Equiv. Bs</th><th class="text-left px-3 py-3">Facturas</th><th class="text-right px-4 py-3">Acciones</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="p in pagos" :key="p.id">
                        <tr :class="p.estatus==='ANULADO' ? 'opacity-50' : ''">
                            <td class="px-4 py-3"><div class="font-bold" x-text="p.numero"></div><div class="text-[11px] text-slate-400" x-text="fechaVe(p.fecha_pago)"></div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="{'bg-blue-100 text-blue-700':p.estatus==='REGISTRADO','bg-emerald-100 text-emerald-700':p.estatus==='CONCILIADO','bg-red-100 text-red-700':p.estatus==='ANULADO'}" x-text="p.estatus"></span></td>
                            <td class="px-3 py-3" x-text="p.clinica_nombre"></td>
                            <td class="px-3 py-3"><div x-text="p.metodo"></div><div class="text-[11px] text-slate-400" x-text="p.referencia ? 'Ref. ' + p.referencia : ''"></div></td>
                            <td class="px-3 py-3 text-right font-semibold" x-text="(p.moneda_codigo==='USD' ? usd(p.monto) : bs(p.monto))"></td>
                            <td class="px-3 py-3 text-right" x-text="usd(p.monto_usd)"></td>
                            <td class="px-3 py-3 text-right" x-text="bs(p.monto_bs)"></td>
                            <td class="px-3 py-3 text-xs text-slate-500" x-text="p.facturas"></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button x-show="p.estatus==='REGISTRADO'" @click="conciliar(p)" class="px-2.5 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg">Conciliar</button>
                                <button x-show="esAdmin && p.estatus!=='ANULADO'" @click="anularPago(p)" class="px-2.5 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg">Anular</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!pagos.length"><td colspan="8" class="px-4 py-10 text-center text-slate-400">No hay cobros con esos filtros.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============ MODAL: REGISTRAR COBRO ============ -->
    <div x-show="cb.abierto" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl my-6">
            <div class="flex items-center justify-between px-6 py-4 border-b"><h2 class="text-lg font-bold text-slate-800">Registrar cobro</h2>
                <button @click="cb.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button></div>
            <div class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-500">Clínica que paga *</label>
                        <select x-model="cb.clinica_id" @change="cargarPendientes()" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                            <option value="">— Selecciona —</option>
                            <template x-for="c in cat.clinicas" :key="c.id"><option :value="c.id" x-text="c.razon_social"></option></template></select></div>
                    <div><label class="text-xs font-semibold text-slate-500">Método de pago *</label>
                        <select x-model="cb.metodo_pago_id" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                            <option value="">— Selecciona —</option>
                            <template x-for="m in cat.metodos" :key="m.id"><option :value="m.id" x-text="m.nombre + ' (' + m.moneda_codigo + ')'"></option></template></select></div>
                    <div><label class="text-xs font-semibold text-slate-500">Fecha del pago *</label>
                        <input type="date" x-model="cb.fecha_pago" :max="cat.hoy" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
                    <div><label class="text-xs font-semibold text-slate-500" x-text="'Monto recibido en ' + (metodo ? metodo.moneda_codigo : '—') + ' *'"></label>
                        <div class="flex gap-2"><input type="number" step="0.01" min="0" x-model.number="cb.monto" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                            <button @click="pagarTodo()" class="mt-1 px-3 text-xs font-semibold bg-slate-100 hover:bg-slate-200 rounded-lg whitespace-nowrap" title="Llenar con toda la deuda">Todo</button></div></div>
                    <div><label class="text-xs font-semibold text-slate-500">Tasa del día (Bs por $1)</label>
                        <input type="number" step="0.0001" min="0" x-model.number="cb.tasa_cambio" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg">
                        <p class="text-[11px] text-slate-400 mt-1" x-text="cat.tasa.exacta ? ('Fuente: ' + cat.tasa.fuente) : '⚠ Tasa antigua: verifícala'"></p></div>
                    <div><label class="text-xs font-semibold text-slate-500" x-text="'Referencia' + (metodo && metodo.requiere_referencia==1 ? ' *' : '')"></label>
                        <input type="text" x-model="cb.referencia" maxlength="100" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
                    <div><label class="text-xs font-semibold text-slate-500">Observaciones</label>
                        <input type="text" x-model="cb.observaciones" maxlength="255" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
                    <label class="flex items-center gap-2 text-sm md:col-span-2" x-show="metodo && metodo.aplica_igtf==1">
                        <input type="checkbox" x-model="cb.cobrar_igtf" class="w-4 h-4"> Cobrar IGTF (<span x-text="cat.igtf_porcentaje"></span>%) sobre este pago en divisas</label>
                </div>

                <!-- Resumen de equivalencias -->
                <div class="bg-slate-50 rounded-xl p-4 text-sm grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div><div class="text-[11px] text-slate-400">Se aplica a facturas</div><div class="font-bold" x-text="usd(calc.usd)"></div></div>
                    <div><div class="text-[11px] text-slate-400">Equivalente en Bs</div><div class="font-bold" x-text="bs(calc.bs)"></div></div>
                    <div><div class="text-[11px] text-slate-400">IGTF a cobrar</div><div class="font-bold" x-text="calc.igtf > 0 ? (metodo.moneda_codigo + ' ' + fmt(calc.igtf)) : '—'"></div></div>
                    <div><div class="text-[11px] text-slate-400">Deuda pendiente de la clínica</div><div class="font-bold text-amber-600" x-text="usd(pendTotal)"></div></div>
                </div>

                <!-- Facturas pendientes -->
                <div>
                    <div class="flex items-center justify-between mb-2"><h3 class="text-sm font-bold text-slate-700">Facturas pendientes</h3>
                        <label class="text-xs flex items-center gap-2"><input type="checkbox" x-model="cb.manual" class="w-4 h-4"> Elegir manualmente a qué facturas se aplica</label></div>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-sm"><thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-3 py-2">Factura</th><th class="text-left px-3 py-2">Vence</th>
                            <th class="text-right px-3 py-2">Saldo USD</th><th class="text-right px-3 py-2 w-36" x-text="cb.manual ? 'Aplicar USD' : 'Se aplicará'"></th></tr></thead>
                            <tbody class="divide-y divide-slate-100"><template x-for="f in pendientes" :key="f.id"><tr>
                                <td class="px-3 py-2 font-semibold" x-text="f.numero"></td>
                                <td class="px-3 py-2 text-xs" :class="f.dias_mora>0 ? 'text-red-600 font-semibold' : 'text-slate-500'" x-text="fechaVe(f.fecha_vencimiento) + (f.dias_mora>0 ? ' (' + f.dias_mora + ' d mora)' : '')"></td>
                                <td class="px-3 py-2 text-right" x-text="usd(f.saldo_usd)"></td>
                                <td class="px-3 py-2 text-right"><template x-if="cb.manual"><input type="number" step="0.01" min="0" x-model.number="f._aplicar" class="w-28 px-2 py-1 border border-slate-300 rounded-md text-right"></template>
                                    <template x-if="!cb.manual"><span class="font-semibold text-emerald-700" x-text="usd(reparto[f.id] || 0)"></span></template></td>
                            </tr></template>
                            <tr x-show="!pendientes.length"><td colspan="4" class="px-3 py-6 text-center text-slate-400 text-sm">Selecciona una clínica con deuda.</td></tr></tbody></table>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Sin selección manual, el pago se aplica primero a las facturas más antiguas.</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-slate-50 rounded-b-2xl">
                <button @click="cb.abierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cancelar</button>
                <button @click="registrar()" :disabled="cb.guardando" class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg disabled:opacity-50">Registrar cobro</button>
            </div>
        </div>
    </div>

    <!-- ============ MODAL: ESTADO DE CUENTA ============ -->
    <div x-show="ec.abierto" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl my-6" x-show="ec.data">
            <template x-if="ec.data"><div>
                <div class="flex items-center justify-between px-6 py-4 border-b"><div><h2 class="text-lg font-bold text-slate-800">Estado de cuenta</h2>
                    <p class="text-sm text-slate-500" x-text="ec.data.clinica.razon_social + ' · ' + ec.data.clinica.tipo_documento + '-' + ec.data.clinica.numero_documento"></p></div>
                    <button @click="ec.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button></div>
                <div class="p-6 overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-3 py-2">Fecha</th><th class="text-left px-3 py-2">Documento</th>
                    <th class="text-right px-3 py-2">Cargo</th><th class="text-right px-3 py-2">Abono</th><th class="text-right px-3 py-2">Saldo</th></tr></thead>
                    <tbody class="divide-y divide-slate-100"><template x-for="(m,i) in ec.data.movimientos" :key="i"><tr>
                        <td class="px-3 py-2" x-text="fechaVe(m.fecha)"></td><td class="px-3 py-2"><div class="font-semibold" x-text="m.numero"></div><div class="text-[11px] text-slate-400" x-text="m.detalle"></div></td>
                        <td class="px-3 py-2 text-right" x-text="m.cargo>0 ? usd(m.cargo) : ''"></td><td class="px-3 py-2 text-right text-emerald-700" x-text="m.abono>0 ? usd(m.abono) : ''"></td>
                        <td class="px-3 py-2 text-right font-semibold" x-text="usd(m.saldo)"></td></tr></template></tbody></table>
                    <div class="text-right mt-4 text-lg font-black">Saldo actual: <span class="text-amber-600" x-text="usd(ec.data.saldo_usd)"></span></div></div>
                <div class="flex justify-end gap-2 px-6 py-4 border-t bg-slate-50 rounded-b-2xl"><button @click="window.print()" class="px-3 py-2 text-sm font-semibold bg-white border border-slate-300 hover:bg-slate-100 rounded-lg">Imprimir</button>
                    <button @click="ec.abierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cerrar</button></div>
            </div></template>
        </div>
    </div>

    <!-- ============ MODAL: WHATSAPP (recordatorio de cobranza) ============ -->
    <div x-show="wa.abierto" class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/60 p-4">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b"><h3 class="font-bold text-slate-800">Recordatorio por WhatsApp</h3>
                <button @click="wa.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button></div>
            <div class="p-6 space-y-4 text-sm">
                <p class="text-slate-500"><strong x-text="wa.cliente"></strong></p>
                <div><label class="text-xs font-semibold text-slate-500">Teléfono (con código de país)</label><input type="text" x-model="wa.tel" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div><label class="text-xs font-semibold text-slate-500">Mensaje (editable)</label><textarea x-model="wa.msg" rows="8" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono"></textarea></div>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 border-t bg-slate-50 rounded-b-2xl">
                <button @click="abrirWa(true)" :disabled="!wa.tel" class="px-3 py-2 text-sm font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-lg disabled:opacity-50">WhatsApp Web</button>
                <button @click="abrirWa(false)" :disabled="!wa.tel" class="px-4 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-50">Abrir WhatsApp</button></div>
        </div>
    </div>
</div>

<script>
/** Componente Alpine de Cobranzas (ver comentario del archivo). */
function cobrosApp(esAdmin) {
    const API = '/cobros-controller';
    const r2 = (x) => Math.round((Number(x) + Number.EPSILON) * 100) / 100;
    return {
        esAdmin, tab: 'cxc',
        cat: { clinicas: [], metodos: [], tasa: { tasa: 0, exacta: false }, igtf_porcentaje: 3, hoy: hoyISO() },
        cxc: { data: [], totales: { saldo_usd: 0, corriente: 0, d1_30: 0, d31_60: 0, d61_90: 0, d90_mas: 0 }, total_bs_estimado: 0 },
        pagos: [], pagosTot: { usd: 0, bs: 0, igtf_bs: 0 }, fp: { q: '', desde: '', hasta: '', estatus: '' },
        pendientes: [], pendTotal: 0,
        cb: { abierto: false, guardando: false, clinica_id: '', metodo_pago_id: '', fecha_pago: '', monto: 0, tasa_cambio: 0, referencia: '', observaciones: '', cobrar_igtf: true, manual: false },
        ec: { abierto: false, data: null },
        wa: { abierto: false, tel: '', msg: '', cliente: '' },

        async init() {
            const c = await api(API, { action: 'catalogos' });
            if (c.status === 'success') this.cat = Object.assign(this.cat, c); else toast('error', c.message);
            await this.cargarCxc();
            // Llegada desde otra pantalla: ?clinica=ID  o  ?factura=ID
            const q = new URLSearchParams(location.search);
            if (q.get('clinica')) this.nuevoCobro(q.get('clinica'));
            else if (q.get('factura')) {
                const r = await api('/ventas-controller', { action: 'ver', id: q.get('factura') });
                if (r.status === 'success') this.nuevoCobro(r.documento.clinica_id);
            }
            refrescarIconos();
        },
        get vencidoTotal() { const t = this.cxc.totales; return r2(t.d1_30 + t.d31_60 + t.d61_90 + t.d90_mas); },
        async cargarCxc() { const r = await api(API, { action: 'cxc_resumen' }); if (r.status === 'success') this.cxc = r; else toast('error', r.message); },
        async listarPagos() {
            const r = await api(API, Object.assign({ action: 'listar_pagos' }, this.fp));
            if (r.status === 'success') { this.pagos = r.data; this.pagosTot = r.totales; } else toast('error', r.message);
        },

        // ---------------- registrar cobro
        get metodo() { return this.cat.metodos.find(m => String(m.id) === String(this.cb.metodo_pago_id)) || null; },
        get calc() {
            const m = this.metodo, monto = Number(this.cb.monto) || 0, tasa = Number(this.cb.tasa_cambio) || 0;
            if (!m || !tasa) return { usd: 0, bs: 0, igtf: 0 };
            if (m.moneda_codigo === 'VES') return { usd: r2(monto / tasa), bs: r2(monto), igtf: 0 };
            const igtf = (m.aplica_igtf == 1 && this.cb.cobrar_igtf) ? r2(monto * Number(this.cat.igtf_porcentaje) / 100) : 0;
            return { usd: r2(monto), bs: r2(monto * tasa), igtf };
        },
        // Reparto automático (más antigua primero) para mostrar en pantalla
        get reparto() {
            const out = {}; let resta = this.calc.usd;
            for (const f of this.pendientes) { if (resta <= 0.004) break; const t = Math.min(resta, Number(f.saldo_usd)); out[f.id] = r2(t); resta = r2(resta - t); }
            return out;
        },
        async nuevoCobro(clinicaId) {
            this.cb = { abierto: true, guardando: false, clinica_id: clinicaId ? String(clinicaId) : '', metodo_pago_id: '', fecha_pago: this.cat.hoy,
                        monto: 0, tasa_cambio: this.cat.tasa.tasa || 0, referencia: '', observaciones: '', cobrar_igtf: true, manual: false };
            this.pendientes = []; this.pendTotal = 0;
            if (clinicaId) await this.cargarPendientes();
        },
        async cargarPendientes() {
            this.pendientes = []; this.pendTotal = 0;
            if (!this.cb.clinica_id) return;
            const r = await api(API, { action: 'pendientes', clinica_id: this.cb.clinica_id });
            if (r.status === 'success') { this.pendientes = r.data.map(f => Object.assign(f, { _aplicar: 0 })); this.pendTotal = r.total_usd; }
        },
        pagarTodo() {
            const m = this.metodo; if (!m) return toast('warning', 'Elige primero el método de pago.');
            const t = Number(this.cb.tasa_cambio) || 0; if (!t) return toast('warning', 'Falta la tasa de cambio.');
            this.cb.monto = m.moneda_codigo === 'VES' ? r2(this.pendTotal * t) : this.pendTotal;
        },
        async registrar() {
            const m = this.metodo;
            if (!this.cb.clinica_id || !m) return toast('warning', 'Selecciona la clínica y el método de pago.');
            if (!(this.cb.monto > 0)) return toast('warning', 'Escribe el monto recibido.');
            const payload = { action: 'registrar', clinica_id: this.cb.clinica_id, metodo_pago_id: this.cb.metodo_pago_id, fecha_pago: this.cb.fecha_pago,
                monto: this.cb.monto, tasa_cambio: this.cb.tasa_cambio, referencia: this.cb.referencia, observaciones: this.cb.observaciones,
                cobrar_igtf: this.cb.cobrar_igtf ? 1 : 0 };
            if (this.cb.manual) payload.aplicaciones = this.pendientes.filter(f => Number(f._aplicar) > 0).map(f => ({ documento_id: f.id, monto_usd: Number(f._aplicar) }));
            this.cb.guardando = true;
            const r = await api(API, payload);
            this.cb.guardando = false;
            if (r.status !== 'success') return toast('error', r.message);
            toast('success', r.message);
            this.cb.abierto = false;
            await this.cargarCxc();
            if (this.tab === 'pagos') this.listarPagos();
        },

        // ---------------- otras acciones
        async conciliar(p) { const r = await api(API, { action: 'conciliar', id: p.id }); if (r.status === 'success') { toast('success', r.message); this.listarPagos(); } else toast('error', r.message); },
        async anularPago(p) {
            const motivo = await pedirMotivo('Anular el cobro ' + p.numero);
            if (!motivo) return;
            const r = await api(API, { action: 'anular_pago', id: p.id, motivo });
            if (r.status === 'success') { toast('success', r.message); this.listarPagos(); this.cargarCxc(); } else toast('error', r.message);
        },
        async estadoCuenta(id) {
            const r = await api(API, { action: 'estado_cuenta', clinica_id: id });
            if (r.status === 'success') this.ec = { abierto: true, data: r }; else toast('error', r.message);
        },
        async recordatorio(id) {
            const r = await api(API, { action: 'whatsapp_cobranza', clinica_id: id });
            if (r.status !== 'success') return toast('error', r.message);
            this.wa = { abierto: true, tel: r.whatsapp.telefono, msg: r.whatsapp.mensaje, cliente: r.cliente };
        },
        abrirWa(web) {
            const tel = String(this.wa.tel).replace(/\D+/g, ''); if (!tel) return;
            const txt = encodeURIComponent(this.wa.msg);
            window.open(web ? ('https://web.whatsapp.com/send?phone=' + tel + '&text=' + txt) : ('https://wa.me/' + tel + '?text=' + txt), '_blank', 'noopener');
        },
    };
}
</script>
<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
