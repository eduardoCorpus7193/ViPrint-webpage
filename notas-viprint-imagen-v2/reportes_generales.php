<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/modulos_2_3_10_helpers.php';
require_login();
if (!m2310_can_view_reports()) { flash('danger','No tienes permiso para ver reportes generales.'); redirect_to('index.php'); }
require_once __DIR__ . '/includes/header.php';

$desde = m2310_date_or_today($_GET['desde'] ?? date('Y-m-01'));
$hasta = m2310_date_or_today($_GET['hasta'] ?? date('Y-m-d'));
$empresa = trim($_GET['empresa'] ?? '');
$empresaNombreReporte = 'Todas las empresas';
if ($empresa === 'viprint') $empresaNombreReporte = 'ViPrint';
if ($empresa === 'imagen') $empresaNombreReporte = 'Imagen';

$notasValid = m2310_notas_valid_sql('n');
$pagosValid = m2310_pagos_valid_sql('p');
$empresaWhereNotas = '';
$empresaWherePagos = '';
$paramsNotas = array($desde,$hasta);
$paramsPagos = array($desde,$hasta);
if ($empresa !== '') {
    $empresaWhereNotas = ' AND e.clave=?'; $paramsNotas[] = $empresa;
    $empresaWherePagos = ' AND e.clave=?'; $paramsPagos[] = $empresa;
}

function first_row_report($sql, $params=array()) { $stmt=db()->prepare($sql); $stmt->execute($params); return $stmt->fetch() ?: array(); }
function all_rows_report($sql, $params=array()) { $stmt=db()->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(); }
function reporte_fecha_larga($f) { return $f ? date_mx($f) : ''; }
function reporte_money($v) { return '$' . number_format((float)$v, 2); }
function reporte_estado($v) { return m2310_estado_text($v ?: 'Sin estado'); }

$kpiNotas = first_row_report("SELECT COUNT(*) notas, COALESCE(SUM(n.total),0) total_notas, COALESCE(SUM(n.saldo),0) saldo_periodo FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.fecha_nota BETWEEN ? AND ? $notasValid $empresaWhereNotas", $paramsNotas);
$kpiPagos = first_row_report("SELECT COALESCE(SUM(CASE WHEN p.concepto='devolucion' THEN -p.monto ELSE p.monto END),0) cobrado FROM v2_pagos p JOIN v2_empresas e ON e.id=p.empresa_id WHERE p.fecha_pago BETWEEN ? AND ? $pagosValid $empresaWherePagos", $paramsPagos);
$kpiAbiertas = first_row_report("SELECT COUNT(*) abiertas, COALESCE(SUM(n.saldo),0) saldo_abierto FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.estado_entrega NOT IN ('entregada','cancelada') AND n.saldo>0 $notasValid" . ($empresa!==''?' AND e.clave=?':''), $empresa!==''?array($empresa):array());
$kpiTerminadas = first_row_report("SELECT COUNT(*) entregadas FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.fecha_entrega BETWEEN ? AND ? AND n.estado_entrega='entregada' $notasValid $empresaWhereNotas", $paramsNotas);

$pagosMetodo = all_rows_report("SELECT p.forma_pago, COALESCE(SUM(CASE WHEN p.concepto='devolucion' THEN -p.monto ELSE p.monto END),0) total, COUNT(*) cantidad FROM v2_pagos p JOIN v2_empresas e ON e.id=p.empresa_id WHERE p.fecha_pago BETWEEN ? AND ? $pagosValid $empresaWherePagos GROUP BY p.forma_pago ORDER BY total DESC", $paramsPagos);
$ventasEmpresa = all_rows_report("SELECT e.nombre empresa, COUNT(*) notas, COALESCE(SUM(n.total),0) total, COALESCE(SUM(n.pagado),0) pagado, COALESCE(SUM(n.saldo),0) saldo FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.fecha_nota BETWEEN ? AND ? $notasValid $empresaWhereNotas GROUP BY e.id,e.nombre ORDER BY total DESC", $paramsNotas);
$estados = all_rows_report("SELECT n.estado_entrega, COUNT(*) cantidad, COALESCE(SUM(n.total),0) total, COALESCE(SUM(n.saldo),0) saldo FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.fecha_nota BETWEEN ? AND ? $notasValid $empresaWhereNotas GROUP BY n.estado_entrega ORDER BY cantidad DESC", $paramsNotas);
$produccion = all_rows_report("SELECT n.estado_produccion, COUNT(*) cantidad FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.fecha_nota BETWEEN ? AND ? $notasValid $empresaWhereNotas GROUP BY n.estado_produccion ORDER BY cantidad DESC", $paramsNotas);
$porDia = all_rows_report("SELECT p.fecha_pago fecha, COALESCE(SUM(CASE WHEN p.concepto='devolucion' THEN -p.monto ELSE p.monto END),0) cobrado FROM v2_pagos p JOIN v2_empresas e ON e.id=p.empresa_id WHERE p.fecha_pago BETWEEN ? AND ? $pagosValid $empresaWherePagos GROUP BY p.fecha_pago ORDER BY p.fecha_pago ASC", $paramsPagos);
$topProductos = all_rows_report("SELECT LEFT(np.descripcion,120) descripcion, COALESCE(SUM(np.cantidad),0) cantidad, COALESCE(SUM(np.total),0) total FROM v2_nota_partidas np JOIN v2_notas n ON n.id=np.nota_id JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.fecha_nota BETWEEN ? AND ? $notasValid $empresaWhereNotas GROUP BY LEFT(np.descripcion,120) ORDER BY total DESC LIMIT 10", $paramsNotas);
$deudoresParams = $empresa!=='' ? array($empresa) : array();
$deudores = all_rows_report("SELECT n.id,n.folio,n.cliente_nombre,n.telefono,n.fecha_nota,n.fecha_promesa,n.total,n.pagado,n.saldo,e.nombre empresa FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.saldo>0 AND n.estado_entrega<>'cancelada' $notasValid" . ($empresa!==''?' AND e.clave=?':'') . " ORDER BY n.saldo DESC, n.fecha_nota ASC LIMIT 50", $deudoresParams);
?>
<style>
.report-print-area{display:none;}
.report-mini{font-size:12px;}
.report-mini .report-title{font-size:20px;font-weight:800;margin:0;color:#111;}
.report-mini .report-subtitle{font-size:12px;color:#444;margin:2px 0 12px 0;}
.report-mini .summary-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px;}
.report-mini .summary-box{border:1px solid #222;padding:8px;border-radius:4px;min-height:55px;}
.report-mini .summary-label{font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.02em;}
.report-mini .summary-value{font-size:17px;font-weight:800;color:#111;line-height:1.2;}
.report-mini .section-title{font-size:14px;font-weight:800;margin:12px 0 4px 0;border-bottom:2px solid #111;padding-bottom:2px;}
.report-mini table{width:100%;border-collapse:collapse;margin-bottom:8px;}
.report-mini th,.report-mini td{border:1px solid #999;padding:4px 5px;vertical-align:top;}
.report-mini th{background:#eee;color:#111;font-weight:800;}
.report-mini .text-end{text-align:right;}
.report-mini .muted{color:#555;}
.report-mini .small{font-size:10px;}
.report-mini .avoid-break{page-break-inside:avoid;break-inside:avoid;}
.report-mini .two-cols{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
@media print{
  @page{size:letter;margin:9mm;}
  html,body{background:#fff!important;color:#000!important;width:auto!important;height:auto!important;overflow:visible!important;}
  nav,.navbar,.vip-navbar,.no-print,.screen-area,.alert{display:none!important;}
  main.container-fluid{display:block!important;width:100%!important;max-width:none!important;margin:0!important;padding:0!important;}
  .report-print-area{display:block!important;visibility:visible!important;position:static!important;background:#fff!important;color:#000!important;}
  .report-print-area *{visibility:visible!important;color:#000!important;box-shadow:none!important;text-shadow:none!important;}
  .report-mini{font-size:10.5px!important;line-height:1.18!important;}
  .report-mini .report-title{font-size:18px!important;}
  .report-mini .summary-grid{grid-template-columns:repeat(4,1fr)!important;gap:5px!important;}
  .report-mini .summary-box{padding:5px!important;min-height:auto!important;}
  .report-mini .summary-label{font-size:8.8px!important;}
  .report-mini .summary-value{font-size:14px!important;}
  .report-mini .section-title{font-size:12px!important;margin:8px 0 3px 0!important;}
  .report-mini th,.report-mini td{padding:2px 3px!important;font-size:9px!important;}
  .report-mini table{margin-bottom:5px!important;page-break-inside:auto!important;}
  .report-mini tr{page-break-inside:avoid!important;break-inside:avoid!important;}
  .report-mini .two-cols{grid-template-columns:1fr 1fr!important;gap:6px!important;}
  a[href]:after{content:""!important;}
}
</style>

<div class="screen-area">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h1 class="h3 mb-1">Reportes generales</h1>
      <div class="text-muted">Ventas, cobros, saldos, estados y productos más vendidos.</div>
    </div>
    <button class="btn btn-outline-secondary no-print" onclick="window.print()">Imprimir reporte</button>
  </div>
  <form class="card card-body mb-3 no-print" method="get">
    <div class="row g-2 align-items-end">
      <div class="col-md-3"><label class="form-label">Desde</label><input type="date" class="form-control" name="desde" value="<?= h($desde) ?>"></div>
      <div class="col-md-3"><label class="form-label">Hasta</label><input type="date" class="form-control" name="hasta" value="<?= h($hasta) ?>"></div>
      <div class="col-md-3"><label class="form-label">Empresa</label><select class="form-select" name="empresa"><option value="">Todas</option><option value="viprint" <?= $empresa==='viprint'?'selected':'' ?>>ViPrint</option><option value="imagen" <?= $empresa==='imagen'?'selected':'' ?>>Imagen</option></select></div>
      <div class="col-md-3"><button class="btn btn-primary w-100">Actualizar reporte</button></div>
    </div>
  </form>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Ventas del periodo</div><div class="h4 mb-0"><?= m2310_money_short($kpiNotas['total_notas'] ?? 0) ?></div><div class="small text-muted"><?= (int)($kpiNotas['notas'] ?? 0) ?> notas</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Cobrado en el periodo</div><div class="h4 mb-0 text-success"><?= m2310_money_short($kpiPagos['cobrado'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Saldo pendiente abierto</div><div class="h4 mb-0 text-danger"><?= m2310_money_short($kpiAbiertas['saldo_abierto'] ?? 0) ?></div><div class="small text-muted"><?= (int)($kpiAbiertas['abiertas'] ?? 0) ?> notas con saldo</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Entregadas en periodo</div><div class="h4 mb-0 text-primary"><?= (int)($kpiTerminadas['entregadas'] ?? 0) ?></div></div></div></div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Cobros por método</strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Método</th><th class="text-end">Cantidad</th><th class="text-end">Total</th></tr></thead><tbody><?php foreach($pagosMetodo as $r): ?><tr><td><?= h(m2310_estado_text($r['forma_pago'])) ?></td><td class="text-end"><?= (int)$r['cantidad'] ?></td><td class="text-end"><?= m2310_money_short($r['total']) ?></td></tr><?php endforeach; ?><?php if(!$pagosMetodo): ?><tr><td colspan="3" class="text-center text-muted py-3">Sin cobros.</td></tr><?php endif; ?></tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Ventas por empresa</strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Empresa</th><th class="text-end">Notas</th><th class="text-end">Venta</th><th class="text-end">Saldo</th></tr></thead><tbody><?php foreach($ventasEmpresa as $r): ?><tr><td><?= h($r['empresa']) ?></td><td class="text-end"><?= (int)$r['notas'] ?></td><td class="text-end"><?= m2310_money_short($r['total']) ?></td><td class="text-end"><?= m2310_money_short($r['saldo']) ?></td></tr><?php endforeach; ?><?php if(!$ventasEmpresa): ?><tr><td colspan="4" class="text-center text-muted py-3">Sin ventas.</td></tr><?php endif; ?></tbody></table></div></div></div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Estados de entrega</strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Estado</th><th class="text-end">Notas</th><th class="text-end">Venta</th><th class="text-end">Saldo</th></tr></thead><tbody><?php foreach($estados as $r): ?><tr><td><?= m2310_badge($r['estado_entrega']) ?></td><td class="text-end"><?= (int)$r['cantidad'] ?></td><td class="text-end"><?= m2310_money_short($r['total']) ?></td><td class="text-end"><?= m2310_money_short($r['saldo']) ?></td></tr><?php endforeach; ?><?php if(!$estados): ?><tr><td colspan="4" class="text-center text-muted py-3">Sin datos.</td></tr><?php endif; ?></tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Producción</strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Estado</th><th class="text-end">Notas</th></tr></thead><tbody><?php foreach($produccion as $r): ?><tr><td><?= m2310_badge($r['estado_produccion']) ?></td><td class="text-end"><?= (int)$r['cantidad'] ?></td></tr><?php endforeach; ?><?php if(!$produccion): ?><tr><td colspan="2" class="text-center text-muted py-3">Sin datos.</td></tr><?php endif; ?></tbody></table></div></div></div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Cobrado por día</strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Fecha</th><th class="text-end">Cobrado</th></tr></thead><tbody><?php foreach($porDia as $r): ?><tr><td><?= date_mx($r['fecha']) ?></td><td class="text-end"><?= m2310_money_short($r['cobrado']) ?></td></tr><?php endforeach; ?><?php if(!$porDia): ?><tr><td colspan="2" class="text-center text-muted py-3">Sin cobros.</td></tr><?php endif; ?></tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Productos / promociones más vendidos</strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Artículo</th><th class="text-end">Cantidad</th><th class="text-end">Total</th></tr></thead><tbody><?php foreach($topProductos as $r): ?><tr><td><?= h($r['descripcion']) ?></td><td class="text-end"><?= number_format((float)$r['cantidad'],2) ?></td><td class="text-end"><?= m2310_money_short($r['total']) ?></td></tr><?php endforeach; ?><?php if(!$topProductos): ?><tr><td colspan="3" class="text-center text-muted py-3">Sin productos.</td></tr><?php endif; ?></tbody></table></div></div></div>
  </div>

  <div class="card mb-3"><div class="card-header"><strong>Clientes con saldo pendiente</strong></div><div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead><tr><th>Nota</th><th>Cliente</th><th>Fecha</th><th>Promesa</th><th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th><th></th></tr></thead><tbody><?php foreach($deudores as $r): ?><tr><td><strong><?= h($r['folio']) ?></strong><br><span class="text-muted small"><?= h($r['empresa']) ?></span></td><td><?= h($r['cliente_nombre']) ?><br><span class="text-muted small"><?= h($r['telefono']) ?></span></td><td><?= date_mx($r['fecha_nota']) ?></td><td><?= date_mx($r['fecha_promesa']) ?></td><td class="text-end"><?= m2310_money_short($r['total']) ?></td><td class="text-end"><?= m2310_money_short($r['pagado']) ?></td><td class="text-end text-danger"><?= m2310_money_short($r['saldo']) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('nota_ver.php?id='.(int)$r['id']) ?>">Abrir</a></td></tr><?php endforeach; ?><?php if(!$deudores): ?><tr><td colspan="8" class="text-center text-muted py-4">No hay saldos pendientes.</td></tr><?php endif; ?></tbody></table></div></div>
</div>

<div id="printArea" class="report-print-area report-mini">
  <div class="avoid-break">
    <div class="report-title">Reporte general</div>
    <div class="report-subtitle">Empresa: <?= h($empresaNombreReporte) ?> &nbsp; | &nbsp; Periodo: <?= h(reporte_fecha_larga($desde)) ?> al <?= h(reporte_fecha_larga($hasta)) ?> &nbsp; | &nbsp; Generado: <?= h(date('d/m/Y H:i')) ?></div>
    <div class="summary-grid">
      <div class="summary-box"><div class="summary-label">Ventas</div><div class="summary-value"><?= reporte_money($kpiNotas['total_notas'] ?? 0) ?></div><div class="small muted"><?= (int)($kpiNotas['notas'] ?? 0) ?> notas</div></div>
      <div class="summary-box"><div class="summary-label">Cobrado</div><div class="summary-value"><?= reporte_money($kpiPagos['cobrado'] ?? 0) ?></div></div>
      <div class="summary-box"><div class="summary-label">Saldo pendiente</div><div class="summary-value"><?= reporte_money($kpiAbiertas['saldo_abierto'] ?? 0) ?></div><div class="small muted"><?= (int)($kpiAbiertas['abiertas'] ?? 0) ?> notas</div></div>
      <div class="summary-box"><div class="summary-label">Entregadas</div><div class="summary-value"><?= (int)($kpiTerminadas['entregadas'] ?? 0) ?></div></div>
    </div>
  </div>

  <div class="two-cols">
    <div class="avoid-break"><div class="section-title">Cobros por método</div><table><thead><tr><th>Método</th><th class="text-end">Cant.</th><th class="text-end">Total</th></tr></thead><tbody><?php foreach($pagosMetodo as $r): ?><tr><td><?= h(reporte_estado($r['forma_pago'])) ?></td><td class="text-end"><?= (int)$r['cantidad'] ?></td><td class="text-end"><?= reporte_money($r['total']) ?></td></tr><?php endforeach; ?><?php if(!$pagosMetodo): ?><tr><td colspan="3">Sin cobros.</td></tr><?php endif; ?></tbody></table></div>
    <div class="avoid-break"><div class="section-title">Ventas por empresa</div><table><thead><tr><th>Empresa</th><th class="text-end">Notas</th><th class="text-end">Venta</th><th class="text-end">Saldo</th></tr></thead><tbody><?php foreach($ventasEmpresa as $r): ?><tr><td><?= h($r['empresa']) ?></td><td class="text-end"><?= (int)$r['notas'] ?></td><td class="text-end"><?= reporte_money($r['total']) ?></td><td class="text-end"><?= reporte_money($r['saldo']) ?></td></tr><?php endforeach; ?><?php if(!$ventasEmpresa): ?><tr><td colspan="4">Sin ventas.</td></tr><?php endif; ?></tbody></table></div>
  </div>

  <div class="two-cols">
    <div class="avoid-break"><div class="section-title">Estados de entrega</div><table><thead><tr><th>Estado</th><th class="text-end">Notas</th><th class="text-end">Venta</th><th class="text-end">Saldo</th></tr></thead><tbody><?php foreach($estados as $r): ?><tr><td><?= h(reporte_estado($r['estado_entrega'])) ?></td><td class="text-end"><?= (int)$r['cantidad'] ?></td><td class="text-end"><?= reporte_money($r['total']) ?></td><td class="text-end"><?= reporte_money($r['saldo']) ?></td></tr><?php endforeach; ?><?php if(!$estados): ?><tr><td colspan="4">Sin datos.</td></tr><?php endif; ?></tbody></table></div>
    <div class="avoid-break"><div class="section-title">Producción</div><table><thead><tr><th>Estado</th><th class="text-end">Notas</th></tr></thead><tbody><?php foreach($produccion as $r): ?><tr><td><?= h(reporte_estado($r['estado_produccion'])) ?></td><td class="text-end"><?= (int)$r['cantidad'] ?></td></tr><?php endforeach; ?><?php if(!$produccion): ?><tr><td colspan="2">Sin datos.</td></tr><?php endif; ?></tbody></table></div>
  </div>

  <div class="two-cols">
    <div class="avoid-break"><div class="section-title">Cobrado por día</div><table><thead><tr><th>Fecha</th><th class="text-end">Cobrado</th></tr></thead><tbody><?php foreach($porDia as $r): ?><tr><td><?= h(date_mx($r['fecha'])) ?></td><td class="text-end"><?= reporte_money($r['cobrado']) ?></td></tr><?php endforeach; ?><?php if(!$porDia): ?><tr><td colspan="2">Sin cobros.</td></tr><?php endif; ?></tbody></table></div>
    <div class="avoid-break"><div class="section-title">Productos / promociones más vendidos</div><table><thead><tr><th>Artículo</th><th class="text-end">Cant.</th><th class="text-end">Total</th></tr></thead><tbody><?php foreach($topProductos as $r): ?><tr><td><?= h($r['descripcion']) ?></td><td class="text-end"><?= number_format((float)$r['cantidad'],2) ?></td><td class="text-end"><?= reporte_money($r['total']) ?></td></tr><?php endforeach; ?><?php if(!$topProductos): ?><tr><td colspan="3">Sin productos.</td></tr><?php endif; ?></tbody></table></div>
  </div>

  <div class="section-title">Clientes con saldo pendiente</div>
  <table>
    <thead><tr><th>Nota</th><th>Empresa</th><th>Cliente</th><th>Fecha</th><th>Promesa</th><th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th></tr></thead>
    <tbody>
      <?php foreach($deudores as $r): ?><tr><td><strong><?= h($r['folio']) ?></strong></td><td><?= h($r['empresa']) ?></td><td><?= h($r['cliente_nombre']) ?><br><span class="small muted"><?= h($r['telefono']) ?></span></td><td><?= h(date_mx($r['fecha_nota'])) ?></td><td><?= h(date_mx($r['fecha_promesa'])) ?></td><td class="text-end"><?= reporte_money($r['total']) ?></td><td class="text-end"><?= reporte_money($r['pagado']) ?></td><td class="text-end"><?= reporte_money($r['saldo']) ?></td></tr><?php endforeach; ?>
      <?php if(!$deudores): ?><tr><td colspan="8">No hay saldos pendientes.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
