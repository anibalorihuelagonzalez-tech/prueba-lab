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
                <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Logística y Despachos</h1>
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

            <!-- Action Bar & Header Card (CNSB Logistics Style) -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 md:p-6 mb-6 md:mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-600 via-indigo-600 to-sky-500"></div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-brand-50 text-brand-700 border border-brand-200 tracking-wider uppercase">Logística CNSB Lab</span>
                        <span class="text-xs font-mono text-slate-400 font-semibold">• Despachos y Cadena de Custodia</span>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900">Control de Envíos y Repartidores</h2>
                    <p class="text-sm text-slate-500 mt-1">Arma lotes de envío agrupando los casos de pacientes terminados y asígnalos a tu flota de repartidores con etiquetas QR.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <button onclick="abrirEscanerQR()"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold shadow transition-all duration-200 space-x-2 w-full sm:w-auto text-sm">
                        <i data-lucide="scan" class="w-5 h-5 text-brand-400"></i>
                        <span>Escanear QR (Abrir / Recibir)</span>
                    </button>
                    <button onclick="abrirModalRepartidores()"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-medium hover:bg-slate-200 border border-slate-200/80 transition-all duration-200 space-x-2 w-full sm:w-auto text-sm">
                        <i data-lucide="bike" class="w-5 h-5 text-slate-600"></i>
                        <span>Gestionar Repartidores</span>
                    </button>
                    <button onclick="abrirModalCrearEnvio()"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 w-full sm:w-auto text-sm">
                        <i data-lucide="package-plus" class="w-5 h-5"></i>
                        <span>Crear Envío (Caja)</span>
                    </button>
                </div>
            </div>

            <!-- Table Card Container (Envíos / Cajas Grandes) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="buscar-envio" placeholder="Buscar por código, clínica, sede o repartidor..."
                            class="w-full pl-9 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all text-slate-600">
                    </div>
                    <div class="flex items-center space-x-2">
                        <select id="filtro-estatus-envio" class="px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-600">
                            <option value="">Todos los estatus</option>
                            <option value="Pendiente">Pendientes / Empacados</option>
                            <option value="En Ruta">En Ruta</option>
                            <option value="Entregado">Entregados</option>
                        </select>
                    </div>
                </div>

                <!-- Tabulator Table: Envíos -->
                <div class="w-full">
                    <div id="envios-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: CREAR / EDITAR ENVÍO (CAJA GRANDE + CAJAS PEQUEÑAS)              -->
    <!-- ========================================================================= -->
    <div id="envioModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-5xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[95vh] flex flex-col"
            id="modalEnvioContainer">
            
            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="p-2 rounded-xl bg-brand-50 text-brand-600">
                        <i data-lucide="package" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 id="modalEnvioTitle" class="text-lg font-bold text-slate-800">Armar Nuevo Envío (Caja Principal)</h3>
                        <p class="text-xs text-slate-500">Configura el destino y selecciona los trabajos/casos que irán dentro de este despacho.</p>
                    </div>
                </div>
                <button onclick="cerrarModalEnvio()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Body Dividido: Izquierda (Datos Caja Grande) | Derecha (Selección Cajas Pequeñas) -->
            <form id="envioForm" class="flex-1 flex flex-col lg:flex-row overflow-hidden">
                <input type="hidden" id="envio_id" name="id">

                <!-- Columna Izquierda: Configuración del Envío -->
                <div class="w-full lg:w-5/12 p-5 border-b lg:border-b-0 lg:border-r border-slate-100 space-y-4 overflow-y-auto bg-white">
                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                        <i data-lucide="truck" class="w-4 h-4 text-brand-500"></i>
                        <span>1. Datos de Ruta y Transporte</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Clínica Destino</label>
                        <select id="envio_clinica" name="clinica_id" required onchange="cargarSedesEnvio(this.value)"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-slate-700">
                            <option value="">Seleccione clínica...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Sede / Sucursal de Entrega</label>
                        <select id="envio_sede" name="sede_id" required disabled onchange="cargarCasosPendientesPorSede(this.value)"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-slate-700 disabled:opacity-60">
                            <option value="">Primero seleccione una clínica...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Repartidor Asignado</label>
                        <select id="envio_repartidor" name="repartidor_id" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-slate-700">
                            <option value="">Seleccione un repartidor...</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Fecha Salida</label>
                            <input type="date" id="envio_fecha" name="fecha_salida" required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Estatus</label>
                            <select id="envio_estatus" name="estatus" required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700">
                                <option value="Pendiente">Empacado / Listo</option>
                                <option value="En Ruta">En Ruta</option>
                                <option value="Entregado">Entregado</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Notas de Entrega / Precinto</label>
                        <textarea id="envio_observaciones" name="observaciones" rows="2"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-slate-700 resize-none"
                            placeholder="Ej. Entregar en recepción piso 2, preguntar por Dra. Ana..."></textarea>
                    </div>
                </div>

                <!-- Columna Derecha: Selección de Cajas Pequeñas (Casos / Servicios de Pacientes) -->
                <div class="w-full lg:w-7/12 p-5 bg-slate-50/60 flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between mb-3 flex-shrink-0">
                        <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                            <i data-lucide="boxes" class="w-4 h-4 text-brand-600"></i>
                            <span>2. Contenido del Envío (Cajas Pequeñas / Casos)</span>
                        </div>
                        <span id="contadorCajas" class="px-2.5 py-1 rounded-full text-xs font-bold bg-brand-100 text-brand-700">
                            0 casos seleccionados
                        </span>
                    </div>

                    <!-- Buscador rápido de casos dentro de la caja -->
                    <div class="mb-3 flex items-center justify-between gap-2 flex-shrink-0">
                        <div class="relative flex-1">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                            <input type="text" id="filtrar-cajas-input" oninput="filtrarCajasPequenas(this.value)"
                                placeholder="Filtrar casos por paciente o servicio (ej. Corona Zirconio)..."
                                class="w-full pl-9 pr-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                        </div>
                        <button type="button" onclick="seleccionarTodasLasCajas()"
                            class="px-3 py-2 text-xs font-semibold text-brand-600 bg-white border border-slate-200 rounded-xl hover:bg-brand-50 transition-colors whitespace-nowrap">
                            Marcar Todos
                        </button>
                    </div>

                    <!-- Contenedor Scrollable de Tarjetas de Casos (Cajas Pequeñas) -->
                    <div id="contenedor-cajas-pequenas" class="flex-1 overflow-y-auto space-y-2.5 pr-1 min-h-[260px]">
                        <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400 border-2 border-dashed border-slate-200 rounded-2xl bg-white/50">
                            <i data-lucide="package-open" class="w-10 h-10 mb-2 opacity-40"></i>
                            <p class="text-sm font-medium text-slate-600">Ninguna sede seleccionada</p>
                            <p class="text-xs mt-1">Elige una Clínica y su Sede a la izquierda para ver los servicios de pacientes listos para empacar.</p>
                        </div>
                    </div>

                    <!-- Footer del Modal dentro del Form -->
                    <div class="pt-4 mt-4 border-t border-slate-200/80 flex items-center justify-end space-x-3 flex-shrink-0">
                        <button type="button" onclick="cerrarModalEnvio()"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                            Cancelar
                        </button>
                        <button type="submit" id="btnGuardarEnvio"
                            class="px-6 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm flex items-center space-x-2">
                            <i data-lucide="check-square" class="w-4 h-4"></i>
                            <span>Guardar y Empacar Envío</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: VER DETALLE DEL ENVÍO (DESGLOSE DE LA CAJA GRANDE)               -->
    <!-- ========================================================================= -->
    <div id="verEnvioModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] flex flex-col"
            id="verEnvioModalContainer">
            
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 flex items-center space-x-2">
                        <span>Manifiesto de Envío</span>
                        <span id="verEnvioCodigo" class="px-2.5 py-0.5 text-xs font-mono bg-brand-100 text-brand-700 rounded-md">#ENV-000</span>
                    </h3>
                </div>
                <button onclick="cerrarVerEnvioModal()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-5 overflow-y-auto space-y-5 flex-1">
                <!-- Tarjeta Resumen Ruta -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div>
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Destino (Clínica / Sede)</span>
                        <p id="verEnvioDestino" class="text-sm font-bold text-slate-800 mt-0.5"></p>
                        <p id="verEnvioDireccion" class="text-xs text-slate-500 mt-0.5"></p>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Repartidor Asignado</span>
                        <p id="verEnvioRepartidor" class="text-sm font-bold text-slate-800 mt-0.5"></p>
                        <p id="verEnvioTelefonoRep" class="text-xs text-slate-500 mt-0.5"></p>
                    </div>
                    <div class="sm:text-right">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Fecha y Estatus</span>
                        <p id="verEnvioFecha" class="text-sm font-bold text-slate-800 mt-0.5"></p>
                        <span id="verEnvioEstatusBadge" class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold"></span>
                    </div>
                </div>

                <!-- Lista de Cajas Pequeñas (Casos dentro del envío) -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center space-x-1.5">
                        <i data-lucide="layers" class="w-4 h-4 text-brand-500"></i>
                        <span>Cajas Pequeñas Empacadas (Casos de Pacientes)</span>
                    </h4>
                    <div id="verListaCajasPequenas" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        <!-- Llenado dinámico por JS -->
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-100 text-xs text-amber-800">
                    <strong class="block uppercase text-[10px] text-amber-600 mb-0.5">Observaciones de Entrega:</strong>
                    <span id="verEnvioObservaciones">Sin observaciones registradas.</span>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="imprimirEtiquetaCajaPrincipal(idEnvioEnVista)"
                        class="px-3 py-2 rounded-xl bg-[#FFCC00]/20 hover:bg-[#FFCC00]/40 text-amber-900 border border-amber-300 text-xs font-bold transition-colors flex items-center space-x-1.5">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-[#D40511]"></i>
                        <span>Imprimir Etiqueta Master QR</span>
                    </button>
                    <button type="button" onclick="cambiarEstatusRapido('En Ruta')"
                        class="px-3 py-2 rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 text-xs font-semibold transition-colors flex items-center space-x-1">
                        <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                        <span>Marcar En Ruta</span>
                    </button>
                    <button type="button" onclick="cambiarEstatusRapido('Entregado')"
                        class="px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 text-xs font-semibold transition-colors flex items-center space-x-1">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                        <span>Marcar Entregado</span>
                    </button>
                </div>
                <button type="button" onclick="cerrarVerEnvioModal()"
                    class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 font-medium hover:bg-slate-50 text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: GESTIÓN DE REPARTIDORES (MAESTRO-DETALLE)                        -->
    <!-- ========================================================================= -->
    <div id="repartidoresModal"
        class="fixed inset-0 z-[55] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-4xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[92vh] flex flex-col"
            id="modalRepartidoresContainer">
            
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <i data-lucide="bike" class="w-5 h-5 text-brand-600"></i>
                    <h3 class="text-lg font-bold text-slate-800">Flota de Repartidores / Mensajeros</h3>
                </div>
                <button onclick="cerrarModalRepartidores()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
                <!-- Izquierda: Formulario Repartidor -->
                <div class="w-full md:w-1/3 border-b md:border-b-0 md:border-r border-slate-100 p-5 bg-white overflow-y-auto">
                    <h4 id="formRepartidorTitle" class="text-sm font-bold text-slate-800 mb-4 flex items-center space-x-2">
                        <i data-lucide="user-plus" class="w-4 h-4 text-brand-500"></i>
                        <span>Registrar Repartidor</span>
                    </h4>

                    <form id="repartidorForm" class="space-y-3.5">
                        <input type="hidden" id="repartidor_id" name="id">

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nombre Completo</label>
                            <input type="text" id="rep_nombre" name="nombre_completo" required
                                class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                placeholder="Ej. Carlos Mendoza">
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div class="col-span-1">
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tipo</label>
                                <select id="rep_tipo_doc" name="tipo_documento" required
                                    class="w-full px-2.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Cédula</label>
                                <input type="text" id="rep_num_doc" name="numero_documento" required maxlength="10"
                                    class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none"
                                    placeholder="20123456">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Teléfono</label>
                            <input type="text" id="rep_telefono" name="telefono" required maxlength="11"
                                class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none"
                                placeholder="04121234567">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Vehículo / Placa</label>
                            <input type="text" id="rep_vehiculo" name="vehiculo"
                                class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none"
                                placeholder="Ej. Moto Empire Keeway - AB1C23D">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Estatus</label>
                            <select id="rep_estatus" name="estatus"
                                class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                                <option value="1">Activo / Disponible</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>

                        <div class="pt-2 flex flex-col space-y-2">
                            <button type="submit" id="btnGuardarRepartidor"
                                class="w-full px-4 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-md shadow-brand-500/25 transition-all text-sm flex items-center justify-center">
                                Guardar Repartidor
                            </button>
                            <button type="button" id="btnCancelarRepartidor" onclick="resetRepartidorForm()"
                                class="hidden w-full px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 text-sm">
                                Cancelar Edición
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Derecha: Tabla Repartidores -->
                <div class="w-full md:w-2/3 bg-slate-50/40 p-0 md:p-4 flex flex-col min-h-[320px]">
                    <div id="repartidores-table" class="w-full flex-1 bg-white rounded-xl shadow-sm border border-slate-200"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL CONFIRMACIÓN DE ELIMINAR (ENVÍO O REPARTIDOR)                       -->
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
                <p id="eliminarMensaje" class="text-sm text-slate-500 mt-2 mb-6 leading-relaxed"></p>
                
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
                    <input type="text" id="input-escaner-manual" placeholder="O ingresa el código (ej. ENV-0001, ORD-0001)..."
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
                        <p class="text-[11px] text-slate-500">Impresión térmica para caja de despacho o caso dental.</p>
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
                        <span id="eti-tipo-badge" class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-brand-600 text-white tracking-wider">
                            CAJA PRINCIPAL
                        </span>
                    </div>

                    <!-- Código QR Centrado -->
                    <div class="py-2 text-center flex flex-col items-center justify-center">
                        <div id="eti-qrcode-container" class="p-2 border-2 border-dashed border-slate-300 rounded-xl bg-white inline-block"></div>
                        <span id="eti-codigo-texto" class="block font-mono font-black text-lg tracking-widest mt-1 text-slate-900">
                            ENV-0001
                        </span>
                    </div>

                    <!-- Metadatos de Envío / Caso -->
                    <div class="border-t-2 border-b-2 border-slate-900 py-2.5 text-xs space-y-2 font-medium leading-tight">
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Destino / Clínica:</span>
                            <strong id="eti-clinica" class="text-right text-slate-900 font-bold break-words flex-1">Clínica Dental</strong>
                        </div>
                        <div id="eti-row-sede" class="flex justify-between items-start gap-2">
                            <span class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Sede / Sucursal:</span>
                            <strong id="eti-sede" class="text-right text-slate-900 font-bold break-words flex-1">Sede Principal</strong>
                        </div>
                        <div id="eti-row-extra1" class="flex justify-between items-start gap-2">
                            <span id="eti-label-extra1" class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Repartidor:</span>
                            <strong id="eti-val-extra1" class="text-right text-slate-900 font-bold break-words flex-1">Deiver Martínez</strong>
                        </div>
                        <div id="eti-row-extra2" class="flex justify-between items-start gap-2">
                            <span id="eti-label-extra2" class="text-slate-500 uppercase text-[10px] font-bold flex-shrink-0">Contenido:</span>
                            <strong id="eti-val-extra2" class="text-right text-slate-900 font-bold break-words flex-1">1 Caja(s)</strong>
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

    <!-- Tabulator Local -->
    <script type="text/javascript" src="/assets/vendor/tabulator/tabulator.min.js"></script>

    <script>
        let tableEnvios;
        let tableRepartidores;
        let itemParaEliminar = null; // { id, tipo: 'envio' | 'repartidor' }
        let idEnvioEnVista = null;
        let cajasDisponiblesCache = []; // Almacena las cajas pequeñas de la sede seleccionada

        // =========================================================================
        // SISTEMA DE NOTIFICACIÓN UNIFICADO
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
        // INICIALIZACIÓN TABLA PRINCIPAL DE ENVÍOS
        // =========================================================================
        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
            precargarSelectoresBase();

            tableEnvios = new Tabulator("#envios-table", {
                ajaxURL: "/obtener-despachos-controller",
                ajaxConfig: "POST",
                ajaxParams: { action: "listar_envios" },
                ajaxResponse: function (url, params, response) {
                    return response.data || [];
                },
                layout: "fitColumns",
                responsiveLayout: "collapse",
                placeholder: "<div class='p-8 text-center text-slate-500'><i data-lucide='package-search' class='w-12 h-12 mx-auto mb-3 opacity-20'></i>No hay envíos registrados en el sistema.</div>",
                pagination: "local",
                paginationSize: 10,
                locale: "es",
                langs: {
                    "es": { "pagination": { "first": "PRIMERO", "last": "ÚLTIMO", "prev": "ANTERIOR", "next": "SIGUIENTE" } }
                },
                columns: [
                    { formatter: "responsiveCollapse", width: 35, minWidth: 35, hozAlign: "center", resizable: false, headerSort: false, responsive: 0 },
                    { 
                        title: "Caja / Envío", field: "codigo", width: 130, responsive: 0,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `<span class="font-mono font-bold text-brand-700 bg-brand-50 px-2.5 py-1 rounded-lg border border-brand-100">#ENV-${String(d.id).padStart(4, '0')}</span>`;
                        }
                    },
                    { 
                        title: "Destino (Clínica / Sede)", field: "clinica_nombre", minWidth: 200, responsive: 0,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <span class="block font-bold text-slate-800">${d.clinica_nombre}</span>
                                    <span class="block text-xs text-slate-500"><i data-lucide="map-pin" class="w-3 h-3 inline mr-1 text-slate-400"></i>${d.sede_nombre}</span>
                                </div>
                            `;
                        }
                    },
                    { 
                        title: "Repartidor", field: "repartidor_nombre", minWidth: 160,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div class="flex items-center space-x-2">
                                    <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 text-xs font-bold">${(d.repartidor_nombre || 'R').substring(0,1)}</div>
                                    <div>
                                        <span class="block text-xs font-bold text-slate-700">${d.repartidor_nombre || 'Sin asignar'}</span>
                                        <span class="block text-[11px] text-slate-400">${d.repartidor_telefono || ''}</span>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    { 
                        title: "Contenido", field: "total_cajas_pequenas", width: 140, hozAlign: "center",
                        formatter: (cell) => {
                            const cant = cell.getValue() || 0;
                            return `
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    <i data-lucide="boxes" class="w-3.5 h-3.5 mr-1.5 text-brand-600"></i>${cant} ${cant == 1 ? 'caso' : 'casos'}
                                </span>
                            `;
                        }
                    },
                    { title: "Fecha Salida", field: "fecha_salida", width: 120, hozAlign: "center" },
                    { 
                        title: "Estatus", field: "estatus", width: 130, hozAlign: "center",
                        formatter: (cell) => {
                            const est = cell.getValue();
                            const badges = {
                                'Pendiente': 'bg-slate-100 text-slate-700 border-slate-200',
                                'En Ruta':   'bg-amber-50 text-amber-700 border-amber-200',
                                'Entregado': 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            };
                            const clase = badges[est] || badges['Pendiente'];
                            return `<span class="px-2.5 py-1 rounded-full text-xs font-semibold border ${clase}">${est}</span>`;
                        }
                    },
                    { 
                        title: "Acciones", width: 170, hozAlign: "center", headerSort: false, resizable: false, responsive: 0,
                        formatter: function(cell, formatterParams, onRendered) {
                            const id = cell.getData().id;
                            onRendered(() => lucide.createIcons());
                            return `
                                <div class="flex items-center justify-center space-x-1">
                                    <button class="btn-ver-envio p-1.5 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="Abrir Caja / Ver Casos" data-id="${id}">
                                        <i data-lucide="package-open" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn-imprimir-eti p-1.5 text-amber-700 bg-amber-50 hover:bg-[#FFCC00]/50 border border-amber-200 rounded-lg transition-colors" title="Imprimir Etiqueta QR Master" data-id="${id}">
                                        <i data-lucide="qr-code" class="w-4 h-4 text-[#D40511]"></i>
                                    </button>
                                    <button class="btn-editar-envio p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Editar Envío" data-id="${id}">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn-eliminar-envio p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Eliminar Envío" data-id="${id}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ]
            });

            // Filtros de búsqueda
            document.getElementById("buscar-envio").addEventListener("input", actualizarFiltrosTabla);
            document.getElementById("filtro-estatus-envio").addEventListener("change", actualizarFiltrosTabla);

            // Eventos de la tabla de envíos
            document.getElementById("envios-table").addEventListener("click", function(e) {
                const btnVer = e.target.closest(".btn-ver-envio");
                const btnImprimirEti = e.target.closest(".btn-imprimir-eti");
                const btnEditar = e.target.closest(".btn-editar-envio");
                const btnEliminar = e.target.closest(".btn-eliminar-envio");

                if (btnVer) verDetalleEnvio(btnVer.getAttribute("data-id"));
                if (btnImprimirEti) imprimirEtiquetaCajaPrincipal(btnImprimirEti.getAttribute("data-id"));
                if (btnEditar) editarEnvio(btnEditar.getAttribute("data-id"));
                if (btnEliminar) abrirEliminarModal(btnEliminar.getAttribute("data-id"), 'envio');
            });

            // Submit Crear / Editar Envío (Caja Grande + Array de Cajas Pequeñas)
            document.getElementById("envioForm").addEventListener("submit", async function(e) {
                e.preventDefault();
                const checkboxes = document.querySelectorAll('input[name="casos_ids[]"]:checked');
                if (checkboxes.length === 0) {
                    notificar('warning', 'Debes seleccionar al menos un caso/servicio (caja pequeña) para armar el envío.');
                    return;
                }

                const btn = document.getElementById("btnGuardarEnvio");
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Procesando...</span>`;
                lucide.createIcons();

                const formData = new FormData(this);
                const isEditing = formData.get("id") !== "";
                const url = isEditing ? '/editar-despacho-controller' : '/agregar-despacho-controller';

                try {
                    const response = await fetch(url, { method: 'POST', body: formData });
                    const result = await response.json();
                    cerrarModalEnvio();
                    setTimeout(() => {
                        if (result.status === 'success') {
                            notificar('success', result.message || 'Envío guardado y empacado correctamente.');
                            tableEnvios.setData();
                        } else {
                            notificar('error', result.message || 'Error al guardar el envío.');
                        }
                    }, 350);
                } catch (error) {
                    cerrarModalEnvio();
                    setTimeout(() => notificar('error', 'Error de conexión con el servidor.'), 350);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            });

            // Submit Crear / Editar Repartidor
            document.getElementById("repartidorForm").addEventListener("submit", async function(e) {
                e.preventDefault();
                const btn = document.getElementById("btnGuardarRepartidor");
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin mx-auto"></i>`;
                lucide.createIcons();

                const formData = new FormData(this);
                const isEditing = formData.get("id") !== "";
                formData.append('action', isEditing ? 'update_repartidor' : 'store_repartidor');

                try {
                    const response = await fetch('/repartidores-controller', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.status === 'success') {
                        notificar('success', result.message || 'Repartidor guardado.');
                        resetRepartidorForm();
                        tableRepartidores.setData();
                        precargarSelectoresBase(); // Actualizar select de repartidores en modal de envíos
                    } else {
                        notificar('error', result.message || 'No se pudo guardar el repartidor.');
                    }
                } catch (error) {
                    notificar('error', 'Error de red al guardar repartidor.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            });
        });

        function actualizarFiltrosTabla() {
            const term = document.getElementById("buscar-envio").value.toLowerCase();
            const est = document.getElementById("filtro-estatus-envio").value;
            const filtros = [];

            if (term) {
                filtros.push([
                    { field: "clinica_nombre", type: "like", value: term },
                    { field: "sede_nombre", type: "like", value: term },
                    { field: "repartidor_nombre", type: "like", value: term },
                    { field: "id", type: "like", value: term }
                ]);
            }
            if (est) {
                filtros.push({ field: "estatus", type: "=", value: est });
            }
            tableEnvios.setFilter(filtros);
        }

        // =========================================================================
        // LÓGICA DE LA CAJA GRANDE Y CAJAS PEQUEÑAS (CASOS DE PACIENTES)
        // =========================================================================
        async function precargarSelectoresBase() {
            // 1. Cargar Clínicas
            try {
                const fdClinicas = new FormData();
                fdClinicas.append('action', 'listar_clinicas');
                const resC = await fetch('/obtener-clinicas-controller', { method: 'POST', body: fdClinicas });
                const dataC = await resC.json();
                const selClinica = document.getElementById("envio_clinica");
                selClinica.innerHTML = '<option value="">Seleccione clínica...</option>';
                if (dataC.data) {
                    dataC.data.forEach(c => {
                        selClinica.innerHTML += `<option value="${c.id}">${c.razon_social}</option>`;
                    });
                }
            } catch (e) {}

            // 2. Cargar Repartidores Activos
            try {
                const fdRep = new FormData();
                fdRep.append('action', 'listar_repartidores_activos');
                const resR = await fetch('/repartidores-controller', { method: 'POST', body: fdRep });
                const dataR = await resR.json();
                const selRep = document.getElementById("envio_repartidor");
                selRep.innerHTML = '<option value="">Seleccione un repartidor...</option>';
                if (dataR.data) {
                    dataR.data.forEach(r => {
                        selRep.innerHTML += `<option value="${r.id}">${r.nombre_completo} (${r.vehiculo || 'Motorizado'})</option>`;
                    });
                }
            } catch (e) {}
        }

        async function cargarSedesEnvio(clinicaId, sedeSeleccionada = null) {
            const selSede = document.getElementById("envio_sede");
            if (!clinicaId) {
                selSede.innerHTML = '<option value="">Primero seleccione una clínica...</option>';
                selSede.disabled = true;
                renderizarCajasPequenas([]);
                return;
            }

            selSede.innerHTML = '<option value="">Cargando sucursales...</option>';
            selSede.disabled = true;

            try {
                const fd = new FormData();
                fd.append('action', 'listar_sedes');
                fd.append('clinica_id', clinicaId);
                const res = await fetch('/obtener-sedes-controller', { method: 'POST', body: fd });
                const result = await res.json();

                selSede.innerHTML = '<option value="">Seleccione sede destino...</option>';
                if (result.data && result.data.length > 0) {
                    result.data.forEach(s => {
                        const sel = (sedeSeleccionada && sedeSeleccionada == s.id) ? 'selected' : '';
                        selSede.innerHTML += `<option value="${s.id}" ${sel}>${s.nombre} - ${s.direccion_fisica}</option>`;
                    });
                    selSede.disabled = false;
                } else {
                    selSede.innerHTML = '<option value="">Sin sedes registradas</option>';
                }
            } catch (e) {
                selSede.innerHTML = '<option value="">Error al cargar sedes</option>';
            }
        }

        // Carga los servicios/trabajos de pacientes que pertenecen a esa sede
        async function cargarCasosPendientesPorSede(sedeId, envioIdParaEdicion = null) {
            const contenedor = document.getElementById("contenedor-cajas-pequenas");
            if (!sedeId) {
                cajasDisponiblesCache = [];
                renderizarCajasPequenas([]);
                return;
            }

            contenedor.innerHTML = `
                <div class="h-full flex items-center justify-center p-8 text-slate-400">
                    <i data-lucide="loader-2" class="w-6 h-6 animate-spin mr-2 text-brand-600"></i>
                    <span class="text-sm">Buscando casos de pacientes listos para esta sede...</span>
                </div>
            `;
            lucide.createIcons();

            try {
                const fd = new FormData();
                fd.append('action', 'obtener_casos_por_sede');
                fd.append('sede_id', sedeId);
                if (envioIdParaEdicion) fd.append('envio_id', envioIdParaEdicion);

                const res = await fetch('/obtener-despachos-controller', { method: 'POST', body: fd });
                const result = await res.json();

                cajasDisponiblesCache = result.casos || [];
                renderizarCajasPequenas(cajasDisponiblesCache);
            } catch (e) {
                contenedor.innerHTML = `<div class="p-6 text-center text-rose-500 text-sm">Error al consultar los casos pendientes.</div>`;
            }
        }

        function renderizarCajasPequenas(listaCasos) {
            const contenedor = document.getElementById("contenedor-cajas-pequenas");
            if (!listaCasos || listaCasos.length === 0) {
                contenedor.innerHTML = `
                    <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400 border-2 border-dashed border-slate-200 rounded-2xl bg-white/50">
                        <i data-lucide="inbox" class="w-10 h-10 mb-2 opacity-40"></i>
                        <p class="text-sm font-medium text-slate-600">No hay casos pendientes para empacar en esta sede</p>
                        <p class="text-xs mt-1">Todos los servicios de pacientes de esta sucursal ya fueron despachados o no hay órdenes listas.</p>
                    </div>
                `;
                actualizarContadorCajas();
                lucide.createIcons();
                return;
            }

            contenedor.innerHTML = '';
            listaCasos.forEach(caso => {
                const isChecked = caso.asignado_a_este_envio == 1 ? 'checked' : '';
                const card = document.createElement('label');
                card.className = "caja-pequena-item flex items-start space-x-3 p-3.5 rounded-xl bg-white border border-slate-200 hover:border-brand-400 cursor-pointer transition-all shadow-sm";
                card.setAttribute('data-search', `${caso.paciente_nombre} ${caso.paciente_documento} ${caso.servicio_nombre} ${caso.codigo_orden}`.toLowerCase());
                
                card.innerHTML = `
                    <input type="checkbox" name="casos_ids[]" value="${caso.id}" ${isChecked} onchange="actualizarContadorCajas()"
                        class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded">#ORD-${caso.codigo_orden || caso.id}</span>
                            <span class="text-[11px] font-semibold text-slate-400">Piezas: ${caso.piezas_dentales || 'N/A'}</span>
                        </div>
                        <p class="text-sm font-bold text-slate-800 mt-1 truncate">${caso.servicio_nombre}</p>
                        <p class="text-xs text-slate-500 flex items-center mt-0.5">
                            <i data-lucide="user" class="w-3 h-3 mr-1 text-slate-400"></i>
                            <span>Paciente: <strong>${caso.paciente_nombre}</strong> (${caso.paciente_documento})</span>
                        </p>
                    </div>
                `;
                contenedor.appendChild(card);
            });

            lucide.createIcons();
            actualizarContadorCajas();
        }

        function actualizarContadorCajas() {
            const seleccionados = document.querySelectorAll('input[name="casos_ids[]"]:checked').length;
            document.getElementById("contadorCajas").textContent = `${seleccionados} ${seleccionados === 1 ? 'caso seleccionado' : 'casos seleccionados'}`;
        }

        function filtrarCajasPequenas(termino) {
            const t = termino.toLowerCase();
            document.querySelectorAll('.caja-pequena-item').forEach(el => {
                const texto = el.getAttribute('data-search') || '';
                el.style.display = texto.includes(t) ? 'flex' : 'none';
            });
        }

        function seleccionarTodasLasCajas() {
            const checks = document.querySelectorAll('.caja-pequena-item input[type="checkbox"]');
            const todosMarcados = Array.from(checks).every(c => c.checked);
            checks.forEach(c => {
                if (c.closest('.caja-pequena-item').style.display !== 'none') {
                    c.checked = !todosMarcados;
                }
            });
            actualizarContadorCajas();
        }

        // =========================================================================
        // CONTROL DE MODALES DE ENVÍO (CREAR, EDITAR, VER MANIFIESTO)
        // =========================================================================
        function abrirModalCrearEnvio() {
            document.getElementById("envioForm").reset();
            document.getElementById("envio_id").value = "";
            document.getElementById("envio_fecha").valueAsDate = new Date();
            document.getElementById("envio_sede").innerHTML = '<option value="">Primero seleccione una clínica...</option>';
            document.getElementById("envio_sede").disabled = true;
            document.getElementById("modalEnvioTitle").textContent = "Armar Nuevo Envío (Caja Principal)";
            renderizarCajasPequenas([]);

            const modal = document.getElementById("envioModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("modalEnvioContainer").classList.remove("scale-95");
            }, 10);
        }

        function cerrarModalEnvio() {
            const modal = document.getElementById("envioModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalEnvioContainer").classList.add("scale-95");
            setTimeout(() => { modal.classList.add("hidden"); }, 300);
        }

        async function editarEnvio(id) {
            try {
                const fd = new FormData();
                fd.append('action', 'show_envio');
                fd.append('id', id);

                const res = await fetch('/obtener-despachos-controller', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.status === 'success' && data.envio) {
                    const e = data.envio;
                    document.getElementById("envio_id").value = e.id;
                    document.getElementById("envio_clinica").value = e.clinica_id;
                    await cargarSedesEnvio(e.clinica_id, e.sede_id);
                    
                    document.getElementById("envio_repartidor").value = e.repartidor_id;
                    document.getElementById("envio_fecha").value = e.fecha_salida;
                    document.getElementById("envio_estatus").value = e.estatus;
                    document.getElementById("envio_observaciones").value = e.observaciones || "";

                    await cargarCasosPendientesPorSede(e.sede_id, e.id);

                    document.getElementById("modalEnvioTitle").textContent = `Editar Envío #ENV-${String(e.id).padStart(4, '0')}`;

                    const modal = document.getElementById("envioModal");
                    modal.classList.remove("hidden");
                    setTimeout(() => {
                        modal.classList.remove("opacity-0");
                        document.getElementById("modalEnvioContainer").classList.remove("scale-95");
                    }, 10);
                } else {
                    notificar('error', data.message || 'No se pudo cargar el envío.');
                }
            } catch (e) {
                notificar('error', 'Error al consultar datos del envío.');
            }
        }

        async function verDetalleEnvio(id) {
            try {
                const fd = new FormData();
                fd.append('action', 'show_envio');
                fd.append('id', id);

                const res = await fetch('/obtener-despachos-controller', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.status === 'success' && data.envio) {
                    const e = data.envio;
                    idEnvioEnVista = e.id;

                    document.getElementById("verEnvioCodigo").textContent = `#ENV-${String(e.id).padStart(4, '0')}`;
                    document.getElementById("verEnvioDestino").textContent = `${e.clinica_nombre} — ${e.sede_nombre}`;
                    document.getElementById("verEnvioDireccion").textContent = e.sede_direccion || '';
                    document.getElementById("verEnvioRepartidor").textContent = e.repartidor_nombre;
                    document.getElementById("verEnvioTelefonoRep").textContent = e.repartidor_telefono;
                    document.getElementById("verEnvioFecha").textContent = e.fecha_salida;
                    document.getElementById("verEnvioObservaciones").textContent = e.observaciones || 'Sin observaciones registradas.';

                    const badge = document.getElementById("verEnvioEstatusBadge");
                    badge.textContent = e.estatus;
                    badge.className = "inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border " + 
                        (e.estatus === 'Entregado' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 
                        (e.estatus === 'En Ruta' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200'));

                    const listaContenedor = document.getElementById("verListaCajasPequenas");
                    listaContenedor.innerHTML = '';
                    
                    if (data.cajas_pequenas && data.cajas_pequenas.length > 0) {
                        data.cajas_pequenas.forEach(c => {
                            listaContenedor.innerHTML += `
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-xs font-mono font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded border border-brand-100">#ORD-${c.codigo_orden || c.id}</span>
                                        <span class="text-sm font-bold text-slate-800 ml-2">${c.servicio_nombre}</span>
                                        <p class="text-xs text-slate-500 mt-0.5">Paciente: <strong>${c.paciente_nombre}</strong> (${c.paciente_documento})</p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="px-2 py-1 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-600">
                                            Piezas: ${c.piezas_dentales || 'N/A'}
                                        </span>
                                        <button type="button" onclick="imprimirEtiquetaCajaPequena(${c.id})" class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-[#FFCC00]/40 text-amber-900 border border-amber-300 text-xs font-bold flex items-center gap-1 transition-colors" title="Imprimir Etiqueta QR Caja Pequeña">
                                            <i data-lucide="qr-code" class="w-3.5 h-3.5 text-[#D40511]"></i>
                                            <span>Etiqueta QR</span>
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        listaContenedor.innerHTML = `<p class="text-xs text-slate-400 text-center py-4">No hay casos asociados a este envío.</p>`;
                    }

                    const modal = document.getElementById("verEnvioModal");
                    modal.classList.remove("hidden");
                    setTimeout(() => {
                        modal.classList.remove("opacity-0");
                        document.getElementById("verEnvioModalContainer").classList.remove("scale-95");
                        lucide.createIcons();
                    }, 10);
                }
            } catch (e) {
                notificar('error', 'Error al abrir el manifiesto del envío.');
            }
        }

        function cerrarVerEnvioModal() {
            const modal = document.getElementById("verEnvioModal");
            modal.classList.add("opacity-0");
            document.getElementById("verEnvioModalContainer").classList.add("scale-95");
            setTimeout(() => { modal.classList.add("hidden"); }, 300);
        }

        async function cambiarEstatusRapido(nuevoEstatus) {
            if (!idEnvioEnVista) return;
            try {
                const fd = new FormData();
                fd.append('action', 'cambiar_estatus');
                fd.append('id', idEnvioEnVista);
                fd.append('estatus', nuevoEstatus);

                const res = await fetch('/editar-despacho-controller', { method: 'POST', body: fd });
                const result = await res.json();
                cerrarVerEnvioModal();
                setTimeout(() => {
                    if (result.status === 'success') {
                        notificar('success', `El envío ahora está: ${nuevoEstatus}`);
                        tableEnvios.setData();
                    } else {
                        notificar('error', result.message || 'No se pudo actualizar el estatus.');
                    }
                }, 300);
            } catch (e) {
                notificar('error', 'Error de red.');
            }
        }

        // =========================================================================
        // MODAL Y TABLA DE REPARTIDORES
        // =========================================================================
        function abrirModalRepartidores() {
            resetRepartidorForm();
            if (!tableRepartidores) {
                initTableRepartidores();
            } else {
                tableRepartidores.setData();
            }

            const modal = document.getElementById("repartidoresModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("modalRepartidoresContainer").classList.remove("scale-95");
                if (tableRepartidores) tableRepartidores.redraw(true);
            }, 10);
        }

        function cerrarModalRepartidores() {
            const modal = document.getElementById("repartidoresModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalRepartidoresContainer").classList.add("scale-95");
            setTimeout(() => { modal.classList.add("hidden"); }, 300);
        }

        function resetRepartidorForm() {
            document.getElementById("repartidorForm").reset();
            document.getElementById("repartidor_id").value = "";
            document.getElementById("formRepartidorTitle").innerHTML = `<i data-lucide="user-plus" class="w-4 h-4 text-brand-500"></i><span>Registrar Repartidor</span>`;
            document.getElementById("btnGuardarRepartidor").textContent = "Guardar Repartidor";
            document.getElementById("btnCancelarRepartidor").classList.add("hidden");
            lucide.createIcons();
        }

function initTableRepartidores() {
            tableRepartidores = new Tabulator("#repartidores-table", {
                ajaxURL: "/repartidores-controller",
                ajaxConfig: "POST",
                ajaxParams: { action: "listar_repartidores" },
                ajaxResponse: (url, params, response) => response.data || [],
                layout: "fitColumns",
                placeholder: "<div class='p-6 text-center text-slate-400 text-sm'>No hay repartidores registrados.</div>",
                columns: [
                    { 
                        title: "Repartidor", field: "nombre_completo", minWidth: 160,
                        formatter: (cell) => {
                            const d = cell.getData();
                            return `
                                <div>
                                    <span class="font-bold text-slate-800 block">${d.nombre_completo}</span>
                                    <span class="text-xs font-mono text-slate-400">${d.tipo_documento}-${d.numero_documento}</span>
                                </div>
                            `;
                        }
                    },
                    { title: "Teléfono", field: "telefono", width: 110 },
                    { title: "Vehículo", field: "vehiculo", minWidth: 130 },
                    { 
                        title: "Estatus", field: "estatus", width: 95, hozAlign: "center",
                        formatter: (cell) => cell.getValue() == 1 
                            ? `<span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Activo</span>`
                            : `<span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500">Inactivo</span>`
                    },
                    {
                        title: "Acciones", width: 90, hozAlign: "center", headerSort: false,
                        formatter: (cell) => {
                            const id = cell.getData().id;
                            return `
                                <div class="flex items-center justify-center space-x-1">
                                    <button class="btn-editar-rep p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg" data-id="${id}">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button class="btn-eliminar-rep p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg" data-id="${id}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                // Agregamos el rowFormatter aquí para evitar que se congele el modal
                rowFormatter: function(row) {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons({
                            root: row.getElement()
                        });
                    }
                }
            });

            // NOTA: Asegúrate de eliminar los tableRepartidores.on("renderComplete"...) que estaban aquí
            
            document.getElementById("repartidores-table").addEventListener("click", function(e) {
                const btnEdit = e.target.closest(".btn-editar-rep");
                const btnDel = e.target.closest(".btn-eliminar-rep");

                if (btnEdit) {
                    const id = btnEdit.getAttribute("data-id");
                    const row = tableRepartidores.getData().find(r => r.id == id);
                    if (row) {
                        document.getElementById("repartidor_id").value = row.id;
                        document.getElementById("rep_nombre").value = row.nombre_completo;
                        document.getElementById("rep_tipo_doc").value = row.tipo_documento;
                        document.getElementById("rep_num_doc").value = row.numero_documento;
                        document.getElementById("rep_telefono").value = row.telefono;
                        document.getElementById("rep_vehiculo").value = row.vehiculo || "";
                        document.getElementById("rep_estatus").value = row.estatus;

                        document.getElementById("formRepartidorTitle").innerHTML = `<i data-lucide="edit-3" class="w-4 h-4 text-amber-500"></i><span>Editando Repartidor</span>`;
                        document.getElementById("btnGuardarRepartidor").textContent = "Actualizar";
                        document.getElementById("btnCancelarRepartidor").classList.remove("hidden");
                        lucide.createIcons();
                    }
                } else if (btnDel) {
                    abrirEliminarModal(btnDel.getAttribute("data-id"), 'repartidor');
                }
            });
        }

        // =========================================================================
        // MODAL ELIMINAR UNIFICADO (ENVÍO O REPARTIDOR)
        // =========================================================================
        function abrirEliminarModal(id, tipo) {
            itemParaEliminar = { id, tipo };
            const titulo = document.getElementById("eliminarTitulo");
            const mensaje = document.getElementById("eliminarMensaje");

            if (tipo === 'envio') {
                titulo.textContent = "¿Anular / Eliminar Envío?";
                mensaje.textContent = "Se desarmará la caja de envío. Los casos de los pacientes volverán a quedar como pendientes por despachar.";
            } else {
                titulo.textContent = "¿Eliminar Repartidor?";
                mensaje.textContent = "El repartidor será eliminado del directorio. Esta acción no se puede deshacer.";
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

            const url = itemParaEliminar.tipo === 'envio' ? '/eliminar-despacho-controller' : '/repartidores-controller';
            const fd = new FormData();
            fd.append('id', itemParaEliminar.id);
            if (itemParaEliminar.tipo === 'repartidor') fd.append('action', 'delete_repartidor');

            try {
                const res = await fetch(url, { method: 'POST', body: fd });
                const result = await res.json();
                const tipoActual = itemParaEliminar.tipo;
                cerrarEliminarModal();

                setTimeout(() => {
                    if (result.status === 'success') {
                        notificar('success', result.message || 'Registro eliminado correctamente.');
                        if (tipoActual === 'envio') {
                            tableEnvios.setData();
                        } else {
                            tableRepartidores.setData();
                            precargarSelectoresBase();
                        }
                    } else {
                        notificar('error', result.message || 'No se pudo eliminar el registro.');
                    }
                }, 350);
            } catch (e) {
                cerrarEliminarModal();
                setTimeout(() => notificar('error', 'Error de conexión al intentar eliminar.'), 350);
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
                fd.append('notas', `Actualizado mediante Escaneo QR desde Despachos`);

                const res = await fetch('/tracking-controller', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    notificar('success', json.message);
                    cerrarModalEscanerQR();
                    if (tableEnvios) tableEnvios.setData();
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
                    document.getElementById("eti-tipo-badge").className = "text-[9px] font-black uppercase px-2 py-0.5 rounded bg-indigo-600 text-white tracking-wider";
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
    </script>
</body>
</html>