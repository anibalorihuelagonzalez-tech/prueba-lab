<?php
/**
 * app/controllers/iniciar-sesion-controller.php
 * -------------------------------------------------------------------------
 * Inicio de sesión (endpoint público: es el único que no exige estar logueado).
 *
 * Mejoras de seguridad respecto a la versión anterior:
 *   - Solo deja entrar a usuarios ACTIVOS (estatus = 1). Antes un usuario dado
 *     de baja podía seguir iniciando sesión.
 *   - Límite de intentos fallidos (5 en 10 minutos por correo + IP) contra
 *     ataques de fuerza bruta.
 *   - Nuevo ID de sesión al entrar (Auth::login lo regenera).
 *   - No se muestran errores técnicos al usuario.
 */
require_once __DIR__ . '/../helpers/seguridad.php';
iniciarSesionSegura();
verificarOrigen();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['status' => 'error', 'message' => 'Método no permitido.'], 405);
}

$inputData  = json_decode(file_get_contents('php://input'), true);
$correo     = trim($inputData['email'] ?? $_POST['correo_electronico'] ?? '');
$contrasena = $inputData['password'] ?? $_POST['contrasena'] ?? '';

if ($correo === '' || $contrasena === '') {
    responderJson(['status' => 'error', 'message' => 'Por favor, completa todos los campos.'], 400);
}

// ---------------------------------------------------------------------------
// Anti fuerza-bruta: contador de intentos fallidos guardado en un archivo temporal
// (clave = correo + IP). No requiere tablas nuevas.
// ---------------------------------------------------------------------------
const LOGIN_MAX_INTENTOS = 5;      // intentos permitidos
const LOGIN_VENTANA_SEG  = 600;    // ventana de 10 minutos

$archivoIntentos = sys_get_temp_dir() . '/cnsb_login_' . md5(strtolower($correo) . '|' . ($_SERVER['REMOTE_ADDR'] ?? '')) . '.json';
$intentos = ['n' => 0, 'desde' => time()];
if (is_file($archivoIntentos)) {
    $leido = json_decode((string)@file_get_contents($archivoIntentos), true);
    if (is_array($leido) && (time() - (int)($leido['desde'] ?? 0)) < LOGIN_VENTANA_SEG) {
        $intentos = $leido;
    }
}
if ($intentos['n'] >= LOGIN_MAX_INTENTOS) {
    $faltan = max(1, (int)ceil((LOGIN_VENTANA_SEG - (time() - $intentos['desde'])) / 60));
    responderJson([
        'status'  => 'error',
        'message' => "Demasiados intentos fallidos. Intenta de nuevo en {$faltan} minuto(s).",
    ], 429);
}

try {
    $db = conexion();

    // Solo usuarios activos (u.estatus = 1)
    $stmt = $db->prepare("
        SELECT u.id, u.primer_nombre, u.primer_apellido, u.correo_electronico, u.contrasena,
               u.rol_id, r.rol AS rol_nombre
        FROM usuarios u
        LEFT JOIN roles_usuarios r ON u.rol_id = r.id
        WHERE u.correo_electronico = ? AND u.estatus = 1
        LIMIT 1
    ");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
        @unlink($archivoIntentos); // éxito: se reinicia el contador

        require_once __DIR__ . '/../helpers/auth.php';
        Auth::login($usuario);

        responderJson([
            'status'   => 'success',
            'message'  => 'Inicio de sesión exitoso.',
            'redirect' => '/laboratorio/clinicas-sedes',
            'data'     => [
                'id'     => $usuario['id'],
                'nombre' => $usuario['primer_nombre'] . ' ' . $usuario['primer_apellido'],
                'correo' => $usuario['correo_electronico'],
                'rol_id' => $usuario['rol_id'],
                'rol'    => $usuario['rol_nombre'] ?? '',
            ],
        ]);
    }

    // Credenciales incorrectas: se suma un intento fallido
    $intentos['n']++;
    @file_put_contents($archivoIntentos, json_encode($intentos));
    responderJson(['status' => 'error', 'message' => 'Correo electrónico o contraseña incorrectos.'], 401);

} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)], 500);
}
