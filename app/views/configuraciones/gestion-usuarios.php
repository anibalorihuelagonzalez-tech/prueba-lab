<?php
session_start();
// Validación opcional de sesión/rol si lo requieres
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
                <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight">Gestión de Usuarios</h1>
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
                    <h2 class="text-lg font-semibold text-slate-900">Directorio de Usuarios</h2>
                    <p class="text-sm text-slate-500 mt-1">Administra accesos y roles del sistema.</p>
                </div>
                <button onclick="abrirModalCrear()"
                    class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all duration-200 space-x-2 w-full md:w-auto">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                    <span>Agregar Usuario</span>
                </button>
            </div>

            <!-- Table Card Container -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative w-full sm:w-72">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="custom-search-input" placeholder="Buscar usuarios..."
                            class="w-full pl-9 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                    </div>
                </div>

                <!-- Tabulator Table (Sin scroll forzado, usa collapse responsive) -->
                <div class="w-full">
                    <div id="usuarios-table" class="w-full"></div>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL AGREGAR / EDITAR USUARIO                                            -->
    <!-- ========================================================================= -->
    <div id="usuarioModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto"
            id="modalContainer">
            <div
                class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 sticky top-0 z-10">
                <h3 id="modalTitle" class="text-lg font-bold text-slate-800">Agregar Nuevo Usuario</h3>
                <button onclick="cerrarModal()"
                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="usuarioForm" class="p-5 space-y-4 relative">
                <input type="hidden" id="usuario_id" name="id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Primer Nombre</label>
                        <input type="text" id="primer_nombre" name="primer_nombre" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Primer Apellido</label>
                        <input type="text" id="primer_apellido" name="primer_apellido" required
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Correo Electrónico</label>
                    <input type="email" id="correo_electronico" name="correo_electronico" required
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">

                    <div id="errorAlertEmail"
                        class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center space-x-3 shadow-sm hidden transition-all duration-300">
                        <span class="text-amber-500 flex-shrink-0 flex items-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        </span>
                        <p class="text-amber-700 text-sm tracking-tight leading-snug">
                            Por favor, ingresa un correo electrónico válido.
                        </p>
                    </div>
                </div>

                <div id="passwordContainer">
                    <label id="passwordLabel" class="block text-xs font-semibold uppercase text-slate-500 mb-1">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors"
                        placeholder="Mínimo 8 caracteres">
                    <p id="passwordHelpText" class="text-xs text-slate-400 mt-1 hidden">
                        Deja este campo vacío si deseas mantener la contraseña actual.
                    </p>

                    <div id="errorAlertPassword"
                        class="mt-2 p-3 bg-red-50 border border-red-100 rounded-xl flex items-center space-x-3 shadow-sm hidden transition-all duration-300">
                        <span class="text-red-500 flex-shrink-0 flex items-center">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        </span>
                        <p class="text-red-600 text-sm tracking-tight leading-snug">
                            La contraseña debe tener un mínimo de 8 caracteres.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Rol de Usuario</label>
                    <select id="rol_id" name="rol_id" required
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        <option value="">Seleccione un rol...</option>
                        <option value="1">Super Usuario</option>
                        <option value="2">Administrador</option>
                        <option value="3">Recepcionista</option>
                        <option value="4">Protesista</option>
                        <option value="5">Clínica Admin</option>
                    </select>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModal()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm w-full sm:w-auto">Cancelar</button>
                    <button type="submit" id="btnGuardar"
                        class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-lg shadow-brand-500/25 transition-all text-sm w-full sm:w-auto relative flex items-center justify-center min-w-[100px]">
                        <span>Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL VER DETALLES DE USUARIO                                             -->
    <!-- ========================================================================= -->
    <div id="verUsuarioModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto"
            id="verModalContainer">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center space-x-2.5">
                    <div class="h-8 w-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Detalles del Usuario</h3>
                </div>
                <button onclick="cerrarVerModal()"
                    class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6">
                <!-- Avatar y Cabecera -->
                <div class="flex flex-col items-center text-center pb-5 border-b border-slate-100">
                    <div id="verAvatar"
                        class="h-16 w-16 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-2xl font-bold shadow-inner mb-3">
                        U
                    </div>
                    <h4 id="verNombreCompleto" class="text-lg font-bold text-slate-800 tracking-tight">Cargando...</h4>
                    <div class="mt-2 flex items-center space-x-2">
                        <span id="verRolBadge"
                            class="px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-100">
                            Rol
                        </span>
                        <span id="verIdBadge"
                            class="px-2.5 py-0.5 rounded-md text-xs font-mono text-slate-500 bg-slate-100">
                            ID: #0
                        </span>
                    </div>
                </div>

                <!-- Lista de Campos -->
                <div class="mt-5 space-y-3">
                    <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-100 flex items-center justify-between">
                        <div class="flex items-center space-x-2.5 text-slate-500">
                            <i data-lucide="mail" class="w-4 h-4 text-slate-400"></i>
                            <span class="text-xs font-semibold uppercase tracking-wider">Correo</span>
                        </div>
                        <span id="verCorreo" class="text-sm font-medium text-slate-800 truncate max-w-[210px] text-right"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                            <span class="block text-[11px] font-semibold uppercase text-slate-400 tracking-wider">Primer Nombre</span>
                            <span id="verPrimerNombre" class="block text-sm font-semibold text-slate-800 mt-1 truncate"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100">
                            <span class="block text-[11px] font-semibold uppercase text-slate-400 tracking-wider">Primer Apellido</span>
                            <span id="verPrimerApellido" class="block text-sm font-semibold text-slate-800 mt-1 truncate"></span>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <button type="button" onclick="cerrarVerModal()"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                        Cerrar
                    </button>
                    <button type="button" id="btnVerEditar" onclick="abrirEdicionDesdeVer()"
                        class="px-4 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-md shadow-brand-500/20 transition-all text-sm inline-flex items-center space-x-1.5">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Editar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL CONFIRMACIÓN DE BAJA (ESTILO UNIFICADO CON BLUR)                    -->
    <!-- ========================================================================= -->
    <div id="bajaUsuarioModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300"
            id="bajaModalContainer">
            <div class="p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-triangle" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 tracking-tight">¿Dar de baja a este usuario?</h3>
                <p class="text-sm text-slate-500 mt-2 mb-6 leading-relaxed">El usuario será eliminado del sistema y perderá su acceso. Esta acción no se puede deshacer.</p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <button type="button" onclick="cerrarBajaModal()"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition-colors text-sm">
                        Cancelar
                    </button>
                    <button type="button" id="btnConfirmarBaja"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 text-white font-medium hover:bg-rose-700 shadow-md shadow-rose-500/20 transition-colors text-sm flex items-center justify-center">
                        Sí, dar de baja
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 Local -->
    <script src="/assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <!-- Tabulator Local -->
    <script type="text/javascript" src="/assets/vendor/tabulator/tabulator.min.js"></script>

    <script>
        let table;
        let idUsuarioEnVista = null;
        let idUsuarioParaBaja = null; // Variable para almacenar el id a borrar

        // Función Notificar: Intrusiva, con blur y sin romper el aside
        function notificar(icono, mensaje) {
            Swal.fire({
                title: mensaje,
                icon: icono,
                showConfirmButton: true,
                confirmButtonText: 'Entendido',
                buttonsStyling: false,
                scrollbarPadding: false, // Ojoko pe padding ombyaíva aside
                heightAuto: false,       // Ojoko pe flexbox ñembyai
                customClass: {
                    container: 'absolute inset-0 !z-[100]',
                    backdrop: 'backdrop-blur-sm bg-slate-900/50', // Blur moderno
                    popup: 'rounded-2xl shadow-2xl p-6 font-sans bg-white border border-slate-100',
                    title: 'text-lg font-bold text-slate-800 mt-2',
                    htmlContainer: 'text-sm text-slate-600',
                    confirmButton: 'mt-4 w-full sm:w-auto px-6 py-2.5 rounded-xl bg-brand-600 text-white font-medium hover:bg-brand-700 shadow-md shadow-brand-500/20 transition-all text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                }
            });
        }

        const rolesMap = {
            1: 'Super Usuario',
            2: 'Administrador',
            3: 'Recepcionista',
            4: 'Protesista',
            5: 'Clínica Admin'
        };

        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();

            const actionsFormatter = function (cell, formatterParams, onRendered) {
                const id = cell.getValue();
                onRendered(function () {
                    lucide.createIcons();
                });
                return `
                    <div class="flex items-center justify-center space-x-1.5">
                        <button class="btn-ver p-1.5 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="Ver detalles" data-id="${id}">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <button class="btn-editar p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Editar" data-id="${id}">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button class="btn-baja p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Dar de baja" data-id="${id}">
                            <i data-lucide="user-x" class="w-4 h-4"></i>
                        </button>
                    </div>
                `;
            };

            table = new Tabulator("#usuarios-table", {
                ajaxURL: "/obtener-usuarios-controller",
                ajaxResponse: function (url, params, response) {
                    return response.data;
                },
                paginationDataReceived: {
                    last_page: "last_page"
                },
                ajaxRequestFunc: function (url, config, params) {
                    return new Promise((resolve, reject) => {
                        const formData = new FormData();
                        for (const key in params) {
                            if (Array.isArray(params[key])) {
                                params[key].forEach(val => formData.append(`${key}[]`, val));
                            } else {
                                formData.append(key, params[key]);
                            }
                        }
                        formData.append('action', 'listar');

                        fetch(url, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => resolve(data))
                            .catch(error => reject(error));
                    });
                },
                layout: "fitColumns",
                responsiveLayout: "collapse", // Hace que la tabla colapse verticalmente y evita el scroll
                placeholder: "<div class='p-8 text-center text-slate-500'><i data-lucide='inbox' class='w-12 h-12 mx-auto mb-3 opacity-20'></i>No hay usuarios para mostrar o esperando conexión.</div>",
                pagination: "remote",
                paginationSize: 10,
                paginationSizeSelector: [10, 25, 50, 100],
                locale: "es",
                langs: {
                    "es": {
                        "pagination": {
                            "first": "PRIMERO", "first_title": "Primera Página",
                            "last": "ÚLTIMO", "last_title": "Última Página",
                            "prev": "ANTERIOR", "prev_title": "Página Anterior",
                            "next": "SIGUIENTE", "next_title": "Página Siguiente",
                        },
                        "ajax": {
                            "loading": "Cargando datos...",
                            "error": "Error de conexión",
                        }
                    }
                },
                columns: [
                    // Columna de control para ver detalles cuando la tabla colapsa en móvil
                    { formatter: "responsiveCollapse", width: 35, minWidth: 35, hozAlign: "center", resizable: false, headerSort: false, responsive: 0 },
                    { title: "ID", field: "id", width: 70, minWidth: 60, hozAlign: "center" },
                    { 
                        title: "Nombre Completo", 
                        field: "primer_nombre", 
                        minWidth: 170, 
                        responsive: 0,
                        formatter: function (cell) {
                            const data = cell.getRow().getData();
                            return `<div class="font-bold text-slate-800 truncate">${data.primer_nombre || ''} ${data.primer_apellido || ''}</div>`;
                        }
                    },
                    { 
                        title: "Correo Electrónico", 
                        field: "correo_electronico", 
                        minWidth: 180, 
                        formatter: function (cell) {
                            return `<div class="truncate text-slate-600 font-normal">${cell.getValue() || ''}</div>`;
                        }
                    },
                    { 
                        title: "Rol", 
                        field: "rol_nombre", 
                        width: 140, 
                        minWidth: 120, 
                        hozAlign: "center", 
                        formatter: function (cell) {
                            const rol = cell.getValue();
                            return `<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 whitespace-nowrap">${rol || 'Usuario'}</span>`;
                        }
                    },
                    { 
                        title: "Acciones", 
                        field: "id", 
                        width: 140, 
                        minWidth: 130, 
                        hozAlign: "center", 
                        headerSort: false, 
                        resizable: false,
                        responsive: 0,
                        formatter: actionsFormatter 
                    }
                ]
            });

            document.getElementById("usuarios-table").addEventListener('click', function (e) {
                const btnVer = e.target.closest('.btn-ver');
                const btnEditar = e.target.closest('.btn-editar');
                const btnBaja = e.target.closest('.btn-baja');

                if (btnVer) {
                    verUsuario(btnVer.getAttribute('data-id'));
                } else if (btnEditar) {
                    editarUsuario(btnEditar.getAttribute('data-id'));
                } else if (btnBaja) {
                    abrirBajaModal(btnBaja.getAttribute('data-id'));
                }
            });

            document.getElementById("custom-search-input").addEventListener("input", function (e) {
                table.setFilter("correo_electronico", "like", e.target.value);
            });

            document.getElementById("usuarioForm").addEventListener("submit", async function (e) {
                e.preventDefault();

                const emailInput = document.getElementById("correo_electronico");
                const passwordInput = document.getElementById("contrasena");
                const errorAlertEmail = document.getElementById("errorAlertEmail");
                const errorAlertPassword = document.getElementById("errorAlertPassword");
                const usuarioId = document.getElementById("usuario_id").value;
                const isEditing = usuarioId !== "";
                const btnGuardar = document.getElementById("btnGuardar");

                let hasError = false;

                const emailregex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailregex.test(emailInput.value.trim())) {
                    errorAlertEmail.classList.remove('hidden');
                    emailInput.classList.add('border-amber-500', 'focus:border-amber-500', 'focus:ring-amber-500/20', 'animate-shake');
                    emailInput.classList.remove('border-slate-200', 'focus:border-brand-500', 'focus:ring-brand-500/20');
                    setTimeout(() => emailInput.classList.remove('animate-shake'), 300);
                    hasError = true;
                } else {
                    errorAlertEmail.classList.add('hidden');
                    emailInput.classList.remove('border-amber-500', 'focus:border-amber-500', 'focus:ring-amber-500/20');
                    emailInput.classList.add('border-slate-200', 'focus:border-brand-500', 'focus:ring-brand-500/20');
                }

                const passwordregex = /^.{8,}$/;
                const passVal = passwordInput.value;
                if (!isEditing || (isEditing && passVal.length > 0)) {
                    if (!passwordregex.test(passVal)) {
                        errorAlertPassword.classList.remove('hidden');
                        passwordInput.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20', 'animate-shake');
                        passwordInput.classList.remove('border-slate-200', 'focus:border-brand-500', 'focus:ring-brand-500/20');
                        setTimeout(() => passwordInput.classList.remove('animate-shake'), 300);
                        hasError = true;
                    } else {
                        errorAlertPassword.classList.add('hidden');
                        passwordInput.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20');
                        passwordInput.classList.add('border-slate-200', 'focus:border-brand-500', 'focus:ring-brand-500/20');
                    }
                } else {
                    errorAlertPassword.classList.add('hidden');
                    passwordInput.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20');
                    passwordInput.classList.add('border-slate-200', 'focus:border-brand-500', 'focus:ring-brand-500/20');
                }

                if (hasError) {
                    notificar('warning', 'Por favor verifica los campos resaltados.');
                    return;
                }

                const originalBtnContent = btnGuardar.innerHTML;
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>`;
                lucide.createIcons();

                const formData = new FormData(this);
                const targetUrl = isEditing ? '/editar-usuario-controller' : '/crear-usuario-controller';

                try {
                    const response = await fetch(targetUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    cerrarModal();

                    setTimeout(() => {
                        if (result.status === 'success') {
                            notificar('success', result.message || (isEditing ? 'Usuario editado con éxito.' : 'Usuario creado con éxito.'));
                            table.setData();
                        } else {
                            notificar('error', result.message || 'Ocurrió un error al procesar los datos.');
                        }
                    }, 350);
                } catch (error) {
                    console.error("Error de red:", error);
                    cerrarModal();
                    setTimeout(() => {
                        notificar('error', 'Error de red o comunicación con el servidor.');
                    }, 350);
                } finally {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = originalBtnContent;
                }
            });
        });

        // =========================================================================
        // FUNCIONES DE CONTROL DE MODALES
        // =========================================================================
        function abrirModalCrear() {
            document.getElementById("usuarioForm").reset();
            document.getElementById("usuario_id").value = "";
            document.getElementById("modalTitle").textContent = "Agregar Nuevo Usuario";
            document.getElementById("passwordLabel").textContent = "Contraseña";
            document.getElementById("passwordHelpText").classList.add("hidden");
            
            const contrasenaInput = document.getElementById("contrasena");
            contrasenaInput.required = true;
            contrasenaInput.placeholder = "Mínimo 8 caracteres";

            document.getElementById("errorAlertEmail").classList.add('hidden');
            document.getElementById("errorAlertPassword").classList.add('hidden');

            const modal = document.getElementById("usuarioModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("modalContainer").classList.remove("scale-95");
            }, 10);
        }

        function cerrarModal() {
            const modal = document.getElementById("usuarioModal");
            modal.classList.add("opacity-0");
            document.getElementById("modalContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 300);
        }

        function cerrarVerModal() {
            const modal = document.getElementById("verUsuarioModal");
            modal.classList.add("opacity-0");
            document.getElementById("verModalContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 300);
        }

        function abrirEdicionDesdeVer() {
            if (idUsuarioEnVista) {
                cerrarVerModal();
                setTimeout(() => {
                    editarUsuario(idUsuarioEnVista);
                }, 300);
            }
        }

        // =========================================================================
        // ACCIONES CRUD (VER, EDITAR, DAR DE BAJA)
        // =========================================================================

        async function verUsuario(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'show');
                formData.append('id', id);

                const response = await fetch('/obtener-usuarios-controller', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success' && data.usuario) {
                    const u = data.usuario;
                    idUsuarioEnVista = u.id;

                    const inicial = (u.primer_nombre ? u.primer_nombre.charAt(0) : 'U').toUpperCase();
                    document.getElementById("verAvatar").textContent = inicial;
                    document.getElementById("verNombreCompleto").textContent = `${u.primer_nombre} ${u.primer_apellido}`;
                    document.getElementById("verIdBadge").textContent = `ID: #${u.id}`;
                    document.getElementById("verRolBadge").textContent = rolesMap[u.rol_id] || `Rol ${u.rol_id}`;
                    document.getElementById("verCorreo").textContent = u.correo_electronico;
                    document.getElementById("verPrimerNombre").textContent = u.primer_nombre;
                    document.getElementById("verPrimerApellido").textContent = u.primer_apellido;

                    const modal = document.getElementById("verUsuarioModal");
                    modal.classList.remove("hidden");
                    setTimeout(() => {
                        modal.classList.remove("opacity-0");
                        document.getElementById("verModalContainer").classList.remove("scale-95");
                        lucide.createIcons();
                    }, 10);
                } else {
                    notificar('error', data.message || 'No se pudieron obtener los detalles del usuario.');
                }
            } catch (error) {
                console.error("Error al cargar detalles de usuario:", error);
                notificar('error', 'Error al consultar los datos del usuario.');
            }
        }

        async function editarUsuario(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'show');
                formData.append('id', id);

                const response = await fetch('/obtener-usuarios-controller', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success' && data.usuario) {
                    const u = data.usuario;
                    document.getElementById("usuario_id").value = u.id;
                    document.getElementById("primer_nombre").value = u.primer_nombre;
                    document.getElementById("primer_apellido").value = u.primer_apellido;
                    document.getElementById("correo_electronico").value = u.correo_electronico;
                    document.getElementById("rol_id").value = u.rol_id;

                    const contrasenaInput = document.getElementById("contrasena");
                    contrasenaInput.value = "";
                    contrasenaInput.required = false;
                    contrasenaInput.placeholder = "Dejar en blanco para mantener actual";
                    document.getElementById("passwordLabel").textContent = "Contraseña (Opcional)";
                    document.getElementById("passwordHelpText").classList.remove("hidden");

                    document.getElementById("modalTitle").textContent = "Editar Usuario";

                    document.getElementById("errorAlertEmail").classList.add('hidden');
                    document.getElementById("errorAlertPassword").classList.add('hidden');

                    const modal = document.getElementById("usuarioModal");
                    modal.classList.remove("hidden");
                    setTimeout(() => {
                        modal.classList.remove("opacity-0");
                        document.getElementById("modalContainer").classList.remove("scale-95");
                        lucide.createIcons();
                    }, 10);
                } else {
                    notificar('error', data.message || 'No se pudo cargar la información del usuario.');
                }
            } catch (error) {
                console.error("Error al cargar usuario para editar:", error);
                notificar('error', 'Error de red al cargar el usuario.');
            }
        }

        // ---------------------------------------------------------
        // LÓGICA DE BAJA CON MODAL UNIFICADO Y NOTIFICACIÓN INTRUSIVA
        // ---------------------------------------------------------
        
        function abrirBajaModal(id) {
            idUsuarioParaBaja = id;
            const modal = document.getElementById("bajaUsuarioModal");
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.classList.remove("opacity-0");
                document.getElementById("bajaModalContainer").classList.remove("scale-95");
            }, 10);
        }

        function cerrarBajaModal() {
            const modal = document.getElementById("bajaUsuarioModal");
            modal.classList.add("opacity-0");
            document.getElementById("bajaModalContainer").classList.add("scale-95");
            setTimeout(() => {
                modal.classList.add("hidden");
                idUsuarioParaBaja = null;
            }, 300);
        }

        document.getElementById("btnConfirmarBaja").addEventListener("click", async function() {
            if (!idUsuarioParaBaja) return;

            const btn = this;
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto"></i>`;
            lucide.createIcons();

            try {
                const formData = new FormData();
                formData.append('id', idUsuarioParaBaja);

                const response = await fetch('/baja-usuario-controller', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                // Cerramos el modal
                cerrarBajaModal();
                
                // Esperamos que la animación de cerrar termine para mostrar la alerta intrusiva
                setTimeout(() => {
                    if (result.status === 'success') {
                        notificar('success', result.message || 'Usuario dado de baja exitosamente.');
                        table.setData();
                    } else {
                        notificar('error', result.message || 'No fue posible dar de baja al usuario.');
                    }
                }, 350);

            } catch (error) {
                console.error("Error al procesar la baja:", error);
                cerrarBajaModal();
                setTimeout(() => {
                    notificar('error', 'Error de red al intentar dar de baja al usuario.');
                }, 350);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        });

    </script>
</body>
</html>