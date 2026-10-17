<?php
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
// Controlador para edición de tasa de cambio
$_POST['action'] = $_POST['action'] ?? 'update';
require_once __DIR__ . '/agregar-tasa-de-cambio-controller.php';
