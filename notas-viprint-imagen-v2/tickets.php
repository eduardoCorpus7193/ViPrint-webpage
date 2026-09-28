<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if(!can_register_payments()){ flash('danger','No tienes permiso para ver tickets.'); redirect_to('index.php'); }
$empresas = empresas();
$empresa_id = (int)($_GET['empresa_id'] ?? 0);
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$params = [$fecha];
$where = 'p.fecha_pago=?';
if ($empresa_id > 0) { $where .= ' AND p.empresa_id=?'; $params[] = $empresa_id; }
$stmt = db()->prepare("SELECT p.*, n.folio, n.cliente_nombre, e.nombre empresa, u.nombre usuario FROM v2_pagos p JOIN v2_notas n ON n.id=p.nota_id JOIN v2_empresas e ON e.id=p.empresa_id LEFT JOIN v2_usuarios u ON u.id=p.usuario_id WHERE $where ORDER BY p.created_at DESC, p.id DESC");
$stmt->execute($params);
$pagos = $stmt->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div><h1 class="h3 mb-0">Tickets de pago</h1><div class="text-muted">Reimpresión de tickets 58mm por fecha.</div></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="<?= url('caja_abrir.php') ?>">Abrir cajón</a></div>
</div>
<form class="card card-body mb-4 no-print" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-4"><label class="form-label">Empresa</label><select class="form-select" name="empresa_id"><option value="0">Todas</option><?php foreach($empresas as $e): ?><option value="<?= (int)$e['id'] ?>" <?= $empresa_id===(int)$e['id']?'selected':'' ?>><?= h($e['nombre']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><label class="form-label">Fecha</label><input type="date" class="form-control" name="fecha" value="<?= h($fecha) ?>"></div>
  <div class="col-md-4"><button class="btn btn-outline-primary w-100">Buscar</button></div>
</div></form>
<div class="card"><div class="card-header">Pagos encontrados: <?= count($pagos) ?></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Empresa</th><th>Nota</th><th>Cliente</th><th>Concepto</th><th>Método</th><th>Monto</th><th>Usuario</th><th></th></tr></thead><tbody>
<?php foreach($pagos as $p): ?><tr><td><?= h($p['empresa']) ?></td><td><a href="<?= url('nota_ver.php?id='.$p['nota_id']) ?>"><?= h($p['folio']) ?></a></td><td><?= h($p['cliente_nombre']) ?></td><td><?= h($p['concepto']) ?></td><td><?= h($p['forma_pago']) ?><?= $p['forma_pago_otro']?' / '.h($p['forma_pago_otro']):'' ?></td><td class="fw-bold"><?= money($p['monto']) ?></td><td><?= h($p['usuario'] ?? '') ?></td><td><a class="btn btn-sm btn-primary" href="<?= url('ticket_pago.php?pago_id='.$p['id']) ?>">Ticket</a></td></tr><?php endforeach; ?>
<?php if(!$pagos): ?><tr><td colspan="8" class="text-center text-muted py-4">No hay pagos en esta fecha.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
