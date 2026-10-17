<?php

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$grupos = [
    'ventas'         => ['/ventas/'],
    'contabilidad'   => ['/contabilidad/'],
    'envios'         => ['/logistica/tracking', '/logistica/despachos', '/logistica/recepcion', '/envios/tracking', '/envios/recepcion'],
    'laboratorio'    => ['/laboratorio/clinicas-sedes', '/laboratorio/pacientes', '/laboratorio/casos'],
    'administracion' => ['/administracion/dashboard-financiero', '/administracion/documentos'],
    'rrhh'           => ['/recursos-humanos/personal', '/recursos-humanos/cargos', '/recursos-humanos/departamentos'],
    'config'         => ['/configuraciones/tasa-de-cambio', '/configuraciones/monedas', '/configuraciones/gestion-usuarios'],
];

$grupoActivo = '';
foreach ($grupos as $key => $rutas) {
    foreach ($rutas as $ruta) {
        if (strpos($currentPath, $ruta) === 0) {
            $grupoActivo = $key;
            break 2;
        }
    }
}

function isActive($path, $currentPath) {
    return strpos($currentPath, $path) === 0 ? true : false;
}
?>

<!-- DEPENDENCIAS DE JAVASCRIPT LOCALES PARA EL ASIDE -->
<script defer src="/assets/vendor/alpinejs/collapse.min.js"></script>
<script defer src="/assets/vendor/alpinejs/alpine.min.js"></script>
<script src="/assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>

<!-- WRAPPER PRINCIPAL DE ESTADO -->
<div x-data="{ mobileMenuOpen: false }" class="h-full flex-shrink-0 z-50">

    <!-- CABECERA MÓVIL -->
    <div class="lg:hidden fixed top-0 left-0 w-full h-16 bg-slate-950 border-b border-slate-800 flex items-center justify-between px-4 z-40 shadow-sm">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-lg shadow-brand-500/20">
                <i data-lucide="activity" class="w-5 h-5 text-white"></i>
            </div>
            <h1 class="text-white font-bold text-xl tracking-tight">CNSB<span class="text-brand-500">Lab</span></h1>
        </div>
        <button @click="mobileMenuOpen = true" class="text-slate-400 hover:text-white p-2 focus:outline-none focus:ring-2 focus:ring-brand-500/50 rounded-xl transition-colors bg-slate-900">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- BACKDROP -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300" 
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0" 
         @click="mobileMenuOpen = false"
         class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 lg:hidden" style="display: none;">
    </div>

    <!-- BARRA LATERAL -->
    <aside :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-950 flex flex-col h-full border-r border-slate-800 overflow-y-auto transform transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0 shadow-2xl lg:shadow-none pt-16 lg:pt-0">

        <!-- Logo Escritorio -->
        <div class="h-16 px-6 border-b border-slate-800 flex items-center justify-between sticky top-0 bg-slate-950/95 backdrop-blur-sm z-10 hidden lg:flex">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-lg shadow-brand-500/20">
                    <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                </div>
                <h1 class="text-white font-bold text-xl tracking-tight">CNSB<span class="text-brand-500"> Lab</span></h1>
            </div>
        </div>

        <!-- Cerrar en móvil -->
        <div class="h-16 px-4 flex items-center justify-end lg:hidden absolute top-0 right-0 w-full bg-slate-950/95 backdrop-blur-sm z-10 border-b border-slate-800">
            <button @click="mobileMenuOpen = false" class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 px-4 py-6 space-y-1.5" x-data="{ openMenu: '<?= $grupoActivo ?>' }">

            <!-- ENVÍOS (Estructura: Tracking, Despachos, Recepción) -->
            <div class="rounded-xl overflow-hidden transition-all duration-200">
                <button @click="openMenu = openMenu === 'envios' ? '' : 'envios'"
                    :class="openMenu === 'envios' ? 'bg-brand-500/20 text-white' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all group">
                    <span class="flex items-center gap-3">
                        <i data-lucide="truck" class="w-5 h-5 transition-colors" :class="openMenu === 'envios' ? 'text-brand-500' : 'text-slate-500 group-hover:text-brand-500'"></i>
                        Envíos
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" :class="openMenu === 'envios' ? 'rotate-180 text-brand-500' : 'text-slate-500'"></i>
                </button>
                <div x-show="openMenu === 'envios'" x-collapse>
                    <ul class="pl-11 pr-3 py-2 space-y-1 relative before:content-[''] before:absolute before:left-[1.375rem] before:top-0 before:bottom-0 before:w-px before:bg-slate-800">
                        <li><a href="/logistica/tracking" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= (isActive('/logistica/tracking', $currentPath) || isActive('/envios/tracking', $currentPath)) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Tracking</a></li>
                        <li><a href="/logistica/despachos" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/logistica/despachos', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Despachos</a></li>
                        <li><a href="/logistica/recepcion" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= (isActive('/logistica/recepcion', $currentPath) || isActive('/envios/recepcion', $currentPath)) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Recepción</a></li>
                    </ul>
                </div>
            </div>

            <!-- LABORATORIO (antes Directorio Médico) -->
            <div class="rounded-xl overflow-hidden transition-all duration-200">
                <button @click="openMenu = openMenu === 'laboratorio' ? '' : 'laboratorio'"
                    :class="openMenu === 'laboratorio' ? 'bg-brand-500/20 text-white' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all group">
                    <span class="flex items-center gap-3">
                        <i data-lucide="users" class="w-5 h-5 transition-colors" :class="openMenu === 'laboratorio' ? 'text-brand-500' : 'text-slate-500 group-hover:text-brand-500'"></i>
                        Directorio Médico
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" :class="openMenu === 'laboratorio' ? 'rotate-180 text-brand-500' : 'text-slate-500'"></i>
                </button>
                <div x-show="openMenu === 'laboratorio'" x-collapse>
                    <ul class="pl-11 pr-3 py-2 space-y-1 relative before:content-[''] before:absolute before:left-[1.375rem] before:top-0 before:bottom-0 before:w-px before:bg-slate-800">
                        <li><a href="/laboratorio/clinicas-sedes" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/laboratorio/clinicas-sedes', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Clínicas y sedes</a></li>
                        <li><a href="/laboratorio/pacientes" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/laboratorio/pacientes', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Pacientes</a></li>
                        <li><a href="/laboratorio/casos" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/laboratorio/casos', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Casos</a></li>
                    </ul>
                </div>
            </div>

            <?php if (Auth::canAccessView('ventas/facturacion')): ?>
            <!-- VENTAS -->
            <div class="rounded-xl overflow-hidden transition-all duration-200">
                <button @click="openMenu = openMenu === 'ventas' ? '' : 'ventas'"
                    :class="openMenu === 'ventas' ? 'bg-brand-500/20 text-white' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all group">
                    <span class="flex items-center gap-3">
                        <i data-lucide="shopping-cart" class="w-5 h-5 transition-colors" :class="openMenu === 'ventas' ? 'text-brand-500' : 'text-slate-500 group-hover:text-brand-500'"></i>
                        Ventas
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" :class="openMenu === 'ventas' ? 'rotate-180 text-brand-500' : 'text-slate-500'"></i>
                </button>
                <div x-show="openMenu === 'ventas'" x-collapse>
                    <ul class="pl-11 pr-3 py-2 space-y-1 relative before:content-[''] before:absolute before:left-[1.375rem] before:top-0 before:bottom-0 before:w-px before:bg-slate-800">
                        <li><a href="/ventas/prefacturas" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/ventas/prefacturas', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Prefacturas</a></li>
                        <li><a href="/ventas/facturas" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/ventas/facturas', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Facturas</a></li>
                        <li><a href="/ventas/notas-credito" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/ventas/notas-credito', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Notas de Crédito</a></li>
                        <li><a href="/ventas/notas-entrega" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/ventas/notas-entrega', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Notas de Entrega</a></li>
                        <li><a href="/ventas/cobranzas" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/ventas/cobranzas', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Cobranzas y CxC</a></li>
                        <?php if (Auth::canAccessView('ventas/metodos-de-pago')): ?>
                        <li><a href="/ventas/metodos-de-pago" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/ventas/metodos-de-pago', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Métodos de Pago</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <?php if (Auth::canAccessView('contabilidad/plan-cuentas')): ?>
            <!-- CONTABILIDAD -->
            <div class="rounded-xl overflow-hidden transition-all duration-200">
                <button @click="openMenu = openMenu === 'contabilidad' ? '' : 'contabilidad'"
                    :class="openMenu === 'contabilidad' ? 'bg-brand-500/20 text-white' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all group">
                    <span class="flex items-center gap-3">
                        <i data-lucide="calculator" class="w-5 h-5 transition-colors" :class="openMenu === 'contabilidad' ? 'text-brand-500' : 'text-slate-500 group-hover:text-brand-500'"></i>
                        Contabilidad
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" :class="openMenu === 'contabilidad' ? 'rotate-180 text-brand-500' : 'text-slate-500'"></i>
                </button>
                <div x-show="openMenu === 'contabilidad'" x-collapse>
                    <ul class="pl-11 pr-3 py-2 space-y-1 relative before:content-[''] before:absolute before:left-[1.375rem] before:top-0 before:bottom-0 before:w-px before:bg-slate-800">
                        <li><a href="/contabilidad/plan-cuentas" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/contabilidad/plan-cuentas', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Plan de Cuentas</a></li>
                        <li><a href="/contabilidad/asientos" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/contabilidad/asientos', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Asientos Contables</a></li>
                        <li><a href="/contabilidad/gastos" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/contabilidad/gastos', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Gastos y Compras</a></li>
                        <li><a href="/contabilidad/libros" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/contabilidad/libros', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Libros Contables</a></li>
                        <li><a href="/contabilidad/estados-financieros" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/contabilidad/estados-financieros', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Estados Financieros</a></li>
                        <li><a href="/contabilidad/periodos" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/contabilidad/periodos', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Períodos y Cierre</a></li>
                        <li><a href="/contabilidad/configuracion" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/contabilidad/configuracion', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Configuración</a></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- ADMINISTRACIÓN -->
            <div class="rounded-xl overflow-hidden transition-all duration-200">
                <button @click="openMenu = openMenu === 'administracion' ? '' : 'administracion'"
                    :class="openMenu === 'administracion' ? 'bg-brand-500/20 text-white' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all group">
                    <span class="flex items-center gap-3">
                        <i data-lucide="file-text" class="w-5 h-5 transition-colors" :class="openMenu === 'administracion' ? 'text-brand-500' : 'text-slate-500 group-hover:text-brand-500'"></i>
                        Administración
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" :class="openMenu === 'administracion' ? 'rotate-180 text-brand-500' : 'text-slate-500'"></i>
                </button>
                <div x-show="openMenu === 'administracion'" x-collapse>
                    <ul class="pl-11 pr-3 py-2 space-y-1 relative before:content-[''] before:absolute before:left-[1.375rem] before:top-0 before:bottom-0 before:w-px before:bg-slate-800">
                        <li><a href="/administracion/dashboard-financiero" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/administracion/dashboard-financiero', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Dashboard Financiero</a></li>
                        <li><a href="/administracion/documentos" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/administracion/documentos', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Generación de Documentos</a></li>
                    </ul>
                </div>
            </div>

            <!-- RECURSOS HUMANOS -->
            <div class="rounded-xl overflow-hidden transition-all duration-200">
                <button @click="openMenu = openMenu === 'rrhh' ? '' : 'rrhh'"
                    :class="openMenu === 'rrhh' ? 'bg-brand-500/20 text-white' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all group">
                    <span class="flex items-center gap-3">
                        <i data-lucide="user-check" class="w-5 h-5 transition-colors" :class="openMenu === 'rrhh' ? 'text-brand-500' : 'text-slate-500 group-hover:text-brand-500'"></i>
                        Recursos Humanos
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" :class="openMenu === 'rrhh' ? 'rotate-180 text-brand-500' : 'text-slate-500'"></i>
                </button>
                <div x-show="openMenu === 'rrhh'" x-collapse>
                    <ul class="pl-11 pr-3 py-2 space-y-1 relative before:content-[''] before:absolute before:left-[1.375rem] before:top-0 before:bottom-0 before:w-px before:bg-slate-800">
                        <li><a href="/recursos-humanos/personal" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/recursos-humanos/personal', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Gestión de Personal</a></li>
                        <li><a href="/recursos-humanos/cargos" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/recursos-humanos/cargos', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Cargos</a></li>
                        <li><a href="/recursos-humanos/departamentos" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/recursos-humanos/departamentos', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Departamentos</a></li>
                    </ul>
                </div>
            </div>

            <!-- CONFIGURACIONES -->
            <div class="rounded-xl overflow-hidden transition-all duration-200">
                <button @click="openMenu = openMenu === 'config' ? '' : 'config'"
                    :class="openMenu === 'config' ? 'bg-brand-500/20 text-white' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-semibold rounded-xl transition-all group">
                    <span class="flex items-center gap-3">
                        <i data-lucide="settings" class="w-5 h-5 transition-colors" :class="openMenu === 'config' ? 'text-brand-500' : 'text-slate-500 group-hover:text-brand-500'"></i>
                        Configuraciones
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" :class="openMenu === 'config' ? 'rotate-180 text-brand-500' : 'text-slate-500'"></i>
                </button>
                <div x-show="openMenu === 'config'" x-collapse>
                    <ul class="pl-11 pr-3 py-2 space-y-1 relative before:content-[''] before:absolute before:left-[1.375rem] before:top-0 before:bottom-0 before:w-px before:bg-slate-800">
                        <li><a href="/configuraciones/tasa-de-cambio" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/configuraciones/tasa-de-cambio', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Tasas de Cambio</a></li>
                        <li><a href="/configuraciones/monedas" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/configuraciones/monedas', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Monedas</a></li>
                        <li><a href="/configuraciones/gestion-usuarios" class="block px-3 py-2 rounded-lg text-sm transition-all font-medium <?= isActive('/configuraciones/gestion-usuarios', $currentPath) ? 'text-white bg-brand-500/20 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' ?>">Gestión Usuarios</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Perfil con Cerrar Sesión -->
        <div class="p-4 mt-auto border-t border-slate-800 bg-slate-900/30">
            <div class="flex items-center gap-3 bg-slate-900/50 p-2.5 rounded-xl border border-slate-800 hover:border-slate-700 transition-all">
                <div class="relative flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm shadow-sm ring-2 ring-slate-800">
                        <?= htmlspecialchars(substr($_SESSION['usuario_nombre'] ?? 'A', 0, 1)) ?>
                    </div>
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-slate-900 rounded-full"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-100 truncate">Administrador</p>
                    <p class="text-[11px] font-medium text-slate-500 truncate">admin@sistema.com</p>
                </div>
                <button onclick="cerrarSesion()" 
                        title="Cerrar Sesión"
                        class="p-2 text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-all flex-shrink-0">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </aside>
</div>

<script>
    function cerrarSesion() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: 'Serás redirigido a la pantalla de inicio de sesión.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            buttonsStyling: false,
            scrollbarPadding: false,
            heightAuto: false,
            customClass: {
                container: 'absolute inset-0 !z-[100]',
                backdrop: 'backdrop-blur-sm bg-slate-900/50',
                popup: 'rounded-2xl shadow-2xl p-6 font-sans bg-white border border-slate-100',
                title: 'text-lg font-bold text-slate-800 mt-2',
                htmlContainer: 'text-sm text-slate-600',
                confirmButton: 'mt-2 px-5 py-2.5 rounded-xl bg-rose-600 text-white font-medium hover:bg-rose-700 shadow-md shadow-rose-500/20 transition-all text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2',
                cancelButton: 'mt-2 ml-3 px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-all text-sm focus:outline-none'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/cerrar-sesion-controller';
            }
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>