<?php
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Diagnóstico Cotizaciones</title><style>body{font-family:Arial,sans-serif;margin:30px} .ok{color:green;font-weight:bold}.bad{color:#b00020;font-weight:bold} code{background:#f4f4f4;padding:2px 5px}</style></head><body>
<h1>Diagnóstico - Cotizaciones ViPrint</h1>
<p>PHP: <strong><?php echo htmlspecialchars(PHP_VERSION); ?></strong></p>
<p>PDO: <span class="<?php echo extension_loaded('pdo')?'ok':'bad'; ?>"><?php echo extension_loaded('pdo')?'Activo':'No activo'; ?></span></p>
<p>PDO MySQL: <span class="<?php echo extension_loaded('pdo_mysql')?'ok':'bad'; ?>"><?php echo extension_loaded('pdo_mysql')?'Activo':'No activo'; ?></span></p>
<?php
try {
    require_once __DIR__ . '/config/database.php';
    echo '<p>Conexión MySQL: <span class="ok">Correcta</span></p>';
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo '<p>Tablas encontradas:</p><ul>';
    foreach ($tables as $t) echo '<li><code>' . htmlspecialchars($t) . '</code></li>';
    echo '</ul>';
} catch (Throwable $e) {
    echo '<p>Conexión MySQL: <span class="bad">Error</span></p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
?>
</body></html>
