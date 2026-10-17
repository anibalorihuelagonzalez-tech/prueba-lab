<?php
/**
 * Helper de Autenticación y Autorización de Usuarios
 * Ubicación: app/helpers/auth.php
 * 
 * Este archivo centraliza la validación de sesión, identificación del usuario autenticado
 * y el control de acceso a vistas y módulos según el rol del usuario.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

class Auth {
    /**
     * Matriz de permisos predeterminada por vista / módulo.
     * Puedes modificar o extender estos roles según las necesidades del negocio.
     */
    private static array $permisosVistas = [
        // Módulo Envíos / Logística
        'logistica/tracking'          => ['SUPER USUARIO', 'ADMINISTRADOR', 'RECEPCIONISTA', 'PROTESISTA'],
        'logistica/despachos'         => ['SUPER USUARIO', 'ADMINISTRADOR', 'RECEPCIONISTA'],

        // Módulo Laboratorio
        'laboratorio/clinicas-sedes'  => ['SUPER USUARIO', 'ADMINISTRADOR', 'RECEPCIONISTA'],
        'laboratorio/pacientes'       => ['SUPER USUARIO', 'ADMINISTRADOR', 'RECEPCIONISTA', 'PROTESISTA'],
        'laboratorio/casos'           => ['SUPER USUARIO', 'ADMINISTRADOR', 'RECEPCIONISTA', 'PROTESISTA'],

        // Módulo Administración (Generación de Documentos)
        'administracion/documentos'   => ['SUPER USUARIO', 'ADMINISTRADOR'],
        
        // Módulo Recursos Humanos
        'recursos-humanos/personal'   => ['SUPER USUARIO', 'ADMINISTRADOR'],

        // Módulo Ventas
        'ventas/prefacturas'          => ['SUPER USUARIO', 'ADMINISTRADOR', 'RECEPCIONISTA'],
        'ventas/metodos-de-pago'      => ['SUPER USUARIO', 'ADMINISTRADOR'],

        // Módulo Administración (Dashboard financiero)
        'administracion/dashboard-financiero' => ['SUPER USUARIO', 'ADMINISTRADOR'],

        // Módulo Ventas (facturación, cobranzas, métodos de pago)
        'ventas/facturacion'          => ['SUPER USUARIO', 'ADMINISTRADOR', 'RECEPCIONISTA'],
        'ventas/cobranzas'            => ['SUPER USUARIO', 'ADMINISTRADOR', 'RECEPCIONISTA'],

        // Módulo Contabilidad (solo administración)
        'contabilidad/plan-cuentas'   => ['SUPER USUARIO', 'ADMINISTRADOR'],
        'contabilidad/asientos'       => ['SUPER USUARIO', 'ADMINISTRADOR'],
        'contabilidad/libros'         => ['SUPER USUARIO', 'ADMINISTRADOR'],
        'contabilidad/estados-financieros' => ['SUPER USUARIO', 'ADMINISTRADOR'],
        'contabilidad/gastos'         => ['SUPER USUARIO', 'ADMINISTRADOR'],
        'contabilidad/periodos'       => ['SUPER USUARIO', 'ADMINISTRADOR'],
        'contabilidad/configuracion'  => ['SUPER USUARIO', 'ADMINISTRADOR'],

        // Módulo Configuraciones
        'configuraciones/gestion-usuarios' => ['SUPER USUARIO', 'ADMINISTRADOR'],
        'configuraciones/tasa-de-cambio'   => ['SUPER USUARIO', 'ADMINISTRADOR'],
    ];

    /**
     * Verifica si hay un usuario autenticado con sesión activa.
     */
    public static function check(): bool {
        return !empty($_SESSION['usuario_id']);
    }

    /**
     * Alias en español para check().
     */
    public static function estaAutenticado(): bool {
        return self::check();
    }

    /**
     * Verifica si no hay ningún usuario autenticado (visitante).
     */
    public static function guest(): bool {
        return !self::check();
    }

    /**
     * Retorna el ID del usuario en sesión.
     */
    public static function id(): ?int {
        return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
    }

    /**
     * Retorna el nombre completo del usuario en sesión.
     */
    public static function name(): ?string {
        return $_SESSION['usuario_nombre'] ?? null;
    }

    /**
     * Retorna el correo electrónico del usuario en sesión.
     */
    public static function email(): ?string {
        return $_SESSION['usuario_correo'] ?? null;
    }

    /**
     * Retorna el rol_id del usuario en sesión.
     */
    public static function roleId(): ?int {
        return isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;
    }

    /**
     * Retorna el nombre del rol del usuario (ej: 'SUPER USUARIO', 'ADMINISTRADOR', 'PROTESISTA').
     * Si no está en sesión, lo consulta directamente en la base de datos.
     */
    public static function roleName(): ?string {
        if (!empty($_SESSION['rol_nombre'])) {
            return strtoupper(trim($_SESSION['rol_nombre']));
        }

        $rolId = self::roleId();
        if (!$rolId) {
            return null;
        }

        try {
            $db = conexion();
            $stmt = $db->prepare("SELECT rol FROM roles_usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([$rolId]);
            $nombre = $stmt->fetchColumn();
            if ($nombre) {
                $_SESSION['rol_nombre'] = strtoupper(trim($nombre));
                return $_SESSION['rol_nombre'];
            }
        } catch (\Exception $e) {
            // Ignorar error de consulta y retornar null
        }

        return null;
    }

    /**
     * Retorna todos los datos del usuario autenticado actual.
     */
    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }

        $id = self::id();
        try {
            $db = conexion();
            $stmt = $db->prepare("
                SELECT u.id, u.primer_nombre, u.primer_apellido, u.correo_electronico, 
                       u.rol_id, r.rol AS rol_nombre, u.estatus
                FROM usuarios u
                INNER JOIN roles_usuarios r ON u.rol_id = r.id
                WHERE u.id = ? LIMIT 1
            ");
            $stmt->execute([$id]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (\Exception $e) {
            return [
                'id' => self::id(),
                'nombre' => self::name(),
                'correo' => self::email(),
                'rol_id' => self::roleId(),
                'rol_nombre' => self::roleName(),
            ];
        }
    }

    /**
     * Comprueba si el usuario autenticado tiene uno o varios roles específicos.
     * Soporta nombres de roles ('PROTESISTA', 'ADMINISTRADOR') o IDs (1, 2, 3...).
     * 
     * @param string|int|array $roles Uno o varios roles a validar.
     * @return bool
     */
    public static function hasRole(string|int|array $roles): bool {
        if (!self::check()) {
            return false;
        }

        $userRoleId = self::roleId();
        $userRoleName = self::roleName();

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $r) {
            if (is_int($r) || ctype_digit((string)$r)) {
                if ((int)$r === $userRoleId) {
                    return true;
                }
            } else {
                $nombreComparar = strtoupper(trim((string)$r));
                if ($userRoleName && ($userRoleName === $nombreComparar || str_contains($userRoleName, $nombreComparar))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Alias en español para hasRole().
     */
    public static function tieneRol(string|int|array $roles): bool {
        return self::hasRole($roles);
    }

    /**
     * Helpers rápidos para los roles estándar de la base de datos
     */
    public static function isSuperUser(): bool {
        return self::hasRole(['SUPER USUARIO', 1]);
    }

    public static function isAdmin(): bool {
        return self::hasRole(['ADMINISTRADOR', 'SUPER USUARIO', 1, 2]);
    }

    public static function isProsthetist(): bool {
        return self::hasRole(['PROTESISTA', 4]);
    }

    public static function isReceptionist(): bool {
        return self::hasRole(['RECEPCIONISTA', 3]);
    }

    public static function isClinicAdmin(): bool {
        return self::hasRole(['CLINICA ADMIN', 5]);
    }

    /**
     * Determina si el usuario actual tiene permisos para acceder o visualizar una vista.
     * 
     * @param string $rutaVista Ej: 'laboratorio/casos', 'administracion/documentos'
     * @return bool
     */
    public static function canAccessView(string $rutaVista): bool {
        // Super usuario siempre tiene acceso global
        if (self::isSuperUser()) {
            return true;
        }

        // Limpiar diagonales iniciales
        $rutaLimpia = ltrim($rutaVista, '/');

        // Si la vista no está en la matriz, por defecto permitir si está autenticado
        if (!isset(self::$permisosVistas[$rutaLimpia])) {
            return self::check();
        }

        $rolesPermitidos = self::$permisosVistas[$rutaLimpia];
        return self::hasRole($rolesPermitidos);
    }

    /**
     * Permite modificar o configurar la matriz de permisos de vistas dinámicamente.
     */
    public static function setPermissions(array $permisos): void {
        self::$permisosVistas = array_merge(self::$permisosVistas, $permisos);
    }

    /**
     * Exige que el usuario esté autenticado. Si no lo está, redirige al login.
     * 
     * @param string $redirect Ruta a redirigir (por defecto '/')
     */
    public static function requireAuth(string $redirect = '/'): void {
        if (!self::check()) {
            header("Location: {$redirect}");
            exit();
        }
    }

    /**
     * Exige que el usuario tenga un rol específico para acceder a la página.
     * Si no lo tiene, redirige a una página de error o 404.
     * 
     * @param string|int|array $roles Roles autorizados.
     * @param string $redirect Ruta de fallo (por defecto '/404')
     */
    public static function requireRole(string|int|array $roles, string $redirect = '/404'): void {
        self::requireAuth();

        if (!self::hasRole($roles)) {
            header("Location: {$redirect}");
            exit();
        }
    }

    /**
     * Inicia sesión guardando los datos del usuario en $_SESSION.
     */
    public static function login(array $usuario): void {
        // 🔒 Seguridad: se genera un ID de sesión NUEVO al iniciar sesión para evitar
        // "fijación de sesión" (que alguien reutilice un ID de sesión previo).
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nombre'] = trim(($usuario['primer_nombre'] ?? '') . ' ' . ($usuario['primer_apellido'] ?? ''));
        $_SESSION['usuario_correo'] = $usuario['correo_electronico'] ?? '';
        $_SESSION['rol_id']         = $usuario['rol_id'] ?? null;
        
        if (!empty($usuario['rol'])) {
            $_SESSION['rol_nombre'] = strtoupper(trim($usuario['rol']));
        } elseif (!empty($usuario['rol_nombre'])) {
            $_SESSION['rol_nombre'] = strtoupper(trim($usuario['rol_nombre']));
        } else {
            // Resolver nombre del rol
            self::roleName();
        }
    }

    /**
     * Destruye la sesión del usuario actual.
     */
    public static function logout(): void {
        $_SESSION = [];
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
        session_destroy();
    }
}

// =========================================================================
// FUNCIONES PROCEDURALES GLOBALES (Para uso rápido en plantillas PHP)
// =========================================================================

if (!function_exists('estaAutenticado')) {
    function estaAutenticado(): bool {
        return Auth::check();
    }
}

if (!function_exists('usuarioActual')) {
    function usuarioActual(): ?array {
        return Auth::user();
    }
}

if (!function_exists('usuarioId')) {
    function usuarioId(): ?int {
        return Auth::id();
    }
}

if (!function_exists('usuarioNombre')) {
    function usuarioNombre(): ?string {
        return Auth::name();
    }
}

if (!function_exists('rolActual')) {
    function rolActual(): ?string {
        return Auth::roleName();
    }
}

if (!function_exists('tieneRol')) {
    function tieneRol(string|int|array $roles): bool {
        return Auth::hasRole($roles);
    }
}

if (!function_exists('puedeVerVista')) {
    function puedeVerVista(string $vista): bool {
        return Auth::canAccessView($vista);
    }
}

if (!function_exists('requerirAutenticacion')) {
    function requerirAutenticacion(string $redirect = '/'): void {
        Auth::requireAuth($redirect);
    }
}

if (!function_exists('requerirRol')) {
    function requerirRol(string|int|array $roles, string $redirect = '/404'): void {
        Auth::requireRole($roles, $redirect);
    }
}
