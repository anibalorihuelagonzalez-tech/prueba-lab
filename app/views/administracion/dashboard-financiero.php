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
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <i data-lucide="line-chart" class="w-4 h-4"></i>
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Dashboard Financiero</h1>
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
                
                <!-- 1. Total Facturado -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Facturado</p>
                        <h3 id="fin-facturado-usd" class="text-2xl font-black text-slate-800 mt-1">$0.00</h3>
                        <p id="fin-facturado-bs" class="text-[11px] text-slate-500 font-semibold mt-0.5">Bs. 0.00</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                        <i data-lucide="receipt" class="w-6 h-6"></i>
                    </div>
                </div>

                <!-- 2. Ingresos Cobrados -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Ingresos Cobrados</p>
                        <h3 id="fin-cobrado-usd" class="text-2xl font-black text-emerald-600 mt-1">$0.00</h3>
                        <p id="fin-cobrado-bs" class="text-[11px] text-emerald-700 font-semibold mt-0.5">Bs. 0.00 percibidos</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="badge-dollar-sign" class="w-6 h-6"></i>
                    </div>
                </div>

                <!-- 3. Cuentas por Cobrar -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Cuentas por Cobrar</p>
                        <h3 id="fin-por-cobrar-usd" class="text-2xl font-black text-amber-600 mt-1">$0.00</h3>
                        <p id="fin-por-cobrar-bs" class="text-[11px] text-amber-700 font-semibold mt-0.5">Bs. 0.00 pendiente</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="wallet-cards" class="w-6 h-6"></i>
                    </div>
                </div>

                <!-- 4. Tasa BCV Vigente -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Tasa Oficial Vigente</p>
                        <h3 id="fin-tasa-bcv" class="text-2xl font-black text-blue-700 font-mono mt-1">0.00 Bs</h3>
                        <p class="text-[11px] text-blue-600 font-medium mt-0.5">Referencia cambiaria BCV</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="coins" class="w-6 h-6"></i>
                    </div>
                </div>

            </div>

            <!-- Two-Column Layout: Saldos por Clínica & Distribución por Categoría -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Tabla: Estado de Cuentas por Clínica (2 cols) -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Estado Financiero por Clínica Odontológica</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Facturación acumulada, cobros reales y saldos pendientes.</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            Balance Clínicas
                        </span>
                    </div>

                    <div class="p-4 flex-1">
                        <div id="tabla-clinicas-financiero" class="w-full"></div>
                    </div>
                </div>

                <!-- Card: Ingresos por Categoría de Servicio (1 col) -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Ingresos por Especialidad</h3>
                            <i data-lucide="pie-chart" class="w-5 h-5 text-slate-400"></i>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Facturación según el tipo de servicio dental prestado.</p>
                        
                        <div id="contenedor-categorias-servicios" class="mt-4 space-y-3">
                            <!-- Categorías dinámicas -->
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 text-xs text-slate-600">
                        <p class="font-bold text-slate-700">💡 Respaldo Contable:</p>
                        <p class="mt-0.5 text-slate-500">Los saldos se actualizan en tiempo real al emitir documentos y confirmar recepciones de pago.</p>
                    </div>
                </div>

            </div>

            <!-- Tabla: Cuentas por Cobrar Pendientes Recientes -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Cuentas Pendientes por Cobrar</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Documentos emitidos en espera de liquidación por parte de las clínicas.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1.5 w-fit">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                        <span>Liquidaciones Pendientes</span>
                    </span>
                </div>

                <div class="w-full">
                    <div id="tabla-pendientes-cobro" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- Modal Confirmación Pago Rápido -->
    <div id="modalConfirmarPago"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300 p-6 text-center"
            id="modalConfirmarPagoContainer">
            <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-4 border border-emerald-100">
                <i data-lucide="check-circle-2" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">¿Registrar Cobro de Documento?</h3>
            <p id="pagoConfirmTexto" class="text-xs text-slate-500 mt-2 mb-6 leading-relaxed">
                El documento pasará al estatus PAGADO y se descontará de las cuentas por cobrar.
            </p>
            
            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="cerrarModalPago()"
                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-xs">
                    Cancelar
                </button>
                <button type="button" id="btnEjecutarPago"
                    class="px-5 py-2 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition-colors text-xs shadow-md shadow-emerald-500/20">
                    Confirmar Pago
                </button>
            </div>
        </div>
    </div>

    <script>
        let tablaClinicasFin = null;
        let tablaPendientes = null;
        let pagoDocIdPendiente = null;

        document.addEventListener("DOMContentLoaded", () => {
            inicializarTablasFinancieras();
            cargarMetricasFinancieras();
        });

        function inicializarTablasFinancieras() {
            // Tabla 1: Clínicas
            tablaClinicasFin = new Tabulator("#tabla-clinicas-financiero", {
                data: [],
                layout: "fitColumns",
                responsiveLayout: "collapse",
                pagination: "local",
                paginationSize: 6,
                placeholder: "No hay clínicas con movimientos registrados.",
                columns: [
                    {
                        title: "Clínica Odontológica", field: "razon_social", minWidth: 200,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <p class="font-bold text-slate-800 text-xs">${d.razon_social}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">${d.tipo_documento}-${d.numero_documento || 'J-00000000'}</p>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Facturado ($)", field: "total_facturado", width: 120, hozAlign: "right",
                        formatter: (cell) => `<span class="font-bold font-mono text-xs text-slate-800">$${parseFloat(cell.getValue() || 0).toFixed(2)}</span>`
                    },
                    {
                        title: "Cobrado ($)", field: "total_cobrado", width: 120, hozAlign: "right",
                        formatter: (cell) => `<span class="font-bold font-mono text-xs text-emerald-600">$${parseFloat(cell.getValue() || 0).toFixed(2)}</span>`
                    },
                    {
                        title: "Pendiente ($)", field: "saldo_pendiente", width: 130, hozAlign: "right",
                        formatter: (cell) => {
                            const val = parseFloat(cell.getValue() || 0);
                            return val > 0 
                                ? `<span class="font-bold font-mono text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">$${val.toFixed(2)}</span>`
                                : `<span class="font-bold font-mono text-xs text-slate-400">$0.00</span>`;
                        }
                    },
                    {
                        title: "Estado", field: "saldo_pendiente", width: 110, hozAlign: "center",
                        formatter: (cell) => {
                            const val = parseFloat(cell.getValue() || 0);
                            return val > 0
                                ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Pendiente</span>`
                                : `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Solvente</span>`;
                        }
                    }
                ],
                renderComplete: () => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });

            // Tabla 2: Pendientes por Cobrar
            tablaPendientes = new Tabulator("#tabla-pendientes-cobro", {
                data: [],
                layout: "fitColumns",
                responsiveLayout: "collapse",
                pagination: "local",
                paginationSize: 8,
                placeholder: "¡Excelente! No hay documentos pendientes por cobrar en este momento.",
                columns: [
                    {
                        title: "Documento", field: "numero_documento", width: 140, hozAlign: "center",
                        formatter: (cell) => `<span class="font-mono font-bold text-xs bg-slate-100 text-slate-800 px-2.5 py-1 rounded-md border border-slate-200">${cell.getValue()}</span>`
                    },
                    {
                        title: "Tipo", field: "tipo_documento", width: 130, hozAlign: "center",
                        formatter: (cell) => `<span class="text-xs font-semibold uppercase text-slate-600">${cell.getValue().replace('_', ' ')}</span>`
                    },
                    {
                        title: "Clínica Deudora", field: "clinica_nombre", minWidth: 200,
                        formatter: (cell) => `<span class="font-bold text-xs text-slate-800">${cell.getValue()}</span>`
                    },
                    {
                        title: "Fecha Emisión", field: "fecha_emision_fmt", width: 130, hozAlign: "center",
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <p class="text-xs font-medium text-slate-700">${cell.getValue()}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold">${d.dias_antiguedad} día(s) emitido</p>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Importe ($ USD)", field: "monto_usd", width: 140, hozAlign: "right",
                        formatter: (cell) => `<span class="font-mono font-bold text-sm text-slate-900">$${parseFloat(cell.getValue() || 0).toFixed(2)}</span>`
                    },
                    {
                        title: "Equiv. Bolívares", field: "monto_bs", width: 150, hozAlign: "right",
                        formatter: (cell) => `<span class="font-mono font-bold text-xs text-emerald-700">Bs. ${parseFloat(cell.getValue() || 0).toLocaleString('es-VE', {minimumFractionDigits: 2})}</span>`
                    },
                    {
                        title: "Acción", width: 130, hozAlign: "center", headerSort: false,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <button onclick="abrirModalPago(${d.id}, '${d.numero_documento}', '${d.monto_usd}')"
                                    class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 text-xs font-bold transition-colors flex items-center gap-1 mx-auto">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Cobrado</span>
                                </button>
                            `;
                        }
                    }
                ],
                renderComplete: () => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
        }

        async function cargarMetricasFinancieras() {
            try {
                const fd = new FormData();
                fd.append('action', 'obtener_metricas');
                const res = await fetch('/dashboard-financiero-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    const r = json.resumen;

                    // Formatear KPIs principales
                    document.getElementById("fin-facturado-usd").textContent = `$${parseFloat(r.facturado_usd || 0).toFixed(2)}`;
                    document.getElementById("fin-facturado-bs").textContent = `Bs. ${parseFloat(r.facturado_bs || 0).toLocaleString('es-VE', {minimumFractionDigits: 2})}`;

                    document.getElementById("fin-cobrado-usd").textContent = `$${parseFloat(r.cobrado_usd || 0).toFixed(2)}`;
                    document.getElementById("fin-cobrado-bs").textContent = `Bs. ${parseFloat(r.cobrado_bs || 0).toLocaleString('es-VE', {minimumFractionDigits: 2})} percibidos`;

                    document.getElementById("fin-por-cobrar-usd").textContent = `$${parseFloat(r.por_cobrar_usd || 0).toFixed(2)}`;
                    document.getElementById("fin-por-cobrar-bs").textContent = `Bs. ${parseFloat(r.por_cobrar_bs || 0).toLocaleString('es-VE', {minimumFractionDigits: 2})} pendiente`;

                    document.getElementById("fin-tasa-bcv").textContent = `${parseFloat(r.tasa_bcv || 1).toFixed(4)} Bs`;

                    // Tablas
                    if (tablaClinicasFin && json.clinicas) {
                        await tablaClinicasFin.setData(json.clinicas);
                    }
                    if (tablaPendientes && json.cuentas_pendientes) {
                        await tablaPendientes.setData(json.cuentas_pendientes);
                    }

                    // Distribución de categorías
                    const contCat = document.getElementById("contenedor-categorias-servicios");
                    contCat.innerHTML = '';
                    if (json.distribucion_servicios && json.distribucion_servicios.length > 0) {
                        json.distribucion_servicios.forEach(cat => {
                            contCat.innerHTML += `
                                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div>
                                        <p class="font-bold text-xs text-slate-800">${cat.categoria_nombre}</p>
                                        <p class="text-[10px] text-slate-400">${cat.total_casos} caso(s) procesado(s)</p>
                                    </div>
                                    <span class="font-mono font-bold text-xs text-brand-700 bg-brand-50 px-2 py-1 rounded border border-brand-200">
                                        $${parseFloat(cat.subtotal_usd || 0).toFixed(2)}
                                    </span>
                                </div>
                            `;
                        });
                    } else {
                        contCat.innerHTML = '<p class="text-xs text-slate-400 italic">No hay registros de servicios aún.</p>';
                    }

                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            } catch (err) {
                console.error("Error al cargar métricas financieras:", err);
            }
        }

        // =========================================================================
        // CONFIRMAR PAGO RÁPIDO
        // =========================================================================
        function abrirModalPago(id, docNum, monto) {
            pagoDocIdPendiente = id;
            document.getElementById("pagoConfirmTexto").innerHTML = `
                ¿Confirmas que el documento <strong>${docNum}</strong> por un monto de <strong>$${parseFloat(monto).toFixed(2)} USD</strong> ha sido cancelado por la clínica?
            `;

            const btn = document.getElementById("btnEjecutarPago");
            btn.onclick = async () => {
                cerrarModalPago();
                await ejecutarCobroRapido(pagoDocIdPendiente);
            };

            const m = document.getElementById("modalConfirmarPago");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
            }, 10);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function cerrarModalPago() {
            const m = document.getElementById("modalConfirmarPago");
            m.classList.add("opacity-0");
            m.firstElementChild.classList.add("scale-95");
            setTimeout(() => {
                m.classList.add("hidden");
                pagoDocIdPendiente = null;
            }, 200);
        }

        // CORREGIDO: antes marcaba la factura como pagada sin registrar dinero ni asiento contable.
        // Ahora lleva al módulo de Cobranzas, donde se registra el cobro completo (método, tasa, referencia).
        async function ejecutarCobroRapido(id) {
            window.location.href = '/ventas/cobranzas?factura=' + encodeURIComponent(id);
        }
    </script>
</body>
</html>
