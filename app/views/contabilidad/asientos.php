<?php
/**
 * =========================================================================
 * app/views/contabilidad/asientos.php  —  ASIENTOS CONTABLES
 * =========================================================================
 * Lista todos los asientos (los automáticos de ventas, cobros y gastos, y los manuales)
 * y permite registrar asientos MANUALES (ajustes, apertura, correcciones).
 * Reglas: Debe = Haber; cuentas de movimiento; período abierto. Un asiento no se edita:
 * se ANULA (genera su inverso). Los automáticos se anulan desde su factura / cobro / gasto.
 * Habla con app/controllers/contabilidad-controller.php (asiento_*).
 */
$tituloPagina = 'Asientos Contables';
$iconoPagina  = 'book-open-check';
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important}</style>

<div x-data="asientosApp()" x-init="init()" x-cloak class="space-y-6">
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[160px]"><label class="text-xs font-semibold text-slate-500">Buscar</label>
            <input type="text" x-model="fl.q" @keydown.enter="listar()" placeholder="Número o concepto" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="text-xs font-semibold text-slate-500">Desde</label><input type="date" x-model="fl.desde" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="text-xs font-semibold text-slate-500">Hasta</label><input type="date" x-model="fl.hasta" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="text-xs font-semibold text-slate-500">Tipo</label>
            <select x-model="fl.tipo" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"><option value="">Todos</option><option>MANUAL</option><option>VENTA</option><option>NOTA_CREDITO</option>
                <option>COBRO</option><option>GASTO</option><option>PAGO_GASTO</option><option>ANULACION</option><option>AJUSTE</option><option>APERTURA</option><option>CIERRE</option></select></div>
        <button @click="listar()" class="px-4 py-2 text-sm font-semibold bg-slate-100 hover:bg-slate-200 rounded-lg">Filtrar</button>
        <button @click="nuevo()" class="px-4 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm">+ Asiento manual</button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-4 py-3">Asiento</th><th class="text-left px-3 py-3">Fecha</th><th class="text-left px-3 py-3">Concepto</th>
                <th class="text-right px-3 py-3">Debe</th><th class="text-right px-3 py-3">Haber</th><th class="text-right px-4 py-3">Acciones</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <template x-for="a in rows" :key="a.id">
                    <tr :class="a.estatus==='ANULADO' ? 'opacity-50' : ''">
                        <td class="px-4 py-3"><div class="font-bold" x-text="a.numero"></div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600" x-text="a.tipo"></span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700" x-show="a.estatus==='ANULADO'">ANULADO</span></td>
                        <td class="px-3 py-3" x-text="fechaVe(a.fecha)"></td>
                        <td class="px-3 py-3"><div x-text="a.concepto"></div><div class="text-[11px] text-slate-400" x-text="'Por ' + a.usuario"></div></td>
                        <td class="px-3 py-3 text-right" x-text="bs(a.total_debe)"></td><td class="px-3 py-3 text-right" x-text="bs(a.total_haber)"></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap"><button @click="ver(a.id)" class="px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Ver</button></td>
                    </tr>
                </template>
                <tr x-show="!rows.length"><td colspan="6" class="px-4 py-10 text-center text-slate-400">No hay asientos con esos filtros.</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Detalle -->
    <div x-show="v.abierto" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl my-6"><template x-if="v.a"><div>
            <div class="flex items-center justify-between px-6 py-4 border-b"><div><h2 class="text-lg font-bold" x-text="'Asiento ' + v.a.numero"></h2>
                <p class="text-xs text-slate-500" x-text="fechaVe(v.a.fecha) + ' · ' + v.a.tipo + ' · ' + v.a.usuario"></p></div>
                <button @click="v.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button></div>
            <div class="p-6 space-y-3 text-sm">
                <p class="font-semibold" x-text="v.a.concepto"></p>
                <p class="text-xs text-red-600" x-show="v.a.reverso_numero" x-text="'Anulado por el asiento ' + v.a.reverso_numero"></p>
                <p class="text-xs text-slate-500" x-show="v.a.original_numero" x-text="'Es la anulación del asiento ' + v.a.original_numero"></p>
                <table class="w-full"><thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-3 py-2">Cuenta</th><th class="text-right px-3 py-2">Debe</th><th class="text-right px-3 py-2">Haber</th></tr></thead>
                    <tbody class="divide-y divide-slate-100"><template x-for="l in v.a.lineas" :key="l.id"><tr>
                        <td class="px-3 py-2"><span class="font-mono text-xs text-slate-500" x-text="l.codigo"></span> <span x-text="l.cuenta"></span><div class="text-[11px] text-slate-400" x-text="l.descripcion"></div></td>
                        <td class="px-3 py-2 text-right" x-text="l.debe>0 ? bs(l.debe) : ''"></td><td class="px-3 py-2 text-right" x-text="l.haber>0 ? bs(l.haber) : ''"></td></tr></template></tbody>
                    <tfoot class="font-bold bg-slate-50"><tr><td class="px-3 py-2">Totales</td><td class="px-3 py-2 text-right" x-text="bs(v.a.total_debe)"></td><td class="px-3 py-2 text-right" x-text="bs(v.a.total_haber)"></td></tr></tfoot></table>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 border-t bg-slate-50 rounded-b-2xl">
                <button x-show="v.a.estatus==='ACTIVO' && !v.a.asiento_original_id && ['MANUAL','AJUSTE','APERTURA','CIERRE','ANULACION'].includes(v.a.tipo) && v.a.tipo!=='ANULACION'" @click="anular(v.a)" class="px-3 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg">Anular asiento</button>
                <button @click="v.abierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cerrar</button></div>
        </div></template></div>
    </div>

    <!-- Nuevo asiento manual -->
    <div x-show="n.abierto" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl my-6">
            <div class="flex items-center justify-between px-6 py-4 border-b"><h2 class="text-lg font-bold">Nuevo asiento manual</h2>
                <button @click="n.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button></div>
            <div class="p-6 space-y-4 text-sm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div><label class="text-xs font-semibold text-slate-500">Fecha *</label><input type="date" x-model="n.fecha" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                    <div><label class="text-xs font-semibold text-slate-500">Tipo</label><select x-model="n.tipo" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option value="MANUAL">Manual</option><option value="AJUSTE">Ajuste</option><option value="APERTURA">Apertura</option></select></div>
                    <div class="md:col-span-2"><label class="text-xs font-semibold text-slate-500">Concepto *</label><input type="text" x-model="n.concepto" maxlength="255" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                </div>
                <div class="border border-slate-200 rounded-xl overflow-x-auto"><table class="w-full"><thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-3 py-2">Cuenta</th><th class="text-left px-3 py-2">Detalle</th>
                    <th class="px-2 py-2 w-36">Debe Bs</th><th class="px-2 py-2 w-36">Haber Bs</th><th class="w-8"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100"><template x-for="(l,i) in n.lineas" :key="i"><tr>
                        <td class="px-3 py-1.5"><select x-model="l.cuenta_id" class="w-full px-2 py-1.5 border border-slate-200 rounded-md"><option value="">— Cuenta —</option>
                            <template x-for="c in hojas" :key="c.id"><option :value="c.id" x-text="c.codigo + ' · ' + c.nombre"></option></template></select></td>
                        <td class="px-3 py-1.5"><input type="text" x-model="l.descripcion" maxlength="255" class="w-full px-2 py-1.5 border border-slate-200 rounded-md"></td>
                        <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" x-model.number="l.debe" @input="if(l.debe>0) l.haber=0" class="w-full px-2 py-1.5 border border-slate-200 rounded-md text-right"></td>
                        <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" x-model.number="l.haber" @input="if(l.haber>0) l.debe=0" class="w-full px-2 py-1.5 border border-slate-200 rounded-md text-right"></td>
                        <td class="px-1"><button @click="n.lineas.splice(i,1)" class="text-red-500 text-lg leading-none">&times;</button></td></tr></template></tbody>
                    <tfoot class="bg-slate-50 font-bold"><tr><td colspan="2" class="px-3 py-2"><button @click="n.lineas.push({cuenta_id:'',descripcion:'',debe:0,haber:0})" class="text-xs font-semibold text-indigo-700">+ Agregar línea</button></td>
                        <td class="px-2 py-2 text-right" x-text="bs(totD)"></td><td class="px-2 py-2 text-right" x-text="bs(totH)"></td><td></td></tr></tfoot></table></div>
                <p class="text-sm font-bold" :class="cuadra ? 'text-emerald-600' : 'text-red-600'" x-text="cuadra ? '✔ El asiento cuadra' : ('✖ Diferencia: ' + bs(Math.abs(totD - totH)))"></p>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-slate-50 rounded-b-2xl">
                <button @click="n.abierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cancelar</button>
                <button @click="guardar()" :disabled="!cuadra" class="px-5 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg disabled:opacity-50">Registrar asiento</button></div>
        </div>
    </div>
</div>

<script>
/** Componente Alpine de Asientos Contables. */
function asientosApp() {
    const API = '/contabilidad-controller';
    const r2 = (x) => Math.round((Number(x) + Number.EPSILON) * 100) / 100;
    return {
        rows: [], hojas: [], fl: { q: '', desde: '', hasta: '', tipo: '' },
        v: { abierto: false, a: null },
        n: { abierto: false, fecha: '', tipo: 'MANUAL', concepto: '', lineas: [] },
        async init() {
            const c = await api(API, { action: 'cuentas_listar' });
            if (c.status === 'success') this.hojas = c.data.filter(x => x.permite_movimiento == 1 && x.is_active == 1);
            await this.listar();
        },
        async listar() { const r = await api(API, Object.assign({ action: 'asientos_listar' }, this.fl)); if (r.status === 'success') this.rows = r.data; else toast('error', r.message); },
        async ver(id) { const r = await api(API, { action: 'asiento_ver', id }); if (r.status === 'success') this.v = { abierto: true, a: r.asiento }; else toast('error', r.message); },
        nuevo() { this.n = { abierto: true, fecha: hoyISO(), tipo: 'MANUAL', concepto: '',
            lineas: [{ cuenta_id: '', descripcion: '', debe: 0, haber: 0 }, { cuenta_id: '', descripcion: '', debe: 0, haber: 0 }] }; },
        get totD() { return r2(this.n.lineas.reduce((s, l) => s + (Number(l.debe) || 0), 0)); },
        get totH() { return r2(this.n.lineas.reduce((s, l) => s + (Number(l.haber) || 0), 0)); },
        get cuadra() { return this.totD > 0 && Math.abs(this.totD - this.totH) < 0.005; },
        async guardar() {
            const r = await api(API, { action: 'asiento_crear', fecha: this.n.fecha, tipo: this.n.tipo, concepto: this.n.concepto, lineas: this.n.lineas });
            if (r.status === 'success') { toast('success', r.message); this.n.abierto = false; this.listar(); } else toast('error', r.message);
        },
        async anular(a) {
            const motivo = await pedirMotivo('Anular el asiento ' + a.numero);
            if (!motivo) return;
            const r = await api(API, { action: 'asiento_anular', id: a.id, motivo });
            if (r.status === 'success') { toast('success', r.message); this.v.abierto = false; this.listar(); } else toast('error', r.message);
        },
    };
}
</script>
<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
