<?php
session_start();
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
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                    <i data-lucide="coins" class="w-4 h-4"></i>
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Gestión de Monedas</h1>
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
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Monedas</p>
                        <h3 id="kpi-total-monedas" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Catálogo registrado</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="wallet" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Moneda Base</p>
                        <h3 id="kpi-moneda-base" class="text-xl font-black text-amber-600 mt-1">USD ($)</h3>
                        <p class="text-[11px] text-amber-700 font-medium mt-0.5 flex items-center gap-1">
                            <i data-lucide="star" class="w-3 h-3 fill-amber-500 text-amber-500"></i>
                            Referencia del sistema
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="badge-dollar-sign" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Monedas Activas</p>
                        <h3 id="kpi-monedas-activas" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Disponibles para conversión</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Monedas Secundarias</p>
                        <h3 id="kpi-monedas-secundarias" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-blue-600 font-medium mt-0.5">Pares cambiarios</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="arrow-left-right" class="w-6 h-6"></i>
                    </div>
                </div>

            </div>

            <!-- Action Bar & Header Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 md:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Directorio de Monedas y Divisas</h2>
                    <p class="text-sm text-slate-500 mt-1">Administra las divisas disponibles para tasas de cambio, presupuestos y facturación del laboratorio.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="abrirModalCrearMoneda()"
                        class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 text-sm w-full md:w-auto">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        <span>Agregar Moneda</span>
                    </button>
                </div>
            </div>

            <!-- Table Card Container -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="buscar-moneda" placeholder="Buscar por código, nombre o símbolo..."
                            class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                    </div>
                    <div class="flex items-center gap-2">
                        <select id="filtro-estatus" onchange="filtrarTablaMonedas()"
                            class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                            <option value="">Todos los estatus</option>
                            <option value="1">Activas</option>
                            <option value="0">Inactivas</option>
                        </select>
                    </div>
                </div>

                <!-- Tabulator Table -->
                <div class="w-full">
                    <div id="monedas-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL AGREGAR / EDITAR MONEDA                                             -->
    <!-- ========================================================================= -->
    <div id="monedaModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto"
            id="modalMonedaContainer">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 sticky top-0 z-10">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="coins" class="w-4 h-4"></i>
                    </div>
                    <h3 id="modalMonedaTitle" class="text-lg font-bold text-slate-800">Agregar Nueva Moneda</h3>
                </div>
                <button onclick="cerrarModalMoneda()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form id="monedaForm" onsubmit="guardarMoneda(event)" class="p-5 space-y-4 relative">
                <input type="hidden" id="moneda_id" name="id">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Código ISO *</label>
                        <input type="text" id="moneda_codigo" name="codigo" required maxlength="3"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 font-mono font-bold uppercase text-slate-800"
                            placeholder="USD">
                        <span class="text-[10px] text-slate-400">3 letras (ej. USD, VES, EUR)</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Símbolo *</label>
                        <input type="text" id="moneda_simbolo" name="simbolo" required maxlength="5"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 font-bold text-slate-800"
                            placeholder="$ o Bs.">
                        <span class="text-[10px] text-slate-400">Ej. $, Bs., €, Col$</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nombre Completo de la Moneda *</label>
                    <input type="text" id="moneda_nombre" name="nombre" required
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-slate-700 font-medium"
                        placeholder="Ej. Dólar Estadounidense">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Decimales</label>
                        <select id="moneda_decimales" name="decimales" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="0">0 (Sin decimales)</option>
                            <option value="2" selected>2 (Estándar 0.00)</option>
                            <option value="3">3 (0.000)</option>
                            <option value="4">4 (Precisión 0.0000)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Estatus</label>
                        <select id="moneda_is_active" name="is_active" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="1">Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/80">
                    <label class="flex items-center space-x-2.5 cursor-pointer">
                        <input type="checkbox" id="moneda_es_base" name="es_base" value="1"
                            class="w-4 h-4 text-amber-600 rounded border-amber-300 focus:ring-amber-500">
                        <span class="text-xs font-bold text-amber-900">Establecer como Moneda Base del Sistema</span>
                    </label>
                    <p class="text-[11px] text-amber-700 mt-1 leading-relaxed pl-6">
                        La moneda base se utiliza como unidad de referencia principal para precios y conversiones cambiarias. Solo una moneda puede ser la base.
                    </p>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalMoneda()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardarMoneda"
                        class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm flex items-center justify-center min-w-[100px]">
                        <span>Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL CONFIRMACIÓN DE ACCIÓN (ESTILO UNIFICADO CON BLUR)                  -->
    <!-- ========================================================================= -->
    <div id="modalConfirmacionAccion"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300 p-6 text-center"
            id="modalConfirmacionContainer">
            <div id="confirmIconContainer" class="w-16 h-16 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-4">
                <i id="confirmIconEl" data-lucide="help-circle" class="w-8 h-8"></i>
            </div>
            <h3 id="confirmModalTitulo" class="text-lg font-bold text-slate-800 tracking-tight">¿Estás seguro?</h3>
            <p id="confirmModalTexto" class="text-sm text-slate-500 mt-2 mb-6 leading-relaxed">Mensaje de confirmación</p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <button type="button" onclick="cerrarModalConfirmacionAccion()"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                    Cancelar
                </button>
                <button type="button" id="btnEjecutarConfirmacion"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 transition-colors text-sm shadow-md">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let tablaMonedas = null;

        document.addEventListener("DOMContentLoaded", () => {
            inicializarTablaMonedas();
            cargarListaMonedas();

            document.getElementById("buscar-moneda").addEventListener("input", function(e) {
                const term = e.target.value.toLowerCase().trim();
                if (!tablaMonedas) return;
                tablaMonedas.setFilter((data) => {
                    if (!term) return true;
                    return (
                        (data.codigo && data.codigo.toLowerCase().includes(term)) ||
                        (data.nombre && data.nombre.toLowerCase().includes(term)) ||
                        (data.simbolo && data.simbolo.toLowerCase().includes(term))
                    );
                });
            });

            // Forzar mayúsculas en el campo código
            document.getElementById("moneda_codigo").addEventListener("input", function() {
                this.value = this.value.toUpperCase();
            });
        });

        function inicializarTablaMonedas() {
            tablaMonedas = new Tabulator("#monedas-table", {
                data: [],
                layout: "fitColumns",
                responsiveLayout: "collapse",
                pagination: "local",
                paginationSize: 10,
                placeholder: "No hay monedas registradas en el sistema.",
                columns: [
                    {
                        title: "Código", field: "codigo", minWidth: 100, hozAlign: "center",
                        formatter: (cell) => {
                            return `<span class="font-mono font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-md text-xs border border-slate-200">${cell.getValue()}</span>`;
                        }
                    },
                    {
                        title: "Nombre de la Moneda", field: "nombre", minWidth: 200,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <p class="font-bold text-slate-800 text-xs">${d.nombre}</p>
                                    <p class="text-[10px] text-slate-400">Símbolo: <strong class="text-slate-600">${d.simbolo}</strong> • Decimales: ${d.decimales}</p>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Símbolo", field: "simbolo", minWidth: 90, hozAlign: "center",
                        formatter: (cell) => `<span class="font-bold text-sm text-slate-700">${cell.getValue()}</span>`
                    },
                    {
                        title: "Rol / Tipo", field: "es_base", minWidth: 140, hozAlign: "center",
                        formatter: (cell) => {
                            const esBase = String(cell.getValue()) === '1';
                            if (esBase) {
                                return `
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <i data-lucide="star" class="w-3 h-3 fill-amber-500 text-amber-500"></i>
                                        <span>Moneda Base</span>
                                    </span>
                                `;
                            }
                            return `<span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600">Secundaria</span>`;
                        }
                    },
                    {
                        title: "Estatus", field: "is_active", minWidth: 100, hozAlign: "center",
                        formatter: (cell) => {
                            const activa = String(cell.getValue()) === '1';
                            return activa 
                                ? `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Activa</span>`
                                : `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">Inactiva</span>`;
                        }
                    },
                    {
                        title: "Acciones", minWidth: 160, hozAlign: "center", headerSort: false,
                        formatter: (cell) => {
                            const d = cell.getData();
                            const esBase = String(d.es_base) === '1';
                            return `
                                <div class="flex items-center justify-center gap-1.5">
                                    <button onclick="editarMoneda(${d.id})" title="Editar" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs transition-colors">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    ${!esBase ? `
                                        <button onclick="establecerComoBase(${d.id}, '${d.codigo}')" title="Hacer Moneda Base" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 text-xs transition-colors">
                                            <i data-lucide="star" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="toggleEstatusMoneda(${d.id}, ${d.is_active})" title="${d.is_active == 1 ? 'Desactivar' : 'Activar'}" class="p-1.5 rounded-lg ${d.is_active == 1 ? 'bg-rose-50 text-rose-600 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'} text-xs transition-colors">
                                            <i data-lucide="${d.is_active == 1 ? 'power-off' : 'power'}" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="eliminarMoneda(${d.id}, '${d.codigo}')" title="Eliminar" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    ` : `
                                        <span class="text-[10px] text-slate-400 font-bold italic px-2">Base Principal</span>
                                    `}
                                </div>
                            `;
                        }
                    }
                ],
                renderComplete: () => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });

            tablaMonedas.on("renderComplete", () => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
            tablaMonedas.on("tableBuilt", () => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }

        async function cargarListaMonedas() {
            try {
                const fd = new FormData();
                fd.append('action', 'listar');
                const res = await fetch('/monedas-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    if (tablaMonedas) {
                        await tablaMonedas.setData(json.data);
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                            setTimeout(() => lucide.createIcons(), 60);
                        }
                    }
                    if (json.kpis) {
                        document.getElementById("kpi-total-monedas").textContent = json.kpis.total_monedas || 0;
                        document.getElementById("kpi-moneda-base").textContent = json.kpis.moneda_base || 'No definida';
                        document.getElementById("kpi-monedas-activas").textContent = json.kpis.monedas_activas || 0;
                        document.getElementById("kpi-monedas-secundarias").textContent = json.kpis.monedas_secundarias || 0;
                    }
                }
            } catch (err) {
                console.error("Error al cargar monedas:", err);
            }
        }

        function filtrarTablaMonedas() {
            const val = document.getElementById("filtro-estatus").value;
            if (!tablaMonedas) return;
            if (val === '') {
                tablaMonedas.clearFilter();
            } else {
                tablaMonedas.setFilter("is_active", "=", val);
            }
        }

        // =========================================================================
        // MODAL CREAR / EDITAR
        // =========================================================================
        function abrirModalCrearMoneda() {
            document.getElementById("monedaForm").reset();
            document.getElementById("moneda_id").value = '';
            document.getElementById("moneda_es_base").checked = false;
            document.getElementById("moneda_is_active").value = '1';
            document.getElementById("moneda_decimales").value = '2';
            document.getElementById("modalMonedaTitle").textContent = "Agregar Nueva Moneda";

            const m = document.getElementById("monedaModal");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
            }, 10);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function cerrarModalMoneda() {
            const m = document.getElementById("monedaModal");
            m.classList.add("opacity-0");
            m.firstElementChild.classList.add("scale-95");
            setTimeout(() => m.classList.add("hidden"), 200);
        }

        async function editarMoneda(id) {
            try {
                const fd = new FormData();
                fd.append('action', 'show');
                fd.append('id', id);

                const res = await fetch('/monedas-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status !== 'success') {
                    Swal.fire('Error', json.message || 'No se pudo obtener la moneda.', 'error');
                    return;
                }

                const m = json.moneda;
                document.getElementById("moneda_id").value = m.id;
                document.getElementById("moneda_codigo").value = m.codigo;
                document.getElementById("moneda_nombre").value = m.nombre;
                document.getElementById("moneda_simbolo").value = m.simbolo;
                document.getElementById("moneda_decimales").value = m.decimales;
                document.getElementById("moneda_is_active").value = m.is_active;
                document.getElementById("moneda_es_base").checked = String(m.es_base) === '1';

                document.getElementById("modalMonedaTitle").textContent = `Editar Moneda (${m.codigo})`;

                const modal = document.getElementById("monedaModal");
                modal.classList.remove("hidden");
                setTimeout(() => {
                    modal.classList.remove("opacity-0");
                    modal.firstElementChild.classList.remove("scale-95");
                }, 10);
                if (typeof lucide !== 'undefined') lucide.createIcons();

            } catch (err) {
                console.error("Error al cargar moneda:", err);
            }
        }

        async function guardarMoneda(e) {
            e.preventDefault();
            const btn = document.getElementById("btnGuardarMoneda");
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin mr-1">⌛</span> Guardando...`;

            try {
                const form = document.getElementById("monedaForm");
                const fd = new FormData(form);
                const id = document.getElementById("moneda_id").value;
                fd.append('action', id ? 'update' : 'store');

                if (!document.getElementById("moneda_es_base").checked) {
                    fd.set('es_base', '0');
                }

                const res = await fetch('/monedas-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    cerrarModalMoneda();
                    Swal.fire({
                        title: '¡Éxito!',
                        text: json.message,
                        icon: 'success',
                        timer: 1600,
                        showConfirmButton: false
                    });
                    cargarListaMonedas();
                } else {
                    Swal.fire('Error', json.message || 'No se pudo guardar la moneda.', 'error');
                }
            } catch (err) {
                console.error("Error al guardar moneda:", err);
                Swal.fire('Error', 'Problema de conexión con el servidor.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Guardar</span>';
            }
        }

        let accionConfirmacionPendiente = null;

        function abrirModalConfirmacion({ titulo, texto, icono = 'help-circle', tipo = 'brand', textoBtn = 'Confirmar', onConfirm }) {
            const modal = document.getElementById("modalConfirmacionAccion");
            const container = document.getElementById("modalConfirmacionContainer");
            const iconCont = document.getElementById("confirmIconContainer");
            const iconEl = document.getElementById("confirmIconEl");
            const titleEl = document.getElementById("confirmModalTitulo");
            const textEl = document.getElementById("confirmModalTexto");
            const confirmBtn = document.getElementById("btnEjecutarConfirmacion");

            titleEl.textContent = titulo;
            textEl.textContent = texto;
            confirmBtn.textContent = textoBtn;

            if (tipo === 'danger') {
                iconCont.className = "w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4 border border-rose-100";
                iconEl.setAttribute("data-lucide", icono || "trash-2");
                confirmBtn.className = "w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 text-white font-medium hover:bg-rose-700 transition-colors text-sm shadow-md shadow-rose-600/20";
            } else if (tipo === 'warning' || tipo === 'star') {
                iconCont.className = "w-16 h-16 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-4 border border-amber-100";
                iconEl.setAttribute("data-lucide", icono || "star");
                confirmBtn.className = "w-full sm:w-auto px-5 py-2.5 rounded-xl bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors text-sm shadow-md shadow-amber-600/20";
            } else {
                iconCont.className = "w-16 h-16 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center mx-auto mb-4 border border-brand-100";
                iconEl.setAttribute("data-lucide", icono || "info");
                confirmBtn.className = "w-full sm:w-auto px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 transition-colors text-sm shadow-md shadow-brand-600/20";
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();

            accionConfirmacionPendiente = onConfirm;

            confirmBtn.onclick = async () => {
                const fn = accionConfirmacionPendiente;
                cerrarModalConfirmacionAccion();
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

        function cerrarModalConfirmacionAccion() {
            const modal = document.getElementById("modalConfirmacionAccion");
            const container = document.getElementById("modalConfirmacionContainer");
            if (!modal) return;
            modal.classList.add("opacity-0");
            if (container) container.classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
                accionConfirmacionPendiente = null;
            }, 200);
        }

        function establecerComoBase(id, codigo) {
            abrirModalConfirmacion({
                titulo: `¿Establecer ${codigo} como moneda base?`,
                texto: 'Esta moneda pasará a ser la referencia principal para todas las tasas de cambio del sistema.',
                icono: 'star',
                tipo: 'warning',
                textoBtn: 'Sí, establecer como base',
                onConfirm: async () => {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'set_base');
                        fd.append('id', id);

                        const res = await fetch('/monedas-controller', { method: 'POST', body: fd });
                        const json = await res.json();

                        if (json.status === 'success') {
                            Swal.fire({ title: 'Actualizado', text: json.message, icon: 'success', timer: 1500, showConfirmButton: false });
                            cargarListaMonedas();
                        } else {
                            Swal.fire('Error', json.message, 'error');
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
        }

        function toggleEstatusMoneda(id, estatusActual) {
            const nuevo = estatusActual == 1 ? 0 : 1;
            const accion = nuevo == 1 ? 'activar' : 'desactivar';

            abrirModalConfirmacion({
                titulo: `¿Deseas ${accion} esta moneda?`,
                texto: `La moneda cambiará de estado y ${nuevo == 1 ? 'estará disponible' : 'no estará disponible'} para nuevas operaciones.`,
                icono: nuevo == 1 ? 'power' : 'power-off',
                tipo: nuevo == 1 ? 'brand' : 'danger',
                textoBtn: `Sí, ${accion}`,
                onConfirm: async () => {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'toggle_status');
                        fd.append('id', id);
                        fd.append('is_active', nuevo);

                        const res = await fetch('/monedas-controller', { method: 'POST', body: fd });
                        const json = await res.json();

                        if (json.status === 'success') {
                            Swal.fire({ title: 'Actualizado', text: json.message, icon: 'success', timer: 1400, showConfirmButton: false });
                            cargarListaMonedas();
                        } else {
                            Swal.fire('Error', json.message, 'error');
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
        }

        function eliminarMoneda(id, codigo) {
            abrirModalConfirmacion({
                titulo: `¿Eliminar moneda ${codigo}?`,
                texto: 'La moneda será removida del catálogo. Esta acción no se puede deshacer.',
                icono: 'trash-2',
                tipo: 'danger',
                textoBtn: 'Sí, eliminar',
                onConfirm: async () => {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'delete');
                        fd.append('id', id);

                        const res = await fetch('/monedas-controller', { method: 'POST', body: fd });
                        const json = await res.json();

                        if (json.status === 'success') {
                            Swal.fire({ title: 'Eliminada', text: json.message, icon: 'success', timer: 1500, showConfirmButton: false });
                            cargarListaMonedas();
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
