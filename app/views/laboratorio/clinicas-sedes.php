<?php
session_start();
// Validación opcional de sesión/rol
// if (!isset($_SESSION['usuario_id'])) { header('Location: /login.php'); exit(); }
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
            <div class="flex items-center space-x-4">
                <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Directorio de Clínicas</h1>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm font-medium text-slate-600 hidden sm:inline-block">Hola,
                    <strong><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador') ?></strong></span>
                <div
                    class="h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold flex-shrink-0">
                    <?= substr($_SESSION['usuario_nombre'] ?? 'A', 0, 1) ?>
                </div>
            </div>
        </header>

        <!-- Main View Scrollable Area -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden bg-slate-50/50 p-4 md:p-8">

            <!-- Action Bar & Header Card -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 md:p-6 mb-6 md:mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Gestión de Clínicas y Sedes</h2>
                    <p class="text-sm text-slate-500 mt-1">Registra las clínicas asociadas al laboratorio y administra sus diferentes sucursales.</p>
                </div>
                <button onclick="abrirModalClinica()"
                    class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 w-full md:w-auto">
                    <i data-lucide="building-2" class="w-5 h-5"></i>
                    <span>Agregar Clínica</span>
                </button>
            </div>

            <!-- Table Card Container (Clínicas) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative w-full sm:w-72">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="buscar-clinica" placeholder="Buscar clínica por nombre o documento..."
                            class="w-full pl-9 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all text-slate-600">
                    </div>
                </div>

                <!-- Tabulator Table: Clínicas -->
                <div class="w-full">
                    <div id="clinicas-table" class="w-full min-h-[300px]"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL AGREGAR / EDITAR CLÍNICA                                             -->
    <!-- ========================================================================= -->
    <div id="clinicaModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4 py-6">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 max-h-full flex flex-col"
            id="modalClinicaContainer">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 sticky top-0 z-10 flex-shrink-0">
                <h3 id="modalClinicaTitle" class="text-lg font-bold text-slate-800">Agregar Clínica</h3>
                <button type="button" onclick="cerrarModalClinica()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="clinicaForm" class="p-5 space-y-4 overflow-y-auto">
                <input type="hidden" id="clinica_id" name="id">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Razón Social</label>
                    <input type="text" id="razon_social" name="razon_social" required
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700"
                        placeholder="Ej. Clínica Santa Sofía C.A.">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-1">
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tipo Doc.</label>
                        <select id="tipo_documento" name="tipo_documento" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700">
                            <option value="J">J</option>
                            <option value="V">V</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Número</label>
                        <input type="text" id="numero_documento" name="numero_documento" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700"
                            placeholder="Ej. 123456789">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Teléfono</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="telefono" name="telefono" required maxlength="11"
                            class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700"
                            placeholder="Ej. 04141234567">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Dirección Fiscal</label>
                    <!-- MÁS ALTURA AQUÍ PARA DIRECCIÓN FISCAL -->
                    <textarea id="direccion_fiscal" name="direccion_fiscal" required rows="5"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700 resize-none min-h-[120px]"
                        placeholder="Dirección fiscal registrada..."></textarea>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100 flex-shrink-0">
                    <button type="button" onclick="cerrarModalClinica()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm w-full sm:w-auto">Cancelar</button>
                    <button type="submit" id="btnGuardarClinica"
                        class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm w-full sm:w-auto relative flex items-center justify-center min-w-[100px]">
                        <span>Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL GESTIÓN DE SEDES (MAESTRO-DETALLE)                                  -->
    <!-- ========================================================================= -->
    <div id="sedesModal"
        class="fixed inset-0 z-[55] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-2 sm:px-4 py-4 sm:py-6">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-6xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[92vh] flex flex-col"
            id="modalSedesContainer">
            
            <div class="px-4 sm:px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                <div class="pr-4">
                    <h3 class="text-lg font-bold text-slate-800">Gestión de Sedes</h3>
                    <p id="subtituloSedes" class="text-sm text-brand-600 font-medium truncate"></p>
                </div>
                <button type="button" onclick="cerrarModalSedes()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors flex-shrink-0">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <div class="flex flex-col lg:flex-row flex-1 overflow-y-auto lg:overflow-hidden min-h-0">
                <!-- Columna Izquierda: Formulario Agregar Sede -->
                <div class="w-full lg:w-1/3 border-b lg:border-b-0 lg:border-r border-slate-100 bg-white p-4 sm:p-5 overflow-y-auto flex-shrink-0">
                    <h4 id="formSedeTitle" class="text-sm font-bold text-slate-800 mb-4 flex items-center space-x-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-brand-500"></i>
                        <span>Registrar Nueva Sede</span>
                    </h4>
                    
                    <form id="sedeForm" class="space-y-4">
                        <input type="hidden" id="sede_id" name="sede_id">
                        <input type="hidden" id="fk_clinica_id" name="clinica_id">

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nombre de la Sede</label>
                            <input type="text" id="sede_nombre" name="nombre" required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                                placeholder="Ej. Sede Principal Las Mercedes">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tipo de Sede</label>
                            <select id="sede_principal" name="principal" required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                                <option value="0">Sucursal</option>
                                <option value="1">Sede Principal</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Dirección Física</label>
                            <!-- MÁS ALTURA AQUÍ PARA DIRECCIÓN DE SEDE -->
                            <textarea id="sede_direccion" name="direccion_fisica" required rows="5"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors resize-none min-h-[120px]"
                                placeholder="Dirección exacta..."></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3 hidden">
                            <!-- Ocultos temporalmente si no se usan obligatoriamente, o puedes mostrarlos -->
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Latitud</label>
                                <input type="text" id="sede_latitud" name="latitud" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Longitud</label>
                                <input type="text" id="sede_longitud" name="longitud" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl">
                            </div>
                        </div>

                        <div class="pt-2 flex flex-col space-y-2 mt-4">
                            <button type="submit" id="btnGuardarSede"
                                class="w-full px-4 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-md shadow-brand-500/25 transition-all text-sm flex items-center justify-center">
                                Guardar Sede
                            </button>
                            <button type="button" id="btnCancelarSede" onclick="resetSedeForm()" class="hidden w-full px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                                Cancelar Edición
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Columna Derecha: Tabla de Sedes -->
                <div class="w-full lg:w-2/3 bg-slate-50/50 flex flex-col min-h-[350px] lg:min-h-0 flex-1 overflow-hidden">
                    <div class="flex-1 w-full p-2 sm:p-4 overflow-y-auto flex flex-col">
                        <div id="sedes-table" class="w-full min-h-[280px] bg-white rounded-xl shadow-sm border border-slate-200"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL CONFIRMACIÓN DE ELIMINAR -->
    <!-- ========================================================================= -->
    <div id="eliminarModal"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300"
            id="eliminarModalContainer">
            <div class="p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-triangle" class="w-8 h-8"></i>
                </div>
                <h3 id="eliminarTitulo" class="text-lg font-bold text-slate-800 tracking-tight">¿Eliminar registro?</h3>
                <p id="eliminarMensaje" class="text-sm text-slate-500 mt-2 mb-6 leading-relaxed">Esta acción no se puede deshacer.</p>
                
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
    <!-- MODAL DE NOTIFICACIÓN GLOBAL -->
    <!-- ========================================================================= -->
    <div id="notificacionModal"
        class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
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

    <!-- CSS Personalizado para Tabulator -->
    <style>
        .tabulator {
            border: none !important;
            background-color: transparent !important;
            font-family: inherit !important;
        }

        .tabulator .tabulator-header {
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .tabulator .tabulator-header .tabulator-col {
            background-color: transparent !important;
            border-right: none !important;
        }

        .tabulator .tabulator-header .tabulator-col-content {
            padding: 12px 16px !important;
            color: #64748b !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .tabulator .tabulator-row {
            background-color: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
            transition: background-color 0.2s ease;
        }

        .tabulator .tabulator-row:hover {
            background-color: #f8fafc !important;
        }

        .tabulator .tabulator-cell {
            border-right: none !important;
            padding: 12px 14px !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
        }

        .tabulator-row .tabulator-cell {
            display: inline-block;
        }

        /* ----- SOLUCIÓN VISTA MÓVIL (COLUMNAS COLAPSADAS) ----- */
        .tabulator-row .tabulator-responsive-collapse {
            padding: 12px 16px !important;
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0;
        }

        .tabulator-row .tabulator-responsive-collapse table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabulator-row .tabulator-responsive-collapse table tr {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 0;
            border-bottom: 1px dashed #cbd5e1;
        }
        .tabulator-row .tabulator-responsive-collapse table tr:last-child {
            border-bottom: none;
        }
        
        .tabulator-row .tabulator-responsive-collapse table tr td {
            border: none !important;
            padding: 0 !important;
            font-size: 0.875rem;
        }

        /* Título de la columna colapsada (Izquierda) */
        .tabulator-row .tabulator-responsive-collapse table tr td:first-child {
            font-weight: 600;
            color: #475569;
            text-align: left;
            width: 35%;
            padding-right: 8px !important;
        }

        /* Valor de la columna colapsada (Derecha) */
        .tabulator-row .tabulator-responsive-collapse table tr td:last-child {
            font-weight: 500;
            color: #0f172a;
            text-align: right;
            width: 65%;
            word-break: break-word;
        }

        /* Botón de toggle de columnas responsivas */
        .tabulator-responsive-collapse-toggle {
            color: #4f46e5 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background-color: #eef2ff;
            transition: background-color 0.2s;
            padding: 4px;
        }

        .tabulator-responsive-collapse-toggle:hover {
            background-color: #e0e7ff;
        }

        .tabulator-responsive-collapse-toggle svg {
            width: 14px;
            height: 14px;
            stroke-width: 3px;
        }

        /* Paginación Tabulator adaptada a Tailwind */
        .tabulator-footer {
            background-color: #ffffff !important;
            border-top: 1px solid #e2e8f0 !important;
            padding: 12px 16px !important;
            display: flex !important;
            flex-wrap: wrap;
            justify-content: center !important;
            align-items: center !important;
            gap: 12px !important;
        }
        
        @media (min-width: 640px) {
            .tabulator-footer {
                justify-content: space-between !important;
            }
        }

        .tabulator-footer .tabulator-paginator {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 8px !important;
            width: 100% !important;
        }

        @media (min-width: 640px) {
            .tabulator-footer .tabulator-paginator {
                width: auto !important;
            }
        }

        .tabulator-footer .tabulator-pages {
            margin: 0 !important;
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 4px !important;
        }

        .tabulator-footer .tabulator-page {
            padding: 6px 12px !important;
            border-radius: 6px !important;
            border: 1px solid #e2e8f0 !important;
            color: #475569 !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            background: white !important;
            transition: all 0.2s !important;
            cursor: pointer;
        }

        .tabulator-footer .tabulator-page:not(.disabled):hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        .tabulator-footer .tabulator-page.active {
            background-color: #4f46e5 !important;
            color: white !important;
            border-color: #4f46e5 !important;
        }
    </style>

    <!-- Tabulator Local -->
    <script type="text/javascript" src="/assets/vendor/tabulator/tabulator.min.js"></script>

    <script>
        let tableClinicas;
        let tableSedes;
        let itemParaEliminar = null;

        // =========================================================================
        // NOTIFICACIONES GLOBALES
        // =========================================================================
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


        // =========================================================================
        // INICIALIZACIÓN TABULATOR Y EVENTOS
        // =========================================================================
        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();

            // =====================================================================
            // TABLA DE CLÍNICAS
            // =====================================================================
            tableClinicas = new Tabulator("#clinicas-table", {
                ajaxURL: "/obtener-clinicas-controller",
                ajaxConfig: "POST",
                ajaxParams: { action: "listar_clinicas" },
                ajaxResponse: function (url, params, response) {
                    return response.data || [];
                },
                layout: "fitColumns",
                responsiveLayout: "collapse", 
                placeholder: "<div class='p-8 text-center text-slate-500'><i data-lucide='building-2' class='w-12 h-12 mx-auto mb-3 opacity-20'></i>No hay clínicas registradas.</div>",
                pagination: "local",
                paginationSize: 10,
                locale: "es",
                langs: {
                    "es": {
                        "pagination": {
                            "first": "PRIMERO", "last": "ÚLTIMO", "prev": "ANTERIOR", "next": "SIGUIENTE"
                        }
                    }
                },
                columns: [
                    // Botón para desplegar contenido en móviles. 'responsive: 0' obliga a que siempre se vea.
                    { formatter: "responsiveCollapse", width: 35, minWidth: 35, hozAlign: "center", resizable: false, headerSort: false, responsive: 0 },
                    
                    // Razón Social: Obligamos a que SIEMPRE se vea poniendo responsive: 0
                    { title: "Razón Social", field: "razon_social", minWidth: 130, responsive: 0, formatter: (cell) => `<span class="font-bold text-slate-800 leading-tight">${cell.getValue()}</span>` },
                    
                    // Columnas que se colapsarán si no hay espacio
                    { 
                        title: "Documento", field: "numero_documento", minWidth: 110,
                        formatter: function(cell){
                            const data = cell.getData();
                            return `<span class="font-mono text-slate-600">${data.tipo_documento}-${data.numero_documento}</span>`;
                        }
                    },
                    { title: "Teléfono", field: "telefono", minWidth: 110 },
                    
                    // Acciones: Obligamos a que SIEMPRE se vea en teléfono poniendo responsive: 0
                    { 
                        title: "Acciones", width: 140, minWidth: 120, hozAlign: "center", headerSort: false, resizable: false, responsive: 0,
                        formatter: function(cell, formatterParams, onRendered){
                            const id = cell.getData().id;
                            const nombre = cell.getData().razon_social;
                            onRendered(() => lucide.createIcons());
                            return `
                                <div class="flex items-center justify-center space-x-1 w-full">
                                    <button class="btn-sedes px-2 py-1 text-xs font-semibold bg-brand-50 text-brand-600 hover:bg-brand-100 rounded-lg transition-colors border border-brand-200" title="Sedes" data-id="${id}" data-nombre="${nombre}">
                                        <i data-lucide="map" class="w-3.5 h-3.5 sm:mr-1 inline-block"></i><span class="hidden sm:inline">Sedes</span>
                                    </button>
                                    <button class="btn-editar p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors flex-shrink-0" title="Editar" data-id="${id}">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn-eliminar p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors flex-shrink-0" title="Eliminar" data-id="${id}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ]
            });

            // Refrescar iconos y redibujar al renderizar datos
            tableClinicas.on("tableBuilt", function(){ tableClinicas.redraw(true); lucide.createIcons(); });
            tableClinicas.on("dataLoaded", function(){ tableClinicas.redraw(true); lucide.createIcons(); });
            tableClinicas.on("renderComplete", function(){ lucide.createIcons(); });

            // Buscador Clínicas
            document.getElementById("buscar-clinica").addEventListener("input", function (e) {
                const term = e.target.value.toLowerCase();
                tableClinicas.setFilter([
                    {field: "razon_social", type: "like", value: term},
                    {field: "numero_documento", type: "like", value: term}
                ]);
            });

            // Eventos Clínicas
            document.getElementById("clinicas-table").addEventListener('click', function (e) {
                const btnSedes = e.target.closest('.btn-sedes');
                const btnEditar = e.target.closest('.btn-editar');
                const btnEliminar = e.target.closest('.btn-eliminar');

                if (btnSedes) {
                    abrirModalSedes(btnSedes.getAttribute('data-id'), btnSedes.getAttribute('data-nombre'));
                } else if (btnEditar) {
                    editarClinica(btnEditar.getAttribute('data-id'));
                } else if (btnEliminar) {
                    abrirEliminarModal(btnEliminar.getAttribute('data-id'), 'clinica');
                }
            });

            // Formulario Guardar Clínica
            document.getElementById("clinicaForm").addEventListener("submit", async function (e) {
                e.preventDefault();
                const btn = document.getElementById("btnGuardarClinica");
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>`;
                lucide.createIcons();

                const formData = new FormData(this);
                const isEditing = formData.get("id") !== "";
                const targetUrl = isEditing ? '/editar-clinica-controller' : '/agregar-clinica-controller';

                try {
                    const response = await fetch(targetUrl, { method: 'POST', body: formData });
                    const result = await response.json();
                    cerrarModalClinica();
                    setTimeout(() => {
                        if (result.status === 'success') {
                            notificar('success', result.message || 'Clínica guardada exitosamente.');
                            tableClinicas.setData(); 
                        } else {
                            notificar('error', result.message || 'Error al guardar.');
                        }
                    }, 350);
                } catch (error) {
                    cerrarModalClinica();
                    setTimeout(() => notificar('error', 'Error de comunicación.'), 350);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            });

            // Formulario Guardar Sede
            document.getElementById("sedeForm").addEventListener("submit", async function (e) {
                e.preventDefault();
                const btn = document.getElementById("btnGuardarSede");
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto"></i>`;
                lucide.createIcons();

                const formData = new FormData(this);
                const isEditing = formData.get("sede_id") !== "";
                const targetUrl = isEditing ? '/editar-sede-controller' : '/agregar-sede-controller';

                try {
                    const response = await fetch(targetUrl, { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.status === 'success') {
                        notificar('success', result.message || (isEditing ? 'Sede actualizada.' : 'Sede registrada.'));
                        resetSedeForm();
                        if (tableSedes) {
                            tableSedes.setData();
                        }
                    } else {
                        notificar('error', result.message || 'Error al procesar la solicitud.');
                    }
                } catch (error) {
                    notificar('error', 'Error de red al guardar la sede.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            });

            // Delegación de eventos en tabla de sedes (registrada una sola vez)
            document.getElementById("sedes-table").addEventListener('click', function(e){
                const btnEditar = e.target.closest('.btn-editar-sede');
                const btnEliminar = e.target.closest('.btn-eliminar-sede');

                if (btnEditar && tableSedes) {
                    const id = btnEditar.getAttribute('data-id');
                    const rowData = tableSedes.getData().find(row => row.id == id);
                    if(rowData) {
                        document.getElementById("sede_id").value = rowData.id;
                        document.getElementById("sede_nombre").value = rowData.nombre;
                        document.getElementById("sede_direccion").value = rowData.direccion_fisica;
                        document.getElementById("sede_latitud").value = rowData.latitud || "";
                        document.getElementById("sede_longitud").value = rowData.longitud || "";
                        document.getElementById("sede_principal").value = rowData.principal;

                        document.getElementById("formSedeTitle").innerHTML = `<i data-lucide="edit-3" class="w-4 h-4 text-amber-500"></i><span>Editando Sede</span>`;
                        document.getElementById("btnGuardarSede").textContent = "Actualizar Sede";
                        document.getElementById("btnCancelarSede").classList.remove("hidden");
                        lucide.createIcons();
                    }
                } else if (btnEliminar) {
                    abrirEliminarModal(btnEliminar.getAttribute('data-id'), 'sede');
                }
            });

            // Redibujar tablas en resize para garantizar ancho responsivo óptimo
            window.addEventListener('resize', function() {
                if (tableClinicas) tableClinicas.redraw(true);
                if (tableSedes && !document.getElementById("sedesModal").classList.contains("hidden")) {
                    tableSedes.redraw(true);
                }
            });
        }); 

        // =========================================================================
        // FUNCIONES MODAL CLÍNICAS
        // =========================================================================
        function abrirModalClinica() {
            document.getElementById("clinicaForm").reset();
            document.getElementById("clinica_id").value = "";
            document.getElementById("modalClinicaTitle").textContent = "Agregar Clínica";

            const modal = document.getElementById("clinicaModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("modalClinicaContainer").classList.remove("scale-95");
            }, 10);
        }

        function cerrarModalClinica() {
            const modal = document.getElementById("clinicaModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalClinicaContainer").classList.add("scale-95");
            setTimeout(() => { modal.classList.add("hidden"); }, 300);
        }

        async function editarClinica(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'show_clinica');
                formData.append('id', id);

                const response = await fetch('/obtener-clinicas-controller', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.status === 'success' && data.clinica) {
                    const c = data.clinica;
                    document.getElementById("clinica_id").value = c.id;
                    document.getElementById("razon_social").value = c.razon_social;
                    document.getElementById("tipo_documento").value = c.tipo_documento;
                    document.getElementById("numero_documento").value = c.numero_documento;
                    document.getElementById("telefono").value = c.telefono;
                    document.getElementById("direccion_fiscal").value = c.direccion_fiscal;

                    document.getElementById("modalClinicaTitle").textContent = "Editar Clínica";
                    const modal = document.getElementById("clinicaModal");
                    modal.classList.remove("hidden");
                    setTimeout(() => {
                        modal.classList.remove("opacity-0");
                        document.getElementById("modalClinicaContainer").classList.remove("scale-95");
                        lucide.createIcons();
                    }, 10);
                } else {
                    notificar('error', data.message || 'No se pudo cargar la clínica.');
                }
            } catch (error) {
                notificar('error', 'Error al cargar datos.');
            }
        }

        // =========================================================================
        // FUNCIONES MODAL SEDES
        // =========================================================================
        function abrirModalSedes(clinicaId, clinicaNombre) {
            document.getElementById("fk_clinica_id").value = clinicaId;
            document.getElementById("subtituloSedes").textContent = clinicaNombre;
            resetSedeForm(); 

            const modal = document.getElementById("sedesModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("modalSedesContainer").classList.remove("scale-95");

                if (!tableSedes) {
                    initTableSedes(clinicaId);
                } else {
                    tableSedes.setData("/obtener-sedes-controller", { action: "listar_sedes", clinica_id: clinicaId });
                    setTimeout(() => tableSedes.redraw(true), 50);
                }
            }, 50);
        }

        function cerrarModalSedes() {
            const modal = document.getElementById("sedesModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalSedesContainer").classList.add("scale-95");
            setTimeout(() => { 
                modal.classList.add("hidden"); 
                resetSedeForm();
            }, 300);
        }

        function resetSedeForm() {
            document.getElementById("sede_id").value = "";
            document.getElementById("sede_nombre").value = "";
            document.getElementById("sede_direccion").value = "";
            document.getElementById("sede_latitud").value = "";
            document.getElementById("sede_longitud").value = "";
            document.getElementById("sede_principal").value = "0";

            document.getElementById("formSedeTitle").innerHTML = `<i data-lucide="map-pin" class="w-4 h-4 text-brand-500"></i><span>Registrar Nueva Sede</span>`;
            document.getElementById("btnGuardarSede").textContent = "Guardar Sede";
            document.getElementById("btnCancelarSede").classList.add("hidden");
            lucide.createIcons();
        }

        function initTableSedes(clinicaId) {
            tableSedes = new Tabulator("#sedes-table", {
                ajaxURL: "/obtener-sedes-controller",
                ajaxConfig: "POST",
                ajaxParams: { action: "listar_sedes", clinica_id: clinicaId },
                ajaxResponse: function(url, params, response){
                    return response.data || [];
                },
                layout: "fitColumns",
                responsiveLayout: "collapse",
                placeholder: "<div class='p-4 text-center text-slate-400 text-sm mt-8'>No hay sedes registradas.</div>",
                columns: [
                    { formatter: "responsiveCollapse", width: 35, minWidth: 35, hozAlign: "center", resizable: false, headerSort: false, responsive: 0 },
                    
                    // Columna Sede: Se mantiene visible en móvil
                    { 
                        title: "Sede", field: "nombre", minWidth: 130, responsive: 0,
                        formatter: function(cell){
                            const d = cell.getData();
                            const badge = d.principal == 1 ? `<span class="block mt-1 w-max px-1.5 py-0.5 bg-brand-100 text-brand-700 text-[10px] rounded uppercase font-bold">Principal</span>` : '';
                            return `<div class="font-bold text-slate-800 leading-tight">${d.nombre} ${badge}</div>`;
                        }
                    },
                    
                    // Columna Dirección: Esta se ocultará al menú desplegable en pantallas muy pequeñas
                    { title: "Dirección", field: "direccion_fisica", minWidth: 150, formatter: "textarea" },
                    
                    // Columna Acciones: Mantenemos visible (responsive: 0)
                    {
                        title: "Acciones", width: 85, minWidth: 80, hozAlign: "center", headerSort: false, resizable: false, responsive: 0,
                        formatter: function(cell, formatterParams, onRendered){
                            const id = cell.getData().id;
                            onRendered(() => lucide.createIcons());
                            return `
                                <div class="flex items-center justify-center space-x-1">
                                    <button class="btn-editar-sede p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Editar" data-id="${id}">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn-eliminar-sede p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Eliminar" data-id="${id}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ]
            });

            tableSedes.on("tableBuilt", function(){
                tableSedes.redraw(true);
                lucide.createIcons();
            });
            tableSedes.on("dataLoaded", function(){ 
                setTimeout(() => tableSedes.redraw(true), 50);
                lucide.createIcons(); 
            });
            tableSedes.on("renderComplete", function(){ 
                lucide.createIcons(); 
            });
        }

        // =========================================================================
        // FUNCIONES DE ELIMINACIÓN UNIFICADA
        // =========================================================================
        function abrirEliminarModal(id, tipo) {
            itemParaEliminar = { id, tipo };
            const titulo = document.getElementById("eliminarTitulo");
            const mensaje = document.getElementById("eliminarMensaje");
            
            if(tipo === 'clinica') {
                titulo.textContent = "¿Eliminar Clínica?";
                mensaje.textContent = "Se eliminará la clínica y TODAS sus sedes asociadas. Esta acción no se puede deshacer.";
            } else {
                titulo.textContent = "¿Desactivar Sede?";
                mensaje.textContent = "La sede quedará inactiva y no se mostrará en el sistema. Podrás reactivarla después si es necesario.";
            }

            const modal = document.getElementById("eliminarModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("eliminarModalContainer").classList.remove("scale-95");
            }, 10);
        }

        function cerrarEliminarModal() {
            const modal = document.getElementById("eliminarModal");
            modal.classList.add("opacity-0");
            document.getElementById("eliminarModalContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
                itemParaEliminar = null;
            }, 300);
        }

        document.getElementById("btnConfirmarEliminar").addEventListener("click", async function() {
            if (!itemParaEliminar) return;

            const btn = this;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto"></i>`;
            lucide.createIcons();

            const targetUrl = itemParaEliminar.tipo === 'clinica' 
                ? '/eliminar-clinica-controller' 
                : '/eliminar-sede-controller';
            const formData = new FormData();
            formData.append('id', itemParaEliminar.id);

            try {
                const response = await fetch(targetUrl, { method: 'POST', body: formData });
                const result = await response.json();
                const tipoEliminado = itemParaEliminar.tipo;
                cerrarEliminarModal();
                
                setTimeout(() => {
                    if (result.status === 'success') {
                        notificar('success', result.message || 'Registro eliminado.');
                        if(tipoEliminado === 'clinica') tableClinicas.setData();
                        else { tableSedes.setData(); resetSedeForm(); }
                    } else {
                        notificar('error', result.message || 'Error al eliminar.');
                    }
                }, 350);
            } catch (error) {
                cerrarEliminarModal();
                setTimeout(() => notificar('error', 'Error de red.'), 350);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>