<?php
/**
 * =========================================================================
 * app/views/contabilidad/estados-financieros.php  —  ESTADOS FINANCIEROS
 * =========================================================================
 *  - Estado de Resultados : Ingresos - Costos - Gastos = Utilidad (en un rango de fechas).
 *  - Balance General      : Activo = Pasivo + Patrimonio (a una fecha). Si el ejercicio aún no
 *                           se ha cerrado, el resultado acumulado se suma al patrimonio.
 * Montos en bolívares. Habla con app/controllers/reportes-contables-controller.php.
 */
$tituloPagina = 'Estados Financieros';
$iconoPagina  = 'chart-pie';
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important} @media print { aside, header, .no-print { display:none!important } main { overflow:visible!important } }</style>

<div x-data="estadosApp()" x-init="init()" x-cloak class="space-y-5">
    <div class="no-print flex gap-2 border-b border-slate-200">
        <button @click="tab='er'; generar()" :class="tab==='er' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500'" class="px-4 py-2 text-sm font-bold border-b-2">Estado de Resultados</button>
        <button @click="tab='bg'; generar()" :class="tab==='bg' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500'" class="px-4 py-2 text-sm font-bold border-b-2">Balance General</button>
    </div>
    <div class="no-print bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-end gap-3">
        <div x-show="tab==='er'"><label class="text-xs font-semibold text-slate-500">Desde</label><input type="date" x-model="fl.desde" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <div><label class="text-xs font-semibold text-slate-500" x-text="tab==='er' ? 'Hasta' : 'Al'"></label><input type="date" x-model="fl.hasta" class="block mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
        <button @click="generar()" class="px-4 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg">Generar</button>
        <button @click="window.print()" class="px-4 py-2 text-sm font-semibold bg-slate-100 hover:bg-slate-200 rounded-lg">Imprimir</button>
    </div>

    <!-- ESTADO DE RESULTADOS -->
    <div x-show="tab==='er'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 max-w-3xl">
        <h2 class="text-lg font-black">Estado de Resultados</h2><p class="text-xs text-slate-400 mb-4" x-text="'Del ' + fechaVe(er.desde) + ' al ' + fechaVe(er.hasta) + ' · Bs.'"></p>
        <template x-for="g in [['INGRESOS', er.ingresos, er.total_ingresos], ['COSTOS', er.costos, er.total_costos]]" :key="g[0]"><div class="mb-4">
            <h3 class="text-xs font-black text-slate-500 uppercase mb-1" x-text="g[0]"></h3>
            <template x-for="l in g[1]" :key="l.codigo"><div class="flex justify-between text-sm py-1 border-b border-slate-100"><span x-text="l.nombre"></span><span x-text="fmt(l.monto)"></span></div></template>
            <div class="flex justify-between text-sm font-bold pt-1"><span x-text="'Total ' + g[0].toLowerCase()"></span><span x-text="fmt(g[2])"></span></div></div></template>
        <div class="flex justify-between font-black text-base bg-slate-50 rounded-lg px-3 py-2 mb-4"><span>UTILIDAD BRUTA</span><span x-text="fmt(er.utilidad_bruta)"></span></div>
        <h3 class="text-xs font-black text-slate-500 uppercase mb-1">GASTOS</h3>
        <template x-for="l in er.gastos" :key="l.codigo"><div class="flex justify-between text-sm py-1 border-b border-slate-100"><span x-text="l.nombre"></span><span x-text="fmt(l.monto)"></span></div></template>
        <div class="flex justify-between text-sm font-bold pt-1 mb-4"><span>Total gastos</span><span x-text="fmt(er.total_gastos)"></span></div>
        <div class="flex justify-between font-black text-lg rounded-lg px-3 py-3" :class="er.utilidad_neta >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"><span x-text="er.utilidad_neta >= 0 ? 'UTILIDAD NETA' : 'PÉRDIDA NETA'"></span><span x-text="fmt(er.utilidad_neta)"></span></div>
    </div>

    <!-- BALANCE GENERAL -->
    <div x-show="tab==='bg'" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h2 class="text-lg font-black">Balance General</h2><p class="text-xs text-slate-400 mb-4" x-text="'Al ' + fechaVe(bg.hasta) + ' · Bs.'"></p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div><h3 class="text-xs font-black text-slate-500 uppercase mb-1">ACTIVO</h3>
                <template x-for="l in bg.activos" :key="l.codigo"><div class="flex justify-between text-sm py-1 border-b border-slate-100"><span x-text="l.nombre"></span><span x-text="fmt(l.monto)"></span></div></template>
                <div class="flex justify-between font-black bg-slate-50 rounded-lg px-3 py-2 mt-2"><span>TOTAL ACTIVO</span><span x-text="fmt(bg.total_activo)"></span></div></div>
            <div><h3 class="text-xs font-black text-slate-500 uppercase mb-1">PASIVO</h3>
                <template x-for="l in bg.pasivos" :key="l.codigo"><div class="flex justify-between text-sm py-1 border-b border-slate-100"><span x-text="l.nombre"></span><span x-text="fmt(l.monto)"></span></div></template>
                <div class="flex justify-between font-bold text-sm pt-1 mb-4"><span>Total pasivo</span><span x-text="fmt(bg.total_pasivo)"></span></div>
                <h3 class="text-xs font-black text-slate-500 uppercase mb-1">PATRIMONIO</h3>
                <template x-for="l in bg.patrimonio" :key="l.codigo"><div class="flex justify-between text-sm py-1 border-b border-slate-100"><span x-text="l.nombre"></span><span x-text="fmt(l.monto)"></span></div></template>
                <div class="flex justify-between text-sm py-1 border-b border-slate-100" x-show="bg.resultado_periodo_no_cerrado != 0"><span>Resultado acumulado del ejercicio (sin cerrar)</span><span x-text="fmt(bg.resultado_periodo_no_cerrado)"></span></div>
                <div class="flex justify-between font-bold text-sm pt-1"><span>Total patrimonio</span><span x-text="fmt(bg.total_patrimonio)"></span></div>
                <div class="flex justify-between font-black bg-slate-50 rounded-lg px-3 py-2 mt-2"><span>PASIVO + PATRIMONIO</span><span x-text="fmt(bg.total_pasivo_patrimonio)"></span></div></div>
        </div>
        <p class="mt-4 text-sm font-bold" :class="bg.cuadra ? 'text-emerald-600' : 'text-red-600'" x-text="bg.cuadra ? '✔ El balance cuadra: Activo = Pasivo + Patrimonio' : '✖ El balance no cuadra: revisa los asientos'"></p>
    </div>
</div>

<script>
/** Componente Alpine de Estados Financieros. */
function estadosApp() {
    const API = '/reportes-contables-controller';
    const hoy = hoyISO();
    return {
        tab: 'er', fl: { desde: hoy.substring(0, 4) + '-01-01', hasta: hoy },
        er: { desde: hoy, hasta: hoy, ingresos: [], costos: [], gastos: [], total_ingresos: 0, total_costos: 0, total_gastos: 0, utilidad_bruta: 0, utilidad_neta: 0 },
        bg: { hasta: hoy, activos: [], pasivos: [], patrimonio: [], total_activo: 0, total_pasivo: 0, resultado_periodo_no_cerrado: 0, total_patrimonio: 0, total_pasivo_patrimonio: 0, cuadra: true },
        init() { this.generar(); },
        async generar() {
            const r = await api(API, { action: this.tab === 'er' ? 'estado_resultados' : 'balance_general', desde: this.fl.desde, hasta: this.fl.hasta });
            if (r.status !== 'success') return toast('error', r.message);
            if (this.tab === 'er') this.er = r; else this.bg = r;
        },
    };
}
</script>
<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
