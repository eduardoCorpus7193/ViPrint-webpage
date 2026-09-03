<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!can_cash()) { flash('danger','No tienes permiso para imprimir cortes.'); redirect_to('index.php'); }
if (!table_exists('v2_cortes_caja')) { flash('danger','Falta instalar corte diario.'); redirect_to('index.php'); }

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT c.*, u.nombre realizado_nombre, uc.nombre cerrado_nombre FROM v2_cortes_caja c LEFT JOIN v2_usuarios u ON u.id=c.realizado_por LEFT JOIN v2_usuarios uc ON uc.id=c.cerrado_por WHERE c.id=?');
$stmt->execute(array($id));
$c = $stmt->fetch();
if (!$c) { flash('danger','Corte no encontrado.'); redirect_to('caja.php'); }

function ticket_money_corte($n) { return '$' . number_format((float)$n, 2); }
function corte_clean_line($s) {
    $s = (string)$s;
    $s = strtr($s, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','ñ'=>'n','Ñ'=>'N'));
    $s = preg_replace('/[^\x20-\x7E\n\r]/', '', $s);
    return $s;
}

$lines = array();
$lines[] = 'VIPRINT PUBLICIDAD';
$lines[] = 'CORTE DIARIO DE CAJA';
$lines[] = str_repeat('-', 32);
$lines[] = 'Fecha: ' . date_mx($c['fecha_corte']);
$lines[] = 'Corte: #' . (int)$c['id'];
$lines[] = 'Realizo: ' . ($c['realizado_nombre'] ?? '');
$lines[] = 'Cerro: ' . ($c['cerrado_nombre'] ?? '');
$lines[] = str_repeat('-', 32);
$lines[] = 'Fondo inicial: ' . ticket_money_corte($c['fondo_inicial']);
$lines[] = 'Fondo base:    ' . ticket_money_corte($c['fondo_base']);
$lines[] = 'Entrada efec.: ' . ticket_money_corte($c['entradas_efectivo']);
$lines[] = 'Salida efec.:  ' . ticket_money_corte($c['salidas_efectivo_operativas']);
$lines[] = 'Caja esperada: ' . ticket_money_corte($c['caja_esperada']);
$lines[] = 'Contado:       ' . ticket_money_corte($c['efectivo_contado']);
$lines[] = 'Diferencia:    ' . ticket_money_corte($c['diferencia_efectivo']);
$lines[] = 'Entrega Luis:  ' . ticket_money_corte($c['entrega_luis_real']);
$lines[] = 'Queda caja:    ' . ticket_money_corte($c['fondo_final']);
$lines[] = str_repeat('-', 32);
$lines[] = 'Transferencia: ' . ticket_money_corte($c['entradas_transferencia']);
$lines[] = 'Tarjeta:       ' . ticket_money_corte($c['entradas_tarjeta']);
$lines[] = 'Otro:          ' . ticket_money_corte($c['entradas_otro']);
$lines[] = str_repeat('-', 32);
$lines[] = 'Entrega: ' . ($c['entrega_nombre'] ?? '');
$lines[] = 'Recibe:  ' . ($c['recibe_nombre'] ?? 'Luis');
$lines[] = 'Hora:    ' . substr((string)$c['hora_entrega'], 0, 5);
if (trim((string)$c['observaciones']) !== '') {
    $lines[] = str_repeat('-', 32);
    $lines[] = 'Obs: ' . trim((string)$c['observaciones']);
}
$lines[] = str_repeat('-', 32);
$lines[] = 'Firma entrega:';
$lines[] = '';
$lines[] = '____________________________';
$lines[] = '';
$lines[] = 'Firma recibe:';
$lines[] = '';
$lines[] = '____________________________';
$lines[] = '';
$ticketText = corte_clean_line(implode("\n", $lines) . "\n\n\n");
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div><h1 class="h3 mb-0">Ticket de corte</h1><div class="text-muted">Corte <?= h(date_mx($c['fecha_corte'])) ?></div></div>
  <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="<?= url('corte_diario.php?fecha='.$c['fecha_corte']) ?>">Volver al corte</a><button class="btn btn-outline-dark" onclick="window.print()">Imprimir con navegador</button><button class="btn btn-primary" id="btnCorteQz">Imprimir ticket térmico</button></div>
</div>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="ticket58 mx-auto"><pre id="corteTicketText" style="white-space:pre-wrap;margin:0;font-family:'Courier New',monospace;font-size:11px;line-height:1.25;"><?= h($ticketText) ?></pre></div>
  </div>
  <div class="col-lg-8 no-print">
    <div class="card"><div class="card-header">Datos enviados a impresora térmica</div><div class="card-body"><textarea class="form-control" rows="24" readonly><?= h($ticketText) ?></textarea><p class="text-muted small mt-3 mb-0">Este ticket se imprime por QZ Tray en texto RAW para que sea más nítido que la impresión del navegador.</p></div></div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>
<script>
window.CORTE_TICKET_TEXT = <?= json_encode($ticketText, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= url('assets/js/qz-corte.js') ?>"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
