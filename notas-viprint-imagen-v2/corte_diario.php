<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_once __DIR__ . '/includes/header.php';

if (!can_cash()) { flash('danger','No tienes permiso para cortes.'); redirect_to('index.php'); }
if (!table_exists('v2_cortes_caja')) {
    echo '<div class="alert alert-warning"><strong>Falta instalar la actualización de corte diario.</strong><br>Sube y abre <code>instalar_corte_diario_caja_v2.php?clave=corte2026</code>.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
if (!table_exists('v2_caja_movimientos')) {
    echo '<div class="alert alert-warning"><strong>Falta instalar caja.</strong><br>Esta pantalla necesita la tabla <code>v2_caja_movimientos</code>.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$isAdmin = role_in(array('admin'));

function corte_resumen_movimientos_viprint($fecha) {
    $res = array(
        'entradas_efectivo'=>0.0,'salidas_efectivo'=>0.0,'salidas_efectivo_operativas'=>0.0,'entrega_luis_sistema'=>0.0,
        'entradas_transferencia'=>0.0,'entradas_tarjeta'=>0.0,'entradas_otro'=>0.0,
        'salidas_transferencia'=>0.0,'salidas_tarjeta'=>0.0,'salidas_otro'=>0.0,
        'total_entradas'=>0.0,'total_salidas'=>0.0
    );
    $stmt = db()->prepare("SELECT tipo, concepto, forma_pago, COALESCE(SUM(monto),0) total FROM v2_caja_movimientos WHERE fecha_operacion=? GROUP BY tipo, concepto, forma_pago");
    $stmt->execute(array($fecha));
    foreach($stmt->fetchAll() as $r) {
        $monto = (float)$r['total'];
        $tipo = $r['tipo'];
        $forma = $r['forma_pago'];
        $concepto = $r['concepto'];
        if ($tipo === 'entrada') {
            $res['total_entradas'] += $monto;
            if ($forma === 'efectivo') $res['entradas_efectivo'] += $monto;
            elseif ($forma === 'transferencia') $res['entradas_transferencia'] += $monto;
            elseif ($forma === 'tarjeta') $res['entradas_tarjeta'] += $monto;
            else $res['entradas_otro'] += $monto;
        } else {
            $res['total_salidas'] += $monto;
            if ($forma === 'efectivo') {
                $res['salidas_efectivo'] += $monto;
                if ($concepto === 'entrega_luis') $res['entrega_luis_sistema'] += $monto;
                else $res['salidas_efectivo_operativas'] += $monto;
            } elseif ($forma === 'transferencia') $res['salidas_transferencia'] += $monto;
            elseif ($forma === 'tarjeta') $res['salidas_tarjeta'] += $monto;
            else $res['salidas_otro'] += $monto;
        }
    }
    return $res;
}

$mov = corte_resumen_movimientos_viprint($fecha);
$stmt = db()->prepare('SELECT c.*, u.nombre realizado_nombre, uc.nombre cerrado_nombre FROM v2_cortes_caja c LEFT JOIN v2_usuarios u ON u.id=c.realizado_por LEFT JOIN v2_usuarios uc ON uc.id=c.cerrado_por WHERE c.fecha_corte=?');
$stmt->execute(array($fecha));
$c = $stmt->fetch();
$cerrado = $c && (int)$c['cerrado'] === 1;

$fondoInicial = isset($c['fondo_inicial']) ? (float)$c['fondo_inicial'] : 800.00;
$fondoBase = isset($c['fondo_base']) ? (float)$c['fondo_base'] : 800.00;
$cajaEsperada = $fondoInicial + $mov['entradas_efectivo'] - $mov['salidas_efectivo_operativas'];
$efectivoContado = isset($c['efectivo_contado']) ? (float)$c['efectivo_contado'] : $cajaEsperada;
$diferencia = $efectivoContado - $cajaEsperada;
$entregaSugerida = max(0, $efectivoContado - $fondoBase);
$entregaReal = isset($c['entrega_luis_real']) ? (float)$c['entrega_luis_real'] : $entregaSugerida;
$fondoFinal = $efectivoContado - $entregaReal;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div>
    <h1 class="h3 mb-0">Corte diario de caja</h1>
    <div class="text-muted">Corte conjunto ViPrint / Imagen · <?= date_mx($fecha) ?></div>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-primary" href="<?= url('caja.php?fecha='.$fecha) ?>">Volver a caja</a>
    <?php if($cerrado): ?><a class="btn btn-dark" href="<?= url('corte_ticket.php?id='.(int)$c['id']) ?>">Imprimir ticket de corte</a><?php endif; ?>
    <button class="btn btn-primary" onclick="window.print()">Imprimir hoja</button>
  </div>
</div>

<form class="card card-body mb-4 no-print" method="get">
  <div class="row g-2 align-items-end"><div class="col-md-8"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control" value="<?= h($fecha) ?>"></div><div class="col-md-4"><button class="btn btn-outline-primary w-100">Ver fecha</button></div></div>
</form>

<?php if($cerrado): ?>
<div class="alert alert-success"><strong>Corte cerrado.</strong> Cerrado por <?= h($c['cerrado_nombre'] ?? 'usuario') ?> el <?= h($c['cerrado_at'] ?? '') ?>. Los pagos y movimientos de esta fecha están bloqueados.</div>
<?php elseif($c): ?>
<div class="alert alert-info"><strong>Borrador guardado.</strong> Aún no está cerrado. Revisa diferencias antes de entregar el dinero.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="stat"><div class="label">Entradas efectivo</div><div class="value"><?= money($mov['entradas_efectivo']) ?></div></div></div>
  <div class="col-md-3"><div class="stat"><div class="label">Salidas efectivo</div><div class="value"><?= money($mov['salidas_efectivo_operativas']) ?></div></div></div>
  <div class="col-md-3"><div class="stat"><div class="label">Caja esperada</div><div class="value"><?= money($cajaEsperada) ?></div></div></div>
  <div class="col-md-3"><div class="stat"><div class="label">Diferencia</div><div class="value <?= abs($diferencia) > 0.009 ? 'text-danger' : 'text-success' ?>"><?= money($diferencia) ?></div></div></div>
</div>

<div class="card mb-4"><div class="card-header">Resumen por método</div><div class="card-body"><div class="row g-3">
  <div class="col-md-3"><strong>Efectivo</strong><br>Entradas <?= money($mov['entradas_efectivo']) ?><br>Salidas operativas <?= money($mov['salidas_efectivo_operativas']) ?><br>Entrega a Luis registrada <?= money($mov['entrega_luis_sistema']) ?></div>
  <div class="col-md-3"><strong>Transferencia</strong><br>Entradas <?= money($mov['entradas_transferencia']) ?><br>Salidas <?= money($mov['salidas_transferencia']) ?><br><span class="text-muted small">Solo se reporta, no se entrega en efectivo.</span></div>
  <div class="col-md-3"><strong>Tarjeta</strong><br>Entradas <?= money($mov['entradas_tarjeta']) ?><br>Salidas <?= money($mov['salidas_tarjeta']) ?><br><span class="text-muted small">Solo se reporta, no se entrega en efectivo.</span></div>
  <div class="col-md-3"><strong>Otro</strong><br>Entradas <?= money($mov['entradas_otro']) ?><br>Salidas <?= money($mov['salidas_otro']) ?></div>
</div></div></div>

<div class="card"><div class="card-header">Captura del corte</div><div class="card-body">
<?php if($cerrado && !$isAdmin): ?>
  <div class="alert alert-warning mb-0">Este corte ya está cerrado. Solo admin puede corregirlo.</div>
<?php else: ?>
<form method="post" action="<?= url('corte_guardar.php') ?>" id="formCorte" class="row g-3">
  <input type="hidden" name="fecha_corte" value="<?= h($fecha) ?>">
  <input type="hidden" id="entradas_efectivo" value="<?= h($mov['entradas_efectivo']) ?>">
  <input type="hidden" id="salidas_efectivo_operativas" value="<?= h($mov['salidas_efectivo_operativas']) ?>">
  <div class="col-md-3"><label class="form-label required">Fondo inicial capturado</label><input type="number" step="0.01" class="form-control calc-corte" name="fondo_inicial" id="fondo_inicial" value="<?= h($fondoInicial) ?>" required></div>
  <div class="col-md-3"><label class="form-label required">Fondo base que debe quedar</label><input type="number" step="0.01" class="form-control calc-corte" name="fondo_base" id="fondo_base" value="<?= h($fondoBase) ?>" required></div>
  <div class="col-md-3"><label class="form-label required">Efectivo contado físicamente</label><input type="number" step="0.01" class="form-control calc-corte" name="efectivo_contado" id="efectivo_contado" value="<?= h($efectivoContado) ?>" required></div>
  <div class="col-md-3"><label class="form-label required">Entrega real a Luis</label><input type="number" step="0.01" class="form-control calc-corte" name="entrega_luis_real" id="entrega_luis_real" value="<?= h($entregaReal) ?>" required></div>
  <div class="col-md-3"><label class="form-label">Caja esperada</label><input class="form-control" id="caja_esperada_view" value="<?= h(money($cajaEsperada)) ?>" readonly></div>
  <div class="col-md-3"><label class="form-label">Diferencia</label><input class="form-control" id="diferencia_view" value="<?= h(money($diferencia)) ?>" readonly></div>
  <div class="col-md-3"><label class="form-label">Entrega sugerida a Luis</label><input class="form-control" id="entrega_sugerida_view" value="<?= h(money($entregaSugerida)) ?>" readonly></div>
  <div class="col-md-3"><label class="form-label">Fondo final que queda</label><input class="form-control" id="fondo_final_view" value="<?= h(money($fondoFinal)) ?>" readonly></div>
  <div class="col-md-4"><label class="form-label">Entrega</label><input class="form-control" name="entrega_nombre" value="<?= h($c['entrega_nombre'] ?? (current_user()['nombre'] ?? '')) ?>" placeholder="Mafer / Danae"></div>
  <div class="col-md-4"><label class="form-label">Recibe</label><input class="form-control" name="recibe_nombre" value="<?= h($c['recibe_nombre'] ?? 'Luis') ?>" placeholder="Luis"></div>
  <div class="col-md-4"><label class="form-label">Hora de entrega</label><input type="time" class="form-control" name="hora_entrega" value="<?= h($c['hora_entrega'] ?? date('H:i')) ?>"></div>
  <div class="col-12"><label class="form-label">Observaciones generales del corte</label><textarea class="form-control" name="observaciones" rows="3" placeholder="Ej. diferencia por cambio, transferencia pendiente, entrega parcial, aclaración con cliente."><?= h($c['observaciones'] ?? '') ?></textarea></div>
  <div class="col-12" id="adminWarning" style="display:none"><div class="alert alert-warning mb-0">Hay diferencia de efectivo. Solo un usuario admin puede cerrar el corte con diferencia. Puedes guardarlo como borrador.</div></div>
  <div class="col-12 d-flex flex-wrap gap-2">
    <button class="btn btn-outline-primary" name="accion" value="guardar">Guardar borrador</button>
    <button class="btn btn-primary" name="accion" value="cerrar" onclick="return confirm('¿Cerrar corte? Después de cerrar se bloquean pagos y movimientos de esta fecha.')">Cerrar corte y generar entrega a Luis</button>
  </div>
</form>
<?php endif; ?>
</div></div>

<script>
(function(){
  const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
  const money = n => '$' + Number(n || 0).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' MXN';
  const byId = id => document.getElementById(id);
  function val(id){ return parseFloat((byId(id)?.value || '0').replace(/,/g,'')) || 0; }
  function recalc(){
    const entradas = val('entradas_efectivo');
    const salidas = val('salidas_efectivo_operativas');
    const fondoInicial = val('fondo_inicial');
    const fondoBase = val('fondo_base');
    const contado = val('efectivo_contado');
    const entregaReal = val('entrega_luis_real');
    const esperada = fondoInicial + entradas - salidas;
    const diferencia = contado - esperada;
    const sugerida = Math.max(0, contado - fondoBase);
    const finalCaja = contado - entregaReal;
    if(byId('caja_esperada_view')) byId('caja_esperada_view').value = money(esperada);
    if(byId('diferencia_view')) byId('diferencia_view').value = money(diferencia);
    if(byId('entrega_sugerida_view')) byId('entrega_sugerida_view').value = money(sugerida);
    if(byId('fondo_final_view')) byId('fondo_final_view').value = money(finalCaja);
    if(byId('adminWarning')) byId('adminWarning').style.display = (!isAdmin && Math.abs(diferencia) > 0.009) ? 'block' : 'none';
  }
  document.querySelectorAll('.calc-corte').forEach(el => el.addEventListener('input', recalc));
  recalc();
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
