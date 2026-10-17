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
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Administración de Documentos</h1>
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
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Documentos Emitidos</p>
                        <h3 id="kpi-total-docs" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Historial administrativo</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="files" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Facturación ($)</p>
                        <h3 id="kpi-usd" class="text-2xl font-black text-slate-800 mt-1">$0.00</h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">En divisas USD</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="badge-dollar-sign" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Equivalente en Bolívares</p>
                        <h3 id="kpi-bs" class="text-2xl font-black text-slate-800 mt-1">Bs. 0.00</h3>
                        <p class="text-[11px] text-blue-600 font-medium mt-0.5">A tasa oficial vigente</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="coins" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Certificados Garantía</p>
                        <h3 id="kpi-garantias" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-amber-600 font-medium mt-0.5">Calidad y respaldo</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                </div>

            </div>

            <!-- Action Bar Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 md:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Emisión y Control de Documentos</h2>
                    <p class="text-sm text-slate-500 mt-1">Genera facturas, órdenes de laboratorio, cotizaciones, garantías y actas de entrega para clínicas odontológicas.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="abrirModalGenerarDoc()"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 text-sm">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        <span>Generar Nuevo Documento</span>
                    </button>
                </div>
            </div>

            <!-- Tabla General de Documentos -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="buscar-documento" placeholder="Buscar por número, clínica, caso..."
                            class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                    </div>
                    <div class="flex items-center gap-2">
                        <select id="filtro-tipo-doc" onchange="filtrarDocumentos()"
                            class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                            <option value="">Todos los tipos</option>
                            <option value="FACTURA">Facturas / Notas</option>
                            <option value="ORDEN_TRABAJO">Órdenes de Trabajo</option>
                            <option value="PRESUPUESTO">Presupuestos</option>
                            <option value="GARANTIA">Garantías Dentales</option>
                            <option value="GUIA_ENTREGA">Guías de Entrega</option>
                        </select>
                    </div>
                </div>

                <!-- Tabulator Table -->
                <div class="w-full">
                    <div id="documentos-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: GENERAR NUEVO DOCUMENTO ADMINISTRATIVO                           -->
    <!-- ========================================================================= -->
    <div id="modalGenerarDoc"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[95vh] flex flex-col">
            
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                        <i data-lucide="file-plus-2" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Generar Documento Administrativo</h3>
                        <p class="text-xs text-slate-500">Selecciona el tipo de documento y completa los datos de emisión.</p>
                    </div>
                </div>
                <button onclick="cerrarModalGenerarDoc()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form id="form-generar-doc" onsubmit="guardarDocumento(event)" class="p-6 overflow-y-auto space-y-4 flex-1">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tipo de Documento *</label>
                        <select id="doc_tipo" name="tipo_documento" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="FACTURA">Factura / Nota de Cobro</option>
                            <option value="ORDEN_TRABAJO">Orden de Trabajo Formal</option>
                            <option value="PRESUPUESTO">Presupuesto / Cotización</option>
                            <option value="GARANTIA">Certificado de Garantía Dental</option>
                            <option value="GUIA_ENTREGA">Guía y Acta de Entrega</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Clínica Receptora *</label>
                        <select id="doc_clinica" name="clinica_id" required onchange="alCambiarClinicaDoc(this.value)"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="">Seleccione clínica...</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Sede / Sucursal</label>
                        <select id="doc_sede" name="sede_id" onchange="alCambiarSedeDoc(this.value)"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="">Sede principal / Todas</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Vincular a Caso Dental Individual (Opcional)</label>
                        <select id="doc_caso" name="orden_trabajo_id" onchange="autocompletarDesdeCaso(this.value)"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="">Ninguno / Emisión General o en Lote</option>
                        </select>
                    </div>
                </div>

                <!-- Banner de Automatización de Casos de la Clínica -->
                <div id="banner-casos-clinica" class="hidden p-4 rounded-xl bg-gradient-to-r from-indigo-50 to-brand-50 border border-indigo-100/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-500/20">
                            <i data-lucide="sparkles" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-indigo-950 uppercase tracking-wide">Casos de la Clínica Detectados</h4>
                            <p id="texto-casos-detectados" class="text-xs text-indigo-700 font-medium">Se detectaron 0 casos para esta clínica.</p>
                        </div>
                    </div>
                    <button type="button" onclick="cargarRelacionAutomaticaCasos()"
                        class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm flex items-center gap-1.5 transition-colors">
                        <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                        <span>Generar Relación Automática</span>
                    </button>
                </div>

                <!-- Tabla de Conceptos / Servicios Detallados -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="block text-xs font-bold uppercase text-slate-600 tracking-wider">Conceptos y Servicios a Facturar</span>
                            <span class="text-[11px] text-slate-400">Puedes editar cualquier concepto o agregar nuevos ítems</span>
                        </div>
                        <button type="button" onclick="agregarFilaItem()" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-brand-600 text-xs font-bold flex items-center gap-1 shadow-sm transition-colors">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Agregar Fila</span>
                        </button>
                    </div>
                    <div class="overflow-x-auto max-h-56 overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead class="sticky top-0 bg-slate-100 z-10">
                                <tr class="text-slate-500 border-b border-slate-200 text-left">
                                    <th class="py-2 px-2 font-semibold">Descripción del Servicio / Detalle</th>
                                    <th class="py-2 px-2 font-semibold text-center w-20">Cant.</th>
                                    <th class="py-2 px-2 font-semibold text-right w-28">Precio ($)</th>
                                    <th class="py-2 px-2 font-semibold text-right w-28">Subtotal ($)</th>
                                    <th class="py-2 px-2 w-10 text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="tabla-items-form" class="divide-y divide-slate-200/70">
                                <!-- Filas dinámicas -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Montos y Conversión de Moneda -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <span class="block text-xs font-bold uppercase text-slate-400 tracking-wider">Valores Económicos y Conversión</span>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Monto Total USD ($)</label>
                            <input type="number" step="0.01" min="0" id="doc_monto_usd" name="monto_usd" value="0.00" required oninput="calcularTotalBs()"
                                class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 font-bold text-slate-800">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Tasa de Cambio Vigente</label>
                            <input type="number" step="0.0001" min="0.0001" id="doc_tasa" name="tasa_cambio" value="1.0000" readonly
                                class="w-full px-3 py-2 text-sm bg-slate-100 border border-slate-200 rounded-xl text-slate-600 font-mono font-medium cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Total en Bolívares (Bs.)</label>
                            <input type="text" id="doc_monto_bs" readonly
                                class="w-full px-3 py-2 text-sm bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 font-bold cursor-not-allowed" value="Bs. 0.00">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Observaciones / Conceptos Adicionales</label>
                    <textarea id="doc_observaciones" name="observaciones" rows="2"
                        class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 resize-none"
                        placeholder="Ej. Fabricación de prótesis conforme a orden de trabajo o relación de casos recibidos..."></textarea>
                </div>

                <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalGenerarDoc()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="button" onclick="iniciarSupervisionPrevia()" id="btn-previa-supervision"
                        class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-md shadow-brand-500/20 flex items-center gap-2 transition-all">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        <span>Revisar y Validar en Vista Previa</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL SUPERVISIÓN Y VALIDACIÓN PREVIA (ANTES DE EMITIR)                    -->
    <!-- ========================================================================= -->
    <div id="modalSupervisionDoc"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 p-2 sm:p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-3xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[96vh] flex flex-col">
            
            <div class="px-6 py-3.5 border-b border-slate-200 flex items-center justify-between bg-amber-50/70 flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-amber-100 text-amber-800">
                        <i data-lucide="file-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-slate-800">Supervisión y Validación de Documento</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-500 text-white">Borrador Previo</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Revisa, ajusta o valida los conceptos antes de guardar y emitir en el sistema.</p>
                    </div>
                </div>
                <button onclick="volverAFormularioDoc()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-amber-100/50">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Hoja de Supervisión -->
            <div class="p-6 overflow-y-auto flex-1 bg-white text-slate-800 space-y-4">
                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-slate-200 pb-3 gap-2">
                        <div>
                            <span id="sup-tipo-badge" class="px-2.5 py-0.5 rounded bg-slate-900 text-white text-[11px] font-black uppercase tracking-wider">FACTURA</span>
                            <h4 id="sup-clinica-nombre" class="text-sm sm:text-base font-bold text-slate-900 mt-1.5">Clínica Receptora</h4>
                            <p id="sup-clinica-doc" class="text-xs text-slate-500">RIF: J-00000000 • Sede Principal</p>
                        </div>
                        <div class="text-left sm:text-right">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block">FECHA DE EMISIÓN</span>
                            <span id="sup-fecha" class="text-xs font-semibold text-slate-700">Hoy</span>
                        </div>
                    </div>

                    <!-- Tabla de Items a Validar -->
                    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] border-b border-slate-200">
                                <tr>
                                    <th class="py-2 px-3 text-left">Concepto / Servicio Dental</th>
                                    <th class="py-2 px-3 text-center w-20">Cantidad</th>
                                    <th class="py-2 px-3 text-right w-24">Precio ($)</th>
                                    <th class="py-2 px-3 text-right w-28">Subtotal ($)</th>
                                </tr>
                            </thead>
                            <tbody id="sup-tabla-items" class="divide-y divide-slate-100">
                                <!-- Filas de supervisión -->
                            </tbody>
                            <tfoot class="border-t-2 border-slate-200 font-bold bg-slate-50">
                                <tr>
                                    <td colspan="2" class="py-2 px-3 text-right uppercase text-[10px] text-slate-500">Tasa Oficial:</td>
                                    <td colspan="2" class="py-2 px-3 text-right font-mono text-slate-700" id="sup-tasa">1.0000 Bs/USD</td>
                                </tr>
                                <tr class="text-slate-900 text-sm bg-slate-100/80">
                                    <td colspan="2" class="py-2.5 px-3 text-right uppercase text-xs">Total Liquidación:</td>
                                    <td class="py-2.5 px-3 text-right text-brand-700 font-black" id="sup-total-usd">$0.00</td>
                                    <td class="py-2.5 px-3 text-right text-emerald-700 font-black" id="sup-total-bs">Bs. 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Observaciones de la emisión -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Notas u Observaciones a Incluir:</label>
                        <textarea id="sup-observaciones" rows="2" class="w-full px-3 py-2 text-xs bg-white border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20"></textarea>
                    </div>
                </div>
            </div>

            <!-- Footer con Botones -->
            <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <button type="button" onclick="volverAFormularioDoc()"
                    class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-white flex items-center gap-1.5 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Volver a Ajustar</span>
                </button>
                <button type="button" onclick="confirmarYGuardarDocumento()" id="btn-confirmar-doc"
                    class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/25 flex items-center gap-2 transition-all">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>Confirmar y Emitir Documento</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: VISTA PREVIA IMPRIMIBLE DEL DOCUMENTO OFICIAL                    -->
    <!-- ========================================================================= -->
    <div id="modalImprimirDoc"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 p-2 sm:p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-3xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[96vh] flex flex-col">
            
            <!-- Barra superior del visor -->
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-100 flex-shrink-0 print:hidden">
                <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                    <i data-lucide="printer" class="w-4 h-4 text-brand-600"></i>
                    Vista Previa de Documento Oficial
                </span>
                <div class="flex items-center gap-2">
                    <button onclick="imprimirDocumentoDirecto()"
                        class="px-4 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow flex items-center gap-1.5 transition-colors">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Imprimir / PDF</span>
                    </button>
                    <button onclick="cerrarModalImprimirDoc()" class="text-slate-400 hover:text-slate-600 p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Hoja Imprimible (Documento Formal) -->
            <div id="hoja-imprimible" class="p-8 sm:p-12 overflow-y-auto flex-1 bg-white text-slate-800 font-sans space-y-6">
                
                <!-- Encabezado de la Empresa -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b-2 border-slate-900 pb-6 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-slate-950 text-white flex items-center justify-center font-bold text-xl shadow">
                            <i data-lucide="activity" class="w-7 h-7 text-brand-400"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-slate-950 tracking-tight uppercase">CNSB Laboratorio Dental</h2>
                            <p class="text-xs text-slate-600 font-medium">Centro Nacional de Salud Bucal • RIF: J-50756649-8</p>
                            <p class="text-[11px] text-slate-500">Calle 3-A, Urbanización La Urbina, Caracas, Venezuela</p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <span id="prev-tipo-badge" class="inline-block px-3 py-1 rounded-md text-xs font-black bg-slate-950 text-white uppercase tracking-wider mb-1">FACTURA</span>
                        <h3 id="prev-numero" class="text-base font-mono font-black text-brand-700">FAC-2026-0001</h3>
                        <p id="prev-fecha" class="text-xs text-slate-500 font-medium">Fecha: 13/10/2026</p>
                    </div>
                </div>

                <!-- Datos de la Clínica Cliente -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                    <div>
                        <span class="font-bold text-slate-400 uppercase text-[10px] block mb-1">Destinatario / Cliente</span>
                        <h4 id="prev-clinica" class="font-bold text-slate-900 text-sm">Clínica Odontológica</h4>
                        <p id="prev-rif" class="text-slate-600 font-mono mt-0.5">RIF: J-00000000</p>
                        <p id="prev-direccion" class="text-slate-600 mt-0.5">Dirección fiscal</p>
                    </div>
                    <div>
                        <span class="font-bold text-slate-400 uppercase text-[10px] block mb-1">Sede / Caso Vinculado</span>
                        <p id="prev-sede" class="font-semibold text-slate-800">Sede Principal</p>
                        <p id="prev-orden" class="text-slate-600 mt-0.5">Caso Dental: ORD-0001</p>
                        <p id="prev-paciente" class="text-slate-600 mt-0.5">Paciente: Juan Perez</p>
                    </div>
                </div>

                <!-- Detalle de Conceptos / Servicios -->
                <div>
                    <table class="w-full text-left text-xs border border-slate-200 rounded-lg overflow-hidden">
                        <thead class="bg-slate-900 text-white uppercase text-[10px]">
                            <tr>
                                <th class="py-2.5 px-4">Descripción del Concepto / Servicio Dental</th>
                                <th class="py-2.5 px-4 text-center">Piezas</th>
                                <th class="py-2.5 px-4 text-right">Monto ($ USD)</th>
                                <th class="py-2.5 px-4 text-right">Monto (Bs.)</th>
                            </tr>
                        </thead>
                        <tbody id="prev-items-tabla" class="divide-y divide-slate-200">
                            <tr>
                                <td class="py-3 px-4 font-medium" id="prev-servicio-desc">Servicio de Prótesis Dental</td>
                                <td class="py-3 px-4 text-center font-bold" id="prev-piezas-desc">1</td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900" id="prev-monto-usd">$0.00</td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900" id="prev-monto-bs">Bs. 0.00</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                            <tr>
                                <td colspan="2" class="py-2.5 px-4 text-right uppercase text-[11px] text-slate-500">Tasa de Cambio Oficial:</td>
                                <td colspan="2" class="py-2.5 px-4 text-right font-mono text-slate-700" id="prev-tasa-cambio">1.0000 Bs/USD</td>
                            </tr>
                            <tr class="bg-slate-100 text-slate-900">
                                <td colspan="2" class="py-3 px-4 text-right uppercase text-xs">Total Liquidación:</td>
                                <td class="py-3 px-4 text-right text-sm text-brand-700" id="prev-total-usd">$0.00</td>
                                <td class="py-3 px-4 text-right text-sm text-emerald-700" id="prev-total-bs">Bs. 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Cláusulas / Observaciones -->
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs">
                    <span class="font-bold text-slate-700 block mb-1">Términos y Observaciones:</span>
                    <p id="prev-observaciones" class="text-slate-600 leading-relaxed italic">Documento emitido conforme a las especificaciones técnicas odontológicas requeridas.</p>
                </div>

                <!-- Bloque de Firmas -->
                <div class="pt-8 grid grid-cols-2 gap-12 text-center text-xs text-slate-600">
                    <div class="border-t border-slate-400 pt-2">
                        <p class="font-bold text-slate-800" id="prev-firmante">Administración CNSB Lab</p>
                        <p class="text-[10px] text-slate-500">Emisión y Verificación Contable</p>
                    </div>
                    <div class="border-t border-slate-400 pt-2">
                        <p class="font-bold text-slate-800">Recibido Conforme Clínica</p>
                        <p class="text-[10px] text-slate-500">Firma, Cédula y Sello</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ESTILOS EXCLUSIVOS DE IMPRESIÓN -->
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #modalImprimirDoc, #modalImprimirDoc * {
                visibility: visible !important;
            }
            #modalImprimirDoc {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: auto !important;
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #hoja-imprimible {
                padding: 20px !important;
                box-shadow: none !important;
                border: none !important;
            }
            .print\:hidden {
                display: none !important;
            }
        }
    </style>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let tablaDocs = null;
        let catalogoSedes = [];
        let catalogoCasos = [];
        let tasaVigente = 1.00;

        document.addEventListener("DOMContentLoaded", () => {
            inicializarTablaDocumentos();
            cargarCatalogosDocs();
            cargarListaDocumentos();

            document.getElementById("buscar-documento").addEventListener("input", function(e) {
                const term = e.target.value.toLowerCase().trim();
                if (!tablaDocs) return;
                tablaDocs.setFilter((data) => {
                    if (!term) return true;
                    return (
                        (data.numero_documento && data.numero_documento.toLowerCase().includes(term)) ||
                        (data.clinica_nombre && data.clinica_nombre.toLowerCase().includes(term)) ||
                        (data.paciente_nombre && data.paciente_nombre.toLowerCase().includes(term)) ||
                        (data.orden_codigo_qr && data.orden_codigo_qr.toLowerCase().includes(term))
                    );
                });
            });
        });

function inicializarTablaDocumentos() {
            tablaDocs = new Tabulator("#documentos-table", {
                data: [],
                layout: "fitColumns",
                responsiveLayout: "collapse",
                pagination: "local",
                paginationSize: 10,
                placeholder: "No se han emitido documentos administrativos.",
                columns: [
                    {
                        title: "Correlativo", field: "numero_documento", minWidth: 140,
                        formatter: (cell) => {
                            return `<span class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-1 rounded-md text-xs">${cell.getValue()}</span>`;
                        }
                    },
                    {
                        title: "Tipo", field: "tipo_documento", minWidth: 130, hozAlign: "center",
                        formatter: (cell) => {
                            const val = cell.getValue();
                            const colors = {
                                'FACTURA': 'bg-blue-50 text-blue-700 border-blue-200',
                                'ORDEN_TRABAJO': 'bg-purple-50 text-purple-700 border-purple-200',
                                'PRESUPUESTO': 'bg-amber-50 text-amber-700 border-amber-200',
                                'GARANTIA': 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'GUIA_ENTREGA': 'bg-indigo-50 text-indigo-700 border-indigo-200'
                            };
                            const cls = colors[val] || 'bg-slate-100 text-slate-700 border-slate-200';
                            return `<span class="px-2 py-0.5 rounded-full text-xs font-bold border ${cls}">${val}</span>`;
                        }
                    },
                    {
                        title: "Clínica & Sede", field: "clinica_nombre", minWidth: 200,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <p class="font-bold text-slate-800 text-xs">${d.clinica_nombre}</p>
                                    <p class="text-[10px] text-slate-400">${d.sede_nombre}</p>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Monto USD", field: "monto_usd", minWidth: 100, hozAlign: "right",
                        formatter: (cell) => {
                            return `<span class="font-bold text-brand-700 text-xs">$${parseFloat(cell.getValue() || 0).toFixed(2)}</span>`;
                        }
                    },
                    {
                        title: "Monto Bs.", field: "monto_bs", minWidth: 110, hozAlign: "right",
                        formatter: (cell) => {
                            return `<span class="font-bold text-emerald-700 text-xs">Bs. ${parseFloat(cell.getValue() || 0).toLocaleString('es-VE', {minimumFractionDigits: 2})}</span>`;
                        }
                    },
                    {
                        title: "Estatus", field: "estatus", minWidth: 100, hozAlign: "center",
                        formatter: (cell) => {
                            const val = cell.getValue();
                            const cls = val === 'PAGADO' ? 'bg-emerald-50 text-emerald-700' : (val === 'ANULADO' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700');
                            return `<span class="px-2 py-0.5 rounded-full text-[11px] font-bold ${cls}">${val}</span>`;
                        }
                    },
                    {
                        title: "Fecha Emisión", field: "fecha_emision_fmt", minWidth: 130, hozAlign: "center",
                        formatter: (cell) => `<span class="text-xs text-slate-600">${cell.getValue()}</span>`
                    },
                    {
                        title: "Acciones", minWidth: 140, hozAlign: "center", headerSort: false,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="verVistaPreviaDoc(${d.id})" title="Imprimir / Ver" class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </button>
                                    ${d.estatus === 'EMITIDO' ? `
                                        <button onclick="cambiarEstatusDoc(${d.id}, 'PAGADO')" title="Marcar Pagado" class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="cambiarEstatusDoc(${d.id}, 'ANULADO')" title="Anular" class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">
                                            <i data-lucide="ban" class="w-4 h-4"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            `;
                        }
                    }
                ],
                // SOLUCIÓN APLICADA AQUÍ:
                rowFormatter: function(row) {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons({
                            root: row.getElement()
                        });
                    }
                }
            });
        }

        async function cargarListaDocumentos() {
            try {
                const fd = new FormData();
                fd.append('action', 'listar_documentos');
                const res = await fetch('/documentos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    if (tablaDocs) tablaDocs.setData(json.data);
                    if (json.kpis) {
                        document.getElementById("kpi-total-docs").textContent = json.kpis.total_documentos || 0;
                        document.getElementById("kpi-usd").textContent = `$${parseFloat(json.kpis.facturado_usd || 0).toFixed(2)}`;
                        document.getElementById("kpi-bs").textContent = `Bs. ${parseFloat(json.kpis.facturado_bs || 0).toLocaleString('es-VE', {minimumFractionDigits: 2})}`;
                        document.getElementById("kpi-garantias").textContent = json.kpis.total_garantias || 0;
                    }
                }
            } catch (err) {
                console.error("Error al cargar documentos:", err);
            }
        }

        async function cargarCatalogosDocs() {
            try {
                const fd = new FormData();
                fd.append('action', 'cargar_catalogos');
                const res = await fetch('/documentos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    catalogoSedes = json.sedes || [];
                    catalogoCasos = json.casos || [];
                    tasaVigente = json.tasa_actual || 1.00;
                    document.getElementById("doc_tasa").value = tasaVigente.toFixed(4);

                    const selClinica = document.getElementById("doc_clinica");
                    selClinica.innerHTML = '<option value="">Seleccione clínica...</option>';
                    (json.clinicas || []).forEach(c => {
                        selClinica.innerHTML += `<option value="${c.id}">${c.razon_social} (${c.tipo_documento}-${c.numero_documento})</option>`;
                    });

                    const selCaso = document.getElementById("doc_caso");
                    selCaso.innerHTML = '<option value="">Ninguno / Emisión General</option>';
                    (json.casos || []).forEach(cs => {
                        selCaso.innerHTML += `<option value="${cs.id}">${cs.codigo_qr} — ${cs.paciente_nombre} (${cs.servicio_nombre})</option>`;
                    });
                }
            } catch (err) {
                console.error("Error al cargar catálogos:", err);
            }
        }

        let catalogoSedes = [];
        let catalogoCasos = [];
        let tasaVigente = 1.00;
        let datosCasosClinicaActual = null;
        let datosClinicaActual = null;
        let itemsFacturar = [];

        function alCambiarClinicaDoc(clinicaId) {
            filtrarSedesDoc(clinicaId);
            itemsFacturar = [];
            renderizarFilasItems();

            const banner = document.getElementById("banner-casos-clinica");
            if (!clinicaId) {
                if (banner) banner.classList.add("hidden");
                datosCasosClinicaActual = null;
                datosClinicaActual = null;
                return;
            }

            cargarCasosClinica(clinicaId);
        }

        function alCambiarSedeDoc(sedeId) {
            const clinicaId = document.getElementById("doc_clinica").value;
            if (clinicaId) {
                cargarCasosClinica(clinicaId, sedeId);
            }
        }

        async function cargarCasosClinica(clinicaId, sedeId = null) {
            try {
                const fd = new FormData();
                fd.append('action', 'obtener_casos_clinica');
                fd.append('clinica_id', clinicaId);
                if (sedeId) fd.append('sede_id', sedeId);

                const res = await fetch('/documentos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    datosCasosClinicaActual = json;
                    datosClinicaActual = json.clinica;

                    const banner = document.getElementById("banner-casos-clinica");
                    const txt = document.getElementById("texto-casos-detectados");
                    
                    if (banner && txt) {
                        banner.classList.remove("hidden");
                        txt.innerHTML = `Se detectaron <strong>${json.total_casos} casos</strong> registrados para esta clínica. Subtotal estimado: <strong>$${json.gran_total_usd.toFixed(2)} USD</strong>`;
                    }

                    // Auto cargar relación si hay casos
                    if (json.total_casos > 0 && itemsFacturar.length === 0) {
                        cargarRelacionAutomaticaCasos();
                    }
                }
            } catch (err) {
                console.error("Error al cargar casos de la clínica:", err);
            }
        }

        function cargarRelacionAutomaticaCasos() {
            if (!datosCasosClinicaActual || !datosCasosClinicaActual.resumen_servicios || datosCasosClinicaActual.resumen_servicios.length === 0) {
                Swal.fire('Información', 'No se encontraron casos registrados para esta clínica.', 'info');
                return;
            }

            itemsFacturar = [];
            datosCasosClinicaActual.resumen_servicios.forEach(s => {
                const codigosMuestra = s.codigos_casos.slice(0, 4).join(', ');
                const extra = s.codigos_casos.length > 4 ? ` (+${s.codigos_casos.length - 4} más)` : '';
                itemsFacturar.push({
                    descripcion: `${s.servicio_nombre} (Casos: ${codigosMuestra}${extra})`,
                    cantidad: s.total_casos,
                    precio_unitario: parseFloat(s.precio_unitario || 0),
                    subtotal: parseFloat(s.subtotal_usd || 0)
                });
            });

            renderizarFilasItems();

            const obs = document.getElementById("doc_observaciones");
            if (obs && (!obs.value || obs.value.trim() === '')) {
                obs.value = `Relación automatizada de ${datosCasosClinicaActual.total_casos} caso(s) odontológico(s) para ${datosClinicaActual ? datosClinicaActual.razon_social : 'la clínica'}.`;
            }
        }

        function agregarFilaItem(desc = '', cant = 1, precio = 0) {
            itemsFacturar.push({
                descripcion: desc || 'Servicio / Prótesis Dental',
                cantidad: cant,
                precio_unitario: precio,
                subtotal: cant * precio
            });
            renderizarFilasItems();
        }

        function eliminarFilaItem(index) {
            itemsFacturar.splice(index, 1);
            renderizarFilasItems();
        }

        function actualizarItem(index, field, value) {
            if (!itemsFacturar[index]) return;
            if (field === 'descripcion') {
                itemsFacturar[index].descripcion = value;
            } else if (field === 'cantidad') {
                itemsFacturar[index].cantidad = Math.max(1, parseInt(value) || 1);
                itemsFacturar[index].subtotal = itemsFacturar[index].cantidad * itemsFacturar[index].precio_unitario;
            } else if (field === 'precio_unitario') {
                itemsFacturar[index].precio_unitario = Math.max(0, parseFloat(value) || 0);
                itemsFacturar[index].subtotal = itemsFacturar[index].cantidad * itemsFacturar[index].precio_unitario;
            }
            renderizarFilasItems();
        }

        function renderizarFilasItems() {
            const tbody = document.getElementById("tabla-items-form");
            if (!tbody) return;

            if (itemsFacturar.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="py-4 text-center text-slate-400 italic">
                            No hay conceptos agregados aún. Usa "Agregar Fila" o "Generar Relación Automática".
                        </td>
                    </tr>
                `;
                recalcularTotalesItems();
                return;
            }

            let html = '';
            itemsFacturar.forEach((it, idx) => {
                html += `
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-1.5 px-2">
                            <input type="text" value="${it.descripcion.replace(/"/g, '&quot;')}" onchange="actualizarItem(${idx}, 'descripcion', this.value)"
                                class="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-brand-500 font-medium text-slate-800"
                                placeholder="Descripción del servicio...">
                        </td>
                        <td class="py-1.5 px-2 text-center">
                            <input type="number" min="1" step="1" value="${it.cantidad}" onchange="actualizarItem(${idx}, 'cantidad', this.value)"
                                class="w-16 px-2 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-center font-bold focus:outline-none focus:ring-1 focus:ring-brand-500">
                        </td>
                        <td class="py-1.5 px-2 text-right">
                            <input type="number" min="0" step="0.01" value="${it.precio_unitario.toFixed(2)}" onchange="actualizarItem(${idx}, 'precio_unitario', this.value)"
                                class="w-24 px-2 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-right font-mono font-bold focus:outline-none focus:ring-1 focus:ring-brand-500">
                        </td>
                        <td class="py-1.5 px-2 text-right font-bold text-slate-900 font-mono text-xs">
                            $${it.subtotal.toFixed(2)}
                        </td>
                        <td class="py-1.5 px-2 text-center">
                            <button type="button" onclick="eliminarFilaItem(${idx})" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition-colors">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            recalcularTotalesItems();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function recalcularTotalesItems() {
            let total = 0;
            itemsFacturar.forEach(it => {
                total += (it.cantidad * it.precio_unitario);
            });
            const inputUsd = document.getElementById("doc_monto_usd");
            if (inputUsd) {
                inputUsd.value = total.toFixed(2);
                calcularTotalBs();
            }
        }

        // =========================================================================
        // SUPERVISIÓN Y VISTA PREVIA INTERACTIVA (ANTES DE EMITIR)
        // =========================================================================
        function iniciarSupervisionPrevia() {
            const clinicaSelect = document.getElementById("doc_clinica");
            const clinicaId = clinicaSelect.value;
            if (!clinicaId) {
                Swal.fire('Atención', 'Por favor selecciona la clínica receptora antes de supervisar.', 'warning');
                return;
            }

            if (itemsFacturar.length === 0) {
                const montoManual = parseFloat(document.getElementById("doc_monto_usd").value) || 0;
                if (montoManual <= 0) {
                    Swal.fire('Atención', 'Debes incluir al menos un concepto de servicio o un monto válido.', 'warning');
                    return;
                }
                // Si no había items pero hay monto manual, creamos 1 item genérico
                itemsFacturar.push({
                    descripcion: document.getElementById("doc_tipo").value.replace('_', ' ') + ' General',
                    cantidad: 1,
                    precio_unitario: montoManual,
                    subtotal: montoManual
                });
            }

            const tipoDoc = document.getElementById("doc_tipo").value;
            const clinicaNombre = clinicaSelect.options[clinicaSelect.selectedIndex].text;
            const sedeSelect = document.getElementById("doc_sede");
            const sedeNombre = sedeSelect.selectedIndex > 0 ? sedeSelect.options[sedeSelect.selectedIndex].text : 'Sede Principal';

            document.getElementById("sup-tipo-badge").textContent = tipoDoc.replace('_', ' ');
            document.getElementById("sup-clinica-nombre").textContent = clinicaNombre;
            document.getElementById("sup-clinica-doc").textContent = `Destino: ${sedeNombre}`;
            document.getElementById("sup-fecha").textContent = new Date().toLocaleDateString('es-VE');

            const tbodySup = document.getElementById("sup-tabla-items");
            tbodySup.innerHTML = '';

            let totalUsd = 0;
            itemsFacturar.forEach(it => {
                const sub = (it.cantidad * it.precio_unitario);
                totalUsd += sub;
                tbodySup.innerHTML += `
                    <tr>
                        <td class="py-2 px-3 font-medium text-slate-800">${it.descripcion}</td>
                        <td class="py-2 px-3 text-center font-bold text-slate-700">${it.cantidad}</td>
                        <td class="py-2 px-3 text-right font-mono text-slate-700">$${it.precio_unitario.toFixed(2)}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">$${sub.toFixed(2)}</td>
                    </tr>
                `;
            });

            const tasa = parseFloat(document.getElementById("doc_tasa").value) || 1.0;
            const totalBs = totalUsd * tasa;

            document.getElementById("sup-tasa").textContent = `${tasa.toFixed(4)} Bs/USD`;
            document.getElementById("sup-total-usd").textContent = `$${totalUsd.toFixed(2)} USD`;
            document.getElementById("sup-total-bs").textContent = `Bs. ${totalBs.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

            document.getElementById("sup-observaciones").value = document.getElementById("doc_observaciones").value;

            // Ocultar modal de edición y mostrar modal de supervisión
            const mGen = document.getElementById("modalGenerarDoc");
            mGen.classList.add("opacity-0");
            setTimeout(() => {
                mGen.classList.add("hidden");
                const mSup = document.getElementById("modalSupervisionDoc");
                mSup.classList.remove("hidden");
                setTimeout(() => {
                    mSup.classList.remove("opacity-0");
                    mSup.firstElementChild.classList.remove("scale-95");
                }, 10);
            }, 150);

            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function volverAFormularioDoc() {
            // Sincronizar observaciones si el usuario las editó en supervisión
            const obsSup = document.getElementById("sup-observaciones").value;
            document.getElementById("doc_observaciones").value = obsSup;

            const mSup = document.getElementById("modalSupervisionDoc");
            mSup.classList.add("opacity-0");
            mSup.firstElementChild.classList.add("scale-95");

            setTimeout(() => {
                mSup.classList.add("hidden");
                const mGen = document.getElementById("modalGenerarDoc");
                mGen.classList.remove("hidden");
                setTimeout(() => {
                    mGen.classList.remove("opacity-0");
                    mGen.firstElementChild.classList.remove("scale-95");
                }, 10);
            }, 200);
        }

        async function confirmarYGuardarDocumento() {
            const btn = document.getElementById("btn-confirmar-doc");
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin mr-1">⌛</span> Guardando y Emitiendo...`;

            try {
                const fd = new FormData(document.getElementById("form-generar-doc"));
                fd.append('action', 'generar_documento');
                
                // Observaciones finales de supervisión
                const obsFinal = document.getElementById("sup-observaciones").value;
                fd.set('observaciones', obsFinal);

                // Items detallados
                fd.append('items_detalle', JSON.stringify(itemsFacturar));

                const res = await fetch('/documentos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    // Cerrar modal supervisión
                    const mSup = document.getElementById("modalSupervisionDoc");
                    mSup.classList.add("opacity-0");
                    setTimeout(() => mSup.classList.add("hidden"), 200);

                    Swal.fire({
                        title: '¡Documento Emitido!',
                        text: json.message,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Ver e Imprimir',
                        cancelButtonText: 'Cerrar',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'px-4 py-2 rounded-xl bg-brand-600 text-white font-medium text-sm mr-2 shadow-md shadow-brand-500/20',
                            cancelButton: 'px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-medium text-sm'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            verVistaPreviaDoc(json.id);
                        }
                    });

                    cargarListaDocumentos();
                } else {
                    Swal.fire('Error', json.message || 'No se pudo generar el documento.', 'error');
                }
            } catch (err) {
                console.error("Error al emitir doc supervisado:", err);
                Swal.fire('Error', 'Problema de conexión con el servidor.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<i data-lucide="check-circle" class="w-4 h-4 mr-1 inline"></i> Confirmar y Emitir Documento`;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        function abrirModalGenerarDoc() {
            document.getElementById("form-generar-doc").reset();
            document.getElementById("doc_tasa").value = tasaVigente.toFixed(4);
            document.getElementById("doc_monto_bs").value = "Bs. 0.00";
            itemsFacturar = [];
            renderizarFilasItems();
            const banner = document.getElementById("banner-casos-clinica");
            if (banner) banner.classList.add("hidden");

            const m = document.getElementById("modalGenerarDoc");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
            }, 10);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function cerrarModalGenerarDoc() {
            const m = document.getElementById("modalGenerarDoc");
            m.classList.add("opacity-0");
            m.firstElementChild.classList.add("scale-95");
            setTimeout(() => m.classList.add("hidden"), 200);
        }

        async function guardarDocumento(e) {
            e.preventDefault();
            iniciarSupervisionPrevia();
        }

        // =========================================================================
        // VISTA PREVIA E IMPRESIÓN OFICIAL
        // =========================================================================
        async function verVistaPreviaDoc(docId) {
            try {
                const fd = new FormData();
                fd.append('action', 'ver_detalle');
                fd.append('id', docId);

                const res = await fetch('/documentos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status !== 'success') {
                    Swal.fire('Error', json.message || 'No se pudo cargar el documento.', 'error');
                    return;
                }

                const d = json.documento;
                document.getElementById("prev-tipo-badge").textContent = d.tipo_documento.replace('_', ' ');
                document.getElementById("prev-numero").textContent = d.numero_documento;
                document.getElementById("prev-fecha").textContent = `Fecha de Emisión: ${d.fecha_emision_fmt}`;

                document.getElementById("prev-clinica").textContent = d.clinica_nombre;
                document.getElementById("prev-rif").textContent = `RIF / Documento: ${d.clinica_tipo_doc}-${d.clinica_num_doc}`;
                document.getElementById("prev-direccion").textContent = d.clinica_direccion || 'Dirección fiscal registrada';

                document.getElementById("prev-sede").textContent = `Sede: ${d.sede_nombre || 'Principal'}`;
                document.getElementById("prev-orden").textContent = d.orden_codigo_qr ? `Caso Dental: ${d.orden_codigo_qr}` : 'Emisión general de servicios';
                document.getElementById("prev-paciente").textContent = d.paciente_nombre ? `Paciente: ${d.paciente_nombre} (${d.paciente_doc})` : 'Clínica solicitante directa';

                // Renderizar items detallados
                const tbody = document.getElementById("prev-items-tabla");
                tbody.innerHTML = '';

                if (d.items && Array.isArray(d.items) && d.items.length > 0) {
                    d.items.forEach(it => {
                        const cant = it.cantidad || 1;
                        const pUnit = parseFloat(it.precio_unitario || 0);
                        const subUsd = parseFloat(it.subtotal || (cant * pUnit) || 0).toFixed(2);
                        const subBs = (subUsd * (d.tasa_cambio || 1)).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        tbody.innerHTML += `
                            <tr>
                                <td class="py-2.5 px-4 font-medium text-slate-800">${it.descripcion}</td>
                                <td class="py-2.5 px-4 text-center font-bold text-slate-700">${cant}</td>
                                <td class="py-2.5 px-4 text-right font-bold text-slate-900">$${subUsd}</td>
                                <td class="py-2.5 px-4 text-right font-bold text-slate-900">Bs. ${subBs}</td>
                            </tr>
                        `;
                    });
                } else {
                    const usdFmt = parseFloat(d.monto_usd || 0).toFixed(2);
                    const bsFmt = parseFloat(d.monto_bs || 0).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    tbody.innerHTML = `
                        <tr>
                            <td class="py-3 px-4 font-medium">${d.servicio_nombre || `Concepto de ${d.tipo_documento.replace('_', ' ')}`}</td>
                            <td class="py-3 px-4 text-center font-bold">${d.cantidad_piezas_exacta || '1'}</td>
                            <td class="py-3 px-4 text-right font-bold text-slate-900">$${usdFmt}</td>
                            <td class="py-3 px-4 text-right font-bold text-slate-900">Bs. ${bsFmt}</td>
                        </tr>
                    `;
                }

                const usdTotalFmt = parseFloat(d.monto_usd || 0).toFixed(2);
                const bsTotalFmt = parseFloat(d.monto_bs || 0).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                document.getElementById("prev-total-usd").textContent = `$${usdTotalFmt} USD`;
                document.getElementById("prev-total-bs").textContent = `Bs. ${bsTotalFmt}`;

                document.getElementById("prev-tasa-cambio").textContent = `${parseFloat(d.tasa_cambio || 1).toFixed(4)} Bs/USD`;
                document.getElementById("prev-observaciones").textContent = d.observaciones || 'Documento emitido conforme a las especificaciones técnicas odontológicas requeridas.';
                document.getElementById("prev-firmante").textContent = d.usuario_nombre ? `${d.usuario_nombre}` : 'Administración CNSB Lab';

                const m = document.getElementById("modalImprimirDoc");
                m.classList.remove("hidden");
                setTimeout(() => {
                    m.classList.remove("opacity-0");
                    m.firstElementChild.classList.remove("scale-95");
                }, 10);

                if (typeof lucide !== 'undefined') lucide.createIcons();

            } catch (err) {
                console.error("Error al abrir vista previa:", err);
            }
        }

        function cerrarModalImprimirDoc() {
            const m = document.getElementById("modalImprimirDoc");
            m.classList.add("opacity-0");
            m.firstElementChild.classList.add("scale-95");
            setTimeout(() => m.classList.add("hidden"), 200);
        }

        function imprimirEtiquetaDirecta() {
            const el = document.getElementById("area-imprimible-etiqueta");
            if (!el) {
                window.print();
                return;
            }
            const iframe = document.createElement("iframe");
            iframe.style.position = "fixed";
            iframe.style.right = "0";
            iframe.style.bottom = "0";
            iframe.style.width = "0";
            iframe.style.height = "0";
            iframe.style.border = "0";
            document.body.appendChild(iframe);

            const doc = iframe.contentWindow.document;
            doc.open();
            
            // Variables para evadir la inyección automática de GoDaddy
            const endScript = "<" + "/script>";
            const endBody = "<" + "/body>";
            const endHtml = "<" + "/html>";
            
            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Etiqueta de Caja - CNSB Lab</title>
                    <script src="/assets/vendor/tailwindcss/tailwind.min.js">${endScript}
                    <style>
                        @page {
                            size: 100mm 150mm;
                            margin: 4mm;
                        }
                        body {
                            margin: 0;
                            padding: 6px;
                            background: #ffffff;
                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        .print-wrapper {
                            width: 100%;
                            max-width: 92mm;
                            margin: 0 auto;
                            border: 2px solid #0f172a;
                            border-radius: 12px;
                            padding: 14px;
                            box-sizing: border-box;
                            background: #ffffff;
                        }
                        * { box-sizing: border-box; }
                    </style>
                </head>
                <body>
                    <div class="print-wrapper">
                        ${el.innerHTML}
                    </div>
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.focus();
                                window.print();
                                setTimeout(function() {
                                    window.parent.document.body.removeChild(window.frameElement);
                                }, 1000);
                            }, 250);
                        };
                    ${endScript}
                ${endBody}
                ${endHtml}
            `);
            doc.close();
        }

        async function cambiarEstatusDoc(id, nuevoEstatus) {
            const accionTexto = nuevoEstatus === 'PAGADO' ? 'marcar como PAGADO' : 'ANULAR';
            const { isConfirmed } = await Swal.fire({
                title: `¿Deseas ${accionTexto} este documento?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'px-4 py-2 rounded-xl bg-brand-600 text-white font-medium text-sm mr-2',
                    cancelButton: 'px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-medium text-sm'
                }
            });

            if (!isConfirmed) return;

            try {
                const fd = new FormData();
                fd.append('action', 'cambiar_estatus');
                fd.append('id', id);
                fd.append('estatus', nuevoEstatus);

                const res = await fetch('/documentos-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    Swal.fire({ title: 'Actualizado', text: json.message, icon: 'success', timer: 1500, showConfirmButton: false });
                    cargarListaDocumentos();
                } else {
                    Swal.fire('Error', json.message, 'error');
                }
            } catch (err) {
                console.error(err);
            }
        }
    </script>
</body>
</html>
