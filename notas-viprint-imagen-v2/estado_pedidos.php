<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/modulos_2_3_10_helpers.php';
require_login();
require_once __DIR__ . '/includes/header.php';

$q = trim($_GET['q'] ?? '');
$empresa = trim($_GET['empresa'] ?? '');
$alerta = trim($_GET['alerta'] ?? '');
$ver = trim($_GET['ver'] ?? 'abiertas');
$disenador = (int)($_GET['disenador'] ?? 0);
$params = array();
$where = '1=1';
$where .= m2310_notas_valid_sql('n');
if ($q !== '') {
    $where .= " AND (n.folio LIKE ? OR n.cliente_nombre LIKE ? OR n.negocio LIKE ? OR n.telefono LIKE ?)";
    $like = '%' . $q . '%'; array_push($params, $like, $like, $like, $like);
}
if ($empresa !== '') { $where .= " AND e.clave=?"; $params[] = $empresa; }
if ($ver === 'abiertas') { $where .= " AND n.estado_entrega NOT IN ('entregada','cancelada')"; }
if ($ver === 'cerradas') { $where .= " AND n.estado_entrega IN ('entregada','cancelada')"; }
if ($disenador > 0) { $where .= " AND n.disenador_id=?"; $params[] = $disenador; }
if (role_in(array('disenador','externo'))) { $where .= " AND n.disenador_id=?"; $params[] = current_user()['id']; }
if ($alerta === 'no_contesta') $where .= " AND n.estado_contacto='cliente_no_contesta'";
if ($alerta === 'sin_disenador') $where .= " AND n.estado_diseno='sin_asignar'";
if ($alerta === 'en_costura') $where .= " AND n.estado_produccion='en_costura'";
if ($alerta === 'terminada') $where .= " AND n.estado_produccion='terminada' AND n.estado_entrega<>'entregada'";
if ($alerta === 'saldo') $where .= " AND n.saldo>0";
if ($alerta === 'atrasadas') $where .= " AND n.fecha_promesa IS NOT NULL AND n.fecha_promesa<CURDATE() AND n.estado_entrega NOT IN ('entregada','cancelada')";

$sql = "SELECT n.*, e.nombre empresa, e.clave empresa_clave, u.nombre disenador FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id LEFT JOIN v2_usuarios u ON u.id=n.disenador_id WHERE $where ORDER BY COALESCE(n.fecha_promesa,n.fecha_nota) ASC, n.id DESC LIMIT 500";
$stmt = db()->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();

$stats = array('total'=>0,'sin_disenador'=>0,'no_contesta'=>0,'en_diseno'=>0,'en_costura'=>0,'terminada'=>0,'lista'=>0,'atrasadas'=>0,'saldo'=>0);
$today = date('Y-m-d');
foreach ($rows as $r) {
    $stats['total']++;
    if ($r['estado_diseno'] === 'sin_asignar') $stats['sin_disenador']++;
    if ($r['estado_contacto'] === 'cliente_no_contesta') $stats['no_contesta']++;
    if (in_array($r['estado_diseno'], array('pendiente_contacto','en_diseno','en_aprobacion'), true)) $stats['en_diseno']++;
    if ($r['estado_produccion'] === 'en_costura') $stats['en_costura']++;
    if ($r['estado_produccion'] === 'terminada' && $r['estado_entrega'] !== 'entregada') $stats['terminada']++;
    if ($r['estado_entrega'] === 'lista') $stats['lista']++;
    if (!empty($r['fecha_promesa']) && $r['fecha_promesa'] < $today && !in_array($r['estado_entrega'], array('entregada','cancelada'), true)) $stats['atrasadas']++;
    if ((float)$r['saldo'] > 0) $stats['saldo']++;
}
$disenadores = usuarios_activos();
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div><h1 class="h3 mb-1">Estado de pedidos</h1><div class="text-muted">Vista rápida para saber qué está pendiente, atrasado, en costura o terminado.</div></div>
  <a class="btn btn-primary no-print" href="<?= url('nota_form.php') ?>">Nueva nota</a>
</div>

<form class="card card-body mb-3 no-print" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Buscar</label><input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Folio, cliente, teléfono"></div>
    <div class="col-md-2"><label class="form-label">Empresa</label><select class="form-select" name="empresa"><option value="">Todas</option><option value="viprint" <?= $empresa==='viprint'?'selected':'' ?>>ViPrint</option><option value="imagen" <?= $empresa==='imagen'?'selected':'' ?>>Imagen</option></select></div>
    <div class="col-md-2"><label class="form-label">Vista</label><select class="form-select" name="ver"><option value="abiertas" <?= $ver==='abiertas'?'selected':'' ?>>Abiertas</option><option value="todas" <?= $ver==='todas'?'selected':'' ?>>Todas</option><option value="cerradas" <?= $ver==='cerradas'?'selected':'' ?>>Cerradas</option></select></div>
    <div class="col-md-2"><label class="form-label">Alerta</label><select class="form-select" name="alerta"><option value="">Todas</option><option value="atrasadas" <?= $alerta==='atrasadas'?'selected':'' ?>>Atrasadas</option><option value="no_contesta" <?= $alerta==='no_contesta'?'selected':'' ?>>Cliente no contesta</option><option value="sin_disenador" <?= $alerta==='sin_disenador'?'selected':'' ?>>Sin diseñador</option><option value="en_costura" <?= $alerta==='en_costura'?'selected':'' ?>>En costura</option><option value="terminada" <?= $alerta==='terminada'?'selected':'' ?>>Terminadas</option><option value="saldo" <?= $alerta==='saldo'?'selected':'' ?>>Con saldo</option></select></div>
    <div class="col-md-2"><label class="form-label">Diseñador</label><select class="form-select" name="disenador"><option value="0">Todos</option><?php foreach($disenadores as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $disenador===(int)$d['id']?'selected':'' ?>><?= h($d['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-1"><button class="btn btn-outline-primary w-100">Ver</button></div>
  </div>
</form>

<div class="row g-3 mb-3">
  <?php $cards = array(
    array('Total filtrado',$stats['total'],'secondary',''),
    array('Atrasadas',$stats['atrasadas'],'danger','atrasadas'),
    array('Cliente no contesta',$stats['no_contesta'],'warning','no_contesta'),
    array('Sin diseñador',$stats['sin_disenador'],'warning','sin_disenador'),
    array('En costura',$stats['en_costura'],'primary','en_costura'),
    array('Terminadas',$stats['terminada'],'success','terminada'),
    array('Con saldo',$stats['saldo'],'info','saldo'),
    array('Listas',$stats['lista'],'info','')
  ); foreach($cards as $c): ?>
  <div class="col-6 col-md-3 col-xl-2"><a class="text-decoration-none" href="<?= h(url('estado_pedidos.php?alerta='.$c[3].'&ver='.$ver.'&empresa='.$empresa)) ?>"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small"><?= h($c[0]) ?></div><div class="h3 mb-0 text-<?= h($c[2]) ?>"><?= (int)$c[1] ?></div></div></div></a></div>
  <?php endforeach; ?>
</div>

<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Nota</th><th>Cliente</th><th>Promesa</th><th style="min-width:160px">Avance</th><th>Estados</th><th>Alertas</th><th class="text-end">Saldo</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): $prog=m2310_progress_value($r); ?>
<tr>
  <td><strong><?= h($r['folio']) ?></strong><br><span class="text-muted small"><?= h($r['empresa']) ?></span></td>
  <td><div><?= h($r['cliente_nombre']) ?></div><div class="text-muted small"><?= h($r['telefono']) ?> <?= $r['disenador'] ? '· Diseño: '.h($r['disenador']) : '' ?></div></td>
  <td><?= $r['fecha_promesa'] ? date_mx($r['fecha_promesa']) : '<span class="text-muted">Sin fecha</span>' ?></td>
  <td><div class="progress" style="height:10px"><div class="progress-bar <?= h(m2310_progress_class($prog)) ?>" style="width:<?= (int)$prog ?>%"></div></div><div class="small text-muted mt-1"><?= (int)$prog ?>%</div></td>
  <td><div class="d-flex flex-wrap gap-1"><?= m2310_badge($r['estado_contacto']) ?><?= m2310_badge($r['estado_diseno']) ?><?= m2310_badge($r['estado_produccion']) ?><?= m2310_badge($r['estado_entrega']) ?><?= m2310_badge($r['estado_pago']) ?></div></td>
  <td><?= m2310_alert_badges($r) ?></td>
  <td class="text-end <?= (float)$r['saldo']>0?'text-danger':'text-success' ?>"><?= m2310_money_short($r['saldo']) ?></td>
  <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('nota_ver.php?id='.(int)$r['id']) ?>">Abrir</a></td>
</tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">No hay pedidos con estos filtros.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
