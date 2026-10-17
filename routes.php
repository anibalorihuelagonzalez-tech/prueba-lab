<?php
/**
 * =========================================================================
 * routes.php  —  PUNTO DE ENTRADA ÚNICO (front controller) DE LA APLICACIÓN
 * =========================================================================
 * Todas las URLs pasan por aquí (ver .htaccess). Este archivo decide qué vista
 * o qué controlador (endpoint AJAX) se ejecuta.
 *
 * Dos tipos de rutas:
 *   1) VISTAS (páginas HTML)  -> se registran con vista(), que además COMPRUEBA
 *      sesión y rol ANTES de mostrar la página (antes algunas vistas no
 *      validaban nada y la matriz de permisos de Auth no se usaba).
 *   2) CONTROLADORES (JSON)   -> se registran con any(); cada controlador se
 *      protege a sí mismo con apiGuard() (ver app/helpers/api-guard.php).
 */

require_once __DIR__ . '/router.php';
require_once __DIR__ . '/app/helpers/seguridad.php';
require_once __DIR__ . '/app/helpers/auth.php';

// Cookie de sesión segura (HttpOnly + SameSite). No abre la sesión: solo la configura.
configurarCookieSesion();

/**
 * Registra una VISTA protegida.
 *
 * @param string $url         URL pública, ej: '/ventas/facturas'
 * @param string $archivo     Archivo de la vista, ej: 'app/views/ventas/documentos.php'
 * @param string $permisoClave Clave en la matriz de Auth::$permisosVistas (ej: 'ventas/facturacion').
 *                             Si es '' solo se exige estar autenticado.
 */
function vista(string $url, string $archivo, string $permisoClave = ''): void
{
    get($url, function () use ($archivo, $permisoClave) {
        iniciarSesionSegura();

        // 1) Debe haber iniciado sesión
        if (!Auth::check()) {
            header('Location: /');
            exit();
        }
        // 2) Debe tener un rol con permiso para esta vista
        if ($permisoClave !== '' && !Auth::canAccessView($permisoClave)) {
            http_response_code(403);
            include __DIR__ . '/app/views/404.php';
            exit();
        }
        include __DIR__ . '/' . $archivo;
        exit();
    });
}

// =========================================================================
// 1. VISTAS (GET)
// =========================================================================

// --- Inicio / Login (públicas) ---
get('/', 'app/views/index.php');

// --- Factura digital pública: se abre desde el enlace que se envía por WhatsApp.
//     No requiere sesión; se identifica con un código aleatorio e imposible de adivinar.
get('/factura-digital/$codigo', 'app/views/ventas/factura-digital.php');

// --- Módulo: Ventas ---
//     Una sola vista (documentos.php) sirve para todos los tipos; el tipo se deduce de la URL.
vista('/ventas/facturacion',    'app/views/ventas/documentos.php', 'ventas/facturacion');
vista('/ventas/prefacturas',    'app/views/ventas/documentos.php', 'ventas/facturacion');
vista('/ventas/facturas',       'app/views/ventas/documentos.php', 'ventas/facturacion');
vista('/ventas/notas-credito',  'app/views/ventas/documentos.php', 'ventas/facturacion');
vista('/ventas/notas-entrega',  'app/views/ventas/documentos.php', 'ventas/facturacion');
vista('/ventas/cobranzas',      'app/views/ventas/cobranzas.php',  'ventas/cobranzas');
vista('/ventas/metodos-de-pago','app/views/ventas/metodos-de-pago.php', 'ventas/metodos-de-pago');

// --- Módulo: Contabilidad ---
vista('/contabilidad/plan-cuentas',        'app/views/contabilidad/plan-cuentas.php',        'contabilidad/plan-cuentas');
vista('/contabilidad/asientos',            'app/views/contabilidad/asientos.php',            'contabilidad/asientos');
vista('/contabilidad/libros',              'app/views/contabilidad/libros.php',              'contabilidad/libros');
vista('/contabilidad/estados-financieros', 'app/views/contabilidad/estados-financieros.php', 'contabilidad/estados-financieros');
vista('/contabilidad/gastos',              'app/views/contabilidad/gastos.php',              'contabilidad/gastos');
vista('/contabilidad/periodos',            'app/views/contabilidad/periodos.php',            'contabilidad/periodos');
vista('/contabilidad/configuracion',       'app/views/contabilidad/configuracion.php',       'contabilidad/configuracion');

// --- Módulo: Laboratorio ---
vista('/laboratorio/clinicas-sedes', 'app/views/laboratorio/clinicas-sedes.php', 'laboratorio/clinicas-sedes');
vista('/laboratorio/pacientes',      'app/views/laboratorio/pacientes.php',      'laboratorio/pacientes');
vista('/laboratorio/casos',          'app/views/laboratorio/casos.php',          'laboratorio/casos');

// --- Módulo: Logística / Envíos ---
vista('/logistica/despachos', 'app/views/logistica/despachos.php', 'logistica/despachos');
vista('/logistica/tracking',  'app/views/logistica/tracking.php',  'logistica/tracking');
vista('/envios/tracking',     'app/views/logistica/tracking.php',  'logistica/tracking');   // Alias
vista('/logistica/recepcion', 'app/views/logistica/recepcion.php', '');
vista('/envios/recepcion',    'app/views/logistica/recepcion.php', '');                    // Alias

// --- Módulo: Administración ---
vista('/administracion/dashboard-financiero', 'app/views/administracion/dashboard-financiero.php', 'administracion/dashboard-financiero');
vista('/administracion/documentos',           'app/views/administracion/documentos.php',           'administracion/documentos');

// --- Módulo: Recursos Humanos ---
vista('/recursos-humanos/personal',      'app/views/recursos-humanos/personal.php',      'recursos-humanos/personal');
vista('/recursos-humanos/cargos',        'app/views/recursos-humanos/cargos.php',        'recursos-humanos/personal');
vista('/recursos-humanos/departamentos', 'app/views/recursos-humanos/departamentos.php', 'recursos-humanos/personal');

// --- Módulo: Configuraciones ---
vista('/configuraciones/gestion-usuarios', 'app/views/configuraciones/gestion-usuarios.php', 'configuraciones/gestion-usuarios');
vista('/configuraciones/tasa-de-cambio',   'app/views/configuraciones/tasa-de-cambio.php',   'configuraciones/tasa-de-cambio');
vista('/configuraciones/monedas',          'app/views/configuraciones/monedas.php',          'configuraciones/tasa-de-cambio');


// =========================================================================
// 2. CONTROLADORES (API / AJAX ENDPOINTS)  — cada uno se protege con apiGuard()
// =========================================================================

// --- Autenticación y Sesiones ---
any('/iniciar-sesion-controller', 'app/controllers/iniciar-sesion-controller.php');
any('/cerrar-sesion-controller',  'app/controllers/cerrar-sesion-controller.php');

// --- Gestión de Usuarios ---
any('/obtener-usuarios-controller', 'app/controllers/obtener-usuarios-controller.php');
any('/crear-usuario-controller',    'app/controllers/crear-usuario-controller.php');
any('/editar-usuario-controller',   'app/controllers/editar-usuario-controller.php');
any('/baja-usuario-controller',     'app/controllers/baja-usuario-controller.php');

// --- Tasas de Cambio y Monedas ---
any('/obtener-tasas-de-cambio-controller', 'app/controllers/obtener-tasas-de-cambio-controller.php');
any('/agregar-tasa-de-cambio-controller',  'app/controllers/agregar-tasa-de-cambio-controller.php');
post('/editar-tasa-de-cambio-controller',  'app/controllers/editar-tasa-de-cambio-controller.php');
any('/eliminar-tasa-de-cambio-controller', 'app/controllers/eliminar-tasa-de-cambio-controller.php');
any('/monedas-controller',                 'app/controllers/monedas-controller.php');

// --- Clínicas ---
any('/obtener-clinicas-controller', 'app/controllers/obtener-clinicas-controller.php');
any('/agregar-clinica-controller',  'app/controllers/agregar-clinica-controller.php');
any('/editar-clinica-controller',   'app/controllers/editar-clinica-controller.php');
any('/eliminar-clinica-controller', 'app/controllers/eliminar-clinica-controller.php');

// --- Sedes / Sucursales ---
any('/obtener-sedes-controller', 'app/controllers/obtener-sedes-controller.php');
any('/agregar-sede-controller',  'app/controllers/agregar-sede-controller.php');
any('/editar-sede-controller',   'app/controllers/editar-sede-controller.php');
any('/eliminar-sede-controller', 'app/controllers/eliminar-sede-controller.php');

// --- Pacientes y Odontogramas ---
any('/obtener-pacientes-controller',            'app/controllers/obtener-pacientes-controller.php');
any('/agregar-paciente-controller',             'app/controllers/agregar-paciente-controller.php');
any('/editar-paciente-controller',              'app/controllers/editar-paciente-controller.php');
any('/eliminar-paciente-controller',            'app/controllers/eliminar-paciente-controller.php'); // (archivo nuevo)
any('/obtener-odontograma-paciente-controller', 'app/controllers/obtener-odontograma-paciente-controller.php');
any('/editar-odontograma-paciente-controller',  'app/controllers/editar-odontograma-paciente-controller.php');

// --- Laboratorio: Gestión de Casos, Prótesis y Trazabilidad ---
any('/obtener-casos-controller',  'app/controllers/obtener-casos-controller.php');
any('/agregar-caso-controller',   'app/controllers/agregar-caso-controller.php');
any('/procesar-caso-controller',  'app/controllers/procesar-caso-controller.php');

// --- Logística: Despachos / Envíos (Cajas Grandes y Pequeñas) ---
any('/obtener-despachos-controller',  'app/controllers/obtener-despachos-controller.php');
any('/agregar-despacho-controller',   'app/controllers/agregar-despacho-controller.php');
any('/agregar-despachos-controller',  'app/controllers/agregar-despacho-controller.php'); // Alias plural
any('/editar-despacho-controller',    'app/controllers/editar-despacho-controller.php');
// CORREGIDO: antes apuntaba a un archivo inexistente (eliminar-despacho-controller.php)
any('/eliminar-despacho-controller',  'app/controllers/eliminar-despachos-controller.php');
any('/eliminar-despachos-controller', 'app/controllers/eliminar-despachos-controller.php');

// --- Logística: Flota de Repartidores, Tracking y Recepción ---
any('/repartidores-controller', 'app/controllers/repartidores-controller.php');
any('/tracking-controller',     'app/controllers/tracking-controller.php');
any('/recepcion-controller',    'app/controllers/recepcion-controller.php');

// --- Administración: Documentos (no fiscales) y Dashboard Financiero ---
any('/documentos-controller',          'app/controllers/documentos-controller.php');
any('/dashboard-financiero-controller','app/controllers/dashboard-financiero-controller.php');

// --- Ventas: facturación, cobranzas y métodos de pago (NUEVO) ---
any('/ventas-controller',        'app/controllers/ventas-controller.php');
any('/cobros-controller',        'app/controllers/cobros-controller.php');
any('/metodos-pago-controller',  'app/controllers/metodos-pago-controller.php');

// --- Contabilidad (NUEVO): plan de cuentas, asientos, períodos, reportes y gastos ---
any('/contabilidad-controller',        'app/controllers/contabilidad-controller.php');
any('/reportes-contables-controller',  'app/controllers/reportes-contables-controller.php');
any('/gastos-controller',              'app/controllers/gastos-controller.php');

// --- Recursos Humanos: Personal, Cargos y Departamentos ---
any('/recursos-humanos-controller', 'app/controllers/recursos-humanos-controller.php');
any('/cargos-controller',           'app/controllers/cargos-controller.php');
any('/departamentos-controller',    'app/controllers/departamentos-controller.php');


// =========================================================================
// 3. FALLBACK 404 (SIEMPRE AL FINAL DEL ARCHIVO)
// =========================================================================
any('/404', 'app/views/404.php');
