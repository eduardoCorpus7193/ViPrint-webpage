<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_once __DIR__ . '/includes/header.php';

if (!can_cash()) { flash('danger','No tienes permiso para caja y cortes.'); redirect_to('index.php'); }
if (!table_exists('v2_caja_movimientos')) {
    echo '<div class="alert alert-warning"><strong>Falta instalar la actualización de caja.</strong><br>Ejecuta primero el archivo de actualización de caja. Esta pantalla necesita la tabla <code>v2_caja_movimientos</code>.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$empresas = empresas();
$empresaDefault = 0;
foreach ($empresas as $e) {
    if (stripos($e['nombre'], 'viprint') !== false || strtolower($e['clave'] ?? '') === 'viprint') { $empresaDefault = (int)$e['id']; break; }
}
if ($empresaDefault <= 0 && isset($empresas[0])) $empresaDefault = (int)$empresas[0]['id'];

$corte = null;
if (table_exists('v2_cortes_caja')) {
    $stmt = db()->prepare('SELECT c.*, u.nombre realizado_nombre, uc.nombre cerrado_nombre FROM v2_cortes_caja c LEFT JOIN v2_usuarios u ON u.id=c.realizado_por LEFT JOIN v2_usuarios uc ON uc.id=c.cerrado_por WHERE c.fecha_corte=?');
    $stmt->execute(array($fecha));
    $corte = $stmt->fetch();
}
$corteCerrado = $corte && (int)$corte['cerrado'] === 1;

$stmt = db()->prepare("SELECT cm.*, e.nombre empresa, n.folio, n.cliente_nombre, u.nombre usuario
    FROM v2_caja_movimientos cm
    JOIN v2_empresas e ON e.id=cm.empresa_id
    LEFT JOIN v2_notas n ON n.id=cm.nota_id
    LEFT JOIN v2_usuarios u ON u.id=cm.creado_por
    WHERE cm.fecha_operacion=?
    ORDER BY cm.created_at DESC, cm.id DESC");
$stmt->execute(array($fecha));
$movs = $stmt->fetchAll();

$stmt = db()->prepare("SELECT forma_pago, tipo, COALESCE(SUM(monto),0) total
    FROM v2_caja_movimientos
    WHERE fecha_operacion=?
    GROUP BY forma_pago, tipo");
$stmt->execute(array($fecha));
$raw = $stmt->fetchAll();
$metodos = array('efectivo'=>'Efectivo','transferencia'=>'Transferencia','tarjeta'=>'Tarjeta','otro'=>'Otro');
$tot = array();
foreach($metodos as $k=>$v){ $tot[$k] = array('entrada'=>0.0,'salida'=>0.0,'neto'=>0.0); }
foreach($raw as $r){ if(isset($tot[$r['forma_pago']])) $tot[$r['forma_pago']][$r['tipo']] = (float)$r['total']; }
$totalEntradas = 0; $totalSalidas = 0;
foreach($tot as $k=>$fila){ $tot[$k]['neto'] = $fila['entrada'] - $fila['salida']; $totalEntradas += $fila['entrada']; $totalSalidas += $fila['salida']; }
$totalNeto = $totalEntradas - $totalSalidas;

$stmt = db()->prepare("SELECT COALESCE(SUM(monto),0) FROM v2_caja_movimientos WHERE fecha_operacion=? AND tipo='salida' AND concepto='entrega_luis' AND forma_pago='efectivo'");
$stmt->execute(array($fecha));
$entregaLuis = (float)$stmt->fetchColumn();
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div>
    <h1 class="h3 mb-0">Caja diaria</h1>
    <div class="text-muted">Corte conjunto ViPrint / Imagen · <?= date_mx($fecha) ?></div>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-primary" href="<?= url('corte_diario.php?fecha='.$fecha) ?>">Hacer corte</a>
    <?php if($corteCerrado): ?><a class="btn btn-outline-dark" href="<?= url('corte_ticket.php?id='.(int)$corte['id']) ?>">Ticket de corte</a><?php endif; ?>
    <button class="btn btn-primary" onclick="window.print()">Imprimir hoja</button>
  </div>
</div>

<form class="card card-body mb-4 no-print" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-8"><label class="form-label">Fecha</label><input type="date" class="form-control" name="fecha" value="<?= h($fecha) ?>"></div>
    <div class="col-md-4"><button class="btn btn-outline-primary w-100">Ver caja</button></div>
  </div>
</form>

<?php if($corteCerrado): ?>
<div class="alert alert-success">
  <strong>Corte cerrado.</strong> Cerrado por <?= h($corte['cerrado_nombre'] ?? 'usuario') ?> el <?= h($corte['cerrado_at'] ?? '') ?>.
  Los pagos y movimientos de esta fecha quedan bloqueados para evitar cambios después de entregar el dinero.
</div>
<?php elseif($corte): ?>
<div class="alert alert-info">
  <strong>Corte guardado como borrador.</strong> Aún no está cerrado. Puedes revisarlo en “Hacer corte”.
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="stat"><div class="label">Entradas</div><div class="value"><?= money($totalEntradas) ?></div></div></div>
  <div class="col-md-3"><div class="stat"><div class="label">Salidas</div><div class="value"><?= money($totalSalidas) ?></div></div></div>
  <div class="col-md-3"><div class="stat"><div class="label">Saldo neto del día</div><div class="value"><?= money($totalNeto) ?></div></div></div>
  <div class="col-md-3"><div class="stat"><div class="label">Entregado a Luis</div><div class="value"><?= money($entregaLuis) ?></div></div></div>
</div>

<div class="row g-3 mb-4">
<?php foreach($metodos as $k=>$label): ?>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?= h($label) ?></div><div class="fw-bold fs-5"><?= money($tot[$k]['neto']) ?></div><div class="small text-muted">Entradas <?= money($tot[$k]['entrada']) ?> · Salidas <?= money($tot[$k]['salida']) ?></div></div></div></div>
<?php endforeach; ?>
</div>

<div class="card mb-4"><div class="card-header">Movimientos del día</div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Hora</th><th>Empresa</th><th>Tipo</th><th>Concepto</th><th>Método</th><th>Nota</th><th>Descripción</th><th>Monto</th><th>Usuario</th></tr></thead><tbody>
<?php foreach($movs as $m): ?>
<tr>
  <td><?= h(substr((string)$m['hora_operacion'],0,5)) ?></td>
  <td><?= h($m['empresa']) ?></td>
  <td><?= estado_badge($m['tipo']) ?></td>
  <td><?= h(ucfirst(str_replace('_',' ', $m['concepto']))) ?></td>
  <td><?= h($m['forma_pago']) ?><?= $m['forma_pago_otro']?' / '.h($m['forma_pago_otro']):'' ?></td>
  <td><?php if($m['nota_id']): ?><a href="<?= url('nota_ver.php?id='.$m['nota_id']) ?>"><?= h($m['folio']) ?></a><br><span class="small text-muted"><?= h($m['cliente_nombre']) ?></span><?php else: ?><span class="text-muted">Sin nota</span><?php endif; ?></td>
  <td><?= nl2br(h($m['descripcion'] ?? '')) ?><?= $m['referencia']?'<br><span class="small text-muted">Ref: '.h($m['referencia']).'</span>':'' ?></td>
  <td class="fw-bold <?= $m['tipo']==='salida'?'text-danger':'text-success' ?>"><?= $m['tipo']==='salida'?'-':'+' ?><?= money($m['monto']) ?></td>
  <td><?= h($m['usuario'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
<?php if(!$movs): ?><tr><td colspan="9" class="text-center text-muted py-4">No hay movimientos en esta fecha.</td></tr><?php endif; ?>
</tbody></table></div></div></div>

<div class="card no-print"><div class="card-header">Registrar movimiento manual de caja</div><div class="card-body">
<?php if($corteCerrado): ?>
  <div class="alert alert-warning mb-0">Este día ya está cerrado. No se pueden agregar movimientos manuales a esta fecha.</div>
<?php else: ?>
  <form method="post" action="<?= url('caja_movimiento_guardar.php') ?>" class="row g-3">
    <input type="hidden" name="fecha_operacion" value="<?= h($fecha) ?>">
    <div class="col-md-3"><label class="form-label required">Empresa</label><select name="empresa_id" class="form-select" required><?php foreach($empresas as $e): ?><option value="<?= (int)$e['id'] ?>" <?= (int)$e['id']===$empresaDefault?'selected':'' ?>><?= h($e['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label required">Tipo</label><select name="tipo" class="form-select" required><option value="entrada">Entrada</option><option value="salida" selected>Salida</option></select></div>
    <div class="col-md-3"><label class="form-label required">Concepto</label><select name="concepto" class="form-select" required>
      <option value="gasto">Gasto</option>
      <option value="uber_envio">Uber / envío</option>
      <option value="devolucion_cliente">Devolución</option>
      <option value="entrega_luis">Entrega a Luis</option>
      <option value="prestamo_cambio">Préstamo / cambio</option>
      <option value="compra_menor">Compra menor</option>
      <option value="ajuste_caja">Ajuste de caja</option>
      <option value="otro">Otro</option>
    </select></div>
    <div class="col-md-2"><label class="form-label required">Método</label><select name="forma_pago" class="form-select" required><option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="tarjeta">Tarjeta</option><option value="otro">Otro</option></select></div>
    <div class="col-md-2"><label class="form-label required">Monto</label><input type="number" step="0.01" min="0.01" class="form-control" name="monto" required></div>
    <div class="col-md-3"><label class="form-label">Otro método</label><input class="form-control" name="forma_pago_otro"></div>
    <div class="col-md-3"><label class="form-label">Referencia</label><input class="form-control" name="referencia"></div>
    <div class="col-md-6"><label class="form-label required">Descripción</label><input class="form-control" name="descripcion" required placeholder="Ej. compra menor, entrega a Luis, Uber, devolución, ajuste"></div>
    <div class="col-12"><button class="btn btn-primary">Guardar movimiento</button></div>
  </form>
<?php endif; ?>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
