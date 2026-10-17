<?php
// app/controllers/logout-controller.php
// o /api/logout-controller.php según tu estructura

require_once __DIR__ . '/../helpers/seguridad.php';
iniciarSesionSegura(); // abre la sesión (cookie segura) para poder destruirla

// 1. Limpiar todas las variables de sesión
$_SESSION = [];

// 2. Destruir la cookie de sesión en el navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Redirigir al login
header('Location: /');
exit();