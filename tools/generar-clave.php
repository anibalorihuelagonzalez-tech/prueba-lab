<?php
/**
 * tools/generar-clave.php  —  Generador de hash de contraseña (SOLO por consola)
 * -------------------------------------------------------------------------
 * REEMPLAZA a la antigua ruta pública /clave, que mostraba en el navegador el
 * hash de una contraseña fija (123456789) para cualquier visitante.
 *
 * Uso (desde la carpeta del proyecto):
 *     php tools/generar-clave.php "MiNuevaClaveSegura"
 * Copia el resultado en la columna usuarios.contrasena (phpMyAdmin).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$clave = $argv[1] ?? '';
if ($clave === '') {
    fwrite(STDERR, "Uso: php tools/generar-clave.php \"tu clave\"\n");
    exit(1);
}
echo password_hash($clave, PASSWORD_DEFAULT) . PHP_EOL;
