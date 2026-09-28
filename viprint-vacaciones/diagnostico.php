<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';

$checks = [
    'PHP 8.1 o superior' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extensión PDO' => extension_loaded('pdo'),
    'Extensión PDO MySQL' => extension_loaded('pdo_mysql'),
    'Extensión mbstring' => extension_loaded('mbstring'),
];

$dbMessage = '';
$dbOk = false;
try {
    require __DIR__ . '/config/database.php';
    $pdo->query('SELECT 1');
    $dbOk = true;
    $dbMessage = 'Conexión correcta con viprint_vacaciones.';
} catch (Throwable $e) {
    $dbMessage = 'No se pudo conectar. Revisa config/database.php e importa database/schema.sql.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnóstico · ViPrint Vacaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:#F8F6F8}.header{background:#A92624;color:white}.card{border:0;box-shadow:0 .35rem 1.2rem rgba(0,0,0,.07)}</style>
</head>
<body>
<div class="header py-3"><div class="container"><strong>ViPrint · Diagnóstico del sistema de vacaciones</strong></div></div>
<main class="container py-4">
    <div class="card"><div class="card-body p-4">
        <h1 class="h4">Revisión del servidor</h1>
        <p>PHP <?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?></p>
        <ul class="list-group mb-3">
            <?php foreach ($checks as $label => $ok): ?>
                <li class="list-group-item d-flex justify-content-between"><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span><strong class="text-<?= $ok ? 'success' : 'danger' ?>"><?= $ok ? 'Correcto' : 'Falta' ?></strong></li>
            <?php endforeach; ?>
            <li class="list-group-item d-flex justify-content-between"><span>Base de datos</span><strong class="text-<?= $dbOk ? 'success' : 'danger' ?>"><?= $dbOk ? 'Correcto' : 'Error' ?></strong></li>
        </ul>
        <div class="alert alert-<?= $dbOk ? 'success' : 'warning' ?>"><?= htmlspecialchars($dbMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php if ($dbOk): ?><a class="btn btn-dark" href="<?= BASE_URL ?>/index.php">Abrir sistema</a><?php endif; ?>
    </div></div>
</main>
</body>
</html>
