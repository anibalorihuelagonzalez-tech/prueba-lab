<?php
/**
 * =========================================================================
 * app/views/ventas/metodos-de-pago.php  —  MÉTODOS DE PAGO
 * =========================================================================
 * (Antes era un archivo casi vacío.) Aquí se define con qué se les puede cobrar
 * a las clínicas y a qué CUENTA CONTABLE (caja o banco) entra cada método.
 * Habla con app/controllers/metodos-pago-controller.php.
 *   - Moneda: USD o VES. Solo los métodos en USD pueden cobrar IGTF.
 *   - "Requiere referencia": obliga a escribir el n° de transferencia / confirmación.
 */
$tituloPagina = 'Métodos de Pago';
$iconoPagina  = 'credit-card';
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important}</style>

<div x-data="metodosApp()" x-init="init()" x-cloak class="space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">Cada método de pago envía el dinero a su cuenta contable (caja o banco) cuando registras un cobro.</p>
        <button @click="nuevo()" class="px-4 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm">+ Nuevo método</button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-4 py-3">Método</th><th class="text-left px-3 py-3">Tipo</th><th class="px-3 py-3">Moneda</th>
                <th class="text-left px-3 py-3">Cuenta contable</th><th class="px-3 py-3">IGTF</th><th class="px-3 py-3">Estado</th><th class="text-right px-4 py-3">Acciones</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <template x-for="m in rows" :key="m.id">
                    <tr :class="m.is_active==1 ? '' : 'opacity-50'">
                        <td class="px-4 py-3 font-semibold text-slate-800" x-text="m.nombre"></td>
                        <td class="px-3 py-3 text-slate-500" x-text="m.tipo.replace('_',' ')"></td>
                        <td class="px-3 py-3 text-center"><span class="px-2 py-0.5 text-xs font-bold rounded-full" :class="m.moneda_codigo==='USD' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'" x-text="m.moneda_codigo"></span></td>
                        <td class="px-3 py-3 text-slate-600 text-xs" x-text="m.cuenta_codigo + ' · ' + m.cuenta_nombre"></td>
                        <td class="px-3 py-3 text-center" x-text="m.aplica_igtf==1 ? 'Sí' : '—'"></td>
                        <td class="px-3 py-3 text-center"><span class="text-xs font-bold" :class="m.is_active==1 ? 'text-emerald-600' : 'text-slate-400'" x-text="m.is_active==1 ? 'Activo' : 'Inactivo'"></span></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button @click="editar(m)" class="px-2.5 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg">Editar</button>
                            <button @click="alternar(m)" class="px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg" x-text="m.is_active==1 ? 'Desactivar' : 'Activar'"></button>
                            <button @click="eliminar(m)" x-show="m.usos==0" class="px-2.5 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg">Eliminar</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div x-show="f.abierto" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b"><h2 class="text-lg font-bold" x-text="f.id ? 'Editar método' : 'Nuevo método de pago'"></h2>
                <button @click="f.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button></div>
            <div class="p-6 space-y-4 text-sm">
                <div><label class="text-xs font-semibold text-slate-500">Nombre *</label><input type="text" x-model="f.nombre" maxlength="80" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-xs font-semibold text-slate-500">Tipo</label>
                        <select x-model="f.tipo" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            <option value="EFECTIVO">Efectivo</option><option value="TRANSFERENCIA">Transferencia</option><option value="PAGO_MOVIL">Pago móvil</option>
                            <option value="ZELLE">Zelle</option><option value="PUNTO_VENTA">Punto de venta</option><option value="CHEQUE">Cheque</option><option value="OTRO">Otro</option></select></div>
                    <div><label class="text-xs font-semibold text-slate-500">Moneda</label>
                        <select x-model="f.moneda_codigo" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option value="VES">VES (Bolívares)</option><option value="USD">USD (Dólares)</option></select></div>
                </div>
                <div><label class="text-xs font-semibold text-slate-500">Cuenta contable donde entra el dinero *</label>
                    <select x-model="f.cuenta_contable_id" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option value="">— Selecciona —</option>
                        <template x-for="c in cuentas" :key="c.id"><option :value="c.id" x-text="c.codigo + ' · ' + c.nombre"></option></template></select></div>
                <label class="flex items-center gap-2"><input type="checkbox" x-model="f.aplica_igtf" :disabled="f.moneda_codigo!=='USD'" class="w-4 h-4"> Aplica IGTF (solo pagos en divisas)</label>
                <label class="flex items-center gap-2"><input type="checkbox" x-model="f.requiere_referencia" class="w-4 h-4"> Exigir número de referencia al cobrar</label>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-slate-50 rounded-b-2xl">
                <button @click="f.abierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cancelar</button>
                <button @click="guardar()" class="px-5 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg">Guardar</button></div>
        </div>
    </div>
</div>

<script>
/** Componente Alpine de Métodos de Pago. */
function metodosApp() {
    const API = '/metodos-pago-controller';
    return {
        rows: [], cuentas: [],
        f: { abierto: false, id: 0, nombre: '', tipo: 'EFECTIVO', moneda_codigo: 'VES', cuenta_contable_id: '', aplica_igtf: false, requiere_referencia: false },
        async init() {
            const c = await api(API, { action: 'cuentas_disponibles' });
            if (c.status === 'success') this.cuentas = c.data;
            await this.listar();
        },
        async listar() { const r = await api(API, { action: 'listar' }); if (r.status === 'success') this.rows = r.data; else toast('error', r.message); },
        nuevo() { this.f = { abierto: true, id: 0, nombre: '', tipo: 'EFECTIVO', moneda_codigo: 'VES', cuenta_contable_id: '', aplica_igtf: false, requiere_referencia: false }; },
        editar(m) { this.f = { abierto: true, id: m.id, nombre: m.nombre, tipo: m.tipo, moneda_codigo: m.moneda_codigo, cuenta_contable_id: String(m.cuenta_contable_id),
                               aplica_igtf: m.aplica_igtf == 1, requiere_referencia: m.requiere_referencia == 1 }; },
        async guardar() {
            const r = await api(API, Object.assign({ action: 'guardar' }, this.f, { aplica_igtf: this.f.aplica_igtf ? 1 : 0, requiere_referencia: this.f.requiere_referencia ? 1 : 0 }));
            if (r.status === 'success') { toast('success', r.message); this.f.abierto = false; this.listar(); } else toast('error', r.message);
        },
        async alternar(m) { const r = await api(API, { action: 'alternar_estado', id: m.id }); if (r.status === 'success') this.listar(); else toast('error', r.message); },
        async eliminar(m) {
            if (!(await confirmar('¿Eliminar "' + m.nombre + '"?', '', 'Sí, eliminar'))) return;
            const r = await api(API, { action: 'eliminar', id: m.id });
            if (r.status === 'success') { toast('success', r.message); this.listar(); } else toast('error', r.message);
        },
    };
}
</script>
<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
