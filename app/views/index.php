<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CNSB - Sistema Central</title>
    <link rel="stylesheet" href="/assets/css/loader.css">
    <script src="/assets/vendor/tailwindcss/tailwind.min.js"></script>
    <link rel="stylesheet" href="/assets/vendor/fontawesome/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            900: '#0c4a6e',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }

    </script>
    <style>
        @keyframes kenburns {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.12);
            }
        }

        .animate-kenburns {
            animation: kenburns 16s ease-in-out infinite alternate;
            will-change: transform;
        }

    </style>
</head>

<body class="bg-white text-gray-800 font-sans antialiased h-screen flex overflow-hidden">

    <!-- Left Section -->
    <div class="hidden lg:flex lg:w-1/2 relative bg-gray-900 overflow-hidden">
        <!-- 1. Imagen de fondo -->
        <img src="/assets/images/dental_lab_clean.jpg"
            class="absolute inset-0 w-full h-full object-cover z-0 animate-kenburns" alt="Laboratorio dental">

        <!-- 2. Capa oscura general con transparencia -->
        <div class="absolute inset-0 bg-black/40 z-10"></div>

        <!-- 3. Transición lateral derecha -->
        <div class="absolute inset-y-0 right-0 w-48 z-20 pointer-events-none"
            style="background: linear-gradient(to left, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, transparent 100%);">
        </div>

        <!-- 4. Textos -->
        <div class="absolute bottom-16 left-16 z-30 max-w-lg text-white drop-shadow-md">
            <h1 class="text-5xl font-light mb-2 tracking-tight">CNSB</h1>
            <h2 class="text-5xl font-bold mb-6 tracking-tight">LAB</h2>
            <p class="text-white/90 font-light tracking-wide text-xl">Donde la ciencia y la precisión crean sonrisas.
            </p>
        </div>
    </div>

    <!-- Right Section: Login Form -->
    <div class="w-full lg:w-1/2 flex flex-col justify-between bg-white relative px-8 py-10 lg:px-12">

        <div></div>

        <!-- Formulario central -->
        <div class="w-full max-w-md mx-auto">
            <div class="mb-12 text-center lg:text-left">
                <h2 class="text-3xl font-bold text-gray-900 mb-3 tracking-tight">CNSB LAB</h2>
                <p class="text-gray-500 font-normal">Ingrese sus credenciales para acceder al sistema de gestión del
                    laboratorio.</p>
            </div>

            <form id="loginForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Correo Electrónico</label>
                    <div class="relative group">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-brand-500 transition-colors">
                            <i class="fa-regular fa-envelope"></i>
                        </span>
                        <input type="email" id="email" tabindex="1"
                            class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-gray-900 placeholder-gray-400 transition-all shadow-sm"
                            placeholder="tu@correo.com">
                    </div>
                    <div id="errorAlertEmail"
                        class="mt-3 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-center space-x-3 shadow-sm hidden">
                        <span class="text-amber-500 flex-shrink-0 flex items-center">
                            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                        </span>
                        <p class="text-amber-700 text-sm tracking-tight leading-snug">
                            Por favor, ingresa un correo electrónico válido.
                        </p>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-semibold text-gray-700">Contraseña</label>
                    </div>
                    <div class="relative group">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-brand-500 transition-colors">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" id="password" tabindex="2"
                            class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 text-gray-900 placeholder-gray-400 transition-all shadow-sm"
                            placeholder="••••••••">
                    </div>
                    <!-- Mensaje actualizado de la contraseña -->
                    <div id="errorAlertPassword"
                        class="mt-3 p-4 bg-red-50 border border-red-100 rounded-xl flex items-center space-x-3 shadow-sm hidden">
                        <span class="text-red-500 flex-shrink-0 flex items-center">
                            <i class="fa-solid fa-circle-exclamation text-lg"></i>
                        </span>
                        <p class="text-red-600 text-sm tracking-tight leading-snug">
                            La contraseña debe tener un mínimo de 8 caracteres.
                        </p>
                    </div>
                </div>

                <!-- Mensaje de error general de credenciales inválidas (activado por el 401 del backend) -->
                <div id="errorMessage"
                    class="p-4 bg-red-50 border border-red-100 rounded-xl flex items-center space-x-3 shadow-sm hidden">
                    <span class="text-red-500 flex-shrink-0 flex items-center">
                        <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    </span>
                    <p class="text-red-600 text-sm tracking-tight leading-snug">
                        Las credenciales ingresadas no son correctas. Por favor, verifique sus datos.
                    </p>
                </div>

                <div>
                    <button type="submit" id="submitButton" tabindex="3"
                        class="w-full bg-gray-900 text-white font-medium py-4 px-4 rounded-xl transition-all shadow-[0_4px_20px_rgba(0,0,0,0.15)] hover:shadow-[0_8px_30px_rgba(0,0,0,0.3)] hover:-translate-y-0.5 flex justify-center items-center min-h-[56px] group">
                        <span>Iniciar Sesión</span>
                        <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer / Pie de página -->
        <footer class="text-center text-xs text-gray-400 py-2">
            <p>&copy; 2026 CNSB LAB. Todos los derechos reservados.</p>
            <p class="mt-1">
                Desarrollado por <span class="font-medium text-gray-600">Deiver Martinez</span> •
                <a href="https://github.com/deivergit" target="_blank"
                    class="text-brand-600 hover:text-brand-800 transition-colors inline-flex items-center">
                    <i class="fa-brands fa-github mr-1"></i> GitHub
                </a>
            </p>
        </footer>

    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const loginForm = document.getElementById('loginForm');
        const submitButton = document.getElementById('submitButton');

        if (!loginForm) return;

        const originalButtonContent = submitButton ? submitButton.innerHTML : 'Iniciar Sesión';

        function restaurarBoton() {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
            }
        }

        async function iniciarSesion(email, password) {
            try {
                const response = await fetch('/iniciar-sesion-controller', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email,
                        password
                    })
                });

                const errorMessage = document.getElementById('errorMessage');

                if (response.ok) {
                    const data = await response.json();
                    window.location.href = data.redirect || '/laboratorio/clinicas-sedes';
                } else if (response.status === 401) {
                    // Credenciales incorrectas
                    if (errorMessage) {
                        errorMessage.querySelector('p').textContent =
                            "Las credenciales ingresadas no son correctas. Por favor, verifique sus datos.";
                        errorMessage.classList.remove('hidden');
                    }
                    restaurarBoton();
                } else if (response.status === 429) {
                    // Demasiados intentos fallidos (protección anti fuerza bruta del servidor)
                    let aviso = "Demasiados intentos fallidos. Espera unos minutos.";
                    try { aviso = (await response.json()).message || aviso; } catch (e) { /* sin cuerpo JSON */ }
                    if (errorMessage) {
                        errorMessage.querySelector('p').textContent = aviso;
                        errorMessage.classList.remove('hidden');
                    }
                    restaurarBoton();
                } else if (response.status === 404) {
                    // Error de ruta no encontrada
                    if (errorMessage) {
                        errorMessage.querySelector('p').textContent =
                            "Error 404: no se encontró el controlador de inicio de sesión (revisa routes.php).";
                        errorMessage.classList.remove('hidden');
                    }
                    restaurarBoton();
                } else {
                    // Otros errores de servidor (500, etc.)
                    if (errorMessage) {
                        errorMessage.querySelector('p').textContent =
                            "Ocurrió un error en el servidor. Intente más tarde.";
                        errorMessage.classList.remove('hidden');
                    }
                    restaurarBoton();
                }

            } catch (error) {
                console.error("Ocurrió un error de red durante la solicitud:", error);
                const errorMessage = document.getElementById('errorMessage');
                if (errorMessage) {
                    errorMessage.querySelector('p').textContent = "Error de conexión con el servidor.";
                    errorMessage.classList.remove('hidden');
                }
                restaurarBoton();
            }
        }

        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Ocultar mensaje general de credenciales previas si existía
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) errorMessage.classList.add('hidden');

            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            const email = emailInput.value.trim();
            const password = passwordInput.value;

            let hasError = false;

            // 1. Validación de Email
            const emailregex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const errorAlertEmail = document.getElementById('errorAlertEmail');

            if (!emailregex.test(email)) {
                if (errorAlertEmail) errorAlertEmail.classList.remove('hidden');
                emailInput.classList.add('border-red-500', 'animate-shake');
                emailInput.classList.remove('focus:border-brand-500', 'focus:ring-brand-500/20');
                emailInput.classList.add('focus:border-red-500', 'focus:ring-red-500/20');

                setTimeout(() => {
                    emailInput.classList.remove('animate-shake');
                }, 300);

                hasError = true;
            } else {
                if (errorAlertEmail) errorAlertEmail.classList.add('hidden');
                emailInput.classList.remove('border-red-300', 'border-red-500',
                    'focus:border-red-500', 'focus:ring-red-500/20');
                emailInput.classList.add('focus:border-brand-500', 'focus:ring-brand-500/20');
            }

            // 2. Validación de Contraseña (Solo mínimo 8 caracteres)
            const passwordregex = /^.{8,}$/;
            const errorAlertPassword = document.getElementById('errorAlertPassword');

            if (!passwordregex.test(password)) {
                if (errorAlertPassword) errorAlertPassword.classList.remove('hidden');
                passwordInput.classList.add('border-red-500', 'animate-shake');
                passwordInput.classList.remove('focus:border-brand-500', 'focus:ring-brand-500/20');
                passwordInput.classList.add('focus:border-red-500', 'focus:ring-red-500/20');

                setTimeout(() => {
                    passwordInput.classList.remove('animate-shake');
                }, 300);

                hasError = true;
            } else {
                if (errorAlertPassword) errorAlertPassword.classList.add('hidden');
                passwordInput.classList.remove('border-red-300', 'border-red-500',
                    'focus:border-red-500', 'focus:ring-red-500/20');
                passwordInput.classList.add('focus:border-brand-500', 'focus:ring-brand-500/20');
            }

            if (hasError) {
                return;
            }

            // Mostrar estado de carga en el botón
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '';
                const newdiv = document.createElement('div');
                newdiv.classList.add('loader');
                submitButton.appendChild(newdiv);
            }

            await iniciarSesion(email, password);
        });
    });

</script>

</html>
