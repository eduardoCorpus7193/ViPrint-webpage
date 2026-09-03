<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$clave = $_GET['clave'] ?? '';
if ($clave !== 'qr2026') {
    http_response_code(403);
    echo '<!doctype html><meta charset="utf-8"><div style="font-family:Arial;padding:30px"><h1>Acceso restringido</h1><p>Usa la URL con la clave correcta: <code>instalar_qr_cliente_v2.php?clave=qr2026</code></p></div>';
    exit;
}
if (!can_finance() && !role_in(array('admin','direccion','asesor','administracion'))) {
    http_response_code(403);
    echo '<!doctype html><meta charset="utf-8"><div style="font-family:Arial;padding:30px"><h1>Sin permiso</h1><p>Inicia sesión con Luis, Mafer, Eduardo o administrador.</p></div>';
    exit;
}

function qr_token_generate_install($length = 12) {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($alphabet) - 1;
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        try { $token .= $alphabet[random_int(0, $max)]; }
        catch (Exception $e) { $token .= $alphabet[mt_rand(0, $max)]; }
    }
    return $token;
}
function qr_token_exists_install($token, $nota_id = 0) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM v2_notas WHERE public_token = ? AND id <> ?');
    $stmt->execute(array($token, (int)$nota_id));
    return (int)$stmt->fetchColumn() > 0;
}
function qr_index_exists_install($indexName) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $stmt->execute(array('v2_notas', $indexName));
    return (int)$stmt->fetchColumn() > 0;
}

$pdo = db();
$mensajes = array();

try {
    if (!column_exists('v2_notas', 'public_token')) {
        $pdo->exec("ALTER TABLE v2_notas ADD COLUMN public_token VARCHAR(80) NULL AFTER folio");
        $mensajes[] = 'Columna public_token agregada.';
    } else {
        $mensajes[] = 'La columna public_token ya existía.';
    }

    if (!column_exists('v2_notas', 'mostrar_cliente')) {
        $pdo->exec("ALTER TABLE v2_notas ADD COLUMN mostrar_cliente TINYINT(1) NOT NULL DEFAULT 1 AFTER public_token");
        $mensajes[] = 'Columna mostrar_cliente agregada.';
    } else {
        $mensajes[] = 'La columna mostrar_cliente ya existía.';
    }

    if (!qr_index_exists_install('uq_v2_notas_public_token')) {
        $pdo->exec("CREATE UNIQUE INDEX uq_v2_notas_public_token ON v2_notas(public_token)");
        $mensajes[] = 'Índice único de public_token creado.';
    } else {
        $mensajes[] = 'El índice único de public_token ya existía.';
    }

    $stmt = $pdo->query("SELECT id, public_token FROM v2_notas WHERE public_token IS NULL OR public_token = '' ORDER BY id");
    $notas = $stmt->fetchAll();
    $generados = 0;
    foreach ($notas as $nota) {
        $token = '';
        for ($i = 0; $i < 20; $i++) {
            $tmp = qr_token_generate_install(12);
            if (!qr_token_exists_install($tmp, (int)$nota['id'])) {
                $token = $tmp;
                break;
            }
        }
        if ($token !== '') {
            $up = $pdo->prepare('UPDATE v2_notas SET public_token = ?, mostrar_cliente = 1 WHERE id = ?');
            $up->execute(array($token, (int)$nota['id']));
            $generados++;
        }
    }
    $mensajes[] = 'Tokens generados para notas existentes: ' . $generados . '.';
    $ok = true;
} catch (Exception $e) {
    $ok = false;
    $mensajes[] = 'Error: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalación QR Cliente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#F8F6F8">
<div class="container py-5" style="max-width:850px">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 mb-3"><?= $ok ? 'QR de cliente instalado' : 'Error al instalar QR de cliente' ?></h1>
      <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>">
        <?= $ok ? 'La base quedó lista para generar enlaces públicos por nota.' : 'Revisa el error antes de continuar.' ?>
      </div>
      <ul>
        <?php foreach($mensajes as $m): ?><li><?= h($m) ?></li><?php endforeach; ?>
      </ul>
      <hr>
      <p class="mb-1"><strong>Siguiente paso:</strong> elimina este archivo del servidor después de confirmar que funciona.</p>
      <p class="mb-0"><a class="btn btn-primary" href="<?= url('notas.php') ?>">Volver al sistema</a></p>
    </div>
  </div>
</div>
</body>
</html>
