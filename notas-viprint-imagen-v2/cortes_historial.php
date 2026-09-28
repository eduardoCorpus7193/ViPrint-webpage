<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/modulos_2_3_10_helpers.php';
require_login();
if (!m2310_can_use_corte()) { flash('danger','No tienes permiso para ver cortes.'); redirect_to('index.php'); }
require_once __DIR__ . '/includes/header.php';

if (!table_exists('v2_cortes_caja')) {
    echo '<div class="alert alert-warning">Falta instalar la actualización: <code>instalar_modulos_2_3_10_v2.php?clave=modulos2310</code></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
$desde = m2310_date_or_today($_GET['desde'] ?? date('Y-m-d', strtotime('-30 days')));
$hasta = m2310_date_or_today($_GET['hasta'] ?? date('Y-m-d'));
$stmt = db()->prepare("SELECT c.*, u.nombre realizado_nombre, uc.nombre cerrado_nombre FROM v2_cortes_caja c LEFT JOIN v2_usuarios u ON u.id=c.realizado_por LEFT JOIN v2_usuarios uc ON uc.id=c.cerrado_por WHERE c.fecha_corte BETWEEN ? AND ? ORDER BY c.fecha_corte DESC");
$stmt->execute(array($desde,$hasta));
$rows = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div><h1 class="h3 mb-1">Historial de cortes</h1><div class="text-muted">Consulta cortes guardados o cerrados.</div></div>
  <a class="btn btn-primary no-print" href="<?= url('corte_diario.php') ?>">Corte de hoy</a>
</div>
<form class="card card-body mb-3 no-print" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Desde</label><input type="date" class="form-control" name="desde" value="<?= h($desde) ?>"></div>
    <div class="col-md-3"><label class="form-label">Hasta</label><input type="date" class="form-control" name="hasta" value="<?= h($hasta) ?>"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrar</button></div>
  </div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Fecha</th><th>Estado</th><th>Caja esperada</th><th>Contado</th><th>Diferencia</th><th>Entrega a Luis</th><th>Fondo final</th><th>Cerró</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><strong><?= date_mx($r['fecha_corte']) ?></strong></td>
  <td><?= (int)$r['cerrado']===1 ? '<span class="badge text-bg-success">Cerrado</span>' : '<span class="badge text-bg-warning">Borrador</span>' ?></td>
  <td><?= m2310_money_short($r['caja_esperada']) ?></td>
  <td><?= m2310_money_short($r['efectivo_contado']) ?></td>
  <td class="<?= abs((float)$r['diferencia_efectivo'])>0.009?'text-danger':'text-success' ?>"><?= m2310_money_short($r['diferencia_efectivo']) ?></td>
  <td><?= m2310_money_short($r['entrega_luis_real']) ?></td>
  <td><?= m2310_money_short($r['fondo_final']) ?></td>
  <td><div><?= h($r['cerrado_nombre'] ?? '') ?></div><div class="text-muted small"><?= h($r['cerrado_at'] ?? '') ?></div></td>
  <td class="text-end"><div class="btn-group btn-group-sm"><a class="btn btn-outline-primary" href="<?= url('corte_diario.php?fecha='.$r['fecha_corte']) ?>">Ver</a><?php if((int)$r['cerrado']===1): ?><a class="btn btn-outline-secondary" href="<?= url('corte_ticket.php?id='.(int)$r['id']) ?>">Ticket</a><?php endif; ?></div></td>
</tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">No hay cortes en el rango seleccionado.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
