<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

if (!function_exists('can_finance') || !can_finance()) {
    http_response_code(403);
    echo 'No tienes permiso para ejecutar este instalador.';
    exit;
}

$clave = $_GET['clave'] ?? '';
if ($clave !== 'qr2026') {
    http_response_code(403);
    echo 'Clave incorrecta.';
    exit;
}

function installer_column_exists($table, $column) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute(array($table, $column));
    return (int)$stmt->fetchColumn() > 0;
}

function installer_generate_code() {
    try {
        return str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}

$messages = array();

if (!installer_column_exists('v2_notas', 'public_code')) {
    db()->exec("ALTER TABLE v2_notas ADD COLUMN public_code VARCHAR(8) NULL AFTER folio");
    $messages[] = 'Columna public_code agregada.';
} else {
    $messages[] = 'La columna public_code ya existía.';
}

if (!installer_column_exists('v2_notas', 'mostrar_cliente')) {
    db()->exec("ALTER TABLE v2_notas ADD COLUMN mostrar_cliente TINYINT(1) NOT NULL DEFAULT 1 AFTER public_code");
    $messages[] = 'Columna mostrar_cliente agregada.';
} else {
    $messages[] = 'La columna mostrar_cliente ya existía.';
}

try {
    db()->exec("CREATE INDEX idx_v2_notas_public_code ON v2_notas (public_code)");
    $messages[] = 'Índice public_code creado.';
} catch (Exception $e) {
    $messages[] = 'El índice public_code ya existía o no fue necesario.';
}

$stmt = db()->query("SELECT id FROM v2_notas WHERE public_code IS NULL OR public_code = ''");
$rows = $stmt->fetchAll();
$updated = 0;
foreach ($rows as $r) {
    $code = installer_generate_code();
    $up = db()->prepare('UPDATE v2_notas SET public_code = ?, mostrar_cliente = 1 WHERE id = ?');
    $up->execute(array($code, (int)$r['id']));
    $updated++;
}
$messages[] = 'Códigos generados o completados: ' . $updated;

?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalador código cliente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:800px">
  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h4">Instalación completada</h1>
      <ul>
        <?php foreach($messages as $m): ?><li><?= h($m) ?></li><?php endforeach; ?>
      </ul>
      <div class="alert alert-warning mb-0">Por seguridad, elimina del servidor el archivo <strong>instalar_codigo_cliente_v2.php</strong> después de verificar que los tickets imprimen correctamente.</div>
    </div>
  </div>
</div>
</body>
</html>
