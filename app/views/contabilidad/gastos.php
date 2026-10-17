<?php
/**
 * =========================================================================
 * app/views/contabilidad/gastos.php  —  GASTOS Y COMPRAS
 * =========================================================================
 * Registro de egresos del laboratorio (alquiler, servicios, insumos...). Al guardar se genera
 * el asiento (gasto + IVA crédito contra caja/banco o contra Proveedores por pagar).
 * Los gastos A CRÉDITO quedan PENDIENTES hasta que se pagan con el botón "Pagar".
 * Montos en bolívares. Habla con app/controllers/gastos-controller.php.
 */
$tituloPagina = 'Gastos y Compras';
$iconoPagina  = 'receipt-text';
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important}</style>

<div x-data="gastosApp()" x-init="init()" x-cloak class="space-y-6">
    <div class="grid grid-cols-2 gap-4 max-w-xl">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm"><p class="text-[11px] font-bold uppercase text-slate-400">Total gastos (filtro)</p><p class="text-xl font-black mt-1" x-text="bs(total)"></p></div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm"><p class="text-[11px] font-bold uppercase text-slate-400">Pendiente por pagar</p><p class="text-xl font-black text-amber-600 mt-1" x-text="bs(pendiente)"></p></div>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[160px]"><label class="text-xs font-semibold text-slate-500">Buscar</label><input type="text" x-model="fl.q" @keydown.enter="listar()" placeholder="Proveedor, concepto o número" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="text-xs font-semibold text-slate-500">Desde</label><input type="date" x-model="fl.desde" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="text-xs font-semibold text-slate-500">Hasta</label><input type="date" x-model="fl.hasta" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="text-xs font-semibold text-slate-500">Estatus</label><select x-model="fl.estatus" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"><option value="">Todos</option><option>PAGADO</option><option>PENDIENTE</option><option>ANULADO</option></select></div>
        <button @click="listar()" class="px-4 py-2 text-sm font-semibold bg-slate-100 hover:bg-slate-200 rounded-lg">Filtrar</button>
        <button @click="nuevo()" class="px-4 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm">+ Registrar gasto</button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-4 py-3">Gasto</th><th class="text-left px-3 py-3">Proveedor / concepto</th><th class="text-left px-3 py-3">Cuenta</th>
            <th class="text-right px-3 py-3">Base</th><th class="text-right px-3 py-3">IVA</th><th class="text-right px-3 py-3">Total</th><th class="text-right px-4 py-3">Acciones</th></tr></thead>
            <tbody class="divide-y divide-slate-100"><template x-for="g in rows" :key="g.id"><tr :class="g.estatus==='ANULADO' ? 'opacity-50' : ''">
                <td class="px-4 py-3"><div class="font-bold" x-text="g.numero"></div><div class="text-[11px] text-slate-400" x-text="fechaVe(g.fecha)"></div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="{'bg-emerald-100 text-emerald-700':g.estatus==='PAGADO','bg-amber-100 text-amber-700':g.estatus==='PENDIENTE','bg-red-100 text-red-700':g.estatus==='ANULADO'}" x-text="g.estatus"></span></td>
                <td class="px-3 py-3"><div class="font-semibold" x-text="g.proveedor"></div><div class="text-xs text-slate-500" x-text="g.concepto"></div></td>
                <td class="px-3 py-3 text-xs text-slate-500" x-text="g.cuenta_codigo + ' ' + g.cuenta_nombre"></td>
                <td class="px-3 py-3 text-right" x-text="bs(g.base_bs)"></td><td class="px-3 py-3 text-right" x-text="bs(g.iva_bs)"></td><td class="px-3 py-3 text-right font-semibold" x-text="bs(g.total_bs)"></td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <button x-show="g.estatus==='PENDIENTE'" @click="abrirPago(g)" class="px-2.5 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Pagar</button>
                    <button x-show="g.estatus!=='ANULADO'" @click="anular(g)" class="px-2.5 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg">Anular</button></td></tr></template>
                <tr x-show="!rows.length"><td colspan="7" class="px-4 py-10 text-center text-slate-400">No hay gastos con esos filtros.</td></tr></tbody></table>
    </div>

    <!-- Nuevo gasto -->
    <div x-show="f.abierto" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl my-6">
            <div class="flex items-center justify-between px-6 py-4 border-b"><h2 class="text-lg font-bold">Registrar gasto / compra</h2><button @click="f.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button></div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><label class="text-xs font-semibold text-slate-500">Fecha *</label><input type="date" x-model="f.fecha" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div><label class="text-xs font-semibold text-slate-500">N° factura del proveedor</label><input type="text" x-model="f.nro_factura_proveedor" maxlength="40" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div><label class="text-xs font-semibold text-slate-500">Proveedor *</label><input type="text" x-model="f.proveedor" maxlength="150" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div><label class="text-xs font-semibold text-slate-500">RIF del proveedor</label><input type="text" x-model="f.proveedor_rif" maxlength="20" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-500">Concepto *</label><input type="text" x-model="f.concepto" maxlength="255" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-500">Cuenta de gasto / costo *</label>
                    <select x-model="f.cuenta_gasto_id" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option value="">— Selecciona —</option><template x-for="c in cat.cuentas_gasto" :key="c.id"><option :value="c.id" x-text="c.codigo + ' · ' + c.nombre"></option></template></select></div>
                <div><label class="text-xs font-semibold text-slate-500">Base Bs *</label><input type="number" step="0.01" min="0" x-model.number="f.base_bs" @input="calcIva()" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div><label class="text-xs font-semibold text-slate-500">IVA Bs (se sugiere al <span x-text="cat.iva_porcentaje"></span>%)</label><input type="number" step="0.01" min="0" x-model.number="f.iva_bs" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div class="md:col-span-2 font-bold text-right">Total: <span x-text="bs((Number(f.base_bs)||0) + (Number(f.iva_bs)||0))"></span></div>
                <div><label class="text-xs font-semibold text-slate-500">Forma de pago</label><select x-model="f.forma_pago" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option value="CONTADO">Contado (ya se pagó)</option><option value="CREDITO">A crédito (por pagar)</option></select></div>
                <div x-show="f.forma_pago==='CONTADO'"><label class="text-xs font-semibold text-slate-500">Pagado desde (caja / banco) *</label>
                    <select x-model="f.cuenta_pago_id" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option value="">— Selecciona —</option><template x-for="c in cat.cuentas_pago" :key="c.id"><option :value="c.id" x-text="c.codigo + ' · ' + c.nombre"></option></template></select></div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-slate-50 rounded-b-2xl"><button @click="f.abierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cancelar</button>
                <button @click="guardar()" class="px-5 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg">Guardar gasto</button></div>
        </div>
    </div>

    <!-- Pagar gasto a crédito -->
    <div x-show="p.abierto" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl">
            <div class="px-6 py-4 border-b font-bold">Pagar gasto <span x-text="p.numero"></span> · <span x-text="bs(p.total)"></span></div>
            <div class="p-6 space-y-3 text-sm">
                <div><label class="text-xs font-semibold text-slate-500">Fecha de pago</label><input type="date" x-model="p.fecha_pago" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div><label class="text-xs font-semibold text-slate-500">Caja / banco *</label><select x-model="p.cuenta_pago_id" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option value="">— Selecciona —</option><template x-for="c in cat.cuentas_pago" :key="c.id"><option :value="c.id" x-text="c.codigo + ' · ' + c.nombre"></option></template></select></div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-slate-50 rounded-b-2xl"><button @click="p.abierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cancelar</button>
                <button @click="pagar()" class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Registrar pago</button></div>
        </div>
    </div>
</div>

<script>
/** Componente Alpine de Gastos y Compras. */
function gastosApp() {
    const API = '/gastos-controller';
    const r2 = (x) => Math.round((Number(x) + Number.EPSILON) * 100) / 100;
    const vacio = () => ({ abierto: true, fecha: hoyISO(), proveedor: '', proveedor_rif: '', nro_factura_proveedor: '', concepto: '', cuenta_gasto_id: '', base_bs: 0, iva_bs: 0, forma_pago: 'CONTADO', cuenta_pago_id: '' });
    return {
        rows: [], total: 0, pendiente: 0, fl: { q: '', desde: '', hasta: '', estatus: '' },
        cat: { cuentas_gasto: [], cuentas_pago: [], iva_porcentaje: 16 },
        f: Object.assign(vacio(), { abierto: false }), p: { abierto: false, id: 0, numero: '', total: 0, fecha_pago: '', cuenta_pago_id: '' },
        async init() { const c = await api(API, { action: 'catalogos' }); if (c.status === 'success') this.cat = c; await this.listar(); },
        async listar() { const r = await api(API, Object.assign({ action: 'listar' }, this.fl)); if (r.status === 'success') { this.rows = r.data; this.total = r.total_bs; this.pendiente = r.pendiente_bs; } else toast('error', r.message); },
        nuevo() { this.f = vacio(); },
        calcIva() { this.f.iva_bs = r2((Number(this.f.base_bs) || 0) * Number(this.cat.iva_porcentaje) / 100); },
        async guardar() { const r = await api(API, Object.assign({ action: 'guardar' }, this.f)); if (r.status === 'success') { toast('success', r.message); this.f.abierto = false; this.listar(); } else toast('error', r.message); },
        abrirPago(g) { this.p = { abierto: true, id: g.id, numero: g.numero, total: g.total_bs, fecha_pago: hoyISO(), cuenta_pago_id: '' }; },
        async pagar() { const r = await api(API, { action: 'pagar', id: this.p.id, fecha_pago: this.p.fecha_pago, cuenta_pago_id: this.p.cuenta_pago_id }); if (r.status === 'success') { toast('success', r.message); this.p.abierto = false; this.listar(); } else toast('error', r.message); },
        async anular(g) { const motivo = await pedirMotivo('Anular el gasto ' + g.numero); if (!motivo) return; const r = await api(API, { action: 'anular', id: g.id, motivo }); if (r.status === 'success') { toast('success', r.message); this.listar(); } else toast('error', r.message); },
    };
}
</script>
<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
