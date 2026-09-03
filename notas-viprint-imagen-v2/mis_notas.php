<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_once __DIR__ . '/includes/header.php';
$uid=(int)current_user()['id'];
$stmt=db()->prepare("SELECT n.*, e.nombre empresa FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.disenador_id=? AND n.estado_entrega <> 'cancelada' ORDER BY n.fecha_nota DESC,n.id DESC LIMIT 200");
$stmt->execute([$uid]); $rows=$stmt->fetchAll();
?>
<h1 class="h3 mb-3">Mis notas asignadas</h1>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Folio</th><th>Empresa</th><th>Cliente</th><th>Diseño</th><th>Aprob. impresión</th><th>Producción</th><th>Pago</th><th></th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><strong><?= h($r['folio']) ?></strong><br><span class="text-muted small"><?= date_mx($r['fecha_nota']) ?></span></td><td><?= h($r['empresa']) ?></td><td><?= h($r['cliente_nombre']) ?><br><span class="text-muted small"><?= h($r['telefono']) ?></span></td><td><?= estado_badge($r['estado_diseno']) ?></td><td><?= estado_badge($r['estado_aprobacion_impresion']) ?></td><td><?= estado_badge($r['estado_produccion']) ?></td><td><?= estado_badge($r['estado_pago']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= url('nota_ver.php?id='.$r['id']) ?>">Abrir</a></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">No tienes notas asignadas.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
