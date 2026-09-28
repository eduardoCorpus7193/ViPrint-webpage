<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!can_cash()) { flash('danger','No tienes permiso para ver aperturas.'); redirect_to('index.php'); }
if (!table_exists('v2_caja_aperturas')) { flash('warning','Falta ejecutar database/update_tickets_cajon_v2.sql.'); redirect_to('caja_abrir.php'); }
$empresas = empresas();
$empresa_id = (int)($_GET['empresa_id'] ?? ($empresas[0]['id'] ?? 0));
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$stmt = db()->prepare("SELECT a.*, e.nombre empresa, n.folio, n.cliente_nombre, u.nombre usuario FROM v2_caja_aperturas a JOIN v2_empresas e ON e.id=a.empresa_id LEFT JOIN v2_notas n ON n.id=a.nota_id LEFT JOIN v2_usuarios u ON u.id=a.usuario_id WHERE a.empresa_id=? AND a.fecha_apertura=? ORDER BY a.created_at DESC, a.id DESC");
$stmt->execute([$empresa_id, $fecha]);
$items = $stmt->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div><h1 class="h3 mb-0">Aperturas de cajón</h1><div class="text-muted">Registro diario de aperturas por pago, manuales y pruebas.</div></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="<?= url('caja_abrir.php?empresa_id='.$empresa_id) ?>">Abrir cajón</a><button class="btn btn-primary" onclick="window.print()">Imprimir</button></div>
</div>
<form class="card card-body mb-4 no-print" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label">Empresa</label><select name="empresa_id" class="form-select"><?php foreach($empresas as $e): ?><option value="<?= (int)$e['id'] ?>" <?= $empresa_id===(int)$e['id']?'selected':'' ?>><?= h($e['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Fecha</label><input type="date" class="form-control" name="fecha" value="<?= h($fecha) ?>"></div>
    <div class="col-md-4"><button class="btn btn-outline-primary w-100">Ver aperturas</button></div>
  </div>
</form>
<div class="card"><div class="card-header">Aperturas del día: <?= count($items) ?></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Hora</th><th>Tipo</th><th>Nota</th><th>Motivo</th><th>Impresora</th><th>Usuario</th></tr></thead><tbody>
<?php foreach($items as $a): ?><tr><td><?= h(substr($a['hora_apertura'],0,5)) ?></td><td><?= estado_badge($a['tipo']) ?></td><td><?php if($a['nota_id']): ?><a href="<?= url('nota_ver.php?id='.$a['nota_id']) ?>"><?= h($a['folio']) ?></a><br><span class="small text-muted"><?= h($a['cliente_nombre']) ?></span><?php else: ?><span class="text-muted">Sin nota</span><?php endif; ?></td><td><?= h($a['motivo']) ?><br><span class="small text-muted">Cmd: <?= h($a['comando']) ?></span></td><td><?= h($a['impresora']) ?></td><td><?= h($a['usuario'] ?? '') ?></td></tr><?php endforeach; ?>
<?php if(!$items): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay aperturas registradas en esta fecha.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
