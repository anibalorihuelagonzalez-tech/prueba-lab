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
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Módulo de Recepción</h1>
                </div>
            </div>
            <div class="flex items-center space-x-3 md:space-x-4 hidden sm:flex">
                <span class="text-sm font-medium text-slate-600">Hola,
                    <strong><?= htmlspecialchars(Auth::name() ?? 'Recepcionista') ?></strong></span>
                <div class="h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                    <?= htmlspecialchars(substr(Auth::name() ?? 'R', 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Main View Scrollable Area -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden bg-slate-50/50 p-4 md:p-8 space-y-6">

            <!-- KPIs Header Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total en Logística</p>
                        <h3 id="kpi-total" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Casos en seguimiento</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                        <i data-lucide="boxes" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Recibidos en Laboratorio</p>
                        <h3 id="kpi-recibidos-lab" class="text-2xl font-black text-brand-600 mt-1">0</h3>
                        <p class="text-[11px] text-brand-600 font-medium mt-0.5">Entradas procesadas</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                        <i data-lucide="building" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Recibidos en Clínica</p>
                        <h3 id="kpi-recibidos-clinica" class="text-2xl font-black text-emerald-600 mt-1">0</h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Entregados a destino</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pendientes de Recepción</p>
                        <h3 id="kpi-pendientes" class="text-2xl font-black text-amber-600 mt-1">0</h3>
                        <p class="text-[11px] text-amber-600 font-medium mt-0.5">En tránsito / Despacho</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                </div>

            </div>

            <!-- Quick Scanner Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="qr-code" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Escanear o Registrar Recepción Inmediata</h3>
                        <p class="text-xs text-slate-500">Ingresa el código del caso (ej. ORD-0001) para confirmar su llegada.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative flex-1 md:w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="scan-line" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="input-escaner-qr" placeholder="Escanear código QR..."
                            class="w-full pl-9 pr-3 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 font-mono font-bold uppercase text-slate-800"
                            onkeydown="if(event.key === 'Enter') buscarYRecepcionarDirecto()">
                    </div>
                    <button onclick="buscarYRecepcionarDirecto()"
                        class="px-4 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 transition-colors text-xs flex items-center gap-1.5 shadow-md shadow-brand-500/20 whitespace-nowrap">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>Buscar</span>
                    </button>
                </div>
            </div>

            <!-- Tabla de Recepción -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <!-- Tabs -->
                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                        <button onclick="cambiarTabRecepcion('')" id="tab-todos"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-800 shadow-sm">
                            Todos
                        </button>
                        <button onclick="cambiarTabRecepcion('LABORATORIO')" id="tab-lab"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-500 hover:text-slate-800">
                            Recepción Lab
                        </button>
                        <button onclick="cambiarTabRecepcion('CLINICA')" id="tab-clinica"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-500 hover:text-slate-800">
                            Recepción Clínicas
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="buscar-recepcion" placeholder="Buscar por código, clínica, paciente..."
                            class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                    </div>
                </div>

                <!-- Tabulator Table -->
                <div class="w-full">
                    <div id="recepcion-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL CONFIRMAR RECEPCIÓN                                                 -->
    <!-- ========================================================================= -->
    <div id="modalConfirmarRecepcion"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                        <i data-lucide="package-check" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Confirmar Recepción de Caso</h3>
                </div>
                <button onclick="cerrarModalRecepcion()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="formRecepcion" onsubmit="guardarRecepcion(event)" class="p-6 space-y-4">
                <input type="hidden" id="rec_caso_id" name="caso_id">

                <!-- Info Card del Caso -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <span id="rec_codigo_qr" class="font-mono font-bold text-sm text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">ORD-0001</span>
                        <span id="rec_estatus_actual" class="text-[11px] font-semibold text-slate-500">Pendiente</span>
                    </div>
                    <div>
                        <h4 id="rec_clinica_nombre" class="font-bold text-slate-900 text-xs">Clínica Odontológica</h4>
                        <p id="rec_servicio_paciente" class="text-slate-500 text-[11px] mt-0.5">Paciente: Juan Perez • Prótesis Acrílica</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Destino de Recepción *</label>
                    <select id="rec_tipo_recepcion" name="tipo_recepcion" required
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                        <option value="LABORATORIO">Recepción en Laboratorio (Llegada de Clínica hacia CNSB)</option>
                        <option value="CLINICA">Recepción en Clínica (Llegada de CNSB hacia la Clínica)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nombre de la Persona que Recibe *</label>
                    <input type="text" id="rec_nombre_receptor" name="nombre_receptor" required
                        value="<?= htmlspecialchars(Auth::name() ?? '') ?>"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-800 font-medium"
                        placeholder="Nombre completo del recepcionista...">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Observaciones / Estado del Paquete</label>
                    <textarea id="rec_observaciones" name="observaciones" rows="2"
                        class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 resize-none"
                        placeholder="Ej. Caja recibida con sello intacto, modelos completos..."></textarea>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalRecepcion()"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardarRecepcion"
                        class="px-5 py-2 rounded-xl bg-teal-600 text-white font-medium hover:bg-teal-700 shadow-md shadow-teal-500/20 transition-all text-sm flex items-center justify-center min-w-[120px]">
                        <span>Confirmar Llegada</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let tablaRecepcion = null;
        let tabFiltroActual = '';

        document.addEventListener("DOMContentLoaded", () => {
            inicializarTablaRecepcion();
            cargarListaRecepcion();

            document.getElementById("buscar-recepcion").addEventListener("input", function(e) {
                const term = e.target.value.toLowerCase().trim();
                if (!tablaRecepcion) return;
                tablaRecepcion.setFilter((data) => {
                    if (!term) return true;
                    return (
                        (data.codigo_qr && data.codigo_qr.toLowerCase().includes(term)) ||
                        (data.clinica_nombre && data.clinica_nombre.toLowerCase().includes(term)) ||
                        (data.paciente_nombre && data.paciente_nombre.toLowerCase().includes(term)) ||
                        (data.servicio_nombre && data.servicio_nombre.toLowerCase().includes(term)) ||
                        (data.estatus_actual && data.estatus_actual.toLowerCase().includes(term))
                    );
                });
            });
        });

        function inicializarTablaRecepcion() {
            tablaRecepcion = new Tabulator("#recepcion-table", {
                data: [],
                layout: "fitColumns",
                responsiveLayout: "collapse",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50],
                placeholder: "No hay casos en este filtro de recepción.",
                columns: [
                    {
                        title: "Código / QR", field: "codigo_qr", width: 120, hozAlign: "center",
                        formatter: (cell) => `<span class="font-mono font-bold text-xs bg-slate-100 text-slate-800 px-2 py-1 rounded-md border border-slate-200">${cell.getValue()}</span>`
                    },
                    {
                        title: "Clínica y Sede", field: "clinica_nombre", minWidth: 180,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <p class="font-bold text-slate-800 text-xs">${d.clinica_nombre}</p>
                                    <p class="text-[11px] text-slate-400">${d.sede_nombre}</p>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Paciente & Trabajo Dental", field: "servicio_nombre", minWidth: 200,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <p class="font-semibold text-slate-800 text-xs">${d.servicio_nombre}</p>
                                    <p class="text-[11px] text-slate-500">Paciente: <strong>${d.paciente_nombre || 'No asignado'}</strong> (${d.cantidad_piezas_exacta || 1} pzs)</p>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Estatus Actual", field: "estatus_actual", minWidth: 170, hozAlign: "center",
                        formatter: (cell) => {
                            const est = cell.getValue() || 'PENDIENTE';
                            let badgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
                            if (est.includes('Laboratorio')) {
                                badgeClass = 'bg-brand-50 text-brand-700 border-brand-200';
                            } else if (est.includes('Clínica') || est.includes('Entregado')) {
                                badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            } else if (est.includes('Despacho') || est.includes('Tránsito')) {
                                badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                            }
                            return `<span class="px-2.5 py-1 rounded-full text-[11px] font-bold border ${badgeClass}">${est}</span>`;
                        }
                    },
                    {
                        title: "Fecha Ingreso", field: "fecha_ingreso_fmt", width: 130, hozAlign: "center",
                        formatter: (cell) => `<span class="text-xs text-slate-600">${cell.getValue() || '—'}</span>`
                    },
                    {
                        title: "Acciones de Recepción", minWidth: 200, hozAlign: "center", headerSort: false,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div class="flex items-center justify-center gap-1.5">
                                    <button onclick="abrirModalRecepcion(${d.id}, 'LABORATORIO')" title="Recibir en Lab" 
                                        class="px-2.5 py-1 rounded-lg bg-brand-50 text-brand-700 hover:bg-brand-100 border border-brand-200 text-xs font-semibold flex items-center gap-1 transition-colors">
                                        <i data-lucide="building" class="w-3.5 h-3.5"></i>
                                        <span>Recibir Lab</span>
                                    </button>
                                    <button onclick="abrirModalRecepcion(${d.id}, 'CLINICA')" title="Recibir en Clínica" 
                                        class="px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200 text-xs font-semibold flex items-center gap-1 transition-colors">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        <span>Recibir Clínica</span>
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

            tablaRecepcion.on("renderComplete", () => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }

        async function cargarListaRecepcion() {
            try {
                const fd = new FormData();
                fd.append('action', 'listar_recepciones');
                if (tabFiltroActual) fd.append('tipo_recepcion', tabFiltroActual);

                const res = await fetch('/recepcion-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    if (tablaRecepcion) {
                        await tablaRecepcion.setData(json.data);
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }
                    if (json.kpis) {
                        document.getElementById("kpi-total").textContent = json.kpis.total_casos || 0;
                        document.getElementById("kpi-recibidos-lab").textContent = json.kpis.recibidos_lab || 0;
                        document.getElementById("kpi-recibidos-clinica").textContent = json.kpis.recibidos_clinica || 0;
                        document.getElementById("kpi-pendientes").textContent = json.kpis.pendientes || 0;
                    }
                }
            } catch (err) {
                console.error("Error al cargar lista recepción:", err);
            }
        }

        function cambiarTabRecepcion(tab) {
            tabFiltroActual = tab;
            const btnTodos = document.getElementById("tab-todos");
            const btnLab = document.getElementById("tab-lab");
            const btnClinica = document.getElementById("tab-clinica");

            [btnTodos, btnLab, btnClinica].forEach(b => {
                b.className = "px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-500 hover:text-slate-800";
            });

            if (tab === 'LABORATORIO') {
                btnLab.className = "px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-800 shadow-sm";
            } else if (tab === 'CLINICA') {
                btnClinica.className = "px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-800 shadow-sm";
            } else {
                btnTodos.className = "px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-slate-800 shadow-sm";
            }

            cargarListaRecepcion();
        }

        async function buscarYRecepcionarDirecto() {
            const input = document.getElementById("input-escaner-qr");
            const codigo = input.value.trim();
            if (!codigo) {
                Swal.fire('Atención', 'Por favor ingresa o escanea un código QR.', 'warning');
                return;
            }

            try {
                const fd = new FormData();
                fd.append('action', 'buscar_por_qr');
                fd.append('codigo_qr', codigo);

                const res = await fetch('/recepcion-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    input.value = '';
                    abrirModalRecepcion(json.data.id, 'LABORATORIO', json.data);
                } else {
                    Swal.fire('No encontrado', json.message || 'Código no registrado.', 'error');
                }
            } catch (err) {
                console.error("Error en búsqueda directa:", err);
            }
        }

        function abrirModalRecepcion(casoId, tipoPref = 'LABORATORIO', casoData = null) {
            let data = casoData;
            if (!data && tablaRecepcion) {
                data = tablaRecepcion.getData().find(x => String(x.id) === String(casoId));
            }

            if (!data) return;

            document.getElementById("rec_caso_id").value = data.id;
            document.getElementById("rec_codigo_qr").textContent = data.codigo_qr;
            document.getElementById("rec_estatus_actual").textContent = data.estatus_actual || 'Pendiente';
            document.getElementById("rec_clinica_nombre").textContent = `${data.clinica_nombre} (${data.sede_nombre || 'Principal'})`;
            document.getElementById("rec_servicio_paciente").textContent = `Paciente: ${data.paciente_nombre || 'No asignado'} • ${data.servicio_nombre}`;
            document.getElementById("rec_tipo_recepcion").value = tipoPref;
            document.getElementById("rec_observaciones").value = '';

            const m = document.getElementById("modalConfirmarRecepcion");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
            }, 10);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function cerrarModalRecepcion() {
            const m = document.getElementById("modalConfirmarRecepcion");
            m.classList.add("opacity-0");
            m.firstElementChild.classList.add("scale-95");
            setTimeout(() => m.classList.add("hidden"), 200);
        }

        async function guardarRecepcion(e) {
            e.preventDefault();
            const btn = document.getElementById("btnGuardarRecepcion");
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin mr-1">⌛</span> Procesando...`;

            try {
                const fd = new FormData(document.getElementById("formRecepcion"));
                fd.append('action', 'registrar_recepcion');

                const res = await fetch('/recepcion-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    cerrarModalRecepcion();
                    Swal.fire({
                        title: '¡Recepción Confirmada!',
                        text: json.message,
                        icon: 'success',
                        timer: 1600,
                        showConfirmButton: false
                    });
                    cargarListaRecepcion();
                } else {
                    Swal.fire('Error', json.message || 'No se pudo confirmar la recepción.', 'error');
                }
            } catch (err) {
                console.error("Error al registrar recepción:", err);
                Swal.fire('Error', 'Problema de conexión con el servidor.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Confirmar Llegada</span>';
            }
        }
    </script>
</body>
</html>
