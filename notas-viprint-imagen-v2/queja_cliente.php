<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/actualizacion_20260924_helpers.php';

function qc_label($value) { return ucfirst(str_replace('_',' ', (string)$value)); }

$folio = strtoupper(trim((string)($_GET['folio'] ?? $_POST['folio'] ?? '')));
$codigo = strtoupper(trim((string)($_GET['codigo'] ?? $_POST['codigo'] ?? $_GET['c'] ?? $_POST['c'] ?? '')));
$error = '';
$nota = null;
$partidas = array();

if ($folio !== '' && $codigo !== '') {
    $sql = "SELECT n.*, e.nombre empresa, e.clave empresa_clave FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE UPPER(n.folio)=?";
    $params = array($folio);
    if (function_exists('column_exists') && column_exists('v2_notas','public_code')) { $sql .= " AND UPPER(n.public_code)=?"; $params[] = $codigo; }
    if (function_exists('column_exists') && column_exists('v2_notas','mostrar_cliente')) { $sql .= " AND n.mostrar_cliente=1"; }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $nota = $stmt->fetch();
    if (!$nota) $error = 'No encontramos una nota activa con ese folio y código.';
    else {
        $stmt = db()->prepare('SELECT descripcion, cantidad, total FROM v2_nota_partidas WHERE nota_id=? AND LOWER(TRIM(descripcion)) <> ? ORDER BY id');
        $stmt->execute(array((int)$nota['id'], strtolower(VIPRINT_IVA_DESC)));
        $partidas = $stmt->fetchAll();
    }
} elseif ($folio !== '' || $codigo !== '') {
    $error = 'Escribe folio y código.';
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Queja o sugerencia | ViPrint</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{--vp:#A92624;--bg:#F8F6F8} body{background:var(--bg)} .brand{height:8px;background:var(--vp)} .btn-vp{background:var(--vp);border-color:var(--vp);color:#fff}.btn-vp:hover{background:#8f1f1d;border-color:#8f1f1d;color:#fff}.logo{max-height:70px;max-width:220px;object-fit:contain}
</style>
</head>
<body><div class="brand"></div>
<div class="container py-4 py-md-5" style="max-width:880px">
  <div class="text-center mb-4"><img src="<?= h(logo_src()) ?>" class="logo mb-2" alt="ViPrint"><h1 class="h3 mb-1">Queja, sugerencia o comentario</h1><p class="text-muted mb-0">Tu mensaje se relacionará con la nota indicada.</p></div>

  <?php if (!$nota): ?>
  <div class="card shadow-sm"><div class="card-body p-4">
    <?php if($error): ?><div class="alert alert-warning"><?= h($error) ?></div><?php endif; ?>
    <form method="get" class="row g-3">
      <div class="col-md-5"><label class="form-label">Folio</label><input class="form-control text-uppercase" name="folio" value="<?= h($folio) ?>" required></div>
      <div class="col-md-5"><label class="form-label">Código</label><input class="form-control" name="codigo" value="<?= h($codigo) ?>" required></div>
      <div class="col-md-2 d-grid align-items-end"><button class="btn btn-vp">Continuar</button></div>
    </form>
  </div></div>
  <?php endif; ?>

  <?php if ($nota): ?>
  <div class="card shadow-sm mb-4"><div class="card-body p-4">
    <h2 class="h5">Confirma tu pedido</h2>
    <p class="mb-2">Confirma que tu pedido fue de <strong><?= h($nota['negocio'] ?: $nota['cliente_nombre']) ?></strong> con ViPrint.</p>
    <div class="small text-muted mb-2">Folio: <strong><?= h($nota['folio']) ?></strong></div>
    <ul class="mb-0"><?php foreach($partidas as $p): ?><li><?= h($p['descripcion']) ?><?= (float)$p['cantidad']!=1.0 ? ' · Cant. '.h($p['cantidad']) : '' ?></li><?php endforeach; ?></ul>
  </div></div>

  <div class="card shadow-sm"><div class="card-body p-4">
    <form method="post" action="<?= h(url('queja_guardar.php')) ?>" enctype="multipart/form-data">
      <input type="hidden" name="folio" value="<?= h($folio) ?>"><input type="hidden" name="codigo" value="<?= h($codigo) ?>"><input type="hidden" name="nota_id" value="<?= (int)$nota['id'] ?>">
      <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="confirma" value="1" required id="confirma"><label class="form-check-label" for="confirma">Sí, confirmo que esta es mi nota/pedido.</label></div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?= h($nota['cliente_nombre']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?= h($nota['telefono']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Tipo</label><select class="form-select" name="tipo" required><option value="queja">Queja</option><option value="sugerencia">Sugerencia</option><option value="comentario">Comentario</option><option value="duda">Duda</option></select></div>
        <div class="col-md-8"><label class="form-label">Foto opcional</label><input class="form-control" type="file" name="foto" accept="image/png,image/jpeg,image/webp"></div>
        <div class="col-12"><label class="form-label">Mensaje</label><textarea class="form-control" name="mensaje" rows="5" required placeholder="Escribe aquí tu comentario"></textarea></div>
        <div class="col-12 d-grid"><button class="btn btn-vp btn-lg">Enviar</button></div>
      </div>
    </form>
  </div></div>
  <?php endif; ?>
</div></body></html>
