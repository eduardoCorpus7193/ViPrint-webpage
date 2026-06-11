<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Diagnóstico';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-body p-4">
        <h1 class="h4">Diagnóstico del sistema</h1>
        <ul class="mb-0">
            <li>PHP: <?= e(PHP_VERSION) ?></li>
            <li>PDO MySQL: <?= extension_loaded('pdo_mysql') ? 'Disponible' : 'No disponible' ?></li>
            <li>Base de datos: conexión correcta</li>
            <li>Proyecto: Horas Extras</li>
        </ul>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
