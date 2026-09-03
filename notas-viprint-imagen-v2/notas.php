<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_once __DIR__ . '/includes/header.php';
$q = trim($_GET['q'] ?? ''); $empresa=$_GET['empresa']??''; $pago=$_GET['pago']??''; $diseno=$_GET['diseno']??'';
$params=[]; $where='1=1';
if($q!==''){ $where.=" AND (n.folio LIKE ? OR n.cliente_nombre LIKE ? OR n.negocio LIKE ? OR n.telefono LIKE ?)"; $like='%'.$q.'%'; array_push($params,$like,$like,$like,$like); }
if($empresa!==''){ $where.=" AND e.clave=?"; $params[]=$empresa; }
if($pago!==''){ $where.=" AND n.estado_pago=?"; $params[]=$pago; }
if($diseno!==''){ $where.=" AND n.estado_diseno=?"; $params[]=$diseno; }
if(role_in(['disenador','externo'])){ $where.=" AND n.disenador_id=?"; $params[]=current_user()['id']; }
$sql="SELECT n.*,e.nombre empresa,u.nombre disenador FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id LEFT JOIN v2_usuarios u ON u.id=n.disenador_id WHERE $where ORDER BY n.fecha_nota DESC,n.id DESC LIMIT 300";
$stmt=db()->prepare($sql); $stmt->execute($params); $rows=$stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Notas</h1><a class="btn btn-primary" href="<?= url('nota_form.php') ?>">Nueva nota</a></div>
<form class="card card-body mb-3 no-print"><div class="row g-2"><div class="col-md-3"><input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Buscar folio, cliente, teléfono"></div><div class="col-md-2"><select class="form-select" name="empresa"><option value="">Empresa</option><option value="viprint" <?= $empresa==='viprint'?'selected':'' ?>>ViPrint</option><option value="imagen" <?= $empresa==='imagen'?'selected':'' ?>>Imagen</option></select></div><div class="col-md-2"><select class="form-select" name="pago"><option value="">Pago</option><option value="sin_pago">Sin pago</option><option value="parcial">Parcial</option><option value="liquidada">Liquidada</option><option value="cancelada">Cancelada</option></select></div><div class="col-md-2"><select class="form-select" name="diseno"><option value="">Diseño</option><option value="sin_asignar">Sin asignar</option><option value="en_diseno">En diseño</option><option value="en_aprobacion">En aprobación</option><option value="aprobado">Aprobado</option></select></div><div class="col-md-3"><button class="btn btn-outline-primary w-100">Filtrar</button></div></div></form>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Folio</th><th>Empresa</th><th>Cliente</th><th>Diseñador</th><th>Estados</th><th>Total</th><th>Pagado</th><th>Saldo</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><strong><?= h($r['folio']) ?></strong><br><span class="text-muted small"><?= date_mx($r['fecha_nota']) ?></span></td><td><?= h($r['empresa']) ?></td><td><?= h($r['cliente_nombre']) ?><br><span class="text-muted small"><?= h($r['telefono']) ?></span></td><td><?= h($r['disenador'] ?? 'Sin asignar') ?></td><td><div><?= estado_badge($r['estado_diseno']) ?> <?= estado_badge($r['estado_produccion']) ?></div><div class="mt-1"><?= estado_badge($r['estado_pago']) ?> <?= estado_badge($r['estado_entrega']) ?></div></td><td><?= money($r['total']) ?></td><td><?= money($r['pagado']) ?></td><td><?= money($r['saldo']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= url('nota_ver.php?id='.$r['id']) ?>">Abrir</a></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">No hay resultados.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
