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
                <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Directorio de Pacientes</h1>
            </div>
            <div class="flex items-center space-x-3 md:space-x-4 hidden sm:flex">
                <span class="text-sm font-medium text-slate-600">Hola,
                    <strong><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador') ?></strong></span>
                <div
                    class="h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
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
                    <h2 class="text-lg font-semibold text-slate-900">Gestión de Pacientes e Historia Bucal</h2>
                    <p class="text-sm text-slate-500 mt-1">Administra los pacientes registrados en el laboratorio y
                        gestiona su odontograma.</p>
                </div>
                <button onclick="abrirModalPaciente()"
                    class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 w-full md:w-auto">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                    <span>Agregar Paciente</span>
                </button>
            </div>

            <!-- Table Card Container (Pacientes) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div
                    class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative w-full sm:w-80">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="buscar-paciente" placeholder="Buscar por nombre, apellido o documento..."
                            class="w-full pl-9 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all text-slate-600">
                    </div>
                </div>

                <!-- Tabulator Table: Pacientes -->
                <div class="w-full">
                    <div id="pacientes-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL AGREGAR / EDITAR PACIENTE                                           -->
    <!-- ========================================================================= -->
    <div id="pacienteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[92vh] flex flex-col"
            id="modalPacienteContainer">
            <div
                class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 sticky top-0 z-10 flex-shrink-0">
                <h3 id="modalPacienteTitle" class="text-lg font-bold text-slate-800">Agregar Paciente</h3>
                <button onclick="cerrarModalPaciente()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="pacienteForm" class="p-5 space-y-4 overflow-y-auto">
                <input type="hidden" id="paciente_id" name="id">

                <!-- Nombres -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Primer Nombre</label>
                        <input type="text" id="primer_nombre" name="primer_nombre" required maxlength="45"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700"
                            placeholder="Ej. Juan">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Segundo Nombre <span
                                class="text-slate-400 normal-case font-normal">(Opcional)</span></label>
                        <input type="text" id="segundo_nombre" name="segundo_nombre" maxlength="45"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700"
                            placeholder="Ej. Carlos">
                    </div>
                </div>

                <!-- Apellidos -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Primer Apellido</label>
                        <input type="text" id="primer_apellido" name="primer_apellido" required maxlength="45"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700"
                            placeholder="Ej. Pérez">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Segundo Apellido <span
                                class="text-slate-400 normal-case font-normal">(Opcional)</span></label>
                        <input type="text" id="segundo_apellido" name="segundo_apellido" maxlength="45"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700"
                            placeholder="Ej. Gómez">
                    </div>
                </div>

                <!-- Documento y Sexo -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tipo Doc.</label>
                        <select id="tipo_documento" name="tipo_documento" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700">
                            <option value="V">V</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">N° Documento</label>
                        <input type="text" id="numero_documento" name="numero_documento" required maxlength="8"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700"
                            placeholder="Ej. 24123456">
                    </div>
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Sexo</label>
                        <select id="sexo" name="sexo" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700">
                            <option value="H">Hombre</option>
                            <option value="M">Mujer</option>
                        </select>
                    </div>
                </div>

                <!-- Clínica y Sede -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Clínica</label>
                        <select id="clinica_id" name="clinica_id"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700">
                            <option value="">Sin clínica asignada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Sede de la Clínica</label>
                        <select id="sede_id" name="sede_id"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700">
                            <option value="">Sin sede asignada</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100 flex-shrink-0">
                    <button type="button" onclick="cerrarModalPaciente()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm w-full sm:w-auto">Cancelar</button>
                    <button type="submit" id="btnGuardarPaciente"
                        class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm w-full sm:w-auto relative flex items-center justify-center min-w-[100px]">
                        <span>Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL ODONTOGRAMA INTERACTIVO (SALUD BUCAL)                               -->
    <!-- ========================================================================= -->
    <div id="odontogramaModal"
        class="fixed inset-0 z-[55] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-4xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[95vh] flex flex-col"
            id="modalOdontogramaContainer">

            <div
                class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 flex items-center space-x-2">
                        <i data-lucide="smile" class="w-5 h-5 text-brand-600"></i>
                        <span>Odontograma y Salud Bucal</span>
                    </h3>
                    <p id="subtituloOdontograma" class="text-xs sm:text-sm text-slate-500 font-medium mt-0.5"></p>
                </div>
                <button onclick="cerrarModalOdontograma()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <div class="p-5 overflow-y-auto overflow-x-hidden space-y-6 flex-1">
                <input type="hidden" id="odonto_paciente_id">

                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                    <span class="block text-xs font-bold uppercase text-slate-400 tracking-wider mb-2.5">1. Selecciona
                        un estado y haz clic sobre el diente:</span>
                    <div class="flex flex-wrap gap-2" id="herramientasOdontograma">
                        <button type="button" onclick="seleccionarHerramienta('sano')" data-tool="sano"
                            class="tool-btn ring-2 ring-offset-1 ring-brand-500 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-slate-700 border border-slate-300 flex items-center space-x-2 shadow-sm transition-all">
                            <span class="w-3 h-3 rounded-full bg-white border border-slate-400 inline-block"></span>
                            <span>Sano</span>
                        </button>
                        <button type="button" onclick="seleccionarHerramienta('caries')" data-tool="caries"
                            class="tool-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2 transition-all">
                            <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                            <span>Caries / Afectado</span>
                        </button>
                        <button type="button" onclick="seleccionarHerramienta('restaurado')" data-tool="restaurado"
                            class="tool-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200 flex items-center space-x-2 transition-all">
                            <span class="w-3 h-3 rounded-full bg-sky-500 inline-block"></span>
                            <span>Restaurado / Calza</span>
                        </button>
                        <button type="button" onclick="seleccionarHerramienta('protesis')" data-tool="protesis"
                            class="tool-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 flex items-center space-x-2 transition-all">
                            <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                            <span>Corona / Prótesis</span>
                        </button>
                        <button type="button" onclick="seleccionarHerramienta('ausente')" data-tool="ausente"
                            class="tool-btn px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-200 text-slate-700 border border-slate-300 flex items-center space-x-2 transition-all">
                            <span class="w-3 h-3 rounded-full bg-slate-600 inline-block"></span>
                            <span>Ausente / Extraído</span>
                        </button>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-2xl p-2 sm:p-4 md:p-6 bg-white shadow-inner overflow-hidden">
                    <div class="w-full space-y-4 sm:space-y-6">

                        <div>
                            <div
                                class="text-center text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2 sm:mb-3">
                                Arcada Superior</div>
                            <div class="grid grid-cols-2 gap-2 sm:gap-4 md:gap-6 border-b border-dashed border-slate-200 pb-4 sm:pb-6">
                                <div id="cuadrante-1"
                                    class="flex justify-end gap-0.5 sm:gap-1 md:gap-1.5 pr-1 sm:pr-2 border-r border-slate-200"></div>
                                <div id="cuadrante-2" class="flex justify-start gap-0.5 sm:gap-1 md:gap-1.5 pl-1 sm:pl-2"></div>
                            </div>
                        </div>

                        <div>
                            <div class="grid grid-cols-2 gap-2 sm:gap-4 md:gap-6 pt-2">
                                <div id="cuadrante-4"
                                    class="flex justify-end gap-0.5 sm:gap-1 md:gap-1.5 pr-1 sm:pr-2 border-r border-slate-200"></div>
                                <div id="cuadrante-3" class="flex justify-start gap-0.5 sm:gap-1 md:gap-1.5 pl-1 sm:pl-2"></div>
                            </div>
                            <div
                                class="text-center text-[11px] font-bold uppercase tracking-widest text-slate-400 mt-2 sm:mt-3">
                                Arcada Inferior</div>
                        </div>

                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Observaciones de Salud
                        Bucal / Notas de Laboratorio</label>
                    <textarea id="odonto_observaciones" rows="3"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-slate-700 resize-none"
                        placeholder="Especificaciones de colorimetría (Ej. A2, B1), requerimientos de prótesis, alergias o detalles de las piezas marcadas..."></textarea>
                </div>

            </div>

            <div
                class="px-5 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 flex-shrink-0">
                <button type="button" onclick="limpiarOdontograma()"
                    class="text-xs font-semibold text-slate-500 hover:text-slate-700 underline">
                    Marcar todos como sanos
                </button>
                <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                    <button type="button" onclick="cerrarModalOdontograma()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-100 transition-colors text-sm w-full sm:w-auto">
                        Cerrar
                    </button>
                    <button type="button" id="btnGuardarOdontograma" onclick="guardarOdontograma()"
                        class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm w-full sm:w-auto flex items-center justify-center space-x-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Guardar Odontograma</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL CONFIRMACIÓN DE ELIMINAR                                            -->
    <!-- ========================================================================= -->
    <div id="eliminarModal"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300"
            id="eliminarModalContainer">
            <div class="p-6 text-center">
                <div
                    class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-triangle" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 tracking-tight">¿Eliminar Paciente?</h3>
                <p class="text-sm text-slate-500 mt-2 mb-6 leading-relaxed">Se eliminará el registro del paciente y su
                    historial de odontograma. Esta acción no se puede deshacer.</p>

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
        class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300"
            id="notificacionModalContainer">
            <div class="p-6 text-center">
                <div id="notificacionIconContainer"
                    class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
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
        let tablePacientes;
        let idPacienteParaEliminar = null;
        let catalogosPacientes = { clinicas: [], sedes: [] };

        // Variables del Odontograma
        let herramientaActiva = 'sano';
        let estadoDientes = {};
        const cuadrantesFDI = {
            'cuadrante-1': [18, 17, 16, 15, 14, 13, 12, 11],
            'cuadrante-2': [21, 22, 23, 24, 25, 26, 27, 28],
            'cuadrante-4': [48, 47, 46, 45, 44, 43, 42, 41],
            'cuadrante-3': [31, 32, 33, 34, 35, 36, 37, 38]
        };

        // Paleta y estilos modernos para el SVG anatómico
        const configuracionEstados = {
            sano: {
                fill: '#CBD5E1',
                stroke: '#94A3B8',
                label: '',
                labelClass: 'hidden'
            },
            caries: {
                fill: '#F43F5E',
                stroke: '#BE123C',
                label: 'C',
                labelClass: 'bg-rose-500 text-white shadow-sm shadow-rose-200'
            },
            restaurado: {
                fill: '#0284C7',
                stroke: '#0369A1',
                label: 'R',
                labelClass: 'bg-sky-500 text-white shadow-sm shadow-sky-200'
            },
            protesis: {
                fill: '#F59E0B',
                stroke: '#D97706',
                label: 'P',
                labelClass: 'bg-amber-500 text-white shadow-sm shadow-amber-200'
            },
            ausente: {
                fill: '#475569',
                stroke: '#1E293B',
                label: 'X',
                labelClass: 'bg-slate-700 text-slate-100 shadow-sm'
            }
        };

        // =========================================================================
        // NOTIFICACIÓN GLOBAL UNIFICADA
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
            setTimeout(() => { modal.classList.add("hidden"); }, 300);
        }

        // =========================================================================
        // INICIALIZACIÓN DE VISTA Y TABLA
        // =========================================================================
        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
            dibujarEstructuraOdontograma();
            cargarCatalogosPacientes();

            tablePacientes = new Tabulator("#pacientes-table", {
                ajaxURL: "/obtener-pacientes-controller",
                ajaxConfig: "POST",
                ajaxParams: { action: "listar_pacientes" },
                ajaxResponse: function (url, params, response) {
                    return response.data || [];
                },
                layout: "fitColumns",
                responsiveLayout: "collapse",
                placeholder: "<div class='p-8 text-center text-slate-500'><i data-lucide='users' class='w-12 h-12 mx-auto mb-3 opacity-20'></i>No hay pacientes registrados.</div>",
                pagination: "local",
                paginationSize: 10,
                locale: "es",
                langs: {
                    "es": { "pagination": { "first": "PRIMERO", "last": "ÚLTIMO", "prev": "ANTERIOR", "next": "SIGUIENTE" } }
                },
                columns: [
                    { formatter: "responsiveCollapse", width: 35, minWidth: 35, hozAlign: "center", resizable: false, headerSort: false, responsive: 0 },
                    {
                        title: "Documento",
                        field: "numero_documento",
                        width: 125,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `<span class="font-mono font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg text-xs">${d.tipo_documento}-${d.numero_documento}</span>`;
                        }
                    },
                    {
                        title: "Paciente",
                        field: "primer_nombre",
                        minWidth: 200,
                        responsive: 0,
                        formatter: (cell) => {
                            const d = cell.getData();
                            const segNombre = d.segundo_nombre ? ' ' + d.segundo_nombre : '';
                            const segApellido = d.segundo_apellido ? ' ' + d.segundo_apellido : '';
                            return `<div class="font-bold text-slate-800 leading-tight">${d.primer_nombre}${segNombre} ${d.primer_apellido}${segApellido}</div>`;
                        }
                    },
                    {
                        title: "Clínica y Sede",
                        field: "clinica_nombre",
                        minWidth: 180,
                        formatter: (cell) => {
                            const d = cell.getData();
                            if (!d.clinica_nombre) {
                                return `<span class="text-slate-400 italic text-xs">Sin clínica asignada</span>`;
                            }
                            const sedeTxt = d.sede_nombre ? `<span class="block text-[11px] text-brand-600 font-semibold">• ${d.sede_nombre}</span>` : '';
                            return `<div><span class="font-semibold text-slate-700 text-xs">${d.clinica_nombre}</span>${sedeTxt}</div>`;
                        }
                    },
                    {
                        title: "Sexo",
                        field: "sexo",
                        width: 95,
                        hozAlign: "center",
                        formatter: (cell) => {
                            const val = cell.getValue();
                            if (val === 'H') {
                                return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-100">Hombre</span>`;
                            }
                            return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-pink-50 text-pink-700 border border-pink-100">Mujer</span>`;
                        }
                    },
                    {
                        title: "Acciones",
                        width: 180,
                        minWidth: 170,
                        hozAlign: "center",
                        headerSort: false,
                        resizable: false,
                        responsive: 0,
                        formatter: function (cell, formatterParams, onRendered) {
                            const d = cell.getData();
                            const nombreCompleto = `${d.primer_nombre} ${d.primer_apellido}`;
                            onRendered(() => lucide.createIcons());
                            return `
                                <div class="flex items-center justify-center space-x-1">
                                    <button class="btn-odontograma px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200" 
                                        title="Ver Salud Bucal" data-id="${d.id}" data-nombre="${nombreCompleto}">
                                        <i data-lucide="smile" class="w-3.5 h-3.5 inline mr-1"></i>Odontograma
                                    </button>
                                    <button class="btn-editar p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Editar" data-id="${d.id}">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn-eliminar p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Eliminar" data-id="${d.id}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ]
            });

            // Buscador Multicriterio
            document.getElementById("buscar-paciente").addEventListener("input", function (e) {
                const term = e.target.value.toLowerCase();
                tablePacientes.setFilter([
                    [
                        { field: "primer_nombre", type: "like", value: term },
                        { field: "segundo_nombre", type: "like", value: term },
                        { field: "primer_apellido", type: "like", value: term },
                        { field: "segundo_apellido", type: "like", value: term },
                        { field: "numero_documento", type: "like", value: term },
                        { field: "clinica_nombre", type: "like", value: term },
                        { field: "sede_nombre", type: "like", value: term }
                    ]
                ]);
            });

            // Delegación de eventos en la tabla
            document.getElementById("pacientes-table").addEventListener('click', function (e) {
                const btnOdonto = e.target.closest('.btn-odontograma');
                const btnEditar = e.target.closest('.btn-editar');
                const btnEliminar = e.target.closest('.btn-eliminar');

                if (btnOdonto) {
                    abrirModalOdontograma(
                        btnOdonto.getAttribute('data-id'),
                        btnOdonto.getAttribute('data-nombre')
                    );
                } else if (btnEditar) {
                    editarPaciente(btnEditar.getAttribute('data-id'));
                } else if (btnEliminar) {
                    abrirEliminarModal(btnEliminar.getAttribute('data-id'));
                }
            });

            // Submit Formulario Crear/Editar Paciente
            document.getElementById("pacienteForm").addEventListener("submit", async function (e) {
                e.preventDefault();
                const btn = document.getElementById("btnGuardarPaciente");
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>`;
                lucide.createIcons();

                const formData = new FormData(this);
                const isEditing = formData.get("id") !== "";
                const targetUrl = isEditing ? '/editar-paciente-controller' : '/agregar-paciente-controller';

                try {
                    const response = await fetch(targetUrl, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    cerrarModalPaciente();
                    setTimeout(() => {
                        if (result.status === 'success') {
                            notificar('success', result.message || 'Paciente guardado exitosamente.');
                            tablePacientes.setData();
                        } else {
                            notificar('error', result.message || 'Error al procesar la solicitud.');
                        }
                    }, 350);
                } catch (error) {
                    cerrarModalPaciente();
                    setTimeout(() => notificar('error', 'Error de red o comunicación con el servidor.'), 350);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            });
        });

        // =========================================================================
        // CARGA DE CATÁLOGOS (CLÍNICAS Y SEDES)
        // =========================================================================
        async function cargarCatalogosPacientes() {
            try {
                const fd = new FormData();
                fd.append('action', 'cargar_catalogos');
                const res = await fetch('/obtener-casos-controller', { method: 'POST', body: fd });
                const result = await res.json();
                if (result.status === 'success') {
                    catalogosPacientes = result;
                    const selClinica = document.getElementById("clinica_id");
                    selClinica.innerHTML = '<option value="">Sin clínica asignada</option>';
                    (result.clinicas || []).forEach(c => {
                        selClinica.innerHTML += `<option value="${c.id}">${c.razon_social}</option>`;
                    });

                    selClinica.addEventListener('change', function() {
                        filtrarSedesPaciente(this.value);
                    });
                }
            } catch(e) {
                console.error("Error al cargar clínicas para pacientes:", e);
            }
        }

        function filtrarSedesPaciente(clinicaId, sedeIdSeleccionada = null) {
            const selSede = document.getElementById("sede_id");
            selSede.innerHTML = '<option value="">Sin sede asignada</option>';
            if (!clinicaId) return;

            const filtradas = (catalogosPacientes.sedes || []).filter(s => String(s.clinica_id) === String(clinicaId));
            filtradas.forEach(s => {
                const principalTxt = s.principal == 1 ? ' (Principal)' : '';
                const selected = (sedeIdSeleccionada && String(sedeIdSeleccionada) === String(s.id)) ? 'selected' : '';
                selSede.innerHTML += `<option value="${s.id}" ${selected}>${s.nombre}${principalTxt}</option>`;
            });
        }

        // =========================================================================
        // CONTROL DE MODAL PACIENTE (CREAR / EDITAR)
        // =========================================================================
        function abrirModalPaciente() {
            document.getElementById("pacienteForm").reset();
            document.getElementById("paciente_id").value = "";
            document.getElementById("clinica_id").value = "";
            filtrarSedesPaciente(null);
            document.getElementById("modalPacienteTitle").textContent = "Agregar Paciente";

            const modal = document.getElementById("pacienteModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("modalPacienteContainer").classList.remove("scale-95");
            }, 10);
        }

        function cerrarModalPaciente() {
            const modal = document.getElementById("pacienteModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalPacienteContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 300);
        }

        async function editarPaciente(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'show_paciente');
                formData.append('id', id);

                const response = await fetch('/obtener-pacientes-controller', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success' && data.paciente) {
                    const p = data.paciente;
                    document.getElementById("paciente_id").value = p.id;
                    document.getElementById("primer_nombre").value = p.primer_nombre;
                    document.getElementById("segundo_nombre").value = p.segundo_nombre || "";
                    document.getElementById("primer_apellido").value = p.primer_apellido;
                    document.getElementById("segundo_apellido").value = p.segundo_apellido || "";
                    document.getElementById("tipo_documento").value = p.tipo_documento;
                    document.getElementById("numero_documento").value = p.numero_documento;
                    document.getElementById("sexo").value = p.sexo;

                    document.getElementById("clinica_id").value = p.clinica_id || "";
                    filtrarSedesPaciente(p.clinica_id, p.sede_id);

                    document.getElementById("modalPacienteTitle").textContent = "Editar Paciente";

                    const modal = document.getElementById("pacienteModal");
                    modal.classList.remove("hidden");
                    setTimeout(() => {
                        modal.classList.remove("opacity-0");
                        document.getElementById("modalPacienteContainer").classList.remove("scale-95");
                    }, 10);
                } else {
                    notificar('error', data.message || 'No se pudo cargar el paciente.');
                }
            } catch (error) {
                notificar('error', 'Error de red al consultar el paciente.');
            }
        }

        // =========================================================================
        // LÓGICA DEL ODONTOGRAMA INTERACTIVO (SVG ANATÓMICO 5 SUPERFICIES)
        // =========================================================================
        function dibujarEstructuraOdontograma() {
            for (const [contenedorId, dientes] of Object.entries(cuadrantesFDI)) {
                const contenedor = document.getElementById(contenedorId);
                contenedor.innerHTML = '';
                dientes.forEach(num => {
                    estadoDientes[num] = 'sano';
                    const dienteEl = document.createElement('div');
                    dienteEl.className = "diente flex flex-col items-center group cursor-pointer select-none p-0.5 sm:p-1 rounded-lg sm:rounded-xl hover:bg-slate-100/80 transition-all flex-1 max-w-[42px]";
                    dienteEl.id = `diente-wrap-${num}`;
                    dienteEl.onclick = () => cambiarEstadoDiente(num);
                    dienteEl.innerHTML = `
                        <span class="text-[9px] sm:text-[10px] md:text-[11px] font-bold text-slate-500 mb-0.5 sm:mb-1 group-hover:text-brand-600 transition-colors">${num}</span>
                        <div class="diente-box relative flex items-center justify-center w-full">
                            <svg viewBox="0 0 500 500" width="100%" height="100%" class="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 transition-transform group-hover:scale-105 duration-150 drop-shadow-sm">
                                <defs>
                                    <path id="ring-seg-${num}" fill="inherit" d="M 149.35,100.77 A 180 180 0 0 1 350.65,100.77 L 311.51,158.81 A 110 110 0 0 0 188.49,158.81 Z" />
                                </defs>
                                <g id="tooth-g-${num}" fill="#CBD5E1" stroke="#94A3B8" stroke-width="12" stroke-linejoin="round" class="transition-colors duration-200">
                                    <circle cx="250" cy="250" r="72" fill="inherit" />
                                    <use href="#ring-seg-${num}" />
                                    <use href="#ring-seg-${num}" transform="rotate(90 250 250)" />
                                    <use href="#ring-seg-${num}" transform="rotate(180 250 250)" />
                                    <use href="#ring-seg-${num}" transform="rotate(270 250 250)" />
                                </g>
                            </svg>
                            <span id="diente-label-${num}" class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[9px] font-extrabold flex items-center justify-center hidden"></span>
                        </div>
                    `;
                    contenedor.appendChild(dienteEl);
                });
            }
        }

        function seleccionarHerramienta(tool) {
            herramientaActiva = tool;
            document.querySelectorAll('.tool-btn').forEach(btn => {
                if (btn.getAttribute('data-tool') === tool) {
                    btn.classList.add('ring-2', 'ring-offset-1', 'ring-brand-500');
                } else {
                    btn.classList.remove('ring-2', 'ring-offset-1', 'ring-brand-500');
                }
            });
        }

        function cambiarEstadoDiente(num) {
            const nuevoEstado = (estadoDientes[num] === herramientaActiva) ? 'sano' : herramientaActiva;
            aplicarEstiloDiente(num, nuevoEstado);
        }

        function aplicarEstiloDiente(num, estado) {
            const est = configuracionEstados[estado] ? estado : 'sano';
            estadoDientes[num] = est;
            const config = configuracionEstados[est];
            const g = document.getElementById(`tooth-g-${num}`);
            const label = document.getElementById(`diente-label-${num}`);
            const wrap = document.getElementById(`diente-wrap-${num}`);

            if (g && label) {
                g.setAttribute('fill', config.fill);
                g.setAttribute('stroke', config.stroke);

                if (config.label) {
                    label.textContent = config.label;
                    label.className = `absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[9px] font-extrabold flex items-center justify-center ${config.labelClass}`;
                } else {
                    label.textContent = '';
                    label.className = 'hidden';
                }

                if (wrap) {
                    if (est === 'ausente') {
                        wrap.classList.add('opacity-40');
                    } else {
                        wrap.classList.remove('opacity-40');
                    }
                }
            }
        }

        function limpiarOdontograma() {
            Object.keys(estadoDientes).forEach(num => aplicarEstiloDiente(num, 'sano'));
        }

        async function abrirModalOdontograma(pacienteId, nombrePaciente) {
            document.getElementById("odonto_paciente_id").value = pacienteId;
            document.getElementById("subtituloOdontograma").textContent = nombrePaciente;
            limpiarOdontograma();
            document.getElementById("odonto_observaciones").value = "";

            try {
                const formData = new FormData();
                formData.append('action', 'obtener_odontograma');
                formData.append('paciente_id', pacienteId);

                const response = await fetch('/obtener-odontograma-paciente-controller', {
                    method: 'POST', 
                    body: formData 
                });
                const result = await response.json();

                if (result.status === 'success' && result.odontograma) {
                    // Si el backend devuelve datos_json como string JSON
                    let datosDientes = {};
                    if (result.odontograma.datos_json) {
                        datosDientes = typeof result.odontograma.datos_json === 'string'
                            ? JSON.parse(result.odontograma.datos_json)
                            : result.odontograma.datos_json;
                    }
                    Object.entries(datosDientes).forEach(([num, est]) => aplicarEstiloDiente(num, est));
                    document.getElementById("odonto_observaciones").value = result.odontograma.observaciones || "";
                }
            } catch (e) {
                console.warn("Cargando odontograma en blanco:", e);
            }

            const modal = document.getElementById("odontogramaModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("modalOdontogramaContainer").classList.remove("scale-95");
            }, 10);
        }

        function cerrarModalOdontograma() {
            const modal = document.getElementById("odontogramaModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalOdontogramaContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 300);
        }

                async function guardarOdontograma() {
            const pacienteId = document.getElementById("odonto_paciente_id").value;
            const observaciones = document.getElementById("odonto_observaciones").value;
            const btn = document.getElementById("btnGuardarOdontograma");
            const originalHtml = btn.innerHTML;

            if (!pacienteId) {
                notificar('error', 'No se identificó el paciente.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Guardando...</span>`;
            lucide.createIcons();

            const formData = new FormData();
            formData.append('action', 'guardar_odontograma');
            formData.append('paciente_id', pacienteId);
            formData.append('datos_json', JSON.stringify(estadoDientes));
            formData.append('observaciones', observaciones);

            try {
                const response = await fetch('/editar-odontograma-paciente-controller', {
                    method: 'POST', 
                    body: formData 
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    cerrarModalOdontograma();
                    setTimeout(() => {
                        notificar('success', result.message || 'Odontograma actualizado correctamente.');
                    }, 350);
                } else {
                    notificar('error', result.message || 'No se pudo guardar el odontograma.');
                }
            } catch (error) {
                console.error("Error guardando odontograma:", error);
                notificar('error', 'Error de conexión al guardar el odontograma.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                lucide.createIcons();
            }
        }

        // =========================================================================
        // ELIMINACIÓN DE PACIENTE
        // =========================================================================
        function abrirEliminarModal(id) {
            idPacienteParaEliminar = id;
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
                idPacienteParaEliminar = null;
            }, 300);
        }

        document.getElementById("btnConfirmarEliminar").addEventListener("click", async function () {
            if (!idPacienteParaEliminar) return;

            const btn = this;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto"></i>`;
            lucide.createIcons();

            try {
                const formData = new FormData();
                formData.append('id', idPacienteParaEliminar);

                const response = await fetch('/eliminar-paciente-controller', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                cerrarEliminarModal();
                setTimeout(() => {
                    if (result.status === 'success') {
                        notificar('success', result.message || 'Paciente eliminado.');
                        tablePacientes.setData();
                    } else {
                        notificar('error', result.message || 'No se pudo eliminar el paciente.');
                    }
                }, 350);
            } catch (error) {
                cerrarEliminarModal();
                setTimeout(() => notificar('error', 'Error de red al intentar eliminar.'), 350);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    </script>
</body>

</html>