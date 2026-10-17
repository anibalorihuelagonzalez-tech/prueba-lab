<?php
/** Página de error simple (404 no encontrada / 403 sin permiso). */
$codigo = http_response_code() === 403 ? 403 : 404;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $codigo === 403 ? 'Sin permiso' : 'Página no encontrada' ?> · CNSB Lab</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #334155; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0; }
        .box { text-align: center; padding: 2rem; }
        h1 { font-size: 4rem; margin: 0; color: #4f46e5; }
        a { color: #4f46e5; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <div class="box">
        <h1><?= $codigo ?></h1>
        <p><?= $codigo === 403 ? 'No tienes permiso para ver esta página.' : 'La página que buscas no existe.' ?></p>
        <p><a href="/">Volver al inicio</a></p>
    </div>
</body>
</html>
