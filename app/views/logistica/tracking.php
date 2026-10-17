<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../helpers/auth.php';

// Permite acceso a usuarios autenticados
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

        <!-- Top Navbar CNSB Dental -->
        <header
            class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-8 z-10 flex-shrink-0 relative">
            <!-- Franja distintiva CNSB -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-600 via-indigo-600 to-sky-500">
            </div>

            <div class="flex items-center space-x-3 mt-1">
                <div
                    class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold shadow-sm border border-brand-100">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Tracking & Rastreabilidad
                        </h1>
                        <span
                            class="hidden sm:inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-brand-50 text-brand-700 border border-brand-200">
                            CNSB Logistics
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-3 md:space-x-4 hidden sm:flex mt-1">
                <span class="text-sm font-medium text-slate-600">Hola,
                    <strong><?= htmlspecialchars(Auth::name() ?? 'Usuario') ?></strong></span>
                <div
                    class="h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                    <?= htmlspecialchars(substr(Auth::name() ?? 'U', 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Main View Scrollable Area -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden bg-slate-50/50 p-4 md:p-8 space-y-6">

            <!-- KPIs Header Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <div
                    class="bg-white p-5 rounded-2xl border-l-4 border-l-amber-500 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">En Tránsito</p>
                        <h3 id="kpi-transito" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-amber-700 font-semibold mt-0.5 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            En ruta con repartidores
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="truck" class="w-6 h-6"></i>
                    </div>
                </div>

                <div
                    class="bg-white p-5 rounded-2xl border-l-4 border-l-indigo-500 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Preparados en Lab</p>
                        <h3 id="kpi-preparados" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-indigo-600 font-medium mt-0.5">Listos para recolectar</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="package" class="w-6 h-6"></i>
                    </div>
                </div>

                <div
                    class="bg-white p-5 rounded-2xl border-l-4 border-l-emerald-500 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Entregados en Clínica</p>
                        <h3 id="kpi-entregados" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Recepción confirmada</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                    </div>
                </div>

                <div
                    class="bg-white p-5 rounded-2xl border-l-4 border-l-sky-500 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Casos Movilizados</p>
                        <h3 id="kpi-casos" class="text-2xl font-black text-slate-800 mt-1">0</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Cajas pequeñas en tránsito</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <i data-lucide="boxes" class="w-6 h-6"></i>
                    </div>
                </div>

            </div>

            <!-- Buscador y Centro de Escaneo Rápido QR -->
            <div
                class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-900 rounded-2xl shadow-lg p-6 text-white border-t-4 border-t-brand-500 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-5 pointer-events-none">
                    <i data-lucide="truck" class="w-72 h-72 text-white"></i>
                </div>

                <div class="max-w-3xl relative z-10">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase bg-brand-500 text-white shadow">
                            <i data-lucide="scan-line" class="w-3.5 h-3.5 text-white"></i>
                            Rastreo de Envíos y Casos
                        </span>
                        <span class="text-xs text-brand-200 font-mono tracking-wide">Cajas Principales y Casos
                            Dentales</span>
                    </div>

                    <h2 class="text-xl md:text-2xl font-black tracking-tight text-white">Rastreo y Validación de Códigos
                        QR</h2>
                    <p class="text-slate-300 text-xs sm:text-sm mt-1">
                        Ingresa el código de la <strong>Caja Principal</strong> (ej. <code
                            class="text-brand-300 font-bold">ENV-0001</code>) o de la <strong>Caja Pequeña /
                            Caso</strong> (ej. <code class="text-brand-300 font-bold">ORD-0001</code>), o utiliza la
                        cámara para escanear y actualizar su estatus.
                    </p>

                    <div class="mt-4 flex flex-col sm:flex-row gap-3">
                        <form id="form-rastreo-rapido" onsubmit="rastrearCodigoDirecto(event)"
                            class="flex-1 flex gap-2">
                            <div class="relative flex-1">
                                <i data-lucide="search"
                                    class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" id="input-rastreo" placeholder="Ej. ENV-0001 o ORD-0001..." required
                                    class="w-full pl-11 pr-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white/15 transition-all text-sm font-mono font-bold tracking-wider">
                            </div>
                            <button type="submit"
                                class="px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm transition-all shadow-md flex items-center justify-center gap-2 flex-shrink-0">
                                <i data-lucide="crosshair" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Rastrear</span>
                            </button>
                        </form>

                        <!-- Botón Escanear Cámara -->
                        <button type="button" onclick="abrirModalEscanerQR()"
                            class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm transition-all shadow-lg flex items-center justify-center gap-2 flex-shrink-0 border border-slate-700">
                            <i data-lucide="camera" class="w-4 h-4 text-brand-400"></i>
                            <span>Escanear con Cámara</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Panel de Trazabilidad Activa (Se muestra al seleccionar o rastrear un paquete) -->
            <div id="panel-trazabilidad"
                class="hidden bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all duration-300">
                <div
                    class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-brand-50 border border-brand-200 text-brand-600 flex items-center justify-center font-bold">
                            <i data-lucide="package" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 id="traz-codigo" class="text-lg font-black text-slate-800 font-mono tracking-wider">
                                    ENV-0000</h3>
                                <span id="traz-badge"
                                    class="px-2.5 py-0.5 rounded-full text-xs font-bold border">Estatus</span>
                            </div>
                            <p id="traz-destino" class="text-xs text-slate-500 mt-0.5">Destino: Clínica y Sede</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="imprimirEtiquetaActual()"
                            class="px-3.5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs flex items-center gap-1.5 transition-colors shadow-sm">
                            <i data-lucide="printer" class="w-3.5 h-3.5 text-white"></i>
                            <span>Imprimir Etiqueta QR</span>
                        </button>
                        <button onclick="abrirModalActualizarEstatus()"
                            class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200 flex items-center gap-1.5 transition-colors">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            <span>Actualizar Estatus</span>
                        </button>
                        <button onclick="cerrarPanelTrazabilidad()"
                            class="p-2 rounded-xl hover:bg-slate-200/70 text-slate-400 hover:text-slate-600 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-8">
                    <!-- Línea de Tiempo de 4 Pasos -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-6">Línea de Trazabilidad
                            del Envío</h4>
                        <div id="timeline-container" class="grid grid-cols-1 md:grid-cols-4 gap-4 relative">
                            <!-- Inyectado por JS -->
                        </div>
                    </div>

                    <!-- Datos del Transporte y Destino -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <!-- Repartidor -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 flex items-start gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center flex-shrink-0 border border-brand-200 font-bold">
                                <i data-lucide="bike" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] font-bold uppercase text-slate-400">Repartidor y Unidad</span>
                                <h5 id="traz-repartidor-nombre" class="text-sm font-bold text-slate-800 truncate">Nombre
                                    Repartidor</h5>
                                <p id="traz-repartidor-vehiculo" class="text-xs text-slate-600 mt-0.5">Vehículo / Placa
                                </p>
                                <div class="mt-2 flex items-center gap-2">
                                    <a id="traz-repartidor-tel" href="#"
                                        class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline font-semibold">
                                        <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                        <span>Llamar</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Clínica y Sede -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 flex items-start gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="building" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] font-bold uppercase text-slate-400">Clínica y Sede de
                                    Entrega</span>
                                <h5 id="traz-clinica-nombre" class="text-sm font-bold text-slate-800 truncate">Clínica
                                </h5>
                                <p id="traz-sede-dir" class="text-xs text-slate-600 mt-0.5 line-clamp-2">Dirección
                                    física</p>
                                <p id="traz-clinica-tel" class="text-xs text-slate-500 mt-1 font-medium">Teléfono de
                                    contacto</p>
                            </div>
                        </div>
                    </div>

                    <!-- Casos dentales dentro de este paquete -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Cajas Pequeñas (Casos
                                Odontológicos Contenidos)</h4>
                            <span id="traz-total-casos"
                                class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">0
                                casos</span>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-3">Código QR</th>
                                        <th class="py-2.5 px-3">Paciente</th>
                                        <th class="py-2.5 px-3">Prótesis / Servicio</th>
                                        <th class="py-2.5 px-3 text-center">Piezas</th>
                                        <th class="py-2.5 px-3">Protesista</th>
                                        <th class="py-2.5 px-3">Estatus</th>
                                        <th class="py-2.5 px-3 text-center">Etiqueta</th>
                                    </tr>
                                </thead>
                                <tbody id="traz-tabla-casos"
                                    class="divide-y divide-slate-100 text-slate-700 font-medium">
                                    <!-- Inyectado por JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bitácora de Eventos Registrados -->
                    <div id="seccion-bitacora" class="border-t border-slate-100 pt-5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Historial de Eventos
                            de la Cadena de Custodia</h4>
                        <div id="traz-lista-eventos" class="space-y-2 text-xs">
                            <!-- Inyectado por JS -->
                        </div>
                    </div>

                </div>
            </div>

            <!-- Tabla General de Envíos para Monitoreo -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div
                    class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Directorio de Envíos y Trazabilidad</h3>
                        <p class="text-xs text-slate-500">Selecciona cualquier despacho para ver su ruta, escanear o
                            imprimir etiquetas.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative w-full sm:w-64">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </span>
                            <input type="text" id="buscar-envio-tracking" placeholder="Filtrar por código, clínica..."
                                class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                        </div>
                        <select id="filtro-estatus" onchange="filtrarTablaTracking()"
                            class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                            <option value="">Todos los estatus</option>
                            <option value="PREPARADO">Preparados en Lab</option>
                            <option value="TRANSITO">En Tránsito</option>
                            <option value="RECIBIDO">Entregados</option>
                        </select>
                    </div>
                </div>

                <!-- Tabulator Table -->
                <div class="w-full">
                    <div id="tracking-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL ESCÁNER QR INTERACTIVO (CÁMARA Y CÓDIGO DE BARRAS)                  -->
    <!-- ========================================================================= -->
    <div id="modalEscanerQR"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div
            class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[92vh]">

            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-lg bg-[#FFCC00] text-[#D40511] flex items-center justify-center font-bold">
                        <i data-lucide="scan" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Escáner de Cajas y Casos</h3>
                        <p class="text-xs text-slate-500">Apunta la cámara al código QR de la caja</p>
                    </div>
                </div>
                <button onclick="cerrarModalEscanerQR()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <!-- Ventana del Escáner de Cámara -->
                <div id="reader-container"
                    class="relative rounded-2xl overflow-hidden bg-slate-900 min-h-[220px] flex items-center justify-center border-2 border-amber-300 shadow-inner">
                    <div id="qr-reader" class="w-full"></div>
                    <div id="qr-placeholder" class="text-center p-6 text-slate-400">
                        <i data-lucide="camera" class="w-10 h-10 mx-auto mb-2 text-[#FFCC00]"></i>
                        <p class="text-xs">Iniciando cámara del dispositivo...</p>
                    </div>
                </div>

                <!-- Input manual alternativo -->
                <div class="flex gap-2">
                    <input type="text" id="input-escaner-manual"
                        placeholder="O ingresa el código (ej. ENV-0001, ORD-0001)..."
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
        <div
            class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[92vh]">

            <div
                class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 print:hidden">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold shadow-sm">
                        <i data-lucide="tag" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Etiqueta de Identificación QR</h3>
                        <p class="text-[11px] text-slate-500">Impresión térmica para caja de despacho o caso dental.</p>
                    </div>
                </div>
                <button onclick="cerrarModalEtiquetaQR()"
                    class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- CONTENEDOR DE LA ETIQUETA IMPRIMIBLE (CNSB Lab Standard Thermal Label) -->
            <div class="p-6 overflow-y-auto flex-1 flex justify-center bg-slate-100/50">
                <div id="area-imprimible-etiqueta"
                    class="bg-white w-full max-w-[340px] p-5 rounded-2xl border-2 border-slate-900 shadow-md text-slate-900 font-sans space-y-3">

                    <!-- Header CNSB Style -->
                    <div class="border-b-2 border-slate-900 pb-2.5 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span
                                class="bg-slate-950 text-white font-black text-xs px-2.5 py-1 rounded-md tracking-wider">CNSB</span>
                            <span class="font-black text-xs tracking-wider text-slate-900 uppercase">LABORATORIO
                                DENTAL</span>
                        </div>
                        <span id="eti-tipo-badge"
                            class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-brand-600 text-white tracking-wider">
                            CAJA PRINCIPAL
                        </span>
                    </div>

                    <!-- Código QR Centrado -->
                    <div class="py-2 text-center flex flex-col items-center justify-center">
                        <div id="eti-qrcode-container"
                            class="p-2 border-2 border-dashed border-slate-300 rounded-xl bg-white inline-block"></div>
                        <span id="eti-codigo-texto"
                            class="block font-mono font-black text-lg tracking-widest mt-1 text-slate-900">
                            ENV-0001
                        </span>
                    </div>

                    <!-- Metadatos de Envío / Caso -->
                    <div
                        class="border-t-2 border-b-2 border-slate-900 py-2.5 text-xs space-y-2 font-medium leading-tight">
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Destino /
                                Clínica:</span>
                            <strong id="eti-clinica"
                                class="text-right text-slate-900 font-bold break-words flex-1">Clínica Dental</strong>
                        </div>
                        <div id="eti-row-sede" class="flex justify-between items-start gap-2">
                            <span class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Sede /
                                Sucursal:</span>
                            <strong id="eti-sede" class="text-right text-slate-900 font-bold break-words flex-1">Sede
                                Principal</strong>
                        </div>
                        <div id="eti-row-extra1" class="flex justify-between items-start gap-2">
                            <span id="eti-label-extra1"
                                class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Repartidor:</span>
                            <strong id="eti-val-extra1"
                                class="text-right text-slate-900 font-bold break-words flex-1">Deiver Martínez</strong>
                        </div>
                        <div id="eti-row-extra2" class="flex justify-between items-start gap-2">
                            <span id="eti-label-extra2"
                                class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Contenido:</span>
                            <strong id="eti-val-extra2" class="text-right text-slate-900 font-bold break-words flex-1">1
                                Caja(s)</strong>
                        </div>
                        <div class="flex justify-between items-center pt-1 border-t border-slate-200">
                            <span class="text-slate-500 uppercase text-[10px] font-bold">Fecha Emisión:</span>
                            <span id="eti-fecha"
                                class="text-right font-mono font-semibold text-slate-800">13/10/2026</span>
                        </div>
                    </div>

                    <!-- Pie de la Etiqueta -->
                    <div
                        class="text-[9px] text-center text-slate-500 font-mono tracking-tight uppercase leading-relaxed">
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

    <!-- Modal Actualizar Estatus Clásico -->
    <div id="modalEstatus"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div
            class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-800">Actualizar Estado del Envío</h3>
                <button onclick="cerrarModalEstatus()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="form-actualizar-estatus" onsubmit="guardarNuevoEstatus(event)" class="space-y-4">
                <input type="hidden" id="modal-envio-id">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nuevo Estado</label>
                    <select id="modal-nuevo-estatus" required
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 font-semibold">
                        <option value="PREPARADO">Preparado en Lab</option>
                        <option value="TRANSITO">En Tránsito hacia Clínica</option>
                        <option value="RECIBIDO">Entregado en Clínica / Sede</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Notas o Bitácora</label>
                    <textarea id="modal-notas" rows="2" placeholder="Observaciones de entrega..."
                        class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700"></textarea>
                </div>
                <div class="pt-2 flex gap-2">
                    <button type="button" onclick="cerrarModalEstatus()"
                        class="w-1/2 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Cancelar</button>
                    <button type="submit"
                        class="w-1/2 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estilos específicos de impresión para la etiqueta térmica/adhesiva -->
    <style>
        @media print {
            body>*:not(#modalEtiquetaQR) {
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

            #modalEtiquetaQR>div {
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

    <!-- Tabulator Local -->
    <script type="text/javascript" src="/assets/vendor/tabulator/tabulator.min.js"></script>

    <script>
        let tablaTracking = null;
        let envioActualEnVista = null;
        let qrScannerInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
            inicializarTablaTracking();
            cargarDatosTracking();

            document.getElementById("buscar-envio-tracking").addEventListener("input", function (e) {
                const term = e.target.value.toLowerCase();
                tablaTracking.setFilter(function (data) {
                    return (data.codigo && data.codigo.toLowerCase().includes(term)) ||
                        (data.clinica_nombre && data.clinica_nombre.toLowerCase().includes(
                            term)) ||
                        (data.sede_nombre && data.sede_nombre.toLowerCase().includes(term)) ||
                        (data.repartidor_nombre && data.repartidor_nombre.toLowerCase()
                            .includes(term));
                });
            });
        });

        function inicializarTablaTracking() {
            tablaTracking = new Tabulator("#tracking-table", {
                data: [],
                layout: "fitColumns",
                responsiveLayout: "collapse",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [10, 25, 50],
                placeholder: "<div class='p-8 text-center text-slate-500'><i data-lucide='truck' class='w-12 h-12 mx-auto mb-3 opacity-20'></i>No se encontraron envíos para mostrar.</div>",
                columns: [{
                        formatter: "responsiveCollapse",
                        width: 30,
                        minWidth: 30,
                        hozAlign: "center",
                        resizable: false,
                        headerSort: false
                    },
                    {
                        title: "Waybill / Código",
                        field: "codigo",
                        minWidth: 140,
                        responsive: 0,
                        formatter: (cell) => {
                            const val = cell.getValue();
                            return `
                                <div class="flex items-center gap-1.5 font-mono font-black text-slate-900 text-xs">
                                    <span class="w-2 h-2 rounded-full bg-[#FFCC00]"></span>
                                    <span>${val}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Destino (Clínica y Sede)",
                        field: "clinica_nombre",
                        minWidth: 200,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <p class="font-bold text-slate-800 text-xs">${d.clinica_nombre}</p>
                                    <p class="text-[10px] text-slate-400 truncate">${d.sede_nombre}</p>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Repartidor",
                        field: "repartidor_nombre",
                        minWidth: 150,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="bike" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-xs font-semibold text-slate-700">${d.repartidor_nombre}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Cajas Pequeñas",
                        field: "total_cajas",
                        minWidth: 110,
                        hozAlign: "center",
                        formatter: (cell) =>
                            `<span class="px-2.5 py-0.5 rounded-lg text-xs font-black bg-slate-100 text-slate-800 border border-slate-200">${cell.getValue()} casos</span>`
                    },
                    {
                        title: "Estatus de Ruta",
                        field: "estatus_label",
                        minWidth: 160,
                        hozAlign: "center",
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `<span class="px-2.5 py-1 rounded-full text-xs font-bold border ${d.estatus_badge}">${d.estatus_label}</span>`;
                        }
                    },
                    {
                        title: "Fecha Despacho",
                        field: "fecha_ruta_fmt",
                        minWidth: 130,
                        hozAlign: "center",
                        formatter: (cell) =>
                            `<span class="text-xs text-slate-500 font-mono">${cell.getValue()}</span>`
                    },
                    {
                        title: "Acciones",
                        minWidth: 140,
                        hozAlign: "center",
                        headerSort: false,
                        responsive: 0,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div class="flex items-center justify-center gap-1.5">
                                    <button onclick="verTrazabilidadDirecta(${d.id})" title="Ver Trazabilidad" class="p-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-600 text-xs transition-colors">
                                        <i data-lucide="route" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="imprimirEtiquetaCajaPrincipal(${d.id})" title="Imprimir Etiqueta QR" class="p-1.5 rounded-lg bg-[#FFCC00]/20 hover:bg-[#FFCC00]/40 text-amber-800 text-xs transition-colors border border-amber-300">
                                        <i data-lucide="printer" class="w-4 h-4 text-[#D40511]"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                // SOLUCIÓN APLICADA AQUÍ:
                rowFormatter: function (row) {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons({
                            root: row.getElement()
                        });
                    }
                }
            });
        }

        async function cargarDatosTracking() {
            try {
                const fd = new FormData();
                fd.append('action', 'listar_tracking');
                const res = await fetch('/tracking-controller', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();

                if (json.status === 'success') {
                    if (tablaTracking) tablaTracking.setData(json.data);
                    if (json.kpis) {
                        document.getElementById("kpi-transito").textContent = json.kpis.en_transito || 0;
                        document.getElementById("kpi-preparados").textContent = json.kpis.preparados || 0;
                        document.getElementById("kpi-entregados").textContent = json.kpis.entregados || 0;
                        document.getElementById("kpi-casos").textContent = json.kpis.total_casos || 0;
                    }
                }
            } catch (err) {
                console.error("Error al cargar tracking:", err);
            }
        }

        function filtrarTablaTracking() {
            const val = document.getElementById("filtro-estatus").value;
            if (!tablaTracking) return;
            if (!val) {
                tablaTracking.clearFilter();
            } else {
                tablaTracking.setFilter("estatus_envio", "=", val);
            }
        }

        async function verTrazabilidadDirecta(envioId) {
            try {
                const fd = new FormData();
                fd.append('action', 'obtener_trazabilidad');
                fd.append('envio_id', envioId);

                const res = await fetch('/tracking-controller', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();

                if (json.status === 'success') {
                    renderizarPanelTrazabilidad(json);
                } else {
                    alert(json.message || 'No se encontró el envío.');
                }
            } catch (err) {
                console.error("Error al cargar trazabilidad:", err);
            }
        }

        function rastrearCodigoDirecto(e) {
            e.preventDefault();
            const cod = document.getElementById("input-rastreo").value.trim();
            if (!cod) return;

            const fd = new FormData();
            fd.append('action', 'obtener_trazabilidad');
            fd.append('codigo_qr', cod);

            fetch('/tracking-controller', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(json => {
                    if (json.status === 'success') {
                        renderizarPanelTrazabilidad(json);
                    } else {
                        alert(json.message || `No se encontró información para el código ${cod}`);
                    }
                })
                .catch(err => console.error("Error:", err));
        }

        function renderizarPanelTrazabilidad(data) {
            envioActualEnVista = data.envio;
            const e = data.envio;

            document.getElementById("traz-codigo").textContent = e.codigo;
            const badge = document.getElementById("traz-badge");
            badge.textContent = e.estatus_label;
            badge.className = `px-2.5 py-0.5 rounded-full text-xs font-bold border ${e.estatus_badge}`;
            document.getElementById("traz-destino").textContent = `Destino: ${e.clinica_nombre} - ${e.sede_nombre}`;

            document.getElementById("traz-repartidor-nombre").textContent = e.repartidor_nombre;
            document.getElementById("traz-repartidor-vehiculo").textContent = `Vehículo Placa: ${e.placa_vehiculo}`;
            const telLink = document.getElementById("traz-repartidor-tel");
            telLink.href = `tel:${e.repartidor_telefono}`;

            document.getElementById("traz-clinica-nombre").textContent = e.clinica_nombre;
            document.getElementById("traz-sede-dir").textContent = e.sede_direccion;
            document.getElementById("traz-clinica-tel").textContent = e.sede_nombre;

            // Renderizar Timeline CNSB
            const tlContainer = document.getElementById("timeline-container");
            tlContainer.innerHTML = '';
            (data.timeline || []).forEach(step => {
                const borderClass = step.actual ? 'border-brand-500 bg-brand-50/50' : (step.completado ?
                    'border-emerald-500 bg-emerald-50/30' : 'border-slate-200 bg-white opacity-60');
                const dotColor = step.actual ? 'bg-brand-600 text-white animate-pulse' : (step.completado ?
                    'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500');

                tlContainer.innerHTML += `
                    <div class="p-4 rounded-xl border-2 ${borderClass} relative transition-all">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-6 h-6 rounded-full ${dotColor} flex items-center justify-center font-bold text-xs">
                                ${step.paso}
                            </span>
                            <h5 class="text-xs font-bold text-slate-800 truncate">${step.titulo}</h5>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-tight">${step.descripcion}</p>
                        ${step.fecha ? `<span class="block mt-2 font-mono text-[10px] text-slate-400 font-semibold">${step.fecha}</span>` : ''}
                    </div>
                `;
            });

            // Renderizar Casos dentro del Envío
            const tbody = document.getElementById("traz-tabla-casos");
            tbody.innerHTML = '';
            document.getElementById("traz-total-casos").textContent = `${(data.casos || []).length} caso(s)`;

            (data.casos || []).forEach(c => {
                tbody.innerHTML += `
                    <tr class="hover:bg-slate-50">
                        <td class="py-2.5 px-3 font-mono font-bold text-slate-900">${c.codigo_qr}</td>
                        <td class="py-2.5 px-3">${c.paciente_nombre}</td>
                        <td class="py-2.5 px-3">${c.servicio_nombre || 'Prótesis Dental'}</td>
                        <td class="py-2.5 px-3 text-center">${c.cantidad_piezas_exacta}</td>
                        <td class="py-2.5 px-3">${c.protesista_nombre || 'Laboratorio'}</td>
                        <td class="py-2.5 px-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">${c.estatus_actual}</span>
                        </td>
                        <td class="py-2.5 px-3 text-center">
                            <button onclick="imprimirEtiquetaCajaPequena(${c.id})" class="p-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition-colors" title="Imprimir Etiqueta Caso">
                                <i data-lucide="tag" class="w-3.5 h-3.5 text-brand-600"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            // Bitácora
            const listBit = document.getElementById("traz-lista-eventos");
            listBit.innerHTML = '';
            (data.eventos || []).forEach(ev => {
                listBit.innerHTML += `
                    <div class="flex items-start gap-2.5 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                        <div class="w-2 h-2 rounded-full bg-brand-500 mt-1.5 flex-shrink-0"></div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <strong class="text-slate-800 text-xs">${ev.estatus_nombre || 'Movimiento'}</strong>
                                <span class="text-[10px] text-slate-400 font-mono">${ev.fecha_hora_fmt}</span>
                            </div>
                            <p class="text-slate-600 text-xs mt-0.5">${ev.notas || ''}</p>
                            <span class="text-[10px] text-slate-400 block mt-1">Registrado por: ${ev.usuario_nombre || 'Sistema'}</span>
                        </div>
                    </div>
                `;
            });

            const panel = document.getElementById("panel-trazabilidad");
            panel.classList.remove("hidden");
            panel.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function cerrarPanelTrazabilidad() {
            document.getElementById("panel-trazabilidad").classList.add("hidden");
            envioActualEnVista = null;
        }

        // =========================================================================
        // ESCÁNER QR INTERACTIVO (CÁMARA HTML5 Y USB BARCODE)
        // =========================================================================
        function abrirModalEscanerQR() {
            const m = document.getElementById("modalEscanerQR");
            document.getElementById("resultado-escaneo").classList.add("hidden");
            document.getElementById("input-escaner-manual").value = "";
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
            setTimeout(() => m.classList.add("hidden"), 200);
        }

        function iniciarCamaraQR() {
            if (typeof Html5Qrcode !== 'undefined') {
                document.getElementById("qr-placeholder").style.display = 'none';
                qrScannerInstance = new Html5Qrcode("qr-reader");
                qrScannerInstance.start({
                        facingMode: "environment"
                    }, {
                        fps: 10,
                        qrbox: {
                            width: 220,
                            height: 220
                        }
                    },
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

                const res = await fetch('/tracking-controller', {
                    method: 'POST',
                    body: fd
                });
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
                    box.className =
                        "rounded-xl border border-rose-300 bg-rose-50 p-4 text-xs text-rose-700 font-semibold";
                    box.innerHTML =
                        `<i data-lucide="alert-triangle" class="w-4 h-4 inline mr-1"></i> ${json.message}`;
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
                fd.append('notas', `Actualizado mediante Escaneo QR`);

                const res = await fetch('/tracking-controller', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();

                if (json.status === 'success') {
                    alert(json.message);
                    cerrarModalEscanerQR();
                    cargarDatosTracking();
                    if (envioActualEnVista) verTrazabilidadDirecta(envioActualEnVista.id);
                } else {
                    alert(json.message || 'No se pudo actualizar.');
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

                const res = await fetch('/tracking-controller', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();

                if (json.status === 'success') {
                    const e = json.envio;
                    document.getElementById("eti-tipo-badge").textContent = "CAJA PRINCIPAL (MASTER)";
                    document.getElementById("eti-tipo-badge").className =
                        "text-[9px] font-black uppercase px-2 py-0.5 rounded bg-slate-900 text-white";
                    document.getElementById("eti-codigo-texto").textContent = e.codigo_qr;
                    document.getElementById("eti-clinica").textContent = e.clinica_nombre;
                    document.getElementById("eti-sede").textContent = e.sede_nombre;
                    document.getElementById("eti-row-sede").classList.remove("hidden");

                    document.getElementById("eti-label-extra1").textContent = "Repartidor:";
                    document.getElementById("eti-val-extra1").textContent =
                        `${e.repartidor_nombre} (${e.placa_vehiculo})`;

                    document.getElementById("eti-label-extra2").textContent = "Casos dentro:";
                    document.getElementById("eti-val-extra2").textContent = `${json.casos.length} Cajas Pequeñas`;

                    document.getElementById("eti-fecha").textContent = e.fecha_salida_fmt || '13/10/2026';

                    // Generar QR con qrcode.js
                    generarCodigoQREnEtiqueta(e.codigo_qr);

                    abrirModalEtiquetaQR();
                } else {
                    alert(json.message || 'Error al cargar etiqueta.');
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

                const res = await fetch('/tracking-controller', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();

                if (json.status === 'success') {
                    const c = json.caso;
                    document.getElementById("eti-tipo-badge").textContent = "CAJA PEQUEÑA (CASO DENTAL)";
                    document.getElementById("eti-tipo-badge").className =
                        "text-[9px] font-black uppercase px-2 py-0.5 rounded bg-indigo-600 text-white tracking-wider";
                    document.getElementById("eti-codigo-texto").textContent = c.codigo_qr;
                    document.getElementById("eti-clinica").textContent = c.clinica_nombre;
                    document.getElementById("eti-row-sede").classList.add("hidden");

                    document.getElementById("eti-label-extra1").textContent = "Paciente:";
                    document.getElementById("eti-val-extra1").textContent =
                        `${c.paciente_nombre} (${c.paciente_documento})`;

                    document.getElementById("eti-label-extra2").textContent = "Trabajo Dental:";
                    document.getElementById("eti-val-extra2").textContent =
                        `${c.servicio_nombre || 'Prótesis'} (${c.cantidad_piezas_exacta} pz)`;

                    document.getElementById("eti-fecha").textContent = c.fecha_ingreso_fmt || '13/10/2026';

                    // Generar QR con qrcode.js
                    generarCodigoQREnEtiqueta(c.codigo_qr);

                    abrirModalEtiquetaQR();
                } else {
                    alert(json.message || 'Error al cargar etiqueta del caso.');
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

        function imprimirEtiquetaActual() {
            if (envioActualEnVista) {
                imprimirEtiquetaCajaPrincipal(envioActualEnVista.id);
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
            
            // Usamos variables para engañar al inyector automático de GoDaddy
            const endScript = "<" + "/script>";
            const endBody = "<" + "/body>";
            const endHtml = "<" + "/html>";
            
            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Documento Oficial CNSB LAB</title>
                    <script src="/assets/vendor/tailwindcss/tailwind.min.js">${endScript}
                    <style>
                        @page { size: letter portrait; margin: 12mm; }
                        body { font-family: 'Inter', sans-serif; background: white; color: #1e293b; }
                    </style>
                </head>
                <body class="p-6">
                    ${el.innerHTML}
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

// =========================================================================
// MODAL ACTUALIZAR ESTATUS CLÁSICO
// =========================================================================

function abrirModalActualizarEstatus() {
if (!envioActualEnVista) return;
document.getElementById("modal-envio-id").value = envioActualEnVista.id;
document.getElementById("modal-nuevo-estatus").value = envioActualEnVista.estatus_envio;
document.getElementById("modal-notas").value = "";

const m = document.getElementById("modalEstatus");
m.classList.remove("hidden");
setTimeout(() => {
m.classList.remove("opacity-0");
m.firstElementChild.classList.remove("scale-95");
}, 10);
}

function cerrarModalEstatus() {
const m = document.getElementById("modalEstatus");
m.classList.add("opacity-0");
m.firstElementChild.classList.add("scale-95");
setTimeout(() => m.classList.add("hidden"), 200);
}

async function guardarNuevoEstatus(e) {
e.preventDefault();
const id = document.getElementById("modal-envio-id").value;
const est = document.getElementById("modal-nuevo-estatus").value;
const notas = document.getElementById("modal-notas").value;

try {
const fd = new FormData();
fd.append('action', 'actualizar_estatus');
fd.append('envio_id', id);
fd.append('nuevo_estatus', est);
fd.append('notas', notas);

const res = await fetch('/tracking-controller', { method: 'POST', body: fd });
const json = await res.json();

if (json.status === 'success') {
cerrarModalEstatus();
cargarDatosTracking();
verTrazabilidadDirecta(id);
} else {
alert(json.message || 'Error al actualizar.');
}
} catch (err) {
console.error("Error:", err);
}
}
</script>
</body>

</html>