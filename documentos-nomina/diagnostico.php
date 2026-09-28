<?php
declare(strict_types=1);

$checks = [
    'PHP 8.1 o superior' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extensión PDO' => extension_loaded('pdo'),
    'Controlador PDO MySQL' => extension_loaded('pdo_mysql'),
];

$dbStatus = false;
$dbMessage = '';
try {
    require_once __DIR__ . '/config/app.php';
    require_once __DIR__ . '/config/database.php';
    $pdo->query('SELECT 1');
    $dbStatus = true;
    $dbMessage = 'Conexión correcta con la base de datos.';
} catch (Throwable $e) {
    $dbMessage = $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnóstico · Documentos de Nómina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 760px">
    <h1 class="h3 mb-4">Diagnóstico del sistema</h1>
    <div class="list-group mb-4">
        <?php foreach ($checks as $label => $ok): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                <span class="badge text-bg-<?= $ok ? 'success' : 'danger' ?>"><?= $ok ? 'Correcto' : 'Revisar' ?></span>
            </div>
        <?php endforeach; ?>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            Base de datos
            <span class="badge text-bg-<?= $dbStatus ? 'success' : 'danger' ?>"><?= $dbStatus ? 'Correcto' : 'Error' ?></span>
        </div>
    </div>
    <div class="alert alert-<?= $dbStatus ? 'success' : 'danger' ?>"><?= htmlspecialchars($dbMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <a href="./" class="btn btn-dark">Abrir sistema</a>
</div>
</body>
</html>
