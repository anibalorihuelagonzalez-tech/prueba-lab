<?php
/**
 * =========================================================================
 * app/views/contabilidad/periodos.php  —  PERÍODOS Y CIERRE CONTABLE
 * =========================================================================
 *  - Cada mes es un período. Un mes CERRADO no admite asientos (ni facturas, ni cobros, ni gastos
 *    con fecha en ese mes) → protege los libros ya declarados.
 *  - CIERRE DE EJERCICIO: al terminar el año, lleva a cero Ingresos, Costos y Gastos y pasa el
 *    resultado a la cuenta "Resultado del Ejercicio" (asiento tipo CIERRE al 31 de diciembre).
 * Habla con app/controllers/contabilidad-controller.php (periodo_*, cierre_ejercicio).
 */
$tituloPagina = 'Períodos y Cierre';
$iconoPagina  = 'calendar-check';
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important}</style>

<div x-data="periodosApp()" x-init="init()" x-cloak class="space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <label class="text-sm font-semibold text-slate-600">Año</label>
        <select x-model.number="anio" @change="cargar()" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"><template x-for="a in anios" :key="a"><option :value="a" x-text="a"></option></template></select>
        <span x-show="cerrado" class="px-3 py-1 text-xs font-bold bg-violet-100 text-violet-700 rounded-full">Ejercicio cerrado</span>
        <button @click="cerrarEjercicio()" :disabled="cerrado" class="ml-auto px-4 py-2 text-sm font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-lg disabled:opacity-40">Cerrar ejercicio <span x-text="anio"></span></button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <template x-for="m in meses" :key="m.mes"><div class="bg-white p-4 rounded-2xl border shadow-sm" :class="m.estatus==='CERRADO' ? 'border-slate-300 bg-slate-50' : 'border-emerald-200'">
            <p class="font-bold text-slate-800" x-text="nombreMes(m.mes)"></p>
            <p class="text-xs mt-1" :class="m.estatus==='CERRADO' ? 'text-slate-500' : 'text-emerald-600'" x-text="m.estatus"></p>
            <p class="text-[11px] text-slate-400" x-text="m.asientos + ' asiento(s)'"></p>
            <button x-show="m.estatus==='ABIERTO'" @click="cerrar(m)" class="mt-3 w-full px-2 py-1.5 text-xs font-semibold text-white bg-slate-700 hover:bg-slate-800 rounded-lg">Cerrar mes</button>
            <button x-show="m.estatus==='CERRADO'" @click="reabrir(m)" class="mt-3 w-full px-2 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg">Reabrir</button></div></template>
    </div>
    <p class="text-xs text-slate-400">Tip: cierra el mes cuando ya declaraste el IVA y conciliaste los bancos. Si necesitas corregir algo, reabre el mes, corrige y vuelve a cerrarlo.</p>
</div>

<script>
/** Componente Alpine de Períodos contables. */
function periodosApp() {
    const API = '/contabilidad-controller';
    const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const actual = new Date().getFullYear();
    return {
        anio: actual, anios: [actual + 1, actual, actual - 1, actual - 2, actual - 3], meses: [], cerrado: false,
        nombreMes(m) { return MESES[m - 1]; },
        init() { this.cargar(); },
        async cargar() { const r = await api(API, { action: 'periodos_listar', anio: this.anio }); if (r.status === 'success') { this.meses = r.meses; this.cerrado = r.ejercicio_cerrado; } else toast('error', r.message); },
        async cerrar(m) {
            if (!(await confirmar('¿Cerrar ' + this.nombreMes(m.mes) + ' ' + this.anio + '?', 'Ya no se podrán registrar movimientos con fecha en ese mes.', 'Sí, cerrar'))) return;
            const r = await api(API, { action: 'periodo_cerrar', anio: this.anio, mes: m.mes });
            if (r.status === 'success') { toast('success', r.message); this.cargar(); } else toast('error', r.message);
        },
        async reabrir(m) {
            if (!(await confirmar('¿Reabrir ' + this.nombreMes(m.mes) + '?', 'Volverá a admitir movimientos.', 'Sí, reabrir'))) return;
            const r = await api(API, { action: 'periodo_reabrir', anio: this.anio, mes: m.mes });
            if (r.status === 'success') { toast('success', r.message); this.cargar(); } else toast('error', r.message);
        },
        async cerrarEjercicio() {
            if (!(await confirmar('¿Cerrar el ejercicio ' + this.anio + '?', 'Se generará un asiento al 31/12/' + this.anio + ' que lleva a cero ingresos, costos y gastos contra el Resultado del Ejercicio. Diciembre debe estar abierto.', 'Sí, cerrar ejercicio'))) return;
            const r = await api(API, { action: 'cierre_ejercicio', anio: this.anio });
            if (r.status === 'success') { toast('success', r.message); this.cargar(); } else toast('error', r.message);
        },
    };
}
</script>
<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
