<?php
session_start();
// Validación opcional de sesión/rol
// if (!isset($_SESSION['usuario_id'])) { header('Location: /'); exit(); }
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
                <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Gestión de Casos y Prótesis</h1>
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

            <!-- KPIs / Resumen por Etapas de Producción -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
                <button type="button" onclick="filtrarPorEtapaRapida('1')"
                    class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-sky-300 transition-all text-left flex items-center justify-between group">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-sky-600">1. Toma de Impresión</span>
                        <p id="kpi-etapa-1" class="text-2xl font-extrabold text-slate-800 mt-1">0</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <i data-lucide="stamp" class="w-5 h-5"></i>
                    </div>
                </button>

                <button type="button" onclick="filtrarPorEtapaRapida('2')"
                    class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-amber-300 transition-all text-left flex items-center justify-between group">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-amber-600">2. Prueba de Rodete</span>
                        <p id="kpi-etapa-2" class="text-2xl font-extrabold text-slate-800 mt-1">0</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="sliders-horizontal" class="w-5 h-5"></i>
                    </div>
                </button>

                <button type="button" onclick="filtrarPorEtapaRapida('3')"
                    class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-purple-300 transition-all text-left flex items-center justify-between group">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-purple-600">3. Enfilado</span>
                        <p id="kpi-etapa-3" class="text-2xl font-extrabold text-slate-800 mt-1">0</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-5 h-5"></i>
                    </div>
                </button>

                <button type="button" onclick="filtrarPorEtapaRapida('4')"
                    class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-emerald-300 transition-all text-left flex items-center justify-between group">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-emerald-600">4. Instalación / Listo</span>
                        <p id="kpi-etapa-4" class="text-2xl font-extrabold text-slate-800 mt-1">0</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    </div>
                </button>
            </div>

            <!-- Action Bar & Header Card -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 md:p-6 mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Control de Producción y Ubicación Física</h2>
                    <p class="text-sm text-slate-500 mt-1">Registra órdenes de prótesis, asigna protesistas responsables, controla la etapa actual y rastrea dónde se encuentra cada caso físicamente.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <button onclick="abrirEscanerQR()"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-[#FFCC00] hover:bg-amber-400 text-slate-950 font-bold border border-amber-300 shadow transition-all duration-200 space-x-2 w-full sm:w-auto text-sm">
                        <i data-lucide="scan" class="w-5 h-5 text-[#D40511]"></i>
                        <span>Escanear QR (Caja / Caso)</span>
                    </button>
                    <button onclick="abrirModalCrearCaso()"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 w-full sm:w-auto text-sm">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        <span>Nuevo Caso / Orden</span>
                    </button>
                </div>
            </div>

            <!-- Table Card Container -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <!-- Barra de Filtros -->
                <div class="p-4 border-b border-slate-100 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                    <div class="relative w-full xl:w-96">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="buscar-caso" placeholder="Buscar por código QR, paciente, cédula, clínica o responsable..."
                            class="w-full pl-9 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all text-slate-600">
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filtro Etapa -->
                        <select id="filtro-etapa" class="px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                            <option value="">Todas las etapas</option>
                            <option value="1">1. TOMA DE IMPRESION</option>
                            <option value="2">2. PRUEBA DE RODETE</option>
                            <option value="3">3. ENFILADO</option>
                            <option value="4">4. INSTALACION</option>
                        </select>

                        <!-- Filtro Ubicación Física -->
                        <select id="filtro-ubicacion" class="px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                            <option value="">Todas las ubicaciones físicas</option>
                            <option value="RECEPCIÓN LABORATORIO">Recepción Laboratorio</option>
                            <option value="ÁREA DE YESO">Área de Yeso</option>
                            <option value="MESA DE ACRÍLICO / ENFILADO">Mesa de Acrílico / Enfilado</option>
                            <option value="CONTROL DE CALIDAD">Control de Calidad</option>
                            <option value="ÁREA DE DESPACHO">Área de Despacho (Por empacar)</option>
                            <option value="EN TRÁNSITO">En Tránsito (Con Repartidor)</option>
                            <option value="EN CLÍNICA / SEDE">En Clínica / Sede</option>
                        </select>

                        <!-- Filtro Responsable -->
                        <select id="filtro-responsable" class="px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                            <option value="">Todos los responsables</option>
                        </select>
                    </div>
                </div>

                <!-- Tabulator Table: Casos -->
                <div class="w-full">
                    <div id="casos-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: REGISTRAR / EDITAR CASO (ORDEN DE TRABAJO)                       -->
    <!-- ========================================================================= -->
    <div id="casoModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-4xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[95vh] flex flex-col"
            id="modalCasoContainer">
            
            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="p-2 rounded-xl bg-brand-50 text-brand-600">
                        <i data-lucide="clipboard-list" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 id="modalCasoTitle" class="text-lg font-bold text-slate-800">Registrar Nuevo Caso Dental</h3>
                        <p class="text-xs text-slate-500">Asigna la clínica, sede, paciente, prótesis, protesista responsable y ubicación física.</p>
                    </div>
                </div>
                <button onclick="cerrarModalCaso()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form id="casoForm" class="p-5 space-y-4 overflow-y-auto flex-1">
                <input type="hidden" id="caso_id" name="id">

                <!-- Clínica y Sede -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Clínica Solicitante</label>
                        <select id="caso_clinica" name="clinica_id" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="">Seleccione clínica...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Sede de la Clínica</label>
                        <select id="caso_sede" name="sede_id"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="">Todas las sedes / Sede principal</option>
                        </select>
                    </div>
                </div>

                <!-- Selector de Paciente (Tabla interactiva detallada, NO un select) -->
                <div class="p-4 bg-slate-50/80 border border-slate-200/80 rounded-2xl space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Selección de Paciente</label>
                            <p class="text-[11px] text-slate-500">Busca y selecciona el paciente en la tabla inferior.</p>
                        </div>
                        <input type="hidden" id="caso_paciente" name="paciente_id" required>
                        <div id="paciente_badge_seleccion" class="hidden items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                            <span>Paciente Seleccionado</span>
                        </div>
                    </div>

                    <!-- Tarjeta de Paciente Seleccionado -->
                    <div id="tarjeta_paciente_seleccionado" class="p-3 bg-white border border-dashed border-slate-300 rounded-xl flex items-center justify-between text-slate-500 text-xs">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center font-bold text-sm">
                                <i data-lucide="user" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="block font-semibold text-slate-700">Ningún paciente seleccionado</span>
                                <span class="text-[11px] text-slate-400">Usa el buscador y haz clic en "Seleccionar" en la tabla</span>
                            </div>
                        </div>
                    </div>

                    <!-- Buscador de pacientes en modal -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </span>
                        <input type="text" id="buscar_paciente_modal" placeholder="Filtrar por nombre, apellido, cédula o clínica..."
                            class="w-full pl-9 pr-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700">
                    </div>

                    <!-- Micro-tabla de pacientes con scroll -->
                    <div class="max-h-44 overflow-y-auto border border-slate-200 rounded-xl bg-white shadow-inner">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100/80 sticky top-0 text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                                <tr>
                                    <th class="py-2 px-3">Paciente</th>
                                    <th class="py-2 px-3">Documento</th>
                                    <th class="py-2 px-3 hidden sm:table-cell">Sexo</th>
                                    <th class="py-2 px-3">Clínica / Sede</th>
                                    <th class="py-2 px-3 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tabla_pacientes_modal_body" class="divide-y divide-slate-100">
                                <!-- Llenado dinámico vía JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Prótesis, Cantidad de Prótesis y Piezas Dentales -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Prótesis / Servicio</label>
                        <select id="caso_servicio" name="servicio_id" required onchange="ajustarRangoPiezas(this)"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700">
                            <option value="">Seleccione servicio/prótesis...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Cant. Prótesis a Procesar</label>
                        <input type="number" id="caso_cantidad_protesis" name="cantidad_protesis" min="1" value="1" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Cant. Piezas Dentales</label>
                        <input type="number" id="caso_piezas" name="cantidad_piezas_exacta" min="1" max="32" value="1" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-bold">
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Protesista Responsable y Etapa Inicial -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Protesista Responsable</label>
                        <select id="caso_protesista" name="protesista_id" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="">Seleccione protesista...</option>
                        </select>
                        <input type="hidden" id="caso_usuario" name="usuario_id">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Etapa de Producción Inicial</label>
                        <select id="caso_etapa" name="etapa_produccion_id" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-medium">
                            <option value="1">1. TOMA DE IMPRESION</option>
                            <option value="2">2. PRUEBA DE RODETE</option>
                            <option value="3">3. ENFILADO</option>
                            <option value="4">4. INSTALACION</option>
                        </select>
                    </div>
                </div>

                <!-- Estatus Operativo y Ubicación Física -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Estatus Operativo del Caso</label>
                        <select id="caso_estatus" name="estatus_actual" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700">
                            <option value="RECIBIDO EN LAB">RECIBIDO EN LAB</option>
                            <option value="EN PROCESO">EN PROCESO</option>
                            <option value="EN PRUEBA CLINICA">EN PRUEBA CLÍNICA</option>
                            <option value="LISTO PARA DESPACHO">LISTO PARA DESPACHO</option>
                            <option value="ENTREGADO / FINALIZADO">ENTREGADO / FINALIZADO</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Ubicación Física Actual</label>
                        <select id="caso_ubicacion" name="ubicacion" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700">
                            <option value="RECEPCIÓN LABORATORIO">RECEPCIÓN LABORATORIO</option>
                            <option value="ÁREA DE YESO">ÁREA DE YESO</option>
                            <option value="MESA DE ACRÍLICO / ENFILADO">MESA DE ACRÍLICO / ENFILADO</option>
                            <option value="ÁREA DE PULIDO">ÁREA DE PULIDO</option>
                            <option value="CONTROL DE CALIDAD">CONTROL DE CALIDAD</option>
                            <option value="ÁREA DE DESPACHO">ÁREA DE DESPACHO</option>
                            <option value="EN TRÁNSITO">EN TRÁNSITO</option>
                            <option value="EN CLÍNICA / SEDE">EN CLÍNICA / SEDE</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Notas / Indicaciones Técnicas</label>
                    <textarea id="caso_notas" name="notas" rows="2"
                        class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 resize-none"
                        placeholder="Ej. Color A2, paciente bruxómano, enviar rodete para prueba el jueves..."></textarea>
                </div>

                <!-- Footer -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <button type="button" onclick="cerrarModalCaso()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardarCaso"
                        class="px-6 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm flex items-center space-x-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Guardar Caso</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: PROCESAR CASO (CAMBIAR ETAPA, RESPONSABLE Y UBICACIÓN FÍSICA)    -->
    <!-- ========================================================================= -->
    <div id="procesarModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-4xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[94vh] flex flex-col"
            id="modalProcesarContainer">
            
            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="p-2 rounded-xl bg-brand-50 text-brand-600">
                        <i data-lucide="workflow" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-lg font-bold text-slate-800">Procesar Caso y Trazabilidad</h3>
                            <span id="proc_codigo_badge" class="px-2.5 py-0.5 text-xs font-mono font-bold bg-brand-100 text-brand-700 rounded-md">#ORD-0000</span>
                        </div>
                        <p id="proc_subtitulo" class="text-xs text-slate-500 mt-0.5">Paciente y Clínica</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="imprimirEtiquetaCajaPequena(document.getElementById('proc_orden_id').value)"
                        class="px-3 py-1.5 rounded-xl bg-[#FFCC00]/20 hover:bg-[#FFCC00]/40 text-amber-900 border border-amber-300 text-xs font-bold transition-colors flex items-center space-x-1">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-[#D40511]"></i>
                        <span>Etiqueta QR</span>
                    </button>
                    <button onclick="cerrarModalProcesar()"
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>

            <!-- Barra Visual de las 4 Etapas -->
            <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-100 flex-shrink-0">
                <div class="grid grid-cols-4 gap-2" id="stepper-etapas">
                    <div id="step-1" class="p-2 rounded-xl border text-center transition-all">
                        <span class="block text-[10px] font-bold uppercase">Etapa 1</span>
                        <span class="text-xs font-bold">Toma Impresión</span>
                    </div>
                    <div id="step-2" class="p-2 rounded-xl border text-center transition-all">
                        <span class="block text-[10px] font-bold uppercase">Etapa 2</span>
                        <span class="text-xs font-bold">Prueba Rodete</span>
                    </div>
                    <div id="step-3" class="p-2 rounded-xl border text-center transition-all">
                        <span class="block text-[10px] font-bold uppercase">Etapa 3</span>
                        <span class="text-xs font-bold">Enfilado</span>
                    </div>
                    <div id="step-4" class="p-2 rounded-xl border text-center transition-all">
                        <span class="block text-[10px] font-bold uppercase">Etapa 4</span>
                        <span class="text-xs font-bold">Instalación</span>
                    </div>
                </div>
            </div>

            <!-- Body Dividido: Izquierda (Formulario de Movimiento) | Derecha (Línea de Tiempo / Historial) -->
            <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
                
                <!-- Izquierda: Actualizar Etapa, Responsable y Ubicación Física -->
                <form id="procesarForm" class="w-full lg:w-5/12 p-5 border-b lg:border-b-0 lg:border-r border-slate-100 space-y-4 overflow-y-auto bg-white">
                    <input type="hidden" id="proc_orden_id" name="orden_trabajo_id">

                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                        <i data-lucide="arrow-right-circle" class="w-4 h-4 text-brand-500"></i>
                        <span>Registrar Avance / Cambio Físico</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Avanzar o Mover a Etapa</label>
                        <select id="proc_etapa" name="etapa_produccion_id" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-semibold">
                            <option value="1">1. TOMA DE IMPRESION</option>
                            <option value="2">2. PRUEBA DE RODETE</option>
                            <option value="3">3. ENFILADO</option>
                            <option value="4">4. INSTALACION</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Asignar Responsable Actual</label>
                        <select id="proc_usuario" name="usuario_id" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700">
                            <option value="">Seleccione responsable...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">¿Dónde está el caso físicamente?</label>
                        <select id="proc_ubicacion" name="ubicacion" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700">
                            <option value="RECEPCIÓN LABORATORIO">RECEPCIÓN LABORATORIO</option>
                            <option value="ÁREA DE YESO">ÁREA DE YESO</option>
                            <option value="MESA DE ACRÍLICO / ENFILADO">MESA DE ACRÍLICO / ENFILADO</option>
                            <option value="ÁREA DE PULIDO">ÁREA DE PULIDO</option>
                            <option value="CONTROL DE CALIDAD">CONTROL DE CALIDAD</option>
                            <option value="ÁREA DE DESPACHO">ÁREA DE DESPACHO (Listo para empacar)</option>
                            <option value="EN TRÁNSITO">EN TRÁNSITO (Con motorizado)</option>
                            <option value="EN CLÍNICA / SEDE">EN CLÍNICA / SEDE (En manos del Dr.)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Estatus Operativo</label>
                        <select id="proc_estatus" name="estatus_actual" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700">
                            <option value="RECIBIDO EN LAB">RECIBIDO EN LAB</option>
                            <option value="EN PROCESO">EN PROCESO</option>
                            <option value="EN PRUEBA CLINICA">EN PRUEBA CLÍNICA</option>
                            <option value="LISTO PARA DESPACHO">LISTO PARA DESPACHO</option>
                            <option value="ENTREGADO / FINALIZADO">ENTREGADO / FINALIZADO</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Observación de Trazabilidad</label>
                        <textarea id="proc_notas" name="notas" rows="2" required
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 resize-none"
                            placeholder="Ej. Pasa a mesa de enfilado con el protesista Juan..."></textarea>
                    </div>

                    <button type="submit" id="btnGuardarProceso"
                        class="w-full px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm flex items-center justify-center space-x-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Actualizar Etapa y Ubicación</span>
                    </button>
                </form>

                <!-- Derecha: Historial de Trazabilidad Completo -->
                <div class="w-full lg:w-7/12 p-5 bg-slate-50/60 flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between mb-3 flex-shrink-0">
                        <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                            <i data-lucide="history" class="w-4 h-4 text-brand-600"></i>
                            <span>Bitácora de Movimientos y Responsables</span>
                        </div>
                        <span id="proc_caja_envio" class="hidden px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800"></span>
                    </div>

                    <div id="contenedor-trazabilidad" class="flex-1 overflow-y-auto space-y-3 pr-1 min-h-[250px]">
                        <!-- Dinámico vía JS -->
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL CONFIRMACIÓN DE ELIMINAR CASO                                       -->
    <!-- ========================================================================= -->
    <div id="eliminarCasoModal"
        class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300"
            id="eliminarCasoModalContainer">
            <div class="p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-triangle" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 tracking-tight">¿Eliminar Caso Dental?</h3>
                <p class="text-sm text-slate-500 mt-2 mb-6 leading-relaxed">Se eliminará la orden de trabajo y su historial de trazabilidad. Esta acción no se puede deshacer.</p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <button type="button" onclick="cerrarEliminarCasoModal()"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                        Cancelar
                    </button>
                    <button type="button" id="btnConfirmarEliminarCaso"
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
                <div id="notificacionIconContainer" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i id="notificacionIcon" data-lucide="info" class="w-8 h-8"></i>
                </div>
                <h3 id="notificacionTitulo" class="text-lg font-bold text-slate-800 tracking-tight mb-2">Mensaje</h3>
                <p id="notificacionMensaje" class="text-sm text-slate-500 mb-6 leading-relaxed"></p>
                
                <div class="flex justify-center">
                    <button type="button" onclick="cerrarNotificacionModal()"
                        class="w-full px-6 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-md shadow-brand-500/20 transition-all text-sm focus:outline-none">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL ESCÁNER QR INTERACTIVO (CÁMARA Y CÓDIGO DE BARRAS)                  -->
    <!-- ========================================================================= -->
    <div id="modalEscanerQR"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[92vh]">
            
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#FFCC00] text-[#D40511] flex items-center justify-center font-bold">
                        <i data-lucide="scan" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Escáner de Cajas y Casos</h3>
                        <p class="text-xs text-slate-500">Apunta la cámara al código QR de la caja</p>
                    </div>
                </div>
                <button onclick="cerrarModalEscanerQR()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <!-- Ventana del Escáner de Cámara -->
                <div id="reader-container" class="relative rounded-2xl overflow-hidden bg-slate-900 min-h-[220px] flex items-center justify-center border-2 border-amber-300 shadow-inner">
                    <div id="qr-reader" class="w-full"></div>
                    <div id="qr-placeholder" class="text-center p-6 text-slate-400">
                        <i data-lucide="camera" class="w-10 h-10 mx-auto mb-2 text-[#FFCC00]"></i>
                        <p class="text-xs">Iniciando cámara del dispositivo...</p>
                    </div>
                </div>

                <!-- Input manual alternativo -->
                <div class="flex gap-2">
                    <input type="text" id="input-escaner-manual" placeholder="O ingresa el código (ej. ORD-0001, ENV-0001)..."
                        class="flex-1 px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FFCC00] font-mono font-bold">
                    <button type="button" onclick="procesarEscaneoManual()"
                        class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-colors">
                        Consultar
                    </button>
                </div>

                <!-- Resultado del Escaneo -->
                <div id="resultado-escaneo" class="hidden rounded-xl border p-4 space-y-3 transition-all">
                    <!-- Inyectado por JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL IMPRESIÓN DE ETIQUETA QR (CAJA PRINCIPAL O PEQUEÑA)                -->
    <!-- ========================================================================= -->
    <div id="modalEtiquetaQR"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[92vh]">
            
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 print:hidden">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold shadow-sm">
                        <i data-lucide="tag" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Etiqueta de Identificación QR</h3>
                        <p class="text-[11px] text-slate-500">Impresión térmica para caso dental.</p>
                    </div>
                </div>
                <button onclick="cerrarModalEtiquetaQR()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- CONTENEDOR DE LA ETIQUETA IMPRIMIBLE (CNSB Lab Standard Thermal Label) -->
            <div class="p-6 overflow-y-auto flex-1 flex justify-center bg-slate-100/50">
                <div id="area-imprimible-etiqueta" class="bg-white w-full max-w-[340px] p-5 rounded-2xl border-2 border-slate-900 shadow-md text-slate-900 font-sans space-y-3">
                    
                    <!-- Header CNSB Style -->
                    <div class="border-b-2 border-slate-900 pb-2.5 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="bg-slate-950 text-white font-black text-xs px-2.5 py-1 rounded-md tracking-wider">CNSB</span>
                            <span class="font-black text-xs tracking-wider text-slate-900 uppercase">LABORATORIO DENTAL</span>
                        </div>
                        <span id="eti-tipo-badge" class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-indigo-600 text-white tracking-wider">
                            CAJA PEQUEÑA (CASO)
                        </span>
                    </div>

                    <!-- Código QR Centrado -->
                    <div class="py-2 text-center flex flex-col items-center justify-center">
                        <div id="eti-qrcode-container" class="p-2 border-2 border-dashed border-slate-300 rounded-xl bg-white inline-block"></div>
                        <span id="eti-codigo-texto" class="block font-mono font-black text-lg tracking-widest mt-1 text-slate-900">
                            ORD-0001
                        </span>
                    </div>

                    <!-- Metadatos de Envío / Caso -->
                    <div class="border-t-2 border-b-2 border-slate-900 py-2.5 text-xs space-y-2 font-medium leading-tight">
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Destino / Clínica:</span>
                            <strong id="eti-clinica" class="text-right text-slate-900 font-bold break-words flex-1">Clínica Dental</strong>
                        </div>
                        <div id="eti-row-sede" class="flex justify-between items-start gap-2 hidden">
                            <span class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Sede / Sucursal:</span>
                            <strong id="eti-sede" class="text-right text-slate-900 font-bold break-words flex-1">Sede Principal</strong>
                        </div>
                        <div id="eti-row-extra1" class="flex justify-between items-start gap-2">
                            <span id="eti-label-extra1" class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Paciente:</span>
                            <strong id="eti-val-extra1" class="text-right text-slate-900 font-bold break-words flex-1">Juan Pérez</strong>
                        </div>
                        <div id="eti-row-extra2" class="flex justify-between items-start gap-2">
                            <span id="eti-label-extra2" class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Trabajo Dental:</span>
                            <strong id="eti-val-extra2" class="text-right text-slate-900 font-bold break-words flex-1">Prótesis (1 pz)</strong>
                        </div>
                        <div class="flex justify-between items-center pt-1 border-t border-slate-200">
                            <span class="text-slate-500 uppercase text-[10px] font-bold">Fecha Emisión:</span>
                            <span id="eti-fecha" class="text-right font-mono font-semibold text-slate-800">13/10/2026</span>
                        </div>
                    </div>

                    <!-- Pie de la Etiqueta -->
                    <div class="text-[9px] text-center text-slate-500 font-mono tracking-tight uppercase leading-relaxed">
                        Control de Calidad • Escanea para confirmar recepción
                    </div>
                </div>
            </div>

            <!-- Botones de Impresión -->
            <div class="p-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 print:hidden">
                <button type="button" onclick="cerrarModalEtiquetaQR()"
                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-100 transition-colors">
                    Cerrar
                </button>
                <button type="button" onclick="imprimirEtiquetaDirecta()"
                    class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-md shadow-brand-500/25">
                    <i data-lucide="printer" class="w-4 h-4 text-white"></i>
                    <span>Imprimir Etiqueta / PDF</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Estilos específicos de impresión para la etiqueta térmica/adhesiva -->
    <style>
        @media print {
            body > *:not(#modalEtiquetaQR) {
                display: none !important;
            }
            #modalEtiquetaQR {
                display: block !important;
                position: static !important;
                visibility: visible !important;
                background: transparent !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                height: auto !important;
            }
            #modalEtiquetaQR > div {
                transform: none !important;
                max-height: none !important;
                height: auto !important;
                overflow: visible !important;
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #modalEtiquetaQR .print\:hidden {
                display: none !important;
            }
            #area-imprimible-etiqueta {
                visibility: visible !important;
                position: static !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 95mm !important;
                border: 2px solid #0f172a !important;
                box-shadow: none !important;
                padding: 12px !important;
                page-break-inside: avoid !important;
                background: #ffffff !important;
            }
            #area-imprimible-etiqueta * {
                visibility: visible !important;
            }
        }
    </style>

    <!-- Tabulator (Local) -->
    <script type="text/javascript" src="/assets/vendor/tabulator/tabulator.min.js"></script>

    <script>
        let tableCasos;
        let idCasoParaEliminar = null;
        let catalogos = { clinicas: [], sedes: [], pacientes: [], servicios: [], usuarios: [] };
        let pacienteSeleccionado = null;

        // =========================================================================
        // NOTIFICACIONES
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
        // INICIALIZACIÓN DE TABLA Y DATOS
        // =========================================================================
        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
            cargarCatalogosFormulario();

            tableCasos = new Tabulator("#casos-table", {
                ajaxURL: "/obtener-casos-controller",
                ajaxConfig: "POST",
                ajaxParams: { action: "listar_casos" },
                ajaxResponse: function (url, params, response) {
                    actualizarContadoresKPI(response.data || []);
                    return response.data || [];
                },
                layout: "fitColumns",
                responsiveLayout: "collapse",
                placeholder: "<div class='p-8 text-center text-slate-500'><i data-lucide='inbox' class='w-12 h-12 mx-auto mb-3 opacity-20'></i>No hay casos u órdenes de prótesis registrados.</div>",
                pagination: "local",
                paginationSize: 10,
                locale: "es",
                langs: {
                    "es": { "pagination": { "first": "PRIMERO", "last": "ÚLTIMO", "prev": "ANTERIOR", "next": "SIGUIENTE" } }
                },
                columns: [
                    { formatter: "responsiveCollapse", width: 35, minWidth: 35, hozAlign: "center", resizable: false, headerSort: false, responsive: 0 },
                    {
                        title: "Orden / QR", field: "codigo_qr", width: 125, responsive: 0,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <span class="font-mono font-bold text-brand-700 bg-brand-50 px-2.5 py-1 rounded-lg border border-brand-100 text-xs">#${d.codigo_qr}</span>
                                    <span class="block text-[10px] text-slate-400 mt-1">${d.fecha_ingreso_fmt || ''}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Paciente y Clínica / Sede", field: "paciente_nombre", minWidth: 220, responsive: 0,
                        formatter: (cell) => {
                            const d = cell.getData();
                            const sedeHtml = (d.sede_nombre && d.sede_nombre !== 'Sin sede asignada')
                                ? ` <span class="text-brand-600 font-semibold">• ${d.sede_nombre}</span>`
                                : '';
                            return `
                                <div>
                                    <span class="block font-bold text-slate-800">${d.paciente_nombre}</span>
                                    <span class="block text-xs text-slate-500">${d.paciente_documento} • <strong class="text-slate-700">${d.clinica_nombre}</strong>${sedeHtml}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Prótesis y Cantidades", field: "servicio_nombre", minWidth: 190,
                        formatter: (cell) => {
                            const d = cell.getData();
                            const cantProtesis = d.cantidad_protesis || 1;
                            return `
                                <div>
                                    <span class="block font-semibold text-slate-800 text-xs">${d.servicio_nombre}</span>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="px-2 py-0.5 rounded bg-brand-50 text-brand-700 text-[11px] font-bold border border-brand-100">
                                            ${cantProtesis} ${cantProtesis == 1 ? 'prótesis' : 'prótesis'}
                                        </span>
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px] font-bold">
                                            ${d.cantidad_piezas_exacta} ${d.cantidad_piezas_exacta == 1 ? 'pza' : 'pzs'}
                                        </span>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Etapa de Producción", field: "etapa_nombre", width: 185, hozAlign: "center",
                        formatter: (cell) => {
                            const d = cell.getData();
                            const estilosEtapa = {
                                1: 'bg-sky-50 text-sky-700 border-sky-200',
                                2: 'bg-amber-50 text-amber-700 border-amber-200',
                                3: 'bg-purple-50 text-purple-700 border-purple-200',
                                4: 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            };
                            const badgeClass = estilosEtapa[d.etapa_produccion_id] || 'bg-slate-100 text-slate-700 border-slate-200';
                            return `
                                <div class="flex flex-col items-center">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border ${badgeClass}">
                                        ${d.etapa_orden || d.etapa_produccion_id}. ${d.etapa_nombre}
                                    </span>
                                    <span class="text-[10px] font-semibold text-slate-400 mt-1">${d.estatus_actual}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Protesista / Responsable", field: "protesista_nombre", minWidth: 175,
                        formatter: (cell) => {
                            const d = cell.getData();
                            const nombre = d.protesista_nombre || d.responsable_nombre || 'Sin asignar';
                            const rol = d.protesista_nombre ? 'Protesista' : (d.responsable_rol || 'Laboratorio');
                            const inicial = nombre.substring(0, 1);
                            return `
                                <div class="flex items-center space-x-2">
                                    <div class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-bold flex-shrink-0">${inicial}</div>
                                    <div class="truncate">
                                        <span class="block text-xs font-bold text-slate-700 truncate">${nombre}</span>
                                        <span class="block text-[10px] text-slate-400 uppercase">${rol}</span>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Ubicación Física", field: "ubicacion_fisica", minWidth: 185,
                        formatter: (cell) => {
                            const d = cell.getData();
                            const ubic = d.ubicacion_fisica || 'RECEPCIÓN LABORATORIO';
                            const tagEnvio = d.envio_codigo 
                                ? `<span class="block text-[10px] font-mono font-bold text-amber-700 mt-0.5">Caja: #${d.envio_codigo} (${d.envio_estatus})</span>` 
                                : '';
                            return `
                                <div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 mr-1.5 text-brand-600"></i>${ubic}
                                    </span>
                                    ${tagEnvio}
                                </div>
                            `;
                        }
                    },
                {
                    title: "Acciones", width: 175, hozAlign: "center", headerSort: false, resizable: false, responsive: 0,
                    formatter: function(cell, formatterParams) {
                        const id = cell.getData().id;
                        return `
                            <div class="flex items-center justify-center space-x-1">
                                <button class="btn-procesar-caso px-2.5 py-1.5 bg-brand-50 text-brand-700 hover:bg-brand-100 rounded-lg transition-colors text-xs font-bold flex items-center space-x-1" title="Cambiar Etapa / Responsable / Ubicación" data-id="${id}">
                                    <i data-lucide="workflow" class="w-3.5 h-3.5"></i>
                                    <span>Procesar</span>
                                </button>
                                <button class="btn-imprimir-qr p-1.5 text-amber-700 bg-amber-50 hover:bg-[#FFCC00]/50 border border-amber-200 rounded-lg transition-colors" title="Imprimir Etiqueta QR Caja Pequeña" data-id="${id}">
                                    <i data-lucide="qr-code" class="w-4 h-4 text-[#D40511]"></i>
                                </button>
                                <button class="btn-eliminar-caso p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Eliminar Caso" data-id="${id}">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            rowFormatter: function(row) {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons({
                        root: row.getElement()
                    });
                }
            }
        });

            // Eventos de filtros
            document.getElementById("buscar-caso").addEventListener("input", aplicarFiltrosCasos);
            document.getElementById("filtro-etapa").addEventListener("change", aplicarFiltrosCasos);
            document.getElementById("filtro-ubicacion").addEventListener("change", aplicarFiltrosCasos);
            document.getElementById("filtro-responsable").addEventListener("change", aplicarFiltrosCasos);

            // Eventos en la tabla
            document.getElementById("casos-table").addEventListener("click", function(e) {
                const btnProcesar = e.target.closest(".btn-procesar-caso");
                const btnImprimirQr = e.target.closest(".btn-imprimir-qr");
                const btnEliminar = e.target.closest(".btn-eliminar-caso");

                if (btnProcesar) abrirModalProcesar(btnProcesar.getAttribute("data-id"));
                if (btnImprimirQr) imprimirEtiquetaCajaPequena(btnImprimirQr.getAttribute("data-id"));
                if (btnEliminar) abrirEliminarCasoModal(btnEliminar.getAttribute("data-id"));
            });

            // Buscador de pacientes dentro del modal
            document.getElementById("buscar_paciente_modal").addEventListener("input", function(e) {
                renderizarPacientesModal(e.target.value);
            });

            // Submit Crear Caso
            document.getElementById("casoForm").addEventListener("submit", async function(e) {
                e.preventDefault();

                if (!document.getElementById("caso_paciente").value) {
                    notificar('warning', 'Por favor selecciona un paciente de la lista antes de guardar.');
                    return;
                }

                const btn = document.getElementById("btnGuardarCaso");
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Guardando...</span>`;
                lucide.createIcons();

                const fd = new FormData(this);
                try {
                    const res = await fetch('/agregar-caso-controller', { method: 'POST', body: fd });
                    const result = await res.json();
                    cerrarModalCaso();
                    setTimeout(() => {
                        if (result.status === 'success') {
                            notificar('success', result.message || 'Caso registrado exitosamente.');
                            tableCasos.setData();
                        } else {
                            notificar('error', result.message || 'No se pudo guardar el caso.');
                        }
                    }, 300);
                } catch (err) {
                    cerrarModalCaso();
                    setTimeout(() => notificar('error', 'Error de conexión con el servidor.'), 300);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            });

            // Submit Procesar Caso (Cambio de Etapa, Usuario Responsable y Ubicación Física)
            document.getElementById("procesarForm").addEventListener("submit", async function(e) {
                e.preventDefault();
                const btn = document.getElementById("btnGuardarProceso");
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Actualizando...</span>`;
                lucide.createIcons();

                const fd = new FormData(this);
                fd.append('action', 'procesar_caso');

                try {
                    const res = await fetch('/procesar-caso-controller', { method: 'POST', body: fd });
                    const result = await res.json();
                    if (result.status === 'success') {
                        document.getElementById("proc_notas").value = "";
                        await abrirModalProcesar(fd.get('orden_trabajo_id')); // Recarga historial en vivo
                        tableCasos.setData();
                        notificar('success', result.message || 'Trazabilidad, etapa y ubicación actualizadas.');
                    } else {
                        notificar('error', result.message || 'No se pudo actualizar el caso.');
                    }
                } catch (err) {
                    notificar('error', 'Error de red al procesar el caso.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            });
        });

        function actualizarContadoresKPI(datos) {
            const conteo = { 1: 0, 2: 0, 3: 0, 4: 0 };
            datos.forEach(d => {
                const et = parseInt(d.etapa_produccion_id);
                if (conteo[et] !== undefined) conteo[et]++;
            });
            document.getElementById("kpi-etapa-1").textContent = conteo[1];
            document.getElementById("kpi-etapa-2").textContent = conteo[2];
            document.getElementById("kpi-etapa-3").textContent = conteo[3];
            document.getElementById("kpi-etapa-4").textContent = conteo[4];
        }

        function filtrarPorEtapaRapida(etapaId) {
            const sel = document.getElementById("filtro-etapa");
            sel.value = (sel.value === etapaId) ? "" : etapaId;
            aplicarFiltrosCasos();
        }

        function aplicarFiltrosCasos() {
            const term = document.getElementById("buscar-caso").value.toLowerCase();
            const etapa = document.getElementById("filtro-etapa").value;
            const ubicacion = document.getElementById("filtro-ubicacion").value;
            const responsable = document.getElementById("filtro-responsable").value;

            tableCasos.setFilter(function(data) {
                const matchTerm = !term || (
                    (data.codigo_qr && data.codigo_qr.toLowerCase().includes(term)) ||
                    (data.paciente_nombre && data.paciente_nombre.toLowerCase().includes(term)) ||
                    (data.paciente_documento && data.paciente_documento.toLowerCase().includes(term)) ||
                    (data.clinica_nombre && data.clinica_nombre.toLowerCase().includes(term)) ||
                    (data.sede_nombre && data.sede_nombre.toLowerCase().includes(term)) ||
                    (data.servicio_nombre && data.servicio_nombre.toLowerCase().includes(term)) ||
                    (data.protesista_nombre && data.protesista_nombre.toLowerCase().includes(term)) ||
                    (data.responsable_nombre && data.responsable_nombre.toLowerCase().includes(term))
                );
                const matchEtapa = !etapa || String(data.etapa_produccion_id) === String(etapa);
                const matchUbic = !ubicacion || (data.ubicacion_fisica && data.ubicacion_fisica.toUpperCase().includes(ubicacion.toUpperCase()));
                const matchResp = !responsable || String(data.responsable_id) === String(responsable) || String(data.protesista_id) === String(responsable);

                return matchTerm && matchEtapa && matchUbic && matchResp;
            });
        }

        // =========================================================================
        // CARGA DE SELECTORES (CLÍNICAS, SEDES, PACIENTES, SERVICIOS, USUARIOS)
        // =========================================================================
        async function cargarCatalogosFormulario() {
            try {
                const fd = new FormData();
                fd.append('action', 'cargar_catalogos');
                const res = await fetch('/obtener-casos-controller', { method: 'POST', body: fd });
                const result = await res.json();

                if (result.status !== 'success') return;
                catalogos = result;

                // Clínicas
                const selClinica = document.getElementById("caso_clinica");
                selClinica.innerHTML = '<option value="">Seleccione clínica...</option>';
                (catalogos.clinicas || []).forEach(c => {
                    selClinica.innerHTML += `<option value="${c.id}">${c.razon_social}</option>`;
                });

                // Cambio de clínica -> filtrar sedes
                selClinica.addEventListener('change', function() {
                    filtrarSedesPorClinica(this.value);
                });

                // Servicios / Prótesis
                const selServicio = document.getElementById("caso_servicio");
                selServicio.innerHTML = '<option value="">Seleccione servicio/prótesis...</option>';
                (catalogos.servicios || []).forEach(s => {
                    selServicio.innerHTML += `<option value="${s.id}" data-min="${s.rango_min_piezas}" data-max="${s.rango_max_piezas}">${s.nombre} (${s.rango_min_piezas}-${s.rango_max_piezas} pzs)</option>`;
                });

                // Protesistas y Responsables
                const selProtesista = document.getElementById("caso_protesista");
                const selProcUsuario = document.getElementById("proc_usuario");
                const selFiltroResp = document.getElementById("filtro-responsable");

                selProtesista.innerHTML = '<option value="">Seleccione protesista...</option>';
                selProcUsuario.innerHTML = '<option value="">Seleccione responsable...</option>';
                selFiltroResp.innerHTML = '<option value="">Todos los responsables</option>';

                const listaProtesistas = (catalogos.protesistas && catalogos.protesistas.length > 0)
                    ? catalogos.protesistas
                    : (catalogos.usuarios || []).filter(u => (u.rol && u.rol.toUpperCase().includes('PROTESISTA')) || u.rol_id == 4);
                const protesistasParaMostrar = listaProtesistas.length > 0 ? listaProtesistas : (catalogos.usuarios || []);

                protesistasParaMostrar.forEach(u => {
                    selProtesista.innerHTML += `<option value="${u.id}">${u.nombre_completo} — ${u.rol || 'PROTESISTA'}</option>`;
                });

                (catalogos.usuarios || []).forEach(u => {
                    const opt = `<option value="${u.id}">${u.nombre_completo} — ${u.rol}</option>`;
                    selProcUsuario.innerHTML += opt;
                    selFiltroResp.innerHTML += `<option value="${u.id}">${u.nombre_completo}</option>`;
                });

                // Sincronizar hidden usuario_id con el protesista seleccionado
                selProtesista.addEventListener('change', function() {
                    document.getElementById("caso_usuario").value = this.value;
                });

                // Renderizar tabla de pacientes en el modal
                renderizarPacientesModal();

            } catch (e) {
                console.error("Error al cargar catálogos:", e);
            }
        }

        function filtrarSedesPorClinica(clinicaId, sedeIdSeleccionada = null) {
            const selSede = document.getElementById("caso_sede");
            selSede.innerHTML = '<option value="">Todas las sedes / Sede principal</option>';
            if (!clinicaId) return;

            const sedesFiltradas = (catalogos.sedes || []).filter(s => String(s.clinica_id) === String(clinicaId));
            sedesFiltradas.forEach(s => {
                const principalTxt = s.principal == 1 ? ' (Principal)' : '';
                const selected = (sedeIdSeleccionada && String(sedeIdSeleccionada) === String(s.id)) ? 'selected' : '';
                selSede.innerHTML += `<option value="${s.id}" ${selected}>${s.nombre}${principalTxt}</option>`;
            });
        }

        function renderizarPacientesModal(filtro = '') {
            const tbody = document.getElementById("tabla_pacientes_modal_body");
            if (!tbody) return;
            tbody.innerHTML = '';

            const term = (filtro || '').toLowerCase().trim();
            const pacientes = (catalogos.pacientes || []).filter(p => {
                if (!term) return true;
                const nombre = (p.nombre_completo || '').toLowerCase();
                const doc = (p.numero_documento || '').toLowerCase();
                const clinica = (p.clinica_nombre || '').toLowerCase();
                return nombre.includes(term) || doc.includes(term) || clinica.includes(term);
            });

            if (pacientes.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="py-4 text-center text-slate-400 text-xs">No se encontraron pacientes que coincidan con la búsqueda.</td></tr>`;
                return;
            }

            pacientes.forEach(p => {
                const esSeleccionado = pacienteSeleccionado && pacienteSeleccionado.id === p.id;
                const rowClass = esSeleccionado ? 'bg-brand-50/70 font-semibold' : 'hover:bg-slate-50';
                const clinicaSedeTxt = p.clinica_nombre 
                    ? `<span class="font-medium text-slate-700">${p.clinica_nombre}</span>${p.sede_nombre ? '<span class="text-slate-400 block text-[10px]">' + p.sede_nombre + '</span>' : ''}`
                    : '<span class="text-slate-400 italic">Sin clínica</span>';

                tbody.innerHTML += `
                    <tr class="${rowClass} transition-colors">
                        <td class="py-2.5 px-3">
                            <span class="block font-bold text-slate-800">${p.nombre_completo}</span>
                        </td>
                        <td class="py-2.5 px-3 font-mono text-slate-600">${p.tipo_documento}-${p.numero_documento}</td>
                        <td class="py-2.5 px-3 hidden sm:table-cell text-slate-500 uppercase">${p.sexo || '-'}</td>
                        <td class="py-2.5 px-3 text-slate-600">${clinicaSedeTxt}</td>
                        <td class="py-2.5 px-3 text-right">
                            <button type="button" onclick="seleccionarPacienteModal(${p.id})"
                                class="px-2.5 py-1 text-xs font-bold rounded-lg transition-all ${
                                    esSeleccionado 
                                    ? 'bg-emerald-600 text-white shadow-sm' 
                                    : 'bg-brand-50 text-brand-700 hover:bg-brand-600 hover:text-white border border-brand-200'
                                }">
                                ${esSeleccionado ? '✓ Elegido' : 'Seleccionar'}
                            </button>
                        </td>
                    </tr>
                `;
            });
        }

        function seleccionarPacienteModal(id) {
            const p = (catalogos.pacientes || []).find(pac => pac.id === id);
            if (!p) return;

            pacienteSeleccionado = p;
            document.getElementById("caso_paciente").value = p.id;

            const badge = document.getElementById("paciente_badge_seleccion");
            badge.classList.remove("hidden");
            badge.classList.add("flex");

            const clinicaTxt = p.clinica_nombre 
                ? `<strong class="text-slate-700">${p.clinica_nombre}</strong>${p.sede_nombre ? ' • Sede: ' + p.sede_nombre : ''}` 
                : '<span class="text-amber-600">Sin clínica asignada</span>';

            const tarjeta = document.getElementById("tarjeta_paciente_seleccionado");
            tarjeta.className = "p-3 bg-brand-50/60 border border-brand-200 rounded-xl flex items-center justify-between text-xs";
            tarjeta.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                        ${(p.primer_nombre || 'P').substring(0, 1)}
                    </div>
                    <div>
                        <span class="block font-bold text-slate-800 text-sm">${p.nombre_completo}</span>
                        <span class="block text-slate-500">${p.tipo_documento}-${p.numero_documento} • Sexo: <span class="uppercase">${p.sexo || '-'}</span> • ${clinicaTxt}</span>
                    </div>
                </div>
                <button type="button" onclick="deseleccionarPacienteModal()" class="text-slate-400 hover:text-rose-500 p-1.5 rounded-lg hover:bg-white" title="Quitar selección">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            `;
            lucide.createIcons();

            // Si el paciente tiene clínica/sede asignadas, preseleccionarlas en el formulario
            if (p.clinica_id) {
                document.getElementById("caso_clinica").value = p.clinica_id;
                filtrarSedesPorClinica(p.clinica_id, p.sede_id);
            }

            renderizarPacientesModal(document.getElementById("buscar_paciente_modal").value);
        }

        function deseleccionarPacienteModal() {
            pacienteSeleccionado = null;
            document.getElementById("caso_paciente").value = "";
            const badge = document.getElementById("paciente_badge_seleccion");
            badge.classList.add("hidden");
            badge.classList.remove("flex");

            const tarjeta = document.getElementById("tarjeta_paciente_seleccionado");
            tarjeta.className = "p-3 bg-white border border-dashed border-slate-300 rounded-xl flex items-center justify-between text-slate-500 text-xs";
            tarjeta.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center font-bold text-sm">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="block font-semibold text-slate-700">Ningún paciente seleccionado</span>
                        <span class="text-[11px] text-slate-400">Usa el buscador y haz clic en "Seleccionar" en la tabla</span>
                    </div>
                </div>
            `;
            lucide.createIcons();
            renderizarPacientesModal(document.getElementById("buscar_paciente_modal").value);
        }

        function ajustarRangoPiezas(selectEl) {
            const opt = selectEl.options[selectEl.selectedIndex];
            const inputPiezas = document.getElementById("caso_piezas");
            if (opt && opt.getAttribute('data-min')) {
                const min = parseInt(opt.getAttribute('data-min')) || 1;
                const max = parseInt(opt.getAttribute('data-max')) || 32;
                inputPiezas.min = min;
                inputPiezas.max = max;
                inputPiezas.value = min;
            }
        }

        // =========================================================================
        // MODAL CREAR CASO
        // =========================================================================
        function abrirModalCrearCaso() {
            document.getElementById("casoForm").reset();
            document.getElementById("caso_id").value = "";
            document.getElementById("caso_cantidad_protesis").value = "1";
            document.getElementById("caso_piezas").value = "1";
            deseleccionarPacienteModal();
            filtrarSedesPorClinica(null);

            const modal = document.getElementById("casoModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("modalCasoContainer").classList.remove("scale-95");
            }, 10);
        }

        function cerrarModalCaso() {
            const modal = document.getElementById("casoModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalCasoContainer").classList.add("scale-95");
            setTimeout(() => { modal.classList.add("hidden"); }, 300);
        }

        // =========================================================================
        // MODAL PROCESAR CASO Y VER HISTORIAL DE TRAZABILIDAD
        // =========================================================================
        async function abrirModalProcesar(id) {
            try {
                const fd = new FormData();
                fd.append('action', 'detalle_caso');
                fd.append('id', id);

                const res = await fetch('/obtener-casos-controller', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.status !== 'success' || !data.caso) {
                    notificar('error', data.message || 'No se pudo cargar la información del caso.');
                    return;
                }

                const c = data.caso;
                document.getElementById("proc_orden_id").value = c.id;
                document.getElementById("proc_codigo_badge").textContent = `#${c.codigo_qr}`;
                document.getElementById("proc_subtitulo").textContent = `${c.paciente_nombre} (${c.paciente_documento}) • Clínica: ${c.clinica_nombre} • Prótesis: ${c.servicio_nombre}`;

                document.getElementById("proc_etapa").value = c.etapa_produccion_id;
                document.getElementById("proc_usuario").value = c.responsable_id || "";
                document.getElementById("proc_ubicacion").value = c.ubicacion_fisica || "RECEPCIÓN LABORATORIO";
                document.getElementById("proc_estatus").value = c.estatus_actual;

                // Badge si está dentro de una caja de envío
                const badgeEnvio = document.getElementById("proc_caja_envio");
                if (c.envio_codigo) {
                    badgeEnvio.textContent = `Empacado en Caja #${c.envio_codigo}`;
                    badgeEnvio.classList.remove("hidden");
                } else {
                    badgeEnvio.classList.add("hidden");
                }

                // Actualizar barra de 4 etapas
                for (let i = 1; i <= 4; i++) {
                    const stepEl = document.getElementById(`step-${i}`);
                    if (i < c.etapa_produccion_id) {
                        stepEl.className = "p-2 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 text-center";
                    } else if (i == c.etapa_produccion_id) {
                        stepEl.className = "p-2 rounded-xl border-2 border-brand-500 bg-brand-50 text-brand-700 text-center shadow-sm";
                    } else {
                        stepEl.className = "p-2 rounded-xl border border-slate-200 bg-white text-slate-400 text-center opacity-70";
                    }
                }

                // Renderizar bitácora de trazabilidad
                const contenedorHist = document.getElementById("contenedor-trazabilidad");
                contenedorHist.innerHTML = '';

                if (data.historial && data.historial.length > 0) {
                    data.historial.forEach((h, idx) => {
                        const esActual = idx === 0;
                        contenedorHist.innerHTML += `
                            <div class="p-3.5 rounded-xl border ${esActual ? 'bg-white border-brand-200 shadow-sm' : 'bg-white/60 border-slate-200/70'}">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold ${esActual ? 'text-brand-700' : 'text-slate-700'}">
                                        ${h.estatus}
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-400">${h.fecha_cambio}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs text-slate-600">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 font-semibold">
                                        <i data-lucide="user-check" class="w-3 h-3 mr-1 text-brand-600"></i>Resp: ${h.usuario_nombre}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 font-semibold">
                                        <i data-lucide="map-pin" class="w-3 h-3 mr-1 text-amber-600"></i>Ubicación: ${h.ubicacion}
                                    </span>
                                </div>
                                ${h.notas ? `<p class="text-xs text-slate-500 mt-2 italic">"${h.notas}"</p>` : ''}
                            </div>
                        `;
                    });
                } else {
                    contenedorHist.innerHTML = `<p class="text-xs text-slate-400 text-center py-8">Sin movimientos registrados aún.</p>`;
                }

                const modal = document.getElementById("procesarModal");
                modal.classList.remove("hidden");
                setTimeout(() => {
                    modal.classList.remove("opacity-0");
                    document.getElementById("modalProcesarContainer").classList.remove("scale-95");
                    lucide.createIcons();
                }, 10);

            } catch (e) {
                notificar('error', 'Error al consultar la trazabilidad del caso.');
            }
        }

        function cerrarModalProcesar() {
            const modal = document.getElementById("procesarModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalProcesarContainer").classList.add("scale-95");
            setTimeout(() => { modal.classList.add("hidden"); }, 300);
        }

        // =========================================================================
        // ELIMINAR CASO
        // =========================================================================
        function abrirEliminarCasoModal(id) {
            idCasoParaEliminar = id;
            const modal = document.getElementById("eliminarCasoModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("eliminarCasoModalContainer").classList.remove("scale-95");
            }, 10);
        }

        function cerrarEliminarCasoModal() {
            const modal = document.getElementById("eliminarCasoModal");
            modal.classList.add("opacity-0");
            document.getElementById("eliminarCasoModalContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
                idCasoParaEliminar = null;
            }, 300);
        }

        document.getElementById("btnConfirmarEliminarCaso").addEventListener("click", async function() {
            if (!idCasoParaEliminar) return;
            const btn = this;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin mx-auto"></i>`;
            lucide.createIcons();

            const fd = new FormData();
            fd.append('action', 'eliminar_caso');
            fd.append('id', idCasoParaEliminar);

            try {
                const res = await fetch('/procesar-caso-controller', { method: 'POST', body: fd });
                const result = await res.json();
                cerrarEliminarCasoModal();
                setTimeout(() => {
                    if (result.status === 'success') {
                        notificar('success', result.message || 'Caso eliminado correctamente.');
                        tableCasos.setData();
                    } else {
                        notificar('error', result.message || 'No se pudo eliminar el caso.');
                    }
                }, 300);
            } catch (e) {
                cerrarEliminarCasoModal();
                setTimeout(() => notificar('error', 'Error de red al eliminar.'), 300);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });

        // =========================================================================
        // ESCÁNER QR INTERACTIVO (CÁMARA / MANUAL)
        // =========================================================================
        let qrScannerInstance = null;

        function abrirEscanerQR() {
            const m = document.getElementById("modalEscanerQR");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
                iniciarCamaraQR();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 10);
        }

        function cerrarModalEscanerQR() {
            detenerCamaraQR();
            const m = document.getElementById("modalEscanerQR");
            m.classList.add("opacity-0");
            m.firstElementChild.classList.add("scale-95");
            setTimeout(() => {
                m.classList.add("hidden");
                document.getElementById("resultado-escaneo").classList.add("hidden");
                document.getElementById("input-escaner-manual").value = "";
            }, 200);
        }

        function iniciarCamaraQR() {
            if (typeof Html5Qrcode === 'undefined') {
                console.warn("Html5Qrcode no está cargado.");
                return;
            }
            if (!qrScannerInstance) {
                qrScannerInstance = new Html5Qrcode("qr-reader");
            }

            document.getElementById("qr-placeholder").style.display = 'none';

            if (!qrScannerInstance.isScanning) {
                qrScannerInstance.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 220, height: 220 } },
                    (decodedText) => {
                        procesarCodigoEscaneado(decodedText);
                    },
                    (errorMessage) => {
                        // ignore scan errors
                    }
                ).catch(err => {
                    console.log("No se pudo iniciar cámara o permiso denegado:", err);
                    document.getElementById("qr-placeholder").style.display = 'block';
                    document.getElementById("qr-placeholder").innerHTML = `
                        <i data-lucide="alert-circle" class="w-8 h-8 mx-auto text-amber-500 mb-1"></i>
                        <p class="text-xs text-amber-300 font-bold">Cámara no disponible o permiso denegado.</p>
                        <p class="text-[11px] text-slate-400 mt-1">Puedes escribir o pegar el código abajo.</p>
                    `;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        }

        function detenerCamaraQR() {
            if (qrScannerInstance) {
                qrScannerInstance.stop().then(() => {
                    qrScannerInstance.clear();
                    qrScannerInstance = null;
                }).catch(e => console.log(e));
            }
        }

        function procesarEscaneoManual() {
            const val = document.getElementById("input-escaner-manual").value.trim();
            if (val) procesarCodigoEscaneado(val);
        }

        async function procesarCodigoEscaneado(codigoRaw) {
            try {
                const fd = new FormData();
                fd.append('action', 'escanear_qr');
                fd.append('codigo_qr', codigoRaw);

                const res = await fetch('/tracking-controller', { method: 'POST', body: fd });
                const json = await res.json();

                const box = document.getElementById("resultado-escaneo");
                box.classList.remove("hidden");

                if (json.status === 'success') {
                    if (json.tipo === 'CAJA_PRINCIPAL') {
                        const e = json.envio;
                        box.className = "rounded-xl border-2 border-[#FFCC00] bg-amber-50/80 p-4 space-y-3";
                        box.innerHTML = `
                            <div class="flex items-center justify-between border-b border-amber-200 pb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-[#FFCC00] text-slate-900">CAJA PRINCIPAL ENCONTRADA</span>
                                <strong class="font-mono text-sm text-slate-900">${e.codigo_qr}</strong>
                            </div>
                            <div class="text-xs space-y-1 text-slate-700">
                                <p><strong>Destino:</strong> ${e.clinica_nombre} - ${e.sede_nombre}</p>
                                <p><strong>Repartidor:</strong> ${e.repartidor_nombre} (${e.placa_vehiculo})</p>
                                <p><strong>Estado Actual:</strong> <span class="font-bold text-amber-800">${e.estatus_envio}</span> &bull; ${json.casos.length} caso(s) dentro</p>
                            </div>
                            <div class="pt-2 flex flex-wrap gap-2 border-t border-amber-200">
                                <button onclick="ejecutarActualizacionQR('CAJA_PRINCIPAL', ${e.id}, 'ABIERTO EN CLÍNICA')" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                                    <i data-lucide="package-open" class="w-3.5 h-3.5"></i>
                                    <span>Abrir Caja en Clínica</span>
                                </button>
                                <button onclick="ejecutarActualizacionQR('CAJA_PRINCIPAL', ${e.id}, 'RECIBIDO')" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Confirmar Recepción</span>
                                </button>
                            </div>
                        `;
                    } else if (json.tipo === 'CAJA_PEQUENA') {
                        const c = json.caso;
                        box.className = "rounded-xl border-2 border-indigo-500 bg-indigo-50/80 p-4 space-y-3";
                        box.innerHTML = `
                            <div class="flex items-center justify-between border-b border-indigo-200 pb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-indigo-600 text-white">CAJA PEQUEÑA (CASO DENTAL)</span>
                                <strong class="font-mono text-sm text-indigo-950">${c.codigo_qr}</strong>
                            </div>
                            <div class="text-xs space-y-1 text-slate-700">
                                <p><strong>Paciente:</strong> ${c.paciente_nombre} (${c.paciente_documento})</p>
                                <p><strong>Trabajo:</strong> ${c.servicio_nombre || 'Prótesis Dental'} &bull; ${c.cantidad_piezas_exacta} pieza(s)</p>
                                <p><strong>Clínica:</strong> ${c.clinica_nombre}</p>
                                <p><strong>Estado Actual:</strong> <span class="font-bold text-indigo-800">${c.estatus_actual}</span></p>
                            </div>
                            <div class="pt-2 flex flex-wrap gap-2 border-t border-indigo-200">
                                <button onclick="ejecutarActualizacionQR('CAJA_PEQUENA', ${c.id}, 'RECIBIDO EN LABORATORIO')" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                                    <i data-lucide="box" class="w-3.5 h-3.5"></i>
                                    <span>Recibir en Lab</span>
                                </button>
                                <button onclick="ejecutarActualizacionQR('CAJA_PEQUENA', ${c.id}, 'ABIERTO POR ODONTÓLOGO')" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                                    <i data-lucide="folder-check" class="w-3.5 h-3.5"></i>
                                    <span>Abrir en Consultorio</span>
                                </button>
                            </div>
                        `;
                    }
                } else {
                    box.className = "rounded-xl border border-rose-300 bg-rose-50 p-4 text-xs text-rose-700 font-semibold";
                    box.innerHTML = `<i data-lucide="alert-triangle" class="w-4 h-4 inline mr-1"></i> ${json.message}`;
                }
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } catch (err) {
                console.error("Error al procesar escaneo:", err);
            }
        }

        async function ejecutarActualizacionQR(tipo, id, nuevoEstatus) {
            try {
                const fd = new FormData();
                fd.append('action', 'actualizar_estatus_escaneo');
                fd.append('tipo', tipo);
                fd.append('id', id);
                fd.append('nuevo_estatus', nuevoEstatus);
                fd.append('notas', `Actualizado mediante Escaneo QR desde Casos`);

                const res = await fetch('/tracking-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    notificar('success', json.message);
                    cerrarModalEscanerQR();
                    if (tableCasos) tableCasos.setData();
                } else {
                    notificar('error', json.message || 'No se pudo actualizar.');
                }
            } catch (err) {
                console.error("Error al actualizar:", err);
            }
        }

        // =========================================================================
        // IMPRESIÓN DE ETIQUETAS QR (CAJA PRINCIPAL Y PEQUEÑA)
        // =========================================================================
        async function imprimirEtiquetaCajaPrincipal(envioId) {
            try {
                const fd = new FormData();
                fd.append('action', 'etiqueta_caja_principal');
                fd.append('envio_id', envioId);

                const res = await fetch('/tracking-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    const e = json.envio;
                    document.getElementById("eti-tipo-badge").textContent = "CAJA PRINCIPAL (MASTER)";
                    document.getElementById("eti-tipo-badge").className = "text-[9px] font-black uppercase px-2 py-0.5 rounded bg-slate-900 text-white";
                    document.getElementById("eti-codigo-texto").textContent = e.codigo_qr;
                    document.getElementById("eti-clinica").textContent = e.clinica_nombre;
                    document.getElementById("eti-sede").textContent = e.sede_nombre;
                    document.getElementById("eti-row-sede").classList.remove("hidden");

                    document.getElementById("eti-label-extra1").textContent = "Repartidor:";
                    document.getElementById("eti-val-extra1").textContent = `${e.repartidor_nombre} (${e.placa_vehiculo})`;

                    document.getElementById("eti-label-extra2").textContent = "Contenido:";
                    document.getElementById("eti-val-extra2").textContent = `${json.casos.length} Cajas Pequeñas`;

                    document.getElementById("eti-fecha").textContent = e.fecha_salida_fmt || '13/10/2026';

                    generarCodigoQREnEtiqueta(e.codigo_qr);
                    abrirModalEtiquetaQR();
                } else {
                    notificar('error', json.message || 'Error al cargar etiqueta.');
                }
            } catch (err) {
                console.error("Error al cargar etiqueta:", err);
            }
        }

        async function imprimirEtiquetaCajaPequena(ordenId) {
            try {
                const fd = new FormData();
                fd.append('action', 'etiqueta_caja_pequena');
                fd.append('orden_id', ordenId);

                const res = await fetch('/tracking-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    const c = json.caso;
                    document.getElementById("eti-tipo-badge").textContent = "CAJA PEQUEÑA (CASO DENTAL)";
                    document.getElementById("eti-tipo-badge").className = "text-[9px] font-black uppercase px-2 py-0.5 rounded bg-[#D40511] text-white";
                    document.getElementById("eti-codigo-texto").textContent = c.codigo_qr;
                    document.getElementById("eti-clinica").textContent = c.clinica_nombre;
                    document.getElementById("eti-row-sede").classList.add("hidden");

                    document.getElementById("eti-label-extra1").textContent = "Paciente:";
                    document.getElementById("eti-val-extra1").textContent = `${c.paciente_nombre} (${c.paciente_documento})`;

                    document.getElementById("eti-label-extra2").textContent = "Trabajo Dental:";
                    document.getElementById("eti-val-extra2").textContent = `${c.servicio_nombre || 'Prótesis'} (${c.cantidad_piezas_exacta} pz)`;

                    document.getElementById("eti-fecha").textContent = c.fecha_ingreso_fmt || '13/10/2026';

                    generarCodigoQREnEtiqueta(c.codigo_qr);
                    abrirModalEtiquetaQR();
                } else {
                    notificar('error', json.message || 'Error al cargar etiqueta del caso.');
                }
            } catch (err) {
                console.error("Error al cargar etiqueta:", err);
            }
        }

        function generarCodigoQREnEtiqueta(texto) {
            const container = document.getElementById("eti-qrcode-container");
            container.innerHTML = '';
            if (typeof QRCode !== 'undefined') {
                new QRCode(container, {
                    text: texto,
                    width: 140,
                    height: 140,
                    colorDark: "#0f172a",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            } else {
                container.innerHTML = `<span class='font-mono font-bold text-xs p-4 block'>${texto}</span>`;
            }
        }

        function abrirModalEtiquetaQR() {
            const m = document.getElementById("modalEtiquetaQR");
            m.classList.remove("hidden");
            setTimeout(() => {
                m.classList.remove("opacity-0");
                m.firstElementChild.classList.remove("scale-95");
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 10);
        }

        function cerrarModalEtiquetaQR() {
            const m = document.getElementById("modalEtiquetaQR");
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
                    <title>Etiqueta de Caso Dental - CNSB Lab</title>
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
    </script>
</body>
</html>