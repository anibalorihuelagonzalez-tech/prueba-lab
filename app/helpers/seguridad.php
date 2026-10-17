<?php
/**
 * =========================================================================
 * app/helpers/seguridad.php  —  CAPA DE SEGURIDAD COMPARTIDA
 * =========================================================================
 * Este archivo reúne las protecciones que ANTES faltaban en el proyecto:
 *
 *  1. iniciarSesionSegura()  -> abre la sesión con cookie HttpOnly + SameSite
 *                               (evita robo de cookie por JS y CSRF cruzado).
 *  2. errorSeguro()          -> cuando ocurre una excepción NO le muestra al
 *                               usuario el texto técnico de MySQL (que revela
 *                               nombres de tablas/columnas); lo guarda en el
 *                               log del servidor y devuelve un mensaje amable.
 *  3. verificarOrigen()      -> protección CSRF "sin tocar las vistas":
 *                               rechaza peticiones POST/PUT/DELETE que no
 *                               vengan del mismo dominio de la aplicación.
 *  4. responderJson()        -> atajo para devolver JSON y terminar.
 *
 * Para ver los errores técnicos reales mientras desarrollas, pon
 * APP_DEBUG=true en el archivo .env (nunca en producción).
 */

if (!function_exists('configurarCookieSesion')) {
    /**
     * Fija los parámetros de la cookie de sesión SIN abrir la sesión.
     * routes.php lo llama en cada petición, así las vistas que todavía hacen
     * session_start() por su cuenta también quedan con cookie segura.
     */
    function configurarCookieSesion(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,          // la cookie muere al cerrar el navegador
            'path'     => '/',
            'httponly' => true,       // JavaScript NO puede leer la cookie
            'samesite' => 'Lax',      // no se envía en peticiones POST de otros sitios
            'secure'   => $https,     // solo por HTTPS cuando el sitio use HTTPS
        ]);
        ini_set('session.use_strict_mode', '1'); // rechaza IDs de sesión inventados por un atacante
    }
}

if (!function_exists('iniciarSesionSegura')) {
    /**
     * Inicia la sesión PHP una sola vez, con parámetros de cookie seguros.
     * Es seguro llamarla varias veces (si ya hay sesión no hace nada).
     */
    function iniciarSesionSegura(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        configurarCookieSesion();
        session_start();
    }
}

if (!function_exists('modoDebug')) {
    /** ¿Está activado APP_DEBUG en el .env? */
    function modoDebug(): bool
    {
        require_once __DIR__ . '/../config/database.php'; // asegura que el .env esté cargado
        $v = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'false';
        return in_array(strtolower((string)$v), ['1', 'true', 'yes', 'si'], true);
    }
}

if (!function_exists('errorSeguro')) {
    /**
     * Convierte una excepción en un texto SEGURO para mostrar al usuario.
     * - Guarda siempre el detalle técnico en el log del servidor (error_log).
     * - Si es un error de integridad (clave foránea / duplicado) da un mensaje claro.
     * - En modo debug devuelve el mensaje real para facilitar el desarrollo.
     */
    function errorSeguro(Throwable $e): string
    {
        error_log('[CNSB_LAB] ' . get_class($e) . ': ' . $e->getMessage()
            . ' @ ' . $e->getFile() . ':' . $e->getLine());

        if (modoDebug()) {
            return $e->getMessage();
        }
        if ($e instanceof PDOException) {
            $sqlstate = (string)$e->getCode();
            if ($sqlstate === '23000') {
                return 'No se pudo completar la operación: el registro está relacionado con otra información '
                     . '(o ya existe un valor duplicado).';
            }
            return 'Ocurrió un error en la base de datos. El detalle quedó registrado para el administrador.';
        }
        return 'Ocurrió un error inesperado. El detalle quedó registrado para el administrador.';
    }
}

if (!function_exists('responderJson')) {
    /** Devuelve un JSON, fija el código HTTP y termina la ejecución. */
    function responderJson(array $datos, int $codigoHttp = 200): void
    {
        if (!headers_sent()) {
            http_response_code($codigoHttp);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}

if (!function_exists('verificarOrigen')) {
    /**
     * Defensa CSRF por cabeceras Origin/Referer.
     * Una página maliciosa de OTRO dominio no puede hacer que el navegador
     * del usuario envíe un POST con Origin = nuestro dominio, así que si el
     * Origin/Referer no coincide con el host actual (o con APP_URL) se rechaza.
     * Las peticiones GET no se validan (no deben modificar datos).
     */
    function verificarOrigen(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($metodo, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }
        require_once __DIR__ . '/../config/database.php'; // asegura que el .env (APP_URL) esté cargado

        $origen = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
        if ($origen === '') {
            // Los navegadores siempre envían Origin en POST; sin cabecera suele ser
            // una herramienta de pruebas (curl/Postman). Se permite para no estorbar.
            return;
        }

        $partes = parse_url($origen);
        if (!$partes || empty($partes['host'])) {
            responderJson(['status' => 'error', 'message' => 'Origen de la petición no válido.'], 403);
        }
        $autoridadOrigen = strtolower($partes['host'] . (isset($partes['port']) ? ':' . $partes['port'] : ''));

        $permitidos = [strtolower($_SERVER['HTTP_HOST'] ?? '')];
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $permitidos[] = strtolower($_SERVER['HTTP_X_FORWARDED_HOST']);
        }
        $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: '';
        if ($appUrl !== '') {
            $p = parse_url($appUrl);
            if (!empty($p['host'])) {
                $permitidos[] = strtolower($p['host'] . (isset($p['port']) ? ':' . $p['port'] : ''));
            }
        }

        if (!in_array($autoridadOrigen, $permitidos, true)) {
            responderJson(['status' => 'error', 'message' => 'Petición bloqueada por seguridad (origen distinto).'], 403);
        }
    }
}
