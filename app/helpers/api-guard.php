<?php
/**
 * =========================================================================
 * app/helpers/api-guard.php  —  "PORTERO" DE LOS CONTROLADORES (API/AJAX)
 * =========================================================================
 * Antes, muchos controladores podían llamarse SIN iniciar sesión (cualquiera
 * podía listar usuarios, pacientes, etc.). Ahora CADA controlador incluye
 * este archivo y llama a apiGuard([...roles]) en su primera línea útil.
 *
 * apiGuard():
 *   1) abre la sesión de forma segura,
 *   2) exige usuario autenticado (si no -> HTTP 401 en JSON),
 *   3) valida el origen de la petición (anti-CSRF),
 *   4) si se indican roles, exige que el usuario tenga uno de ellos
 *      (el SUPER USUARIO siempre pasa) -> si no, HTTP 403.
 *
 * Ejemplo:  apiGuard(['ADMINISTRADOR']);   // solo admin y super usuario
 *           apiGuard();                    // cualquier usuario logueado
 */

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/auth.php';

if (!function_exists('apiGuard')) {
    function apiGuard(array $rolesPermitidos = []): void
    {
        iniciarSesionSegura();

        if (!Auth::check()) {
            responderJson([
                'status'  => 'error',
                'code'    => 'NO_AUTH',
                'message' => 'Tu sesión expiró. Vuelve a iniciar sesión.',
            ], 401);
        }

        verificarOrigen();

        if (!empty($rolesPermitidos) && !Auth::isSuperUser() && !Auth::hasRole($rolesPermitidos)) {
            responderJson([
                'status'  => 'error',
                'code'    => 'SIN_PERMISO',
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }
    }
}
