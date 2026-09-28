<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/modulos_2_3_10_helpers.php';
require_login();
if (!m2310_can_use_corte()) { flash('danger','No tienes permiso para usar corte diario.'); redirect_to('index.php'); }
require_once __DIR__ . '/includes/header.php';

if (!table_exists('v2_cortes_caja') || !table_exists('v2_caja_movimientos')) {
    echo '<div class="alert alert-warning"><strong>Falta instalar la actualización.</strong><br>Sube y abre <code>instalar_modulos_2_3_10_v2.php?clave=modulos2310</code>.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$fecha = m2310_date_or_today($_GET['fecha'] ?? date('Y-m-d'));
$mov = m2310_corte_resumen_movimientos($fecha);
$stmt = db()->prepare("SELECT c.*, u.nombre realizado_nombre, uc.nombre cerrado_nombre FROM v2_cortes_caja c LEFT JOIN v2_usuarios u ON u.id=c.realizado_por LEFT JOIN v2_usuarios uc ON uc.id=c.cerrado_por WHERE c.fecha_corte=? LIMIT 1");
$stmt->execute(array($fecha));
$corte = $stmt->fetch();
$detalle = m2310_corte_detalle_movimientos($fecha);

$fondoInicial = $corte ? (float)$corte['fondo_inicial'] : 800.00;
$fondoBase = $corte ? (float)$corte['fondo_base'] : 800.00;
$efectivoContado = $corte ? (float)$corte['efectivo_contado'] : max(0, $fondoInicial + $mov['entradas_efectivo'] - $mov['salidas_efectivo_operativas']);
$cajaEsperada = $fondoInicial + $mov['entradas_efectivo'] - $mov['salidas_efectivo_operativas'];
$diferencia = $efectivoContado - $cajaEsperada;
$entregaSugerida = max(0, $efectivoContado - $fondoBase);
$entregaReal = $corte ? (float)$corte['entrega_luis_real'] : $entregaSugerida;
$fondoFinal = $efectivoContado - $entregaReal;
$cerrado = $corte && (int)$corte['cerrado'] === 1;
$canDiff = m2310_can_close_corte_with_difference();
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div>
    <h1 class="h3 mb-1">Corte diario de caja</h1>
    <div class="text-muted">Corte conjunto de ViPrint e Imagen. Fondo base: <?= m2310_money_short($fondoBase) ?>.</div>
  </div>
  <div class="d-flex gap-2 flex-wrap no-print">
    <a class="btn btn-outline-secondary" href="<?= url('cortes_historial.php') ?>">Historial</a>
    <?php if ($cerrado): ?><a class="btn btn-primary" href="<?= url('corte_ticket.php?id='.(int)$corte['id']) ?>">Imprimir ticket</a><?php endif; ?>
  </div>
</div>

<form class="card card-body mb-3 no-print" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" class="form-control" name="fecha" value="<?= h($fecha) ?>"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Ver corte</button></div>
    <div class="col-md-7 text-md-end text-muted small">Las transferencias y tarjetas solo se reportan; la entrega a Luis se calcula con efectivo.</div>
  </div>
</form>

<?php if ($cerrado): ?>
<div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
  <div><strong>Corte cerrado.</strong> Cerrado por <?= h($corte['cerrado_nombre'] ?? 'usuario') ?> el <?= h($corte['cerrado_at']) ?>.</div>
  <?php if (m2310_can_reopen_corte()): ?>
  <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#reabrirCorte">Reabrir corte</button>
  <?php endif; ?>
</div>
<?php if (m2310_can_reopen_corte()): ?>
<div class="collapse mb-3" id="reabrirCorte">
  <form method="post" action="<?= url('corte_reabrir.php') ?>" class="card card-body border-danger" onsubmit="return confirm('¿Seguro que deseas reabrir este corte?');">
    <input type="hidden" name="corte_id" value="<?= (int)$corte['id'] ?>">
    <label class="form-label">Motivo para reabrir</label>
    <textarea class="form-control mb-2" name="motivo" required placeholder="Ej. Se detectó pago capturado con método incorrecto."></textarea>
    <button class="btn btn-danger align-self-start">Reabrir corte</button>
  </form>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Fondo inicial</div><div class="h4 mb-0"><?= m2310_money_short($fondoInicial) ?></div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Entradas efectivo</div><div class="h4 mb-0 text-success"><?= m2310_money_short($mov['entradas_efectivo']) ?></div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Salidas efectivo operativas</div><div class="h4 mb-0 text-danger"><?= m2310_money_short($mov['salidas_efectivo_operativas']) ?></div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Caja esperada</div><div class="h4 mb-0"><?= m2310_money_short($cajaEsperada) ?></div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Transferencia</div><div class="h4 mb-0"><?= m2310_money_short($mov['entradas_transferencia']) ?></div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Tarjeta</div><div class="h4 mb-0"><?= m2310_money_short($mov['entradas_tarjeta']) ?></div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Diferencia</div><div class="h4 mb-0 <?= abs($diferencia) > 0.009 ? 'text-danger' : 'text-success' ?>"><?= m2310_money_short($diferencia) ?></div></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Entrega sugerida a Luis</div><div class="h4 mb-0"><?= m2310_money_short($entregaSugerida) ?></div></div></div></div>
</div>

<form method="post" action="<?= url('corte_guardar.php') ?>" class="card mb-3 no-print">
  <div class="card-header"><strong>Captura del corte</strong></div>
  <div class="card-body">
    <?php if (!$canDiff): ?><div class="alert alert-info py-2">Puedes cerrar el corte si no hay diferencia. Si hay diferencia, solo admin puede cerrarlo.</div><?php endif; ?>
    <input type="hidden" name="fecha_corte" value="<?= h($fecha) ?>">
    <div class="row g-3">
      <div class="col-md-2"><label class="form-label">Fondo inicial</label><input class="form-control" type="number" step="0.01" name="fondo_inicial" value="<?= h($fondoInicial) ?>" <?= $cerrado?'readonly':'' ?>></div>
      <div class="col-md-2"><label class="form-label">Fondo base</label><input class="form-control" type="number" step="0.01" name="fondo_base" value="<?= h($fondoBase) ?>" <?= $cerrado?'readonly':'' ?>></div>
      <div class="col-md-2"><label class="form-label">Efectivo contado</label><input class="form-control" type="number" step="0.01" name="efectivo_contado" value="<?= h($efectivoContado) ?>" <?= $cerrado?'readonly':'' ?>></div>
      <div class="col-md-2"><label class="form-label">Entrega a Luis</label><input class="form-control" type="number" step="0.01" name="entrega_luis_real" value="<?= h($entregaReal) ?>" <?= $cerrado?'readonly':'' ?>></div>
      <div class="col-md-2"><label class="form-label">Entrega</label><input class="form-control" name="entrega_nombre" value="<?= h($corte['entrega_nombre'] ?? (current_user()['nombre'] ?? '')) ?>" <?= $cerrado?'readonly':'' ?>></div>
      <div class="col-md-2"><label class="form-label">Recibe</label><input class="form-control" name="recibe_nombre" value="<?= h($corte['recibe_nombre'] ?? 'Luis') ?>" <?= $cerrado?'readonly':'' ?>></div>
      <div class="col-md-2"><label class="form-label">Hora entrega</label><input class="form-control" type="time" name="hora_entrega" value="<?= h(substr($corte['hora_entrega'] ?? date('H:i'),0,5)) ?>" <?= $cerrado?'readonly':'' ?>></div>
      <div class="col-md-10"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="2" <?= $cerrado?'readonly':'' ?>><?= h($corte['observaciones'] ?? '') ?></textarea></div>
    </div>
  </div>
  <div class="card-footer d-flex gap-2 justify-content-end flex-wrap">
    <?php if (!$cerrado): ?>
      <button class="btn btn-outline-secondary" name="accion" value="guardar">Guardar borrador</button>
      <button class="btn btn-primary" name="accion" value="cerrar" onclick="return confirm('¿Cerrar corte diario? Después se bloquearán movimientos de este día.');">Cerrar corte e imprimir</button>
    <?php else: ?>
      <a class="btn btn-primary" href="<?= url('corte_ticket.php?id='.(int)$corte['id']) ?>">Imprimir ticket de corte</a>
    <?php endif; ?>
  </div>
</form>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Movimientos del día</strong>
    <a class="btn btn-sm btn-outline-primary no-print" href="<?= url('caja.php?fecha='.$fecha) ?>">Ir a caja</a>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead><tr><th>Hora</th><th>Tipo</th><th>Concepto</th><th>Método</th><th>Nota</th><th>Descripción</th><th class="text-end">Monto</th></tr></thead>
      <tbody>
      <?php foreach ($detalle as $d): ?>
        <tr>
          <td><?= h(substr($d['hora_operacion'] ?? '',0,5)) ?></td>
          <td><?= m2310_badge($d['tipo']) ?></td>
          <td><?= h(m2310_estado_text($d['concepto'])) ?></td>
          <td><?= h(m2310_estado_text($d['forma_pago'])) ?></td>
          <td><?= $d['folio'] ? '<a href="'.h(url('nota_ver.php?id='.(int)$d['nota_id'])).'">'.h($d['folio']).'</a>' : '<span class="text-muted">Manual</span>' ?></td>
          <td><div><?= h($d['descripcion']) ?></div><div class="text-muted small"><?= h($d['usuario_nombre'] ?? '') ?></div></td>
          <td class="text-end <?= $d['tipo']==='entrada'?'text-success':'text-danger' ?>"><?= m2310_money_short($d['monto']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$detalle): ?><tr><td colspan="7" class="text-center text-muted py-4">No hay movimientos registrados en esta fecha.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
