<?php
/**
 * =========================================================================
 * app/views/contabilidad/configuracion.php  —  CONFIGURACIÓN FISCAL Y CONTABLE
 * =========================================================================
 * Parámetros que usan Ventas y Contabilidad:
 *   - Datos de la empresa (salen en la factura digital y en los mensajes de WhatsApp).
 *   - Impuestos: IVA y IGTF (verifica las alícuotas vigentes con tu contador).
 *   - Cuentas automáticas: qué cuenta del plan usa el sistema para CxC, ingresos, IVA, etc.
 *   - Plantillas de mensajes de WhatsApp (puedes usar las {variables} indicadas).
 * Habla con app/controllers/contabilidad-controller.php (config_get / config_set).
 */
$tituloPagina = 'Configuración Fiscal y Contable';
$iconoPagina  = 'settings-2';
include __DIR__ . '/../components/pagina-inicio.php';
?>
<style>[x-cloak]{display:none!important}</style>

<div x-data="configApp()" x-init="init()" x-cloak class="space-y-6 max-w-4xl">
    <template x-for="g in grupos" :key="g.titulo"><div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h2 class="font-bold text-slate-800" x-text="g.titulo"></h2><p class="text-xs text-slate-400 mb-4" x-text="g.ayuda"></p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><template x-for="c in g.items" :key="c.clave"><div :class="c.largo ? 'md:col-span-2' : ''">
            <label class="text-xs font-semibold text-slate-500" x-text="c.descripcion"></label>
            <template x-if="c.clave.startsWith('cta_')"><select x-model="v[c.clave]" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"><template x-for="a in cuentas" :key="a.codigo"><option :value="a.codigo" x-text="a.codigo + ' · ' + a.nombre"></option></template></select></template>
            <template x-if="!c.clave.startsWith('cta_') && c.largo"><textarea x-model="v[c.clave]" rows="6" class="w-full mt-1 px-3 py-2 text-xs font-mono border border-slate-300 rounded-lg"></textarea></template>
            <template x-if="!c.clave.startsWith('cta_') && !c.largo"><input type="text" x-model="v[c.clave]" class="w-full mt-1 px-3 py-2 text-sm border border-slate-300 rounded-lg"></template>
        </div></template></div></div></template>
    <div class="flex justify-end"><button @click="guardar()" class="px-6 py-3 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-sm">Guardar configuración</button></div>
</div>

<script>
/** Componente Alpine de Configuración. */
function configApp() {
    const API = '/contabilidad-controller';
    return {
        v: {}, cuentas: [], filas: [],
        async init() { const r = await api(API, { action: 'config_get' }); if (r.status !== 'success') return toast('error', r.message); this.cuentas = r.cuentas; this.filas = r.config; r.config.forEach(c => this.v[c.clave] = c.valor || ''); },
        get grupos() {
            const f = (pref) => this.filas.filter(c => c.clave.startsWith(pref)).map(c => Object.assign({}, c, { largo: c.clave.startsWith('whatsapp_') }));
            const otros = this.filas.filter(c => !c.clave.startsWith('empresa_') && !c.clave.startsWith('cta_') && !c.clave.startsWith('whatsapp_'));
            return [
                { titulo: 'Datos de la empresa', ayuda: 'Aparecen en la factura digital.', items: f('empresa_') },
                { titulo: 'Impuestos y condiciones', ayuda: 'Verifica las alícuotas vigentes con tu contador.', items: otros },
                { titulo: 'Cuentas contables automáticas', ayuda: 'El sistema usa estas cuentas al contabilizar facturas, cobros y gastos.', items: f('cta_') },
                { titulo: 'Mensajes de WhatsApp', ayuda: 'Se pueden editar. Variables entre llaves, ej: {cliente}, {enlace}.', items: f('whatsapp_') },
            ];
        },
        async guardar() { const r = await api(API, { action: 'config_set', valores: this.v }); if (r.status === 'success') toast('success', r.message); else toast('error', r.message); },
    };
}
</script>
<?php include __DIR__ . '/../components/pagina-fin.php'; ?>
