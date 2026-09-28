<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$u = current_user();
if (!$u || !in_array($u['rol'], array('admin','direccion','administracion'), true)) {
    http_response_code(403);
    exit('No tienes permiso para ejecutar esta actualización. Entra como admin, dirección o administración.');
}

$clave = isset($_GET['clave']) ? $_GET['clave'] : '';
if ($clave !== 'terminada2026') {
    exit('Clave incorrecta. Usa ?clave=terminada2026');
}

function h2($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function patch_file($path, $callback) {
    $result = array('file' => basename($path), 'status' => '', 'detail' => '');
    if (!file_exists($path)) {
        $result['status'] = 'omitido';
        $result['detail'] = 'No se encontró el archivo.';
        return $result;
    }
    if (!is_readable($path) || !is_writable($path)) {
        $result['status'] = 'error';
        $result['detail'] = 'El archivo no tiene permisos de lectura/escritura.';
        return $result;
    }
    $original = file_get_contents($path);
    $updated = $callback($original);
    if ($updated === false) {
        $result['status'] = 'sin cambios';
        $result['detail'] = 'Ya estaba actualizado o no se encontró el patrón esperado.';
        return $result;
    }
    if ($updated === $original) {
        $result['status'] = 'sin cambios';
        $result['detail'] = 'Ya estaba actualizado.';
        return $result;
    }
    $backup = $path . '.bak_terminada_' . date('Ymd_His');
    @copy($path, $backup);
    file_put_contents($path, $updated);
    $result['status'] = 'actualizado';
    $result['detail'] = 'Se agregó el estado Terminada. Respaldo: ' . basename($backup);
    return $result;
}

$messages = array();
$db_ok = false;

try {
    db()->exec("ALTER TABLE v2_notas MODIFY COLUMN estado_produccion ENUM('pendiente','para_imprimir','impresa','sublimada','en_costura','terminada','problema','no_aplica') NOT NULL DEFAULT 'pendiente'");
    $db_ok = true;
    $messages[] = array('Base de datos', 'actualizada', 'La columna estado_produccion ya acepta el valor terminada.');
} catch (Exception $e) {
    $messages[] = array('Base de datos', 'error', $e->getMessage());
}

$messages[] = patch_file(__DIR__ . '/nota_form.php', function($s) {
    if (strpos($s, 'value="terminada"') !== false || strpos($s, "value='terminada'") !== false) return $s;
    $option = '<option value="terminada" <?= sel($nota[\'estado_produccion\']??\'\',\'terminada\') ?>>Terminada</option>';
    $needle = '<option value="problema"';
    if (strpos($s, $needle) !== false) {
        return str_replace($needle, $option . $needle, $s);
    }
    return preg_replace('/(<option\s+value=["\']en_costura["\'][^>]*>En costura<\/option>)/', '$1' . $option, $s, 1);
});

$messages[] = patch_file(__DIR__ . '/nota_ver.php', function($s) {
    if (strpos($s, 'value="terminada"') !== false || strpos($s, "value='terminada'") !== false) return $s;
    $option = '<option value="terminada" <?= $n[\'estado_produccion\']==\'terminada\'?\'selected\':\'\' ?>>Terminada</option>';
    $needle = '<option value="problema"';
    if (strpos($s, $needle) !== false) {
        return str_replace($needle, $option . $needle, $s);
    }
    return preg_replace('/(<option\s+value=["\']en_costura["\'][^>]*>En costura<\/option>)/', '$1' . $option, $s, 1);
});

$messages[] = patch_file(__DIR__ . '/includes/bootstrap.php', function($s) {
    $changed = false;
    if (strpos($s, "'terminada'") === false && strpos($s, '"terminada"') === false) {
        $s2 = preg_replace("/'en_costura'\s*=>\s*'[^']*'/", "'en_costura'=>'primary','terminada'=>'success'", $s, 1, $count);
        if ($count > 0) { $s = $s2; $changed = true; }
    }
    if (strpos($s, "'terminada' => 'Terminada'") === false && strpos($s, "'terminada'=>'Terminada'") === false) {
        $s2 = preg_replace("/'en_costura'\s*=>\s*'En costura'\s*,/", "'en_costura' => 'En costura',\n        'terminada' => 'Terminada',", $s, 1, $count);
        if ($count > 0) { $s = $s2; $changed = true; }
    }
    return $changed ? $s : false;
});

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Estado Terminada instalado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#F8F6F8">
<div class="container py-5">
  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h4 <?php echo $db_ok ? 'text-success' : 'text-danger'; ?>">Actualización de producción: Terminada</h1>
      <p>Se agregó el estado <strong>Terminada</strong> para producción, después de <strong>En costura</strong>.</p>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Elemento</th><th>Estado</th><th>Detalle</th></tr></thead>
          <tbody>
          <?php foreach ($messages as $m): ?>
            <tr>
              <td><?php echo h2($m[0] ?? $m['file'] ?? 'Archivo'); ?></td>
              <td><span class="badge text-bg-<?php echo (($m[1] ?? $m['status'] ?? '') === 'error') ? 'danger' : (((($m[1] ?? $m['status'] ?? '') === 'actualizada') || (($m[1] ?? $m['status'] ?? '') === 'actualizado')) ? 'success' : 'secondary'); ?>"><?php echo h2($m[1] ?? $m['status'] ?? ''); ?></span></td>
              <td><?php echo h2($m[2] ?? $m['detail'] ?? ''); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="mb-1"><strong>Prueba:</strong> abre una nota y revisa Producción. Debe aparecer: Pendiente, Para imprimir, Impresa, Sublimada, En costura, Terminada, Problema, No aplica.</p>
      <p class="text-danger mb-0"><strong>Importante:</strong> elimina este archivo del servidor: <code>instalar_estado_terminada_v2.php</code></p>
    </div>
  </div>
</div>
</body>
</html>
