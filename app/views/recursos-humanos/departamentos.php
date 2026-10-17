<?php
session_start();
require_once __DIR__ . '/../../helpers/auth.php';

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
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-8 z-10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                    <i data-lucide="building-2" class="w-4 h-4"></i>
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Departamentos del Laboratorio</h1>
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
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Departamentos</p>
                        <h3 id="kpi-total" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Áreas funcionales</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        <i data-lucide="building-2" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Departamentos Activos</p>
                        <h3 id="kpi-activos" class="text-2xl font-black text-emerald-600 mt-1">0</h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">En operación</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Cargos Vinculados</p>
                        <h3 id="kpi-cargos" class="text-2xl font-black text-indigo-600 mt-1">0</h3>
                        <p class="text-[11px] text-indigo-600 font-medium mt-0.5">Puestos asignados</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="briefcase" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Inactivos</p>
                        <h3 id="kpi-inactivos" class="text-2xl font-black text-slate-400 mt-1">0</h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Sin operaciones</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center">
                        <i data-lucide="archive" class="w-6 h-6"></i>
                    </div>
                </div>

            </div>

            <!-- Action Bar Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 md:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Estructura Organizacional</h2>
                    <p class="text-sm text-slate-500 mt-1">Gestiona dinámicamente las áreas y departamentos que conforman el laboratorio.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="abrirModalCrearDepto()"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 text-sm">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        <span>Nuevo Departamento</span>
                    </button>
                </div>
            </div>

            <!-- Tabla de Departamentos -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="buscar-depto" placeholder="Buscar departamento..."
                            class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600 font-medium">
                    </div>
                    <div class="flex items-center gap-2">
                        <select id="filtro-estatus" onchange="filtrarDepartamentos()"
                            class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600 font-medium">
                            <option value="">Todos los estatus</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                </div>

                <!-- Tabulator Table -->
                <div class="w-full">
                    <div id="departamentos-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL REGISTRAR / EDITAR DEPARTAMENTO                                      -->
    <!-- ========================================================================= -->
    <div id="modalDepto"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                        <i data-lucide="building-2" class="w-4 h-4"></i>
                    </div>
                    <h3 id="modalDeptoTitle" class="text-base font-bold text-slate-800">Nuevo Departamento</h3>
                </div>
                <button onclick="cerrarModalDepto()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="formDepto" onsubmit="guardarDepto(event)" class="p-6 space-y-4">
                <input type="hidden" id="depto_id" name="id">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nombre del Departamento *</label>
                    <input type="text" id="depto_nombre" name="nombre" required
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-800 font-medium"
                        placeholder="Ej. Cerámica y Prótesis Fija">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Descripción / Funciones</label>
                    <textarea id="depto_descripcion" name="descripcion" rows="3"
                        class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 resize-none"
                        placeholder="Descripción opcional de las funciones y alcance de este departamento..."></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Estatus</label>
                    <select id="depto_is_active" name="is_active"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalDepto()"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardarDepto"
                        class="px-5 py-2 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-md shadow-brand-500/20 transition-all text-sm flex items-center justify-center min-w-[90px]">
                        <span>Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL CONFIRMACIÓN ESTILIZADO CON BLUR                                    -->
    <!-- ========================================================================= -->
    <div id="modalConfirmacionDepto"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300 p-6 text-center"
            id="modalConfirmacionDeptoContainer">
            <div id="confirmIconContainerDepto" class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4 border border-rose-100">
                <i id="confirmIconDepto" data-lucide="trash-2" class="w-8 h-8"></i>
            </div>
            <h3 id="confirmTituloDepto" class="text-base font-bold text-slate-800">¿Estás seguro?</h3>
            <p id="confirmTextoDepto" class="text-xs text-slate-500 mt-2 mb-6 leading-relaxed">Mensaje de confirmación</p>
            
            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="cerrarModalConfirmacionDepto()"
                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-xs">
                    Cancelar
                </button>
                <button type="button" id="btnEjecutarConfirmacionDepto"
                    class="px-5 py-2 rounded-xl bg-rose-600 text-white font-medium hover:bg-rose-700 transition-colors text-xs shadow-md">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <script>
        let tablaDeptos = null;
        let accionConfirmacionDepto = null;

        document.addEventListener("DOMContentLoaded", () => {
            inicializarTablaDeptos();
            cargarListaDeptos();

            document.getElementById("buscar-depto").addEventListener("input", function(e) {
                const term = e.target.value.toLowerCase().trim();
                if (!tablaDeptos) return;
                tablaDeptos.setFilter((data) => {
                    if (!term) return true;
                    return (
                        (data.nombre && data.nombre.toLowerCase().includes(term)) ||
                        (data.descripcion && data.descripcion.toLowerCase().includes(term))
                    );
                });
            });
        });

        function inicializarTablaDeptos() {
            tablaDeptos = new Tabulator("#departamentos-table", {
                data: [],
                layout: "fitColumns",
                responsiveLayout: "collapse",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50],
                placeholder: "No se encontraron departamentos registrados.",
                columns: [
                    { title: "ID", field: "id", width: 70, hozAlign: "center", headerSort: true },
                    {
                        title: "Departamento", field: "nombre", minWidth: 220,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <p class="font-bold text-slate-800 text-xs">${d.nombre}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">${d.descripcion || 'Sin descripción'}</p>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Cargos", field: "total_cargos", width: 110, hozAlign: "center",
                        formatter: (cell) => `<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">${cell.getValue()} cargos</span>`
                    },
                    {
                        title: "Personal", field: "total_empleados", width: 110, hozAlign: "center",
                        formatter: (cell) => `<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">${cell.getValue()} pers.</span>`
                    },
                    {
                        title: "Estatus", field: "is_active", width: 110, hozAlign: "center",
                        formatter: (cell) => {
                            const activa = String(cell.getValue()) === '1';
                            return activa 
                                ? `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Activo</span>`
                                : `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">Inactivo</span>`;
                        }
                    },
                    {
                        title: "Acciones", width: 140, hozAlign: "center", headerSort: false,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div class="flex items-center justify-center gap-1.5">
                                    <button onclick="editarDepto(${d.id})" title="Editar" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs transition-colors">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="toggleEstatusDepto(${d.id}, ${d.is_active})" title="${d.is_active == 1 ? 'Desactivar' : 'Activar'}" class="p-1.5 rounded-lg ${d.is_active == 1 ? 'bg-amber-50 text-amber-600 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'} text-xs transition-colors">
                                        <i data-lucide="${d.is_active == 1 ? 'power-off' : 'power'}" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="eliminarDepto(${d.id}, '${d.nombre}')" title="Eliminar" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs transition-colors">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                renderComplete: () => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });

            tablaDeptos.on("renderComplete", () => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }

        async function cargarListaDeptos() {
            try {
                const fd = new FormData();
                fd.append('action', 'listar');
                const res = await fetch('/departamentos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    if (tablaDeptos) {
                        await tablaDeptos.setData(json.data);
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }
                    if (json.kpis) {
                        document.getElementById("kpi-total").textContent = json.kpis.total_departamentos || 0;
                        document.getElementById("kpi-activos").textContent = json.kpis.activos || 0;
                        document.getElementById("kpi-cargos").textContent = json.kpis.total_cargos || 0;
                        document.getElementById("kpi-inactivos").textContent = json.kpis.inactivos || 0;
                    }
                }
            } catch (err) {
                console.error("Error al cargar departamentos:", err);
            }
        }

        function filtrarDepartamentos() {
            const val = document.getElementById("filtro-estatus").value;
            if (!tablaDeptos) return;
            if (val === '') {
                tablaDeptos.clearFilter();
            } else {
                tablaDeptos.setFilter("is_active", "=", val);
            }
        }

        // =========================================================================
        // MODAL CREAR / EDITAR
        // =========================================================================
        function abrirModalCrearDepto() {
            document.getElementById("formDepto").reset();
            document.getElementById("depto_id").value = '';
            document.getElementById("depto_is_active").value = '1';
            document.getElementById("modalDeptoTitle").textContent = "Nuevo Departamento";

            const m = document.getElementById("modalDepto");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
            }, 10);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function cerrarModalDepto() {
            const m = document.getElementById("modalDepto");
            m.classList.add("opacity-0");
            m.firstElementChild.classList.add("scale-95");
            setTimeout(() => m.classList.add("hidden"), 200);
        }

        async function editarDepto(id) {
            try {
                const fd = new FormData();
                fd.append('action', 'show');
                fd.append('id', id);

                const res = await fetch('/departamentos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status !== 'success') {
                    Swal.fire('Error', json.message || 'No se pudo cargar el departamento.', 'error');
                    return;
                }

                const d = json.data;
                document.getElementById("depto_id").value = d.id;
                document.getElementById("depto_nombre").value = d.nombre;
                document.getElementById("depto_descripcion").value = d.descripcion || '';
                document.getElementById("depto_is_active").value = d.is_active;

                document.getElementById("modalDeptoTitle").textContent = `Editar Departamento (${d.nombre})`;

                const m = document.getElementById("modalDepto");
                m.classList.remove("hidden");
                setTimeout(() => {
                    m.classList.remove("opacity-0");
                    m.firstElementChild.classList.remove("scale-95");
                }, 10);
                if (typeof lucide !== 'undefined') lucide.createIcons();

            } catch (err) {
                console.error("Error al cargar depto:", err);
            }
        }

        async function guardarDepto(e) {
            e.preventDefault();
            const btn = document.getElementById("btnGuardarDepto");
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin mr-1">⌛</span> Guardando...`;

            try {
                const form = document.getElementById("formDepto");
                const fd = new FormData(form);
                const id = document.getElementById("depto_id").value;
                fd.append('action', id ? 'update' : 'store');

                const res = await fetch('/departamentos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    cerrarModalDepto();
                    Swal.fire({
                        title: '¡Éxito!',
                        text: json.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    cargarListaDeptos();
                } else {
                    Swal.fire('Error', json.message || 'No se pudo guardar el departamento.', 'error');
                }
            } catch (err) {
                console.error("Error al guardar departamento:", err);
                Swal.fire('Error', 'Problema de conexión con el servidor.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Guardar</span>';
            }
        }

        // =========================================================================
        // MODAL DE CONFIRMACIÓN CON BLUR
        // =========================================================================
        function abrirModalConfirmacionDepto({ titulo, texto, icono = 'trash-2', tipo = 'danger', textoBtn = 'Confirmar', onConfirm }) {
            const modal = document.getElementById("modalConfirmacionDepto");
            const container = document.getElementById("modalConfirmacionDeptoContainer");
            const iconCont = document.getElementById("confirmIconContainerDepto");
            const iconEl = document.getElementById("confirmIconDepto");
            const titleEl = document.getElementById("confirmTituloDepto");
            const textEl = document.getElementById("confirmTextoDepto");
            const confirmBtn = document.getElementById("btnEjecutarConfirmacionDepto");

            titleEl.textContent = titulo;
            textEl.textContent = texto;
            confirmBtn.textContent = textoBtn;

            if (tipo === 'danger') {
                iconCont.className = "w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4 border border-rose-100";
                iconEl.setAttribute("data-lucide", icono || "trash-2");
                confirmBtn.className = "px-4 py-2 rounded-xl bg-rose-600 text-white font-medium hover:bg-rose-700 transition-colors text-xs shadow-md";
            } else {
                iconCont.className = "w-16 h-16 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-4 border border-amber-100";
                iconEl.setAttribute("data-lucide", icono || "power");
                confirmBtn.className = "px-4 py-2 rounded-xl bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors text-xs shadow-md";
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();

            accionConfirmacionDepto = onConfirm;

            confirmBtn.onclick = async () => {
                const fn = accionConfirmacionDepto;
                cerrarModalConfirmacionDepto();
                if (typeof fn === 'function') {
                    await fn();
                }
            };

            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                container.classList.remove("scale-95");
            }, 10);
        }

        function cerrarModalConfirmacionDepto() {
            const modal = document.getElementById("modalConfirmacionDepto");
            const container = document.getElementById("modalConfirmacionDeptoContainer");
            if (!modal) return;
            modal.classList.add("opacity-0");
            if (container) container.classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
                accionConfirmacionDepto = null;
            }, 200);
        }

        function toggleEstatusDepto(id, estatusActual) {
            const nuevo = estatusActual == 1 ? 0 : 1;
            const accion = nuevo == 1 ? 'activar' : 'desactivar';

            abrirModalConfirmacionDepto({
                titulo: `¿Deseas ${accion} este departamento?`,
                texto: `El departamento ${nuevo == 1 ? 'volverá a estar disponible' : 'dejará de estar activo'} para la asignación de cargos.`,
                icono: nuevo == 1 ? 'power' : 'power-off',
                tipo: nuevo == 1 ? 'brand' : 'danger',
                textoBtn: `Sí, ${accion}`,
                onConfirm: async () => {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'toggle_status');
                        fd.append('id', id);
                        fd.append('is_active', nuevo);

                        const res = await fetch('/departamentos-controller', { method: 'POST', body: fd });
                        const json = await res.json();

                        if (json.status === 'success') {
                            Swal.fire({ title: 'Actualizado', text: json.message, icon: 'success', timer: 1400, showConfirmButton: false });
                            cargarListaDeptos();
                        } else {
                            Swal.fire('Error', json.message, 'error');
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
        }

        function eliminarDepto(id, nombre) {
            abrirModalConfirmacionDepto({
                titulo: `¿Eliminar departamento ${nombre}?`,
                texto: 'El departamento será removido permanentemente. Solo es posible si no tiene cargos asignados.',
                icono: 'trash-2',
                tipo: 'danger',
                textoBtn: 'Sí, eliminar',
                onConfirm: async () => {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'delete');
                        fd.append('id', id);

                        const res = await fetch('/departamentos-controller', { method: 'POST', body: fd });
                        const json = await res.json();

                        if (json.status === 'success') {
                            Swal.fire({ title: 'Eliminado', text: json.message, icon: 'success', timer: 1500, showConfirmButton: false });
                            cargarListaDeptos();
                        } else {
                            Swal.fire('Error', json.message, 'error');
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
        }
    </script>
</body>
</html>
