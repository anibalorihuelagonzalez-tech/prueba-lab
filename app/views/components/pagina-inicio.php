<?php
/**
 * app/views/components/pagina-inicio.php  —  CABECERA COMÚN de las pantallas nuevas
 * (Ventas y Contabilidad). Hace tres cosas:
 *   1) comprueba que haya sesión iniciada,
 *   2) imprime <head>, menú lateral y barra superior,
 *   3) abre el <main> donde cada pantalla pone su contenido.
 * Antes de incluirlo, la pantalla define:
 *   $tituloPagina  (texto del título)   $iconoPagina (nombre de icono Lucide)
 * Se cierra con pagina-fin.php.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../helpers/auth.php';
if (!Auth::check()) {
    header('Location: /');
    exit();
}
$tituloPagina = $tituloPagina ?? 'Panel';
$iconoPagina  = $iconoPagina ?? 'layout-dashboard';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<?php include_once __DIR__ . '/head.php'; ?>

<body class="h-full flex overflow-hidden">
    <!-- Utilidades JS compartidas (api(), fmt(), toast()...) -->
    <script src="/assets/js/cnsb.js"></script>

    <!-- MENÚ LATERAL -->
    <?php include_once __DIR__ . '/aside.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <!-- BARRA SUPERIOR -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-8 z-10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center font-bold">
                    <i data-lucide="<?= htmlspecialchars($iconoPagina) ?>" class="w-4 h-4"></i>
                </div>
                <h1 class="text-lg md:text-xl font-bold text-slate-800 tracking-tight"><?= htmlspecialchars($tituloPagina) ?></h1>
            </div>
            <div class="hidden sm:flex items-center space-x-3">
                <span class="text-sm font-medium text-slate-600">Hola, <strong><?= htmlspecialchars(Auth::name() ?? 'Usuario') ?></strong></span>
                <div class="h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                    <?= htmlspecialchars(mb_substr(Auth::name() ?? 'U', 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- ÁREA PRINCIPAL (con scroll) -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden bg-slate-50/50 p-4 md:p-8 space-y-6">
