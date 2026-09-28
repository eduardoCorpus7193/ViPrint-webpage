<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/modulos_2_3_10_helpers.php';
require_login();
if (!m2310_can_use_corte()) { flash('danger','No tienes permiso para imprimir cortes.'); redirect_to('index.php'); }
$id = (int)($_GET['id'] ?? 0);
$fecha = $_GET['fecha'] ?? '';
if ($id > 0) {
    $stmt = db()->prepare("SELECT c.*, u.nombre realizado_nombre, uc.nombre cerrado_nombre FROM v2_cortes_caja c LEFT JOIN v2_usuarios u ON u.id=c.realizado_por LEFT JOIN v2_usuarios uc ON uc.id=c.cerrado_por WHERE c.id=?");
    $stmt->execute(array($id));
} else {
    $fecha = m2310_date_or_today($fecha ?: date('Y-m-d'));
    $stmt = db()->prepare("SELECT c.*, u.nombre realizado_nombre, uc.nombre cerrado_nombre FROM v2_cortes_caja c LEFT JOIN v2_usuarios u ON u.id=c.realizado_por LEFT JOIN v2_usuarios uc ON uc.id=c.cerrado_por WHERE c.fecha_corte=?");
    $stmt->execute(array($fecha));
}
$c = $stmt->fetch();
if (!$c) { flash('danger','Corte no encontrado.'); redirect_to('cortes_historial.php'); }
$lines = array();
$lines[] = 'VIPRINT PUBLICIDAD';
$lines[] = 'CORTE DIARIO DE CAJA';
$lines[] = str_repeat('-', 32);
$lines[] = 'Fecha: ' . date_mx($c['fecha_corte']);
$lines[] = 'Estado: ' . ((int)$c['cerrado']===1 ? 'CERRADO' : 'BORRADOR');
$lines[] = str_repeat('-', 32);
$lines[] = 'Fondo inicial:     ' . m2310_money_short($c['fondo_inicial']);
$lines[] = 'Entradas efectivo: ' . m2310_money_short($c['entradas_efectivo']);
$lines[] = 'Salidas efectivo:  ' . m2310_money_short($c['salidas_efectivo_operativas']);
$lines[] = 'Caja esperada:     ' . m2310_money_short($c['caja_esperada']);
$lines[] = 'Efectivo contado:  ' . m2310_money_short($c['efectivo_contado']);
$lines[] = 'Diferencia:        ' . m2310_money_short($c['diferencia_efectivo']);
$lines[] = str_repeat('-', 32);
$lines[] = 'Transferencia:     ' . m2310_money_short($c['entradas_transferencia']);
$lines[] = 'Tarjeta:           ' . m2310_money_short($c['entradas_tarjeta']);
$lines[] = 'Otro:              ' . m2310_money_short($c['entradas_otro']);
$lines[] = str_repeat('-', 32);
$lines[] = 'Entrega a Luis:    ' . m2310_money_short($c['entrega_luis_real']);
$lines[] = 'Fondo final:       ' . m2310_money_short($c['fondo_final']);
$lines[] = 'Hora entrega:      ' . substr($c['hora_entrega'] ?? '',0,5);
$lines[] = str_repeat('-', 32);
$lines[] = 'Entrega: ' . ($c['entrega_nombre'] ?: '');
$lines[] = 'Recibe:  ' . ($c['recibe_nombre'] ?: 'Luis');
$lines[] = '';
$lines[] = 'Firma entrega:';
$lines[] = '';
$lines[] = '____________________________';
$lines[] = '';
$lines[] = 'Firma recibe:';
$lines[] = '';
$lines[] = '____________________________';
if (!empty($c['observaciones'])) {
    $lines[] = str_repeat('-', 32);
    $lines[] = 'Obs: ' . preg_replace('/\s+/', ' ', trim($c['observaciones']));
}
$lines[] = str_repeat('-', 32);
$lines[] = 'Generado: ' . date('d/m/Y H:i');
$ticketText = implode("\n", $lines) . "\n\n\n";
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3 no-print">
  <div><h1 class="h3 mb-1">Ticket de corte</h1><div class="text-muted">Fecha <?= date_mx($c['fecha_corte']) ?></div></div>
  <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-secondary" href="<?= url('corte_diario.php?fecha='.$c['fecha_corte']) ?>">Volver</a><button class="btn btn-outline-primary" onclick="window.print()">Imprimir con navegador</button><button class="btn btn-primary" id="btnCorteQz">Imprimir ticket térmico</button></div>
</div>
<div class="card" style="max-width:420px"><div class="card-body"><pre id="corteTicketText" style="font-family:Consolas,monospace;font-size:13px;white-space:pre-wrap;margin:0"><?= h($ticketText) ?></pre></div></div>
<script>window.CORTE_TICKET_TEXT = <?= json_encode($ticketText, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>
<script src="<?= url('assets/js/qz-corte.js') ?>?v=2310"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
