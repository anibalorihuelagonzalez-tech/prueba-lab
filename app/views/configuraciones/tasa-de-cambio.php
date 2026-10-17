<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../helpers/auth.php';

// Validación de sesión
if (!Auth::check()) {
    header('Location: /');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<?php include_once __DIR__ . '/../components/head.php'; ?>

<body class="h-full flex overflow-hidden">

    <!-- ASIDE COMPONENT -->
    <?php include_once __DIR__ . '/../components/aside.php'; ?>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <!-- Top Navbar -->
        <header
            class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-8 z-10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center font-bold">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Tasas de Cambio</h1>
                </div>
            </div>
            <div class="flex items-center space-x-3 md:space-x-4 hidden sm:flex">
                <span class="text-sm font-medium text-slate-600">Hola,
                    <strong><?= htmlspecialchars(Auth::name() ?? 'Administrador') ?></strong></span>
                <div
                    class="h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                    <?= htmlspecialchars(substr(Auth::name() ?? 'A', 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Main View Scrollable Area -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden bg-slate-50/50 p-4 md:p-8 space-y-6">

            <!-- KPIs Header Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- KPI 1: Tasa Oficial Vigente -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Tasa Oficial</p>
                            <span id="kpi-tasa-fuente" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-brand-50 text-brand-700 border border-brand-100">BCV</span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 mt-1 flex items-baseline gap-1">
                            <span id="kpi-tasa-valor">0.0000</span>
                            <span id="kpi-tasa-simbolo" class="text-xs font-bold text-slate-400">Bs.</span>
                        </h3>
                        <p id="kpi-tasa-par" class="text-[11px] text-emerald-600 font-semibold mt-0.5">1 USD = 0.0000 VES</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-6 h-6"></i>
                    </div>
                </div>

                <!-- KPI 2: Par Principal -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Par Principal</p>
                        <h3 id="kpi-par-principal" class="text-xl font-black text-indigo-700 mt-1">USD → VES</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Dólar a Bolívar Oficial</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="arrow-left-right" class="w-6 h-6"></i>
                    </div>
                </div>

                <!-- KPI 3: Tasas Activas -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Tasas Activas</p>
                        <h3 id="kpi-tasas-activas" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-blue-600 font-medium mt-0.5">Vigentes en el sistema</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6"></i>
                    </div>
                </div>

                <!-- KPI 4: Total Histórico -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Histórico de Tasas</p>
                        <h3 id="kpi-total-tasas" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Registros acumulados</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="history" class="w-6 h-6"></i>
                    </div>
                </div>

            </div>

            <!-- Action Bar & Header Card -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 md:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Registro de Tasas de Cambio</h2>
                    <p class="text-sm text-slate-500 mt-1">Administra las paridades cambiarias, fuentes oficiales y vigencia en el sistema.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="abrirModalCrear()"
                        class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 text-sm w-full md:w-auto">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        <span>Agregar Tasa</span>
                    </button>
                </div>
            </div>

            <!-- Table Card Container -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    
                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto flex-1">
                        <!-- Búsqueda por texto (moneda, fuente, notas) -->
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </span>
                            <input type="text" id="custom-search-input" placeholder="Buscar par, fuente o nota..."
                                class="w-full pl-9 pr-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-slate-600">
                        </div>

                        <!-- Filtro por fecha -->
                        <div class="relative w-full sm:w-48">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                            </span>
                            <input type="date" id="custom-date-filter" title="Filtrar por fecha de vigencia"
                                class="w-full pl-9 pr-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-slate-600">
                        </div>

                        <!-- Filtro por estatus -->
                        <select id="custom-status-filter"
                            class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-slate-600">
                            <option value="">Todos los estatus</option>
                            <option value="1">Solo Activas</option>
                            <option value="0">Solo Inactivas</option>
                        </select>

                        <!-- Botón limpiar filtros -->
                        <button type="button" onclick="limpiarFiltros()"
                            class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors" title="Limpiar filtros">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="cargarKPIs(); table.setData();"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            <span>Actualizar</span>
                        </button>
                    </div>
                </div>

                <!-- Tabulator Table -->
                <div class="w-full">
                    <div id="tasas-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL AGREGAR / EDITAR TASA                                               -->
    <!-- ========================================================================= -->
    <div id="tasaModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto"
            id="modalContainer">
            <div
                class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 sticky top-0 z-10">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                        <i data-lucide="coins" class="w-4 h-4"></i>
                    </div>
                    <h3 id="modalTitle" class="text-lg font-bold text-slate-800">Agregar Tasa de Cambio</h3>
                </div>
                <button onclick="cerrarModal()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form id="tasaForm" class="p-6 space-y-4">
                <input type="hidden" id="tasa_id" name="id">

                <!-- Pares de Monedas (Origen y Destino) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">
                            Moneda Origen <span class="text-rose-500">*</span>
                        </label>
                        <select id="moneda_origen_id" name="moneda_origen_id" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700 font-medium">
                            <option value="">Cargando monedas...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">
                            Moneda Destino <span class="text-rose-500">*</span>
                        </label>
                        <select id="moneda_destino_id" name="moneda_destino_id" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700 font-medium">
                            <option value="">Cargando monedas...</option>
                        </select>
                    </div>
                </div>

                <!-- Tasa y Fuente -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">
                            Valor de la Tasa <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span id="label-simbolo-destino" class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 font-semibold text-xs">Bs.</span>
                            <input type="number" step="any" min="0.00000001" id="tasa" name="tasa" required
                                class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors font-bold text-slate-800"
                                placeholder="0.0000">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">
                            Fuente / Referencia
                        </label>
                        <input list="fuentes-sugeridas" id="fuente" name="fuente"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700 font-medium"
                            placeholder="ej. BCV, Paralelo, Oficial">
                        <datalist id="fuentes-sugeridas">
                            <option value="BCV">
                            <option value="Paralelo">
                            <option value="EnParaleloVzla">
                            <option value="BCE">
                            <option value="Oficial">
                            <option value="Manual">
                        </datalist>
                    </div>
                </div>

                <!-- Fechas de Vigencia -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">
                            Vigente Desde <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="vigente_desde" name="vigente_desde" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">
                            Vigente Hasta <span class="text-slate-400 font-normal">(Opcional)</span>
                        </label>
                        <input type="date" id="vigente_hasta" name="vigente_hasta"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700">
                    </div>
                </div>

                <!-- Observaciones -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="2"
                        class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700"
                        placeholder="Notas adicionales o motivos de ajuste..."></textarea>
                </div>

                <!-- Estatus y Opciones -->
                <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-100 space-y-2.5">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Estatus de la Tasa</label>
                        <select id="is_active" name="is_active" required
                            class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700 font-medium">
                            <option value="1">Activa (Vigente para el cálculo)</option>
                            <option value="0">Inactiva (Solo histórico)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="desactivar_anteriores" name="desactivar_anteriores" value="1" checked
                            class="w-4 h-4 text-brand-600 rounded border-slate-300 focus:ring-brand-500">
                        <label for="desactivar_anteriores" class="text-xs text-slate-600 select-none">
                            Desactivar automáticamente otras tasas activas del mismo par cambiario
                        </label>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModal()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm w-full sm:w-auto">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardar"
                        class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm w-full sm:w-auto relative flex items-center justify-center min-w-[100px]">
                        <span>Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL VER DETALLES DE TASA                                                -->
    <!-- ========================================================================= -->
    <div id="verTasaModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto"
            id="verModalContainer">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 sticky top-0 z-10">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Detalles de Tasa de Cambio</h3>
                </div>
                <button onclick="cerrarVerModal()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-6 space-y-4">
                
                <!-- Monto Principal y Par -->
                <div class="text-center py-5 bg-gradient-to-b from-slate-50 to-slate-100/60 rounded-2xl border border-slate-200/80">
                    <div class="flex items-center justify-center gap-2 mb-2">
                        <span id="verParOrigen" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-slate-800 shadow-sm border border-slate-200">USD</span>
                        <i data-lucide="arrow-right" class="w-4 h-4 text-slate-400"></i>
                        <span id="verParDestino" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-brand-600 text-white shadow-sm">VES</span>
                    </div>

                    <h4 id="verTasaMonto" class="text-3xl font-black text-slate-800 tracking-tight">0.0000</h4>
                    <span id="verParDescripcion" class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-1 block">1 USD = 0.0000 VES</span>
                    
                    <div class="mt-3 flex items-center justify-center gap-2">
                        <span id="verEstatusBadge"
                            class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                            Activa
                        </span>
                        <span id="verFuenteBadge"
                            class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
                            BCV
                        </span>
                        <span id="verIdBadge"
                            class="px-2.5 py-0.5 rounded-md text-xs font-mono text-slate-500 bg-white border border-slate-200">
                            ID: #0
                        </span>
                    </div>
                </div>

                <!-- Campos de Detalle en Grid -->
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-100">
                            <span class="block text-[10px] font-bold uppercase text-slate-400 tracking-wider">Vigente Desde</span>
                            <span id="verFechaVigenciaDesde" class="block text-sm font-bold text-slate-800 mt-1"></span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-100">
                            <span class="block text-[10px] font-bold uppercase text-slate-400 tracking-wider">Vigente Hasta</span>
                            <span id="verFechaVigenciaHasta" class="block text-sm font-bold text-slate-800 mt-1"></span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-100">
                        <span class="block text-[10px] font-bold uppercase text-slate-400 tracking-wider">Observaciones</span>
                        <p id="verObservaciones" class="text-xs text-slate-700 mt-1 italic leading-relaxed">Sin observaciones</p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-100 flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Registrado por</span>
                        <span id="verUsuario" class="text-xs font-semibold text-slate-800"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                            <span class="block text-[10px] font-semibold uppercase text-slate-400 tracking-wider">Fecha Creación</span>
                            <span id="verFechaCreacion" class="block text-xs font-medium text-slate-700 mt-1 truncate"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                            <span class="block text-[10px] font-semibold uppercase text-slate-400 tracking-wider">Última Actualización</span>
                            <span id="verFechaActualizacion" class="block text-xs font-medium text-slate-700 mt-1 truncate"></span>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarVerModal()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm w-full sm:w-auto">
                        Cerrar
                    </button>
                    <button type="button" id="btnVerEditar" onclick="abrirEdicionDesdeVer()"
                        class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm w-full sm:w-auto inline-flex items-center justify-center space-x-1.5">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Editar Tasa</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL CONFIRMACIÓN DE ELIMINAR                                            -->
    <!-- ========================================================================= -->
    <div id="eliminarTasaModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300"
            id="eliminarModalContainer">
            <div class="p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-triangle" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 tracking-tight">¿Eliminar esta tasa?</h3>
                <p class="text-sm text-slate-500 mt-2 mb-6 leading-relaxed">El registro de la tasa será desactivado y archivado del sistema.</p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <button type="button" onclick="cerrarEliminarModal()"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                        Cancelar
                    </button>
                    <button type="button" id="btnConfirmarEliminar"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 text-white font-medium hover:bg-rose-700 shadow-md shadow-rose-500/20 transition-colors text-sm flex items-center justify-center">
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL DE NOTIFICACIÓN GLOBAL                                              -->
    <!-- ========================================================================= -->
    <div id="notificacionModal"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300"
            id="notificacionModalContainer">
            <div class="p-6 text-center">
                <div id="notificacionIconContainer" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i id="notificacionIcon" data-lucide="info" class="w-8 h-8"></i>
                </div>
                <h3 id="notificacionTitulo" class="text-lg font-bold text-slate-800 tracking-tight mb-2">Mensaje</h3>
                <p id="notificacionMensaje" class="text-sm text-slate-500 mb-6 leading-relaxed"></p>
                
                <div class="flex justify-center">
                    <button type="button" onclick="cerrarNotificacionModal()"
                        class="w-full px-6 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-md shadow-brand-500/20 transition-all text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabulator Local -->
    <script type="text/javascript" src="/assets/vendor/tabulator/tabulator.min.js"></script>

    <script>
        let table;
        let catalogoMonedas = [];
        let idTasaEnVista = null;
        let idTasaParaEliminar = null;

        // Notificaciones unificadas
        function notificar(icono, mensaje) {
            const modal = document.getElementById("notificacionModal");
            const container = document.getElementById("notificacionModalContainer");
            const iconContainer = document.getElementById("notificacionIconContainer");
            const iconEl = document.getElementById("notificacionIcon");
            const tituloEl = document.getElementById("notificacionTitulo");
            const mensajeEl = document.getElementById("notificacionMensaje");

            const colores = {
                success: { bg: 'bg-emerald-50', text: 'text-emerald-500', icon: 'check-circle', title: '¡Éxito!' },
                error:   { bg: 'bg-rose-50',    text: 'text-rose-500',    icon: 'alert-circle', title: 'Ocurrió un error' },
                warning: { bg: 'bg-amber-50',   text: 'text-amber-500',   icon: 'alert-triangle', title: 'Advertencia' },
                info:    { bg: 'bg-brand-50',   text: 'text-brand-500',   icon: 'info', title: 'Información' }
            };

            const config = colores[icono] || colores.info;

            iconContainer.className = `w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 ${config.bg} ${config.text}`;
            iconEl.setAttribute('data-lucide', config.icon);
            tituloEl.textContent = config.title;
            mensajeEl.textContent = mensaje;

            lucide.createIcons();

            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                container.classList.remove("scale-95");
            }, 10);
        }

        function cerrarNotificacionModal() {
            const modal = document.getElementById("notificacionModal");
            const container = document.getElementById("notificacionModalContainer");

            modal.classList.add("opacity-0");
            container.classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 300);
        }

        // Cargar catálogo de monedas para los selectores del modal
        async function cargarCatalogoMonedas() {
            try {
                const res = await fetch('/obtener-tasas-de-cambio-controller', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=catalogos'
                });
                const data = await res.json();
                if (data.status === 'success' && Array.isArray(data.monedas)) {
                    catalogoMonedas = data.monedas;
                    poblarSelectoresMonedas();
                }
            } catch (e) {
                console.error("Error al cargar monedas:", e);
            }
        }

        function poblarSelectoresMonedas(selectedOrigen = null, selectedDestino = null) {
            const selectOrigen = document.getElementById("moneda_origen_id");
            const selectDestino = document.getElementById("moneda_destino_id");

            let htmlOrigen = '';
            let htmlDestino = '';

            catalogoMonedas.forEach(m => {
                const isBase = m.es_base == 1 || m.is_base == 1;
                const label = `${m.codigo} - ${m.nombre} (${m.simbolo})`;
                htmlOrigen += `<option value="${m.id}">${label} ${isBase ? '★ (Base)' : ''}</option>`;
                htmlDestino += `<option value="${m.id}">${label}</option>`;
            });

            selectOrigen.innerHTML = htmlOrigen;
            selectDestino.innerHTML = htmlDestino;

            // Valores por defecto: Origen USD (o moneda base) y Destino VES (ID 2 o secundaria)
            if (selectedOrigen) {
                selectOrigen.value = selectedOrigen;
            } else {
                const base = catalogoMonedas.find(m => m.es_base == 1 || m.is_base == 1) || catalogoMonedas[0];
                if (base) selectOrigen.value = base.id;
            }

            if (selectedDestino) {
                selectDestino.value = selectedDestino;
            } else {
                const ves = catalogoMonedas.find(m => m.codigo === 'VES') || catalogoMonedas[1] || catalogoMonedas[0];
                if (ves) selectDestino.value = ves.id;
            }

            actualizarSimboloDestino();
        }

        function actualizarSimboloDestino() {
            const selectDestino = document.getElementById("moneda_destino_id");
            const idDestino = parseInt(selectDestino.value);
            const m = catalogoMonedas.find(item => item.id == idDestino);
            const labelSimbolo = document.getElementById("label-simbolo-destino");
            if (m && labelSimbolo) {
                labelSimbolo.textContent = m.simbolo;
            }
        }

        document.getElementById("moneda_destino_id").addEventListener("change", actualizarSimboloDestino);

        // Cargar KPIs de resumen
        async function cargarKPIs() {
            try {
                const res = await fetch('/obtener-tasas-de-cambio-controller', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=kpis'
                });
                const data = await res.json();
                if (data.status === 'success' && data.kpis) {
                    const k = data.kpis;
                    document.getElementById("kpi-total-tasas").textContent = k.total_tasas;
                    document.getElementById("kpi-tasas-activas").textContent = k.tasas_activas;

                    if (k.tasa_principal) {
                        const tp = k.tasa_principal;
                        const tasaNum = parseFloat(tp.tasa || 0);
                        document.getElementById("kpi-tasa-valor").textContent = tasaNum.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
                        document.getElementById("kpi-tasa-simbolo").textContent = tp.destino_simbolo || 'Bs.';
                        document.getElementById("kpi-tasa-fuente").textContent = tp.fuente || 'BCV';
                        document.getElementById("kpi-tasa-par").textContent = `1 ${tp.origen_codigo} = ${tasaNum.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 4 })} ${tp.destino_codigo}`;
                        document.getElementById("kpi-par-principal").textContent = `${tp.origen_codigo} → ${tp.destino_codigo}`;
                    }
                }
            } catch (e) {
                console.error("Error al cargar KPIs:", e);
            }
        }

        // Limpiar filtros
        function limpiarFiltros() {
            document.getElementById("custom-search-input").value = "";
            document.getElementById("custom-date-filter").value = "";
            document.getElementById("custom-status-filter").value = "";
            table.setData();
        }

        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
            cargarCatalogoMonedas();
            cargarKPIs();

            const actionsFormatter = function (cell, formatterParams, onRendered) {
                const id = cell.getValue();
                onRendered(function () {
                    lucide.createIcons();
                });
                return `
                    <div class="flex items-center justify-center space-x-1.5">
                        <button class="btn-ver p-1.5 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="Ver detalles" data-id="${id}">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <button class="btn-editar p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Editar" data-id="${id}">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button class="btn-eliminar p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Eliminar" data-id="${id}">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                `;
            };

            table = new Tabulator("#tasas-table", {
                ajaxURL: "/obtener-tasas-de-cambio-controller",
                ajaxResponse: function (url, params, response) {
                    return response.data;
                },
                paginationDataReceived: {
                    last_page: "last_page"
                },
                ajaxRequestFunc: function (url, config, params) {
                    return new Promise((resolve, reject) => {
                        const formData = new FormData();
                        for (const key in params) {
                            if (Array.isArray(params[key])) {
                                params[key].forEach(val => formData.append(`${key}[]`, val));
                            } else {
                                formData.append(key, params[key]);
                            }
                        }
                        formData.append('action', 'listar');

                        // Agregar filtros personalizados
                        const searchVal = document.getElementById("custom-search-input").value.trim();
                        const dateVal   = document.getElementById("custom-date-filter").value;
                        const statusVal = document.getElementById("custom-status-filter").value;

                        if (searchVal) formData.append('search', searchVal);
                        if (dateVal)   formData.append('fecha', dateVal);
                        if (statusVal !== '') formData.append('estatus', statusVal);

                        fetch(url, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                resolve(data);
                                setTimeout(() => lucide.createIcons(), 50);
                            })
                            .catch(error => reject(error));
                    });
                },
                layout: "fitColumns",
                responsiveLayout: "collapse", 
                placeholder: "<div class='p-8 text-center text-slate-500'><i data-lucide='inbox' class='w-12 h-12 mx-auto mb-3 opacity-20'></i>No hay tasas de cambio registradas con los filtros seleccionados.</div>",
                pagination: "remote",
                paginationSize: 10,
                paginationSizeSelector: [10, 25, 50, 100],
                locale: "es",
                langs: {
                    "es": {
                        "pagination": {
                            "first": "PRIMERO", "first_title": "Primera Página",
                            "last": "ÚLTIMO", "last_title": "Última Página",
                            "prev": "ANTERIOR", "prev_title": "Página Anterior",
                            "next": "SIGUIENTE", "next_title": "Página Siguiente",
                        },
                        "ajax": {
                            "loading": "Cargando datos...",
                            "error": "Error de conexión",
                        }
                    }
                },
                columns: [
                    { formatter: "responsiveCollapse", width: 35, minWidth: 35, hozAlign: "center", resizable: false, headerSort: false, responsive: 0 },
                    { title: "ID", field: "id", width: 65, minWidth: 55, hozAlign: "center" },
                    { 
                        title: "Par de Divisas", 
                        field: "par", 
                        minWidth: 170, 
                        responsive: 0,
                        formatter: function (cell) {
                            const row = cell.getData();
                            return `
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">${row.origen_codigo || 'USD'}</span>
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">${row.destino_codigo || 'VES'}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5 truncate">${row.origen_nombre || ''} → ${row.destino_nombre || ''}</div>
                            `;
                        }
                    },
                    { 
                        title: "Valor Tasa", 
                        field: "tasa", 
                        minWidth: 140, 
                        responsive: 0,
                        formatter: function (cell) {
                            const row = cell.getData();
                            const val = parseFloat(cell.getValue() || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
                            return `<div class="font-bold text-slate-900">${val} <span class="text-xs font-semibold text-slate-400">${row.destino_simbolo || 'Bs.'}</span></div>`;
                        }
                    },
                    { 
                        title: "Fuente", 
                        field: "fuente", 
                        width: 120,
                        minWidth: 100, 
                        hozAlign: "center",
                        formatter: function (cell) {
                            const val = cell.getValue() || 'BCV';
                            const isBCV = val.toUpperCase().includes('BCV');
                            const bg = isBCV ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-slate-100 text-slate-700 border-slate-200';
                            return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold ${bg} border whitespace-nowrap">${val}</span>`;
                        }
                    },
                    { 
                        title: "Vigente Desde", 
                        field: "vigente_desde", 
                        minWidth: 130, 
                        formatter: function (cell) {
                            const val = cell.getValue();
                            if(!val) return '';
                            const partes = val.split(' ')[0].split('-');
                            return `<div class="text-slate-700 font-medium text-xs"><i data-lucide="calendar" class="w-3.5 h-3.5 inline text-slate-400 mr-1"></i>${partes[2]}/${partes[1]}/${partes[0]}</div>`;
                        }
                    },
                    { 
                        title: "Vigente Hasta", 
                        field: "vigente_hasta", 
                        minWidth: 120, 
                        formatter: function (cell) {
                            const val = cell.getValue();
                            if(!val) return `<span class="text-xs text-slate-400 italic">Indefinido</span>`;
                            const partes = val.split(' ')[0].split('-');
                            return `<div class="text-slate-600 text-xs"><i data-lucide="calendar" class="w-3.5 h-3.5 inline text-slate-400 mr-1"></i>${partes[2]}/${partes[1]}/${partes[0]}</div>`;
                        }
                    },
                    { 
                        title: "Registrado Por", 
                        field: "usuario_nombre", 
                        minWidth: 140, 
                        formatter: function (cell) {
                            const val = cell.getValue();
                            return val ? `<div class="text-xs text-slate-700 font-medium truncate">${val}</div>` : `<span class="text-xs text-slate-400 italic">Sistema</span>`;
                        }
                    },
                    { 
                        title: "Estatus", 
                        field: "is_active", 
                        width: 110, 
                        minWidth: 95, 
                        hozAlign: "center", 
                        formatter: function (cell) {
                            const estatus = cell.getValue();
                            if (estatus == 1) {
                                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Activa</span>`;
                            } else {
                                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 whitespace-nowrap"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Inactiva</span>`;
                            }
                        }
                    },
                    { 
                        title: "Acciones", 
                        field: "id", 
                        width: 130, 
                        minWidth: 120, 
                        hozAlign: "center", 
                        headerSort: false, 
                        resizable: false,
                        responsive: 0,
                        formatter: actionsFormatter 
                    }
                ]
            });

            // Delegación de eventos en tabla
            document.getElementById("tasas-table").addEventListener('click', function (e) {
                const btnVer = e.target.closest('.btn-ver');
                const btnEditar = e.target.closest('.btn-editar');
                const btnEliminar = e.target.closest('.btn-eliminar');

                if (btnVer) {
                    verTasa(btnVer.getAttribute('data-id'));
                } else if (btnEditar) {
                    editarTasa(btnEditar.getAttribute('data-id'));
                } else if (btnEliminar) {
                    abrirEliminarModal(btnEliminar.getAttribute('data-id'));
                }
            });

            // Filtros dinámicos con debouncing
            let timeoutBusqueda = null;
            document.getElementById("custom-search-input").addEventListener("input", function () {
                clearTimeout(timeoutBusqueda);
                timeoutBusqueda = setTimeout(() => table.setData(), 300);
            });

            document.getElementById("custom-date-filter").addEventListener("change", function () {
                table.setData();
            });

            document.getElementById("custom-status-filter").addEventListener("change", function () {
                table.setData();
            });

            // Envío del formulario Crear / Editar Tasa
            document.getElementById("tasaForm").addEventListener("submit", async function (e) {
                e.preventDefault();

                const btnGuardar = document.getElementById("btnGuardar");
                const originalBtnContent = btnGuardar.innerHTML;
                
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>`;
                lucide.createIcons();

                const formData = new FormData(this);
                const isEditing = formData.get("id") !== "" && formData.get("id") !== null;
                formData.append('action', isEditing ? 'update' : 'store');

                try {
                    const response = await fetch('/agregar-tasa-de-cambio-controller', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    cerrarModal();

                    setTimeout(() => {
                        if (result.status === 'success') {
                            notificar('success', result.message || (isEditing ? 'Tasa actualizada con éxito.' : 'Tasa creada con éxito.'));
                            table.setData();
                            cargarKPIs();
                        } else {
                            notificar('error', result.message || 'Ocurrió un error al procesar los datos.');
                        }
                    }, 350);
                } catch (error) {
                    console.error("Error de red:", error);
                    cerrarModal();
                    setTimeout(() => {
                        notificar('error', 'Error de red o comunicación con el servidor.');
                    }, 350);
                } finally {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = originalBtnContent;
                }
            });
        });

        // Control de modales
        function abrirModalCrear() {
            document.getElementById("tasaForm").reset();
            document.getElementById("tasa_id").value = "";
            document.getElementById("modalTitle").textContent = "Agregar Tasa de Cambio";
            
            // Asignar fecha de hoy como predeterminada
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById("vigente_desde").value = hoy;
            document.getElementById("fuente").value = "BCV";
            document.getElementById("is_active").value = "1";
            document.getElementById("desactivar_anteriores").checked = true;

            poblarSelectoresMonedas();

            const modal = document.getElementById("tasaModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("modalContainer").classList.remove("scale-95");
                lucide.createIcons();
            }, 10);
        }

        function cerrarModal() {
            const modal = document.getElementById("tasaModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 300);
        }

        function cerrarVerModal() {
            const modal = document.getElementById("verTasaModal");
            modal.classList.add("opacity-0");
            document.getElementById("verModalContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 300);
        }

        function abrirEdicionDesdeVer() {
            if (idTasaEnVista) {
                cerrarVerModal();
                setTimeout(() => {
                    editarTasa(idTasaEnVista);
                }, 300);
            }
        }

        // Acciones CRUD
        async function verTasa(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'show');
                formData.append('id', id);

                const response = await fetch('/obtener-tasas-de-cambio-controller', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success' && data.tasa) {
                    const t = data.tasa;
                    idTasaEnVista = t.id;

                    const tasaNum = parseFloat(t.tasa || 0);
                    const formattedRate = tasaNum.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 4 });

                    document.getElementById("verParOrigen").textContent = t.origen_codigo;
                    document.getElementById("verParDestino").textContent = t.destino_codigo;
                    document.getElementById("verTasaMonto").textContent = `${formattedRate} ${t.destino_simbolo}`;
                    document.getElementById("verParDescripcion").textContent = `1 ${t.origen_codigo} = ${formattedRate} ${t.destino_codigo}`;
                    document.getElementById("verIdBadge").textContent = `ID: #${t.id}`;
                    document.getElementById("verFuenteBadge").textContent = t.fuente || 'BCV';

                    const spanEstatus = document.getElementById("verEstatusBadge");
                    if (t.is_active == 1) {
                        spanEstatus.textContent = 'Activa';
                        spanEstatus.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100';
                    } else {
                        spanEstatus.textContent = 'Inactiva';
                        spanEstatus.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200';
                    }

                    document.getElementById("verFechaVigenciaDesde").textContent = t.vigente_desde || 'N/A';
                    document.getElementById("verFechaVigenciaHasta").textContent = t.vigente_hasta || 'Indefinida (Vigente)';
                    document.getElementById("verObservaciones").textContent = t.observaciones || 'Sin observaciones registradas.';
                    document.getElementById("verUsuario").textContent = t.usuario_nombre || 'Sistema / Automático';
                    document.getElementById("verFechaCreacion").textContent = t.created_at || 'N/A';
                    document.getElementById("verFechaActualizacion").textContent = t.updated_at || 'N/A';

                    const modal = document.getElementById("verTasaModal");
                    modal.classList.remove("hidden");
                    setTimeout(() => {
                        modal.classList.remove("opacity-0");
                        document.getElementById("verModalContainer").classList.remove("scale-95");
                        lucide.createIcons();
                    }, 10);
                } else {
                    notificar('error', data.message || 'No se pudieron obtener los detalles de la tasa.');
                }
            } catch (error) {
                console.error("Error al cargar detalles:", error);
                notificar('error', 'Error al consultar los datos.');
            }
        }

        async function editarTasa(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'show');
                formData.append('id', id);

                const response = await fetch('/obtener-tasas-de-cambio-controller', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success' && data.tasa) {
                    const t = data.tasa;

                    poblarSelectoresMonedas(t.moneda_origen_id, t.moneda_destino_id);

                    document.getElementById("tasa_id").value = t.id;
                    document.getElementById("tasa").value = t.tasa;
                    document.getElementById("fuente").value = t.fuente || 'BCV';

                    // Extraer solo la fecha YYYY-MM-DD para el input type="date"
                    document.getElementById("vigente_desde").value = t.vigente_desde ? t.vigente_desde.split(' ')[0] : '';
                    document.getElementById("vigente_hasta").value = t.vigente_hasta ? t.vigente_hasta.split(' ')[0] : '';
                    document.getElementById("observaciones").value = t.observaciones || '';
                    document.getElementById("is_active").value = t.is_active;

                    document.getElementById("modalTitle").textContent = "Editar Tasa de Cambio";

                    const modal = document.getElementById("tasaModal");
                    modal.classList.remove("hidden");
                    setTimeout(() => {
                        modal.classList.remove("opacity-0");
                        document.getElementById("modalContainer").classList.remove("scale-95");
                        lucide.createIcons();
                    }, 10);
                } else {
                    notificar('error', data.message || 'No se pudo cargar la información.');
                }
            } catch (error) {
                console.error("Error al cargar para editar:", error);
                notificar('error', 'Error de red al cargar la información.');
            }
        }

        function abrirEliminarModal(id) {
            idTasaParaEliminar = id;
            const modal = document.getElementById("eliminarTasaModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("eliminarModalContainer").classList.remove("scale-95");
                lucide.createIcons();
            }, 10);
        }

        function cerrarEliminarModal() {
            const modal = document.getElementById("eliminarTasaModal");
            modal.classList.add("opacity-0");
            document.getElementById("eliminarModalContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
                idTasaParaEliminar = null;
            }, 300);
        }

        document.getElementById("btnConfirmarEliminar").addEventListener("click", async function() {
            if (!idTasaParaEliminar) return;

            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto"></i>`;
            lucide.createIcons();

            try {
                const formData = new FormData();
                formData.append('id', idTasaParaEliminar);

                const response = await fetch('/eliminar-tasa-de-cambio-controller', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                cerrarEliminarModal();
                
                setTimeout(() => {
                    if (result.status === 'success') {
                        notificar('success', result.message || 'Tasa eliminada exitosamente.');
                        table.setData();
                        cargarKPIs();
                    } else {
                        notificar('error', result.message || 'No fue posible eliminar el registro.');
                    }
                }, 350);

            } catch (error) {
                console.error("Error al procesar la eliminación:", error);
                cerrarEliminarModal();
                setTimeout(() => {
                    notificar('error', 'Error de red al intentar eliminar.');
                }, 350);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        });

    </script>
</body>
</html>