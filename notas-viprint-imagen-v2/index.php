<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_once __DIR__ . '/includes/header.php';
$empresa = $_GET['empresa'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$params = [];
$where = "1=1";
if ($desde !== '') { $where .= " AND n.fecha_nota >= ?"; $params[] = $desde; }
if ($hasta !== '') { $where .= " AND n.fecha_nota <= ?"; $params[] = $hasta; }
if ($empresa !== '') { $where .= " AND e.clave=?"; $params[] = $empresa; }
if (role_in(['disenador','externo'])) { $where .= " AND n.disenador_id=?"; $params[] = current_user()['id']; }
$stmt = db()->prepare("SELECT COUNT(*) notas, COALESCE(SUM(n.total),0) total, COALESCE(SUM(n.pagado),0) pagado, COALESCE(SUM(n.saldo),0) saldo, COALESCE(SUM(n.utilidad_real),0) utilidad, COALESCE(SUM(n.merma_total),0) mermas, COALESCE(SUM(n.comision_total),0) comisiones FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE $where");
$stmt->execute($params); $s=$stmt->fetch();
$stmt = db()->prepare("SELECT n.*, e.nombre empresa, u.nombre disenador FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id LEFT JOIN v2_usuarios u ON u.id=n.disenador_id WHERE $where ORDER BY n.fecha_nota DESC, n.id DESC LIMIT 50");
$stmt->execute($params); $notas=$stmt->fetchAll();
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div class="d-flex align-items-center gap-3"><img src="<?= h(logo_src()) ?>" alt="ViPrint" class="dashboard-logo"><div><h1 class="h3 mb-0">Panel de notas</h1><div class="text-muted">ViPrint e Imagen en un solo sistema, con reportes separados, caja y corte diario.</div></div></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="<?= url('notas.php') ?>">Ver todas</a><a class="btn btn-primary" href="<?= url('nota_form.php') ?>">Nueva nota</a></div>
</div>
<form class="card card-body mb-4 no-print" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Empresa</label><select name="empresa" class="form-select"><option value="">Todas</option><option value="viprint" <?= $empresa==='viprint'?'selected':'' ?>>ViPrint</option><option value="imagen" <?= $empresa==='imagen'?'selected':'' ?>>Imagen</option></select></div>
    <div class="col-md-3"><label class="form-label">Desde</label><input type="date" class="form-control" name="desde" value="<?= h($desde) ?>"></div>
    <div class="col-md-3"><label class="form-label">Hasta</label><input type="date" class="form-control" name="hasta" value="<?= h($hasta) ?>"></div>
    <div class="col-md-3"><button class="btn btn-outline-primary w-100">Filtrar</button></div>
  </div>
  <div class="small text-muted mt-2">Si no eliges fechas, el panel considera todos los registros. La tabla muestra las últimas 50 notas; para buscar registros anteriores usa “Ver todas”.</div>
</form>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="stat"><div class="label">Notas</div><div class="value"><?= (int)$s['notas'] ?></div></div></div>
  <div class="col-md-3"><div class="stat"><div class="label">Total vendido</div><div class="value"><?= money($s['total']) ?></div></div></div>
  <div class="col-md-3"><div class="stat"><div class="label">Pagado</div><div class="value"><?= money($s['pagado']) ?></div></div></div>
  <div class="col-md-3"><div class="stat"><div class="label">Saldo pendiente</div><div class="value"><?= money($s['saldo']) ?></div></div></div>
  <?php if(can_finance()): ?>
  <div class="col-md-4"><div class="stat"><div class="label">Utilidad real estimada</div><div class="value"><?= money($s['utilidad']) ?></div></div></div>
  <div class="col-md-4"><div class="stat"><div class="label">Mermas</div><div class="value"><?= money($s['mermas']) ?></div></div></div>
  <div class="col-md-4"><div class="stat"><div class="label">Comisiones</div><div class="value"><?= money($s['comisiones']) ?></div></div></div>
  <?php endif; ?>
</div>
<div class="card"><div class="card-header d-flex justify-content-between align-items-center"><span>Últimas 50 notas</span><a class="btn btn-sm btn-outline-primary no-print" href="<?= url('notas.php') ?>">Buscar en todas</a></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Empresa</th><th>Folio</th><th>Cliente</th><th>Diseñador</th><th>Total</th><th>Saldo</th><th>Diseño</th><th>Producción</th><th></th></tr></thead><tbody>
<?php foreach($notas as $n): ?><tr><td><?= h($n['empresa']) ?></td><td><?= h($n['folio']) ?></td><td><?= h($n['cliente_nombre']) ?><br><span class="text-muted small"><?= date_mx($n['fecha_nota']) ?></span></td><td><?= h($n['disenador'] ?? 'Sin asignar') ?></td><td><?= money($n['total']) ?></td><td><?= money($n['saldo']) ?></td><td><?= estado_badge($n['estado_diseno']) ?></td><td><?= estado_badge($n['estado_produccion']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= url('nota_ver.php?id='.$n['id']) ?>">Ver</a></td></tr><?php endforeach; ?>
<?php if(!$notas): ?><tr><td colspan="9" class="text-center text-muted py-4">Sin registros.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
