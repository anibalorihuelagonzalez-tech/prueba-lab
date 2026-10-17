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
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <i data-lucide="user-check" class="w-4 h-4"></i>
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Recursos Humanos</h1>
                </div>
            </div>
            <div class="flex items-center space-x-3 md:space-x-4 hidden sm:flex">
                <span class="text-sm font-medium text-slate-600">Hola,
                    <strong><?= htmlspecialchars(Auth::name() ?? 'Administrador') ?></strong></span>
                <div class="h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                    <?= htmlspecialchars(substr(Auth::name() ?? 'A', 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Main View Scrollable Area -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden bg-slate-50/50 p-4 md:p-8 space-y-6">

            <!-- KPIs Header Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Colaboradores</p>
                        <h3 id="kpi-total-personal" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Equipo registrado CNSB</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Protesistas Dentales</p>
                        <h3 id="kpi-protesistas" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-amber-600 font-medium mt-0.5">Técnicos de laboratorio</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="award" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Logística & Reparto</p>
                        <h3 id="kpi-logistica" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-blue-600 font-medium mt-0.5">Flota y despachadores</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="bike" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Colaboradores Activos</p>
                        <h3 id="kpi-activos" class="text-2xl font-black text-emerald-600 mt-1">0</h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">En nómina activa</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                    </div>
                </div>

            </div>

            <!-- Action Bar Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 md:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Directorio y Expedientes de Personal</h2>
                    <p class="text-sm text-slate-500 mt-1">Administra los técnicos dentales, ceramistas, repartidores y equipo administrativo con fotos de perfil.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="abrirModalRegistrarEmpleado()"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 text-sm">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                        <span>Registrar Nuevo Colaborador</span>
                    </button>
                </div>
            </div>

            <!-- Tabla de Personal -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="buscar-personal" placeholder="Buscar por nombre, cédula, cargo..."
                            class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                    </div>
                    <div class="flex items-center gap-2">
                        <select id="filtro-departamento" onchange="filtrarPersonal()"
                            class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                            <option value="">Todos los departamentos</option>
                            <option value="Laboratorio Dental">Laboratorio Dental</option>
                            <option value="Logística y Envíos">Logística y Envíos</option>
                            <option value="Administración y Finanzas">Administración y Finanzas</option>
                            <option value="Atención y Recepción">Recepción</option>
                        </select>
                        <button type="button" onclick="cargarListaPersonal()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors" title="Recargar">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Tabulator Table -->
                <div class="w-full">
                    <div id="personal-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: REGISTRAR / EDITAR COLABORADOR CON FOTO                           -->
    <!-- ========================================================================= -->
    <div id="modalRegistrarEmpleado"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[92vh] flex flex-col">
            
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 id="modalEmpTitulo" class="text-lg font-bold text-slate-800">Registrar Nuevo Colaborador</h3>
                        <p class="text-xs text-slate-500">Completa los datos del integrante del equipo y asigna su fotografía.</p>
                    </div>
                </div>
                <button onclick="cerrarModalRegistrarEmpleado()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="form-registrar-empleado" enctype="multipart/form-data" onsubmit="guardarEmpleado(event); return false;" class="p-6 overflow-y-auto space-y-4 flex-1">
                <input type="hidden" id="emp_id" name="id">

                <!-- Sección: Fotografía del Colaborador -->
                <div class="flex flex-col sm:flex-row items-center gap-4 p-4 rounded-xl bg-slate-50/80 border border-slate-200/80">
                    <div class="relative w-20 h-20 rounded-full border-2 border-brand-500 overflow-hidden bg-slate-200 flex-shrink-0 flex items-center justify-center shadow-md">
                        <img id="preview-foto" src="/assets/img/avatar-placeholder.png" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\' viewBox=\'0 0 24 24\' fill=\'%23cbd5e1\'><path d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/></svg>'" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 text-center sm:text-left">
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Fotografía del Trabajador</label>
                        <p class="text-[11px] text-slate-500 mb-2">Formatos permitidos: JPG, PNG o WebP. Se ajustará al perfil del empleado.</p>
                        <input type="file" id="input-foto" name="foto" accept="image/*" onchange="previsualizarFoto(this)"
                            class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer">
                    </div>
                </div>

                <!-- Nombres y Apellidos -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Primer Nombre *</label>
                        <input type="text" id="emp_primer_nombre" name="primer_nombre" required
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium"
                            placeholder="Ej. Carlos">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Segundo Nombre</label>
                        <input type="text" id="emp_segundo_nombre" name="segundo_nombre"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium"
                            placeholder="Ej. Eduardo">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Primer Apellido *</label>
                        <input type="text" id="emp_primer_apellido" name="primer_apellido" required
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium"
                            placeholder="Ej. González">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Segundo Apellido</label>
                        <input type="text" id="emp_segundo_apellido" name="segundo_apellido"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium"
                            placeholder="Ej. Pérez">
                    </div>
                </div>

                <!-- Documento de Identidad y Contacto -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tipo Doc. *</label>
                        <select id="emp_tipo_doc" name="tipo_documento" required
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="V">V - Venezolano</option>
                            <option value="E">E - Extranjero</option>
                            <option value="P">P - Pasaporte</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Número de Cédula *</label>
                        <input type="text" id="emp_numero_doc" name="numero_documento" required
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium"
                            placeholder="Ej. 18234567">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Correo Electrónico</label>
                        <input type="email" id="emp_correo" name="correo_electronico"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium"
                            placeholder="colaborador@cnsblab.com">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Teléfono Móvil</label>
                        <input type="text" id="emp_telefono" name="telefono"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium"
                            placeholder="Ej. +58 412 1234567">
                    </div>
                </div>

                <!-- Cargo, Estatus y Contrato -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Puesto / Cargo *</label>
                        <select id="emp_cargo_id" name="cargo_id" required
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="">Seleccione cargo...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tipo de Contrato</label>
                        <select id="emp_tipo_contrato" name="tipo_contrato"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="INDETERMINADO">Indeterminado (Fijo)</option>
                            <option value="DETERMINADO">Determinado (Por obra / tiempo)</option>
                        </select>
                    </div>
                </div>

                <!-- Fechas y Salario Base -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Fecha de Ingreso *</label>
                        <input type="date" id="emp_fecha_ingreso" name="fecha_ingreso" required
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Salario Base Mensual (Bs.)</label>
                        <input type="number" step="0.01" id="emp_salario" name="salario_base_ves"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium"
                            placeholder="0.00">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalRegistrarEmpleado()"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-emp"
                        class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-md shadow-brand-500/20">
                        Guardar Colaborador
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: FICHA DE PERFIL DEL COLABORADOR (CON FOTO COMPLETA)              -->
    <!-- ========================================================================= -->
    <div id="modalFichaEmpleado"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300 p-6 space-y-4">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <span class="text-xs font-bold uppercase text-slate-400">Expediente de Personal</span>
                <button onclick="cerrarModalFichaEmpleado()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="text-center pt-2">
                <div class="w-24 h-24 rounded-full border-4 border-white shadow-xl overflow-hidden mx-auto mb-3 bg-slate-100 flex items-center justify-center">
                    <img id="ficha-foto" src="/assets/img/avatar-placeholder.png" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'96\' height=\'96\' viewBox=\'0 0 24 24\' fill=\'%23cbd5e1\'><path d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/></svg>'" class="w-full h-full object-cover">
                </div>
                <h3 id="ficha-nombre" class="text-lg font-bold text-slate-900">Nombre Colaborador</h3>
                <span id="ficha-cargo" class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 mt-1">Cargo</span>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 space-y-2.5 text-xs text-slate-700 border border-slate-200/80">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Cédula / Documento:</span>
                    <strong id="ficha-documento" class="text-slate-800">V-00000000</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Departamento:</span>
                    <strong id="ficha-depto" class="text-slate-800">Laboratorio Dental</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Contacto / Teléfono:</span>
                    <strong id="ficha-contacto" class="text-slate-800 truncate">+58 412 0000000</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Correo Electrónico:</span>
                    <strong id="ficha-correo" class="text-slate-800 truncate">usuario@cnsblab.com</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Fecha de Ingreso:</span>
                    <strong id="ficha-ingreso" class="text-slate-800">12/10/2026</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Salario Base:</span>
                    <strong id="ficha-salario" class="text-emerald-700 font-bold">Bs. 0.00 (~$0.00 USD)</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Estatus Actual:</span>
                    <span id="ficha-estatus" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700">Activo</span>
                </div>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button" onclick="cerrarModalFichaEmpleado()"
                    class="w-1/2 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    Cerrar
                </button>
                <button type="button" id="btn-editar-desde-ficha" onclick="editarEmpleadoDesdeFicha()"
                    class="w-1/2 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition-colors">
                    Editar Datos
                </button>
            </div>
        </div>
    </div>

    <!-- Tabulator Local -->
    <script type="text/javascript" src="/assets/vendor/tabulator/tabulator.min.js"></script>

    <script>
        let tablaPersonal = null;
        let catalogoCargos = [];
        let tasaActual = 1.0;
        let empleadoSeleccionado = null;

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
            inicializarTablaPersonal();
            cargarListaPersonal();
            cargarCatalogosPersonal();

            document.getElementById("buscar-personal").addEventListener("input", function(e) {
                const term = e.target.value.toLowerCase();
                if (!tablaPersonal) return;
                tablaPersonal.setFilter(function(data) {
                    return (data.nombre_completo && data.nombre_completo.toLowerCase().includes(term)) ||
                           (data.documento_completo && data.documento_completo.toLowerCase().includes(term)) ||
                           (data.cargo_nombre && data.cargo_nombre.toLowerCase().includes(term)) ||
                           (data.correo && data.correo.toLowerCase().includes(term));
                });
                setTimeout(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }, 50);
            });
        });

        function previsualizarFoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById("preview-foto").src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

function inicializarTablaPersonal() {
            tablaPersonal = new Tabulator("#personal-table", {
                data: [],
                layout: "fitColumns",
                responsiveLayout: "collapse",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50],
                placeholder: "<div class='p-8 text-center text-slate-500'><i data-lucide='users' class='w-12 h-12 mx-auto mb-3 opacity-20'></i>No se encontraron colaboradores registrados.</div>",
                columns: [
                    { formatter: "responsiveCollapse", width: 30, minWidth: 30, hozAlign: "center", resizable: false, headerSort: false },
                    {
                        title: "Colaborador", field: "nombre_completo", minWidth: 220, responsive: 0,
                        formatter: (cell) => {
                            const d = cell.getData();
                            const avatarHtml = d.foto 
                                ? `<img src="${d.foto}" class="w-9 h-9 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0" onerror="this.outerHTML='<div class=\\'w-9 h-9 rounded-full bg-brand-100 text-brand-700 font-bold flex items-center justify-center text-xs flex-shrink-0\\'>${(d.primer_nombre||'U').charAt(0)}</div>'">`
                                : `<div class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 font-bold flex items-center justify-center text-xs flex-shrink-0">${(d.primer_nombre||'U').charAt(0)}</div>`;
                            
                            return `
                                <div class="flex items-center gap-2.5">
                                    ${avatarHtml}
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-800 text-xs truncate">${d.nombre_completo}</p>
                                        <p class="text-[10px] text-slate-400 font-mono">${d.documento_completo || 'Sin Cédula'} &bull; ${d.correo}</p>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Cargo / Puesto", field: "cargo_nombre", minWidth: 150, hozAlign: "center",
                        formatter: (cell) => {
                            const val = cell.getValue() || 'General';
                            let badgeCls = 'bg-slate-100 text-slate-700 border-slate-200';
                            if (val.includes('PROTESISTA') || val.includes('CERAMISTA') || val.includes('CAD')) badgeCls = 'bg-amber-50 text-amber-700 border-amber-200';
                            else if (val.includes('REPARTIDOR') || val.includes('LOGÍSTICA')) badgeCls = 'bg-sky-50 text-sky-700 border-sky-200';
                            else if (val.includes('ADMIN') || val.includes('DIRECTOR')) badgeCls = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            return `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold border ${badgeCls}">${val}</span>`;
                        }
                    },
                    {
                        title: "Departamento", field: "departamento", minWidth: 150,
                        formatter: (cell) => `<span class="text-xs font-medium text-slate-600">${cell.getValue()}</span>`
                    },
                    {
                        title: "Salario Ref.", field: "salario_base_ves", minWidth: 130, hozAlign: "right",
                        formatter: (cell) => {
                            const d = cell.getData();
                            const ves = parseFloat(d.salario_base_ves || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 });
                            const usd = parseFloat(d.salario_usd || 0).toFixed(2);
                            return `<div class="text-right"><span class="font-bold text-slate-800 text-xs">Bs. ${ves}</span><div class="text-[10px] text-emerald-600 font-semibold">~$${usd} USD</div></div>`;
                        }
                    },
                    {
                        title: "Estatus", field: "is_active", minWidth: 95, hozAlign: "center",
                        formatter: (cell) => {
                            const activo = String(cell.getValue()) === '1';
                            return activo 
                                ? `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Activo</span>`
                                : `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">Inactivo</span>`;
                        }
                    },
                    {
                        title: "Ingreso", field: "fecha_ingreso_fmt", minWidth: 95, hozAlign: "center",
                        formatter: (cell) => `<span class="text-xs text-slate-500">${cell.getValue() || '-'}</span>`
                    },
                    {
                        title: "Acciones", width: 140, minWidth: 130, hozAlign: "center", headerSort: false, responsive: 0,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" onclick="verFichaEmpleado(${d.id})" title="Ver Ficha y Foto" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs transition-colors inline-flex items-center justify-center">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                    <button type="button" onclick="abrirModalEditarEmpleado(${d.id})" title="Editar Expediente" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 text-xs transition-colors inline-flex items-center justify-center">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button type="button" onclick="toggleEstatusEmpleado(${d.id}, ${d.is_active})" title="${d.is_active == 1 ? 'Desactivar' : 'Activar'}" class="p-1.5 rounded-lg ${d.is_active == 1 ? 'bg-rose-50 text-rose-600 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'} text-xs transition-colors inline-flex items-center justify-center">
                                        <i data-lucide="${d.is_active == 1 ? 'user-x' : 'user-check'}" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                // MODIFICACIÓN APLICADA AQUÍ: 
                // Usar rowFormatter en lugar de los eventos globales (renderComplete, etc.)
                rowFormatter: function(row) {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons({
                            root: row.getElement() // Limita la búsqueda de íconos solo a esta fila
                        });
                    }
                }
            });
        }

        async function cargarListaPersonal() {
            try {
                const fd = new FormData();
                fd.append('action', 'listar_personal');
                const res = await fetch('/recursos-humanos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    tasaActual = json.tasa_actual || 1.0;
                    if (tablaPersonal) {
                        await tablaPersonal.setData(json.data);
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                            setTimeout(() => lucide.createIcons(), 60);
                        }
                    }
                    if (json.kpis) {
                        document.getElementById("kpi-total-personal").textContent = json.kpis.total_personal || 0;
                        document.getElementById("kpi-protesistas").textContent = json.kpis.protesistas || 0;
                        document.getElementById("kpi-logistica").textContent = json.kpis.logistica || 0;
                        document.getElementById("kpi-activos").textContent = json.kpis.activos || 0;
                    }
                }
            } catch (err) {
                console.error("Error al cargar personal:", err);
            }
        }

        async function cargarCatalogosPersonal() {
            try {
                const fd = new FormData();
                fd.append('action', 'cargar_catalogos');
                const res = await fetch('/recursos-humanos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    catalogoCargos = json.cargos || [];
                    const sel = document.getElementById("emp_cargo_id");
                    sel.innerHTML = '<option value="">Seleccione cargo...</option>';
                    catalogoCargos.forEach(c => {
                        sel.innerHTML += `<option value="${c.id}">${c.nombre} (${c.departamento})</option>`;
                    });
                }
            } catch (err) {
                console.error("Error al cargar catálogos:", err);
            }
        }

        function filtrarPersonal() {
            const val = document.getElementById("filtro-departamento").value;
            if (!tablaPersonal) return;
            if (!val) {
                tablaPersonal.clearFilter();
            } else {
                tablaPersonal.setFilter("departamento", "=", val);
            }
            setTimeout(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 50);
        }

        // =========================================================================
        // MODALES REGISTRO, EDICIÓN Y FICHA
        // =========================================================================
        function abrirModalRegistrarEmpleado() {
            document.getElementById("form-registrar-empleado").reset();
            document.getElementById("emp_id").value = "";
            document.getElementById("modalEmpTitulo").textContent = "Registrar Nuevo Colaborador";
            document.getElementById("preview-foto").src = "/assets/img/avatar-placeholder.png";
            document.getElementById("emp_fecha_ingreso").value = new Date().toISOString().split('T')[0];

            const m = document.getElementById("modalRegistrarEmpleado");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 10);
        }

        function obtenerEmpleado(param) {
            if (typeof param === 'object' && param !== null) return param;
            if (tablaPersonal) {
                const data = tablaPersonal.getData();
                return data.find(x => String(x.id) === String(param)) || null;
            }
            return null;
        }

        function abrirModalEditarEmpleado(param) {
            const d = obtenerEmpleado(param);
            if (!d) return;
            document.getElementById("form-registrar-empleado").reset();
            document.getElementById("emp_id").value = d.id;
            document.getElementById("modalEmpTitulo").textContent = "Editar Expediente de Colaborador";
            document.getElementById("emp_primer_nombre").value = d.primer_nombre || '';
            document.getElementById("emp_segundo_nombre").value = d.segundo_nombre || '';
            document.getElementById("emp_primer_apellido").value = d.primer_apellido || '';
            document.getElementById("emp_segundo_apellido").value = d.segundo_apellido || '';
            document.getElementById("emp_tipo_doc").value = d.tipo_documento || 'V';
            document.getElementById("emp_numero_doc").value = d.numero_documento || '';
            document.getElementById("emp_correo").value = d.correo !== 'Sin correo' ? d.correo : '';
            document.getElementById("emp_telefono").value = d.telefono || '';
            document.getElementById("emp_cargo_id").value = d.cargo_id || '';
            document.getElementById("emp_fecha_ingreso").value = d.fecha_ingreso ? d.fecha_ingreso.split(' ')[0] : '';
            document.getElementById("emp_salario").value = d.salario_base_ves || '0.00';

            if (d.foto) {
                document.getElementById("preview-foto").src = d.foto;
            } else {
                document.getElementById("preview-foto").src = "/assets/img/avatar-placeholder.png";
            }

            const m = document.getElementById("modalRegistrarEmpleado");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 10);
        }

        function cerrarModalRegistrarEmpleado() {
            const m = document.getElementById("modalRegistrarEmpleado");
            m.classList.add("opacity-0");
            m.firstElementChild.classList.add("scale-95");
            setTimeout(() => m.classList.add("hidden"), 200);
        }

        let isSubmittingEmpleado = false;

        async function guardarEmpleado(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            if (isSubmittingEmpleado) {
                return false;
            }

            const btn = document.getElementById("btn-guardar-emp");
            const originalText = btn.innerHTML;
            isSubmittingEmpleado = true;
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin mr-1">⌛</span> Guardando...`;

            try {
                const formEl = document.getElementById("form-registrar-empleado");
                const fd = new FormData(formEl);
                const isEditing = document.getElementById("emp_id").value !== "";
                fd.append('action', isEditing ? 'editar_empleado' : 'crear_empleado');

                const res = await fetch('/recursos-humanos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    cerrarModalRegistrarEmpleado();
                    cargarListaPersonal();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: json.message,
                            icon: 'success',
                            timer: 1800,
                            showConfirmButton: false
                        });
                    } else {
                        alert(json.message);
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Atención', json.message || 'Error al guardar.', 'warning');
                    } else {
                        alert(json.message || 'Error al guardar.');
                    }
                }
            } catch (err) {
                console.error("Error al guardar empleado:", err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Error de conexión al guardar el colaborador.', 'error');
                } else {
                    alert("Error de conexión al guardar el colaborador.");
                }
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
                isSubmittingEmpleado = false;
            }
            return false;
        }

        function verFichaEmpleado(param) {
            const d = obtenerEmpleado(param);
            if (!d) return;
            empleadoSeleccionado = d;
            document.getElementById("ficha-nombre").textContent = d.nombre_completo;
            document.getElementById("ficha-cargo").textContent = d.cargo_nombre;
            document.getElementById("ficha-documento").textContent = d.documento_completo || 'V-00000000';
            document.getElementById("ficha-depto").textContent = d.departamento;
            document.getElementById("ficha-contacto").textContent = d.telefono || 'Sin teléfono';
            document.getElementById("ficha-correo").textContent = d.correo || 'Sin correo registrado';
            document.getElementById("ficha-ingreso").textContent = d.fecha_ingreso_fmt || '-';

            const ves = parseFloat(d.salario_base_ves || 0).toLocaleString('es-VE', { minimumFractionDigits: 2 });
            const usd = parseFloat(d.salario_usd || 0).toFixed(2);
            document.getElementById("ficha-salario").textContent = `Bs. ${ves} (~$${usd} USD)`;

            const estEl = document.getElementById("ficha-estatus");
            estEl.textContent = d.is_active == 1 ? 'Activo' : 'Inactivo';
            estEl.className = d.is_active == 1 ? 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700' : 'px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700';

            const fotoEl = document.getElementById("ficha-foto");
            if (d.foto) {
                fotoEl.src = d.foto;
            } else {
                fotoEl.src = "/assets/img/avatar-placeholder.png";
            }

            const m = document.getElementById("modalFichaEmpleado");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 10);
        }

        function editarEmpleadoDesdeFicha() {
            if (empleadoSeleccionado) {
                cerrarModalFichaEmpleado();
                setTimeout(() => abrirModalEditarEmpleado(empleadoSeleccionado), 250);
            }
        }

        function cerrarModalFichaEmpleado() {
            const m = document.getElementById("modalFichaEmpleado");
            m.classList.add("opacity-0");
            m.firstElementChild.classList.add("scale-95");
            setTimeout(() => m.classList.add("hidden"), 200);
        }

        async function toggleEstatusEmpleado(id, estatusActual) {
            const nuevo = estatusActual == 1 ? 0 : 1;
            const accion = nuevo == 1 ? 'activar' : 'desactivar';
            if (!confirm(`¿Está seguro de ${accion} a este colaborador?`)) return;

            try {
                const fd = new FormData();
                fd.append('action', 'cambiar_estatus');
                fd.append('id', id);
                fd.append('estatus', nuevo);

                const res = await fetch('/recursos-humanos-controller', { method: 'POST', body: fd });
                const json = await res.json();
                if (json.status === 'success') {
                    cargarListaPersonal();
                } else {
                    alert(json.message || 'No se pudo cambiar el estatus.');
                }
            } catch (err) {
                console.error("Error al cambiar estatus:", err);
            }
        }
    </script>
</body>
</html>
