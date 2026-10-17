<?php
/**
 * =========================================================================
 * app/views/contabilidad/plan-cuentas.php  —  PLAN DE CUENTAS
 * =========================================================================
 * Catálogo contable en árbol (1 Activo, 1.1 Activo corriente, 1.1.01 ...).
 *  - Cuentas de GRUPO (no admiten movimientos): sirven para ordenar y sumar.
 *  - Cuentas de MOVIMIENTO (hoja): las que se usan en los asientos.
 *  - Cuentas de SISTEMA (🔒): las usa el programa en asientos automáticos; no se borran.
 * El saldo mostrado es el acumulado histórico con el signo natural de la cuenta.
 * Habla con app/controllers/contabilidad-controller.php (cuentas_*).
 */
$tituloPagina = 'Plan de Cuentas';
$iconoPagina  = 'list-tree';
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important}</style>

<div x-data="planApp()" x-init="init()" x-cloak class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <input type="text" x-model="q" placeholder="Buscar por código o nombre…" class="w-full sm:w-72 px-3 py-2 text-sm border border-slate-300 rounded-lg">
        <button @click="nueva()" class="px-4 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm">+ Nueva cuenta</button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase"><tr><th class="text-left px-4 py-3">Código</th><th class="text-left px-3 py-3">Cuenta</th><th class="px-3 py-3">Tipo</th>
                <th class="px-3 py-3">Naturaleza</th><th class="text-right px-3 py-3">Saldo Bs</th><th class="text-right px-4 py-3">Acciones</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <template x-for="c in filtradas" :key="c.id">
                    <tr :class="[c.is_active==1 ? '' : 'opacity-40', c.permite_movimiento==1 ? '' : 'bg-slate-50/80 font-semibold']">
                        <td class="px-4 py-2 font-mono text-xs" x-text="c.codigo"></td>
                        <td class="px-3 py-2"><span :style="'padding-left:' + ((c.nivel-1)*16) + 'px'" x-text="c.nombre"></span>
                            <span x-show="c.es_sistema==1" title="Cuenta usada por el sistema" class="ml-1">🔒</span></td>
                        <td class="px-3 py-2 text-center text-xs text-slate-500" x-text="c.tipo"></td>
                        <td class="px-3 py-2 text-center text-xs text-slate-500" x-text="c.naturaleza"></td>
                        <td class="px-3 py-2 text-right" x-text="bs(saldo(c))"></td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button @click="editar(c)" class="px-2 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg">Editar</button>
                            <button @click="alternar(c)" x-show="c.es_sistema==0" class="px-2 py-1 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg" x-text="c.is_active==1 ? 'Desactivar' : 'Activar'"></button>
                            <button @click="eliminar(c)" x-show="c.es_sistema==0 && c.movimientos==0" class="px-2 py-1 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg">Eliminar</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div x-show="f.abierto" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b"><h2 class="text-lg font-bold" x-text="f.id ? 'Editar cuenta' : 'Nueva cuenta'"></h2>
                <button @click="f.abierto=false" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button></div>
            <div class="p-6 space-y-4 text-sm">
                <div><label class="text-xs font-semibold text-slate-500">Cuenta padre</label>
                    <select x-model="f.padre_id" @change="alElegirPadre()" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option value="">— Ninguna (cuenta raíz) —</option>
                        <template x-for="p in cuentas.filter(x => x.permite_movimiento==0 || x.id==f.padre_id)" :key="p.id"><option :value="p.id" x-text="p.codigo + ' · ' + p.nombre"></option></template></select></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-xs font-semibold text-slate-500">Código *</label><input type="text" x-model="f.codigo" maxlength="30" placeholder="1.1.01.006" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg font-mono"></div>
                    <div><label class="text-xs font-semibold text-slate-500">Nombre *</label><input type="text" x-model="f.nombre" maxlength="150" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"></div>
                    <div><label class="text-xs font-semibold text-slate-500">Tipo</label>
                        <select x-model="f.tipo" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option>ACTIVO</option><option>PASIVO</option><option>PATRIMONIO</option><option>INGRESO</option><option>COSTO</option><option>GASTO</option></select></div>
                    <div><label class="text-xs font-semibold text-slate-500">Naturaleza</label>
                        <select x-model="f.naturaleza" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><option>DEUDORA</option><option>ACREEDORA</option></select></div>
                </div>
                <label class="flex items-center gap-2"><input type="checkbox" x-model="f.permite_movimiento" class="w-4 h-4"> Admite movimientos (desmarca si es una cuenta de grupo)</label>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-slate-50 rounded-b-2xl">
                <button @click="f.abierto=false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg">Cancelar</button>
                <button @click="guardar()" class="px-5 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg">Guardar</button></div>
        </div>
    </div>
</div>

<script>
/** Componente Alpine del Plan de Cuentas. */
function planApp() {
    const API = '/contabilidad-controller';
    return {
        cuentas: [], q: '',
        f: { abierto: false, id: 0, padre_id: '', codigo: '', nombre: '', tipo: 'ACTIVO', naturaleza: 'DEUDORA', permite_movimiento: true },
        async init() { await this.cargar(); },
        async cargar() { const r = await api(API, { action: 'cuentas_listar' }); if (r.status === 'success') this.cuentas = r.data; else toast('error', r.message); },
        get filtradas() {
            const t = this.q.trim().toLowerCase();
            return t ? this.cuentas.filter(c => c.codigo.includes(t) || c.nombre.toLowerCase().includes(t)) : this.cuentas;
        },
        /** Saldo propio con el signo natural; las cuentas de grupo suman a sus descendientes (por prefijo de código). */
        saldo(c) {
            const hijos = c.permite_movimiento == 1 ? [c] : this.cuentas.filter(x => x.codigo === c.codigo || x.codigo.startsWith(c.codigo + '.'));
            let d = 0, h = 0;
            for (const x of hijos) { d += Number(x.debe); h += Number(x.haber); }
            return c.naturaleza === 'DEUDORA' ? d - h : h - d;
        },
        nueva() { this.f = { abierto: true, id: 0, padre_id: '', codigo: '', nombre: '', tipo: 'ACTIVO', naturaleza: 'DEUDORA', permite_movimiento: true }; },
        editar(c) { this.f = { abierto: true, id: c.id, padre_id: c.padre_id ? String(c.padre_id) : '', codigo: c.codigo, nombre: c.nombre, tipo: c.tipo, naturaleza: c.naturaleza, permite_movimiento: c.permite_movimiento == 1 }; },
        /** Al elegir el padre se sugiere código, tipo y naturaleza coherentes. */
        alElegirPadre() {
            const p = this.cuentas.find(x => String(x.id) === String(this.f.padre_id));
            if (!p || this.f.id) return;
            this.f.tipo = p.tipo; this.f.naturaleza = p.naturaleza;
            const hijos = this.cuentas.filter(x => String(x.padre_id) === String(p.id));
            const ult = hijos.map(x => x.codigo.split('.').pop()).sort().pop();
            const largo = ult ? ult.length : 3;
            const sig = String((ult ? parseInt(ult, 10) : 0) + 1).padStart(largo, '0');
            this.f.codigo = p.codigo + '.' + sig;
        },
        async guardar() {
            const r = await api(API, Object.assign({ action: 'cuenta_guardar' }, this.f, { permite_movimiento: this.f.permite_movimiento ? 1 : 0 }));
            if (r.status === 'success') { toast('success', r.message); this.f.abierto = false; this.cargar(); } else toast('error', r.message);
        },
        async alternar(c) { const r = await api(API, { action: 'cuenta_alternar', id: c.id }); if (r.status === 'success') this.cargar(); else toast('error', r.message); },
        async eliminar(c) {
            if (!(await confirmar('¿Eliminar la cuenta ' + c.codigo + '?', c.nombre, 'Sí, eliminar'))) return;
            const r = await api(API, { action: 'cuenta_eliminar', id: c.id });
            if (r.status === 'success') { toast('success', r.message); this.cargar(); } else toast('error', r.message);
        },
    };
}
</script>
<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
