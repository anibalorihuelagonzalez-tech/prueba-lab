<?php
/**
 * =========================================================================
 * app/views/contabilidad/libros.php  —  LIBROS CONTABLES
 * =========================================================================
 * Cuatro reportes en pestañas (todos en bolívares), con botón Imprimir:
 *   - Libro Diario            : todos los asientos con sus líneas.
 *   - Libro Mayor             : movimientos de una cuenta con saldo acumulado.
 *   - Balance de Comprobación : Debe, Haber y saldo de cada cuenta (verifica que todo cuadre).
 *   - Libro de Ventas         : facturas y notas de crédito con base e IVA (para la declaración).
 * Habla con app/controllers/reportes-contables-controller.php.
 */
$tituloPagina = 'Libros Contables';
$iconoPagina  = 'library';
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important} @media print { aside, header, .no-print { display:none!important } main { overflow:visible!important } }</style>

<div x-data="librosApp()" x-init="init()" x-cloak class="space-y-5">
    <div class="no-print flex flex-wrap gap-2 border-b border-slate-200">
        <template x-for="t in tabs" :key="t.k"><button @click="cambiar(t.k)" :class="tab===t.k ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500'" class="px-4 py-2 text-sm font-bold border-b-2" x-text="t.n"></button></template>
    </div>

    <div class="no-print bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-end gap-3">
        <div x-show="tab==='mayor'"><label class="text-xs font-semibold text-slate-500">Cuenta</label>
            <select x-model="fl.cuenta_id" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg max-w-xs"><option value="">— Selecciona —</option>
                <template x-for="c in hojas" :key="c.id"><option :value="c.id" x-text="c.codigo + ' · ' + c.nombre"></option></template></select></div>
        <div x-show="tab!=='comprobacion'"><label class="text-xs font-semibold text-slate-500">Desde</label><input type="date" x-model="fl.desde" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="text-xs font-semibold text-slate-500" x-text="tab==='comprobacion' ? 'Al' : 'Hasta'"></label><input type="date" x-model="fl.hasta" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <button @click="generar()" class="px-4 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg">Generar</button>
        <button @click="window.print()" class="px-4 py-2 text-sm font-semibold bg-slate-100 hover:bg-slate-200 rounded-lg">Imprimir</button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 overflow-x-auto">
        <h2 class="text-lg font-black text-slate-800 mb-1" x-text="titulo"></h2>
        <p class="text-xs text-slate-400 mb-4">Montos en bolívares (Bs.)</p>

        <!-- DIARIO -->
        <div x-show="tab==='diario'" class="space-y-4">
            <template x-for="a in diario.data" :key="a.id"><div class="border border-slate-200 rounded-xl overflow-hidden" :class="a.estatus==='ANULADO' ? 'opacity-60' : ''">
                <div class="bg-slate-50 px-4 py-2 flex justify-between text-sm"><span><strong x-text="a.numero"></strong> · <span x-text="fechaVe(a.fecha)"></span> · <span x-text="a.concepto"></span></span><span class="text-xs text-slate-500" x-text="a.tipo + (a.estatus==='ANULADO' ? ' · ANULADO' : '')"></span></div>
                <table class="w-full text-sm"><tbody class="divide-y divide-slate-100"><template x-for="(l,i) in a.lineas" :key="i"><tr>
                    <td class="px-4 py-1.5"><span class="font-mono text-xs text-slate-500" x-text="l.codigo"></span> <span x-text="l.cuenta"></span></td>
                    <td class="px-4 py-1.5 text-right w-40" x-text="l.debe>0 ? fmt(l.debe) : ''"></td><td class="px-4 py-1.5 text-right w-40" x-text="l.haber>0 ? fmt(l.haber) : ''"></td></tr></template></tbody></table></div></template>
            <p x-show="!diario.data.length" class="text-slate-400 text-sm">Sin asientos en el rango.</p>
            <div class="text-right font-bold text-sm">Totales: Debe <span x-text="fmt(diario.total_debe)"></span> · Haber <span x-text="fmt(diario.total_haber)"></span></div>
        </div>

        <!-- MAYOR -->
        <div x-show="tab==='mayor'" x-cloak><template x-if="mayor.cuenta">
            <div><p class="text-sm mb-2 font-semibold" x-text="mayor.cuenta.codigo + ' · ' + mayor.cuenta.nombre + ' (' + mayor.cuenta.naturaleza + ')'"></p>
                <table class="w-full text-sm"><thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-3 py-2">Fecha</th><th class="text-left px-3 py-2">Asiento / concepto</th><th class="text-right px-3 py-2">Debe</th><th class="text-right px-3 py-2">Haber</th><th class="text-right px-3 py-2">Saldo</th></tr></thead>
                    <tbody class="divide-y divide-slate-100"><tr class="bg-slate-50/60"><td colspan="4" class="px-3 py-2 font-semibold">Saldo inicial</td><td class="px-3 py-2 text-right font-semibold" x-text="fmt(mayor.saldo_inicial)"></td></tr>
                        <template x-for="(m,i) in mayor.movimientos" :key="i"><tr><td class="px-3 py-2" x-text="fechaVe(m.fecha)"></td><td class="px-3 py-2"><span class="font-semibold" x-text="m.numero"></span> <span class="text-slate-500" x-text="m.concepto"></span></td>
                            <td class="px-3 py-2 text-right" x-text="m.debe>0 ? fmt(m.debe) : ''"></td><td class="px-3 py-2 text-right" x-text="m.haber>0 ? fmt(m.haber) : ''"></td><td class="px-3 py-2 text-right font-semibold" x-text="fmt(m.saldo)"></td></tr></template></tbody>
                    <tfoot class="bg-slate-50 font-bold"><tr><td colspan="2" class="px-3 py-2">Totales / Saldo final</td><td class="px-3 py-2 text-right" x-text="fmt(mayor.total_debe)"></td><td class="px-3 py-2 text-right" x-text="fmt(mayor.total_haber)"></td><td class="px-3 py-2 text-right" x-text="fmt(mayor.saldo_final)"></td></tr></tfoot></table></div></template>
            <p x-show="!mayor.cuenta" class="text-slate-400 text-sm">Selecciona una cuenta y pulsa Generar.</p></div>

        <!-- COMPROBACIÓN -->
        <div x-show="tab==='comprobacion'"><table class="w-full text-sm"><thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-3 py-2">Cuenta</th><th class="text-right px-3 py-2">Debe</th><th class="text-right px-3 py-2">Haber</th><th class="text-right px-3 py-2">Saldo deudor</th><th class="text-right px-3 py-2">Saldo acreedor</th></tr></thead>
            <tbody class="divide-y divide-slate-100"><template x-for="c in comp.data" :key="c.id"><tr><td class="px-3 py-2"><span class="font-mono text-xs text-slate-500" x-text="c.codigo"></span> <span x-text="c.nombre"></span></td>
                <td class="px-3 py-2 text-right" x-text="fmt(c.debe)"></td><td class="px-3 py-2 text-right" x-text="fmt(c.haber)"></td><td class="px-3 py-2 text-right" x-text="c.saldo_deudor>0 ? fmt(c.saldo_deudor) : ''"></td><td class="px-3 py-2 text-right" x-text="c.saldo_acreedor>0 ? fmt(c.saldo_acreedor) : ''"></td></tr></template></tbody>
            <tfoot class="bg-slate-50 font-bold"><tr><td class="px-3 py-2">TOTALES</td><td class="px-3 py-2 text-right" x-text="fmt(comp.total_debe)"></td><td class="px-3 py-2 text-right" x-text="fmt(comp.total_haber)"></td><td class="px-3 py-2 text-right" x-text="fmt(comp.total_saldo_deudor)"></td><td class="px-3 py-2 text-right" x-text="fmt(comp.total_saldo_acreedor)"></td></tr></tfoot></table>
            <p class="mt-3 text-sm font-bold" :class="comp.cuadra ? 'text-emerald-600' : 'text-red-600'" x-text="comp.cuadra ? '✔ La contabilidad cuadra (Debe = Haber)' : '✖ No cuadra: revisa los asientos'"></p></div>

        <!-- LIBRO DE VENTAS -->
        <div x-show="tab==='ventas'"><table class="w-full text-sm"><thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-3 py-2">Fecha</th><th class="text-left px-3 py-2">Documento</th><th class="text-left px-3 py-2">Cliente</th><th class="text-left px-3 py-2">RIF</th>
            <th class="text-right px-3 py-2">Exento</th><th class="text-right px-3 py-2">Base imp.</th><th class="text-right px-3 py-2">IVA</th><th class="text-right px-3 py-2">Total</th></tr></thead>
            <tbody class="divide-y divide-slate-100"><template x-for="r in lv.data" :key="r.id"><tr :class="r.estatus==='ANULADO' ? 'line-through opacity-50' : ''">
                <td class="px-3 py-2" x-text="fechaVe(r.fecha_emision)"></td><td class="px-3 py-2"><span x-text="(r.tipo==='NOTA_CREDITO' ? 'NC ' : 'FAC ') + r.numero"></span><div class="text-[11px] text-slate-400" x-text="r.numero_control ? 'Control ' + r.numero_control : ''"></div></td>
                <td class="px-3 py-2" x-text="r.razon_social"></td><td class="px-3 py-2 text-xs" x-text="r.rif"></td>
                <td class="px-3 py-2 text-right" x-text="fmt(r.signo * r.base_exenta_bs)"></td><td class="px-3 py-2 text-right" x-text="fmt(r.signo * r.base_imponible_bs)"></td>
                <td class="px-3 py-2 text-right" x-text="fmt(r.signo * r.iva_bs)"></td><td class="px-3 py-2 text-right font-semibold" x-text="fmt(r.signo * r.total_bs)"></td></tr></template></tbody>
            <tfoot class="bg-slate-50 font-bold"><tr><td colspan="4" class="px-3 py-2">TOTALES (notas de crédito restan; anuladas no suman)</td><td class="px-3 py-2 text-right" x-text="fmt(lv.totales.exenta)"></td><td class="px-3 py-2 text-right" x-text="fmt(lv.totales.imponible)"></td><td class="px-3 py-2 text-right" x-text="fmt(lv.totales.iva)"></td><td class="px-3 py-2 text-right" x-text="fmt(lv.totales.total)"></td></tr></tfoot></table>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm"><div class="bg-slate-50 rounded-xl p-3">IVA débito (ventas)<div class="font-black" x-text="bs(lv.totales.iva)"></div></div>
                <div class="bg-slate-50 rounded-xl p-3">IVA crédito (compras registradas)<div class="font-black" x-text="bs(lv.iva_credito_compras)"></div></div>
                <div class="bg-indigo-50 rounded-xl p-3">IVA por pagar estimado<div class="font-black text-indigo-700" x-text="bs(lv.iva_por_pagar)"></div></div></div></div>
    </div>
</div>

<script>
/** Componente Alpine de Libros Contables. */
function librosApp() {
    const API = '/reportes-contables-controller';
    const hoy = hoyISO();
    return {
        tabs: [{ k: 'diario', n: 'Libro Diario' }, { k: 'mayor', n: 'Libro Mayor' }, { k: 'comprobacion', n: 'Balance de Comprobación' }, { k: 'ventas', n: 'Libro de Ventas' }],
        tab: 'diario', hojas: [], fl: { cuenta_id: '', desde: hoy.substring(0, 8) + '01', hasta: hoy },
        diario: { data: [], total_debe: 0, total_haber: 0 }, mayor: { cuenta: null, movimientos: [] },
        comp: { data: [], total_debe: 0, total_haber: 0, total_saldo_deudor: 0, total_saldo_acreedor: 0, cuadra: true },
        lv: { data: [], totales: { exenta: 0, imponible: 0, iva: 0, total: 0 }, iva_credito_compras: 0, iva_por_pagar: 0 },
        get titulo() { return this.tabs.find(t => t.k === this.tab).n; },
        async init() {
            const c = await api('/contabilidad-controller', { action: 'cuentas_listar' });
            if (c.status === 'success') this.hojas = c.data.filter(x => x.permite_movimiento == 1);
            this.generar();
        },
        cambiar(k) { this.tab = k; this.generar(); },
        async generar() {
            const map = { diario: 'libro_diario', mayor: 'libro_mayor', comprobacion: 'balance_comprobacion', ventas: 'libro_ventas' };
            if (this.tab === 'mayor' && !this.fl.cuenta_id) return;
            const p = { action: map[this.tab], desde: this.tab === 'comprobacion' ? '' : this.fl.desde, hasta: this.fl.hasta, cuenta_id: this.fl.cuenta_id };
            const r = await api(API, p);
            if (r.status !== 'success') return toast('error', r.message);
            if (this.tab === 'diario') this.diario = r;
            else if (this.tab === 'mayor') this.mayor = r;
            else if (this.tab === 'comprobacion') this.comp = r;
            else this.lv = r;
        },
    };
}
</script>
<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
