<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

function viprint_codigo_cliente_generate($length = 4) {
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        try {
            $code .= (string)random_int(0, 9);
        } catch (Exception $e) {
            $code .= (string)mt_rand(0, 9);
        }
    }
    return $code;
}

function viprint_ensure_public_code($nota_id, $current = '') {
    if (!column_exists('v2_notas', 'public_code')) {
        return '';
    }
    $current = trim((string)$current);
    if ($current !== '') {
        return $current;
    }
    $code = viprint_codigo_cliente_generate(4);
    $stmt = db()->prepare('UPDATE v2_notas SET public_code = ? WHERE id = ?');
    $stmt->execute(array($code, (int)$nota_id));
    return $code;
}

function viprint_public_base_url() {
    if (defined('PUBLIC_ORDER_URL') && PUBLIC_ORDER_URL) {
        return rtrim(PUBLIC_ORDER_URL, '/') . '/';
    }
    return 'https://viprint.com.mx/pedido/';
}

function viprint_public_url($folio, $code) {
    if (!$folio || !$code) return '';
    return viprint_public_base_url() . '?folio=' . rawurlencode($folio) . '&codigo=' . rawurlencode($code);
}

$pago_id = (int)($_GET['pago_id'] ?? 0);
if ($pago_id <= 0) {
    flash('danger', 'Pago no válido. Primero registra o selecciona un pago para imprimir su ticket.');
    redirect_to('notas.php');
}

$hasPublicCode = column_exists('v2_notas', 'public_code');
$hasMostrarCliente = column_exists('v2_notas', 'mostrar_cliente');
$publicSelect = '';
if ($hasPublicCode) $publicSelect .= ', n.public_code';
if ($hasMostrarCliente) $publicSelect .= ', n.mostrar_cliente';

$sql = "SELECT p.*, n.folio, n.cliente_nombre, n.negocio, n.telefono, n.total, n.pagado, n.saldo, n.fecha_nota" . $publicSelect . ", e.nombre empresa, e.clave empresa_clave, u.nombre usuario_registra
        FROM v2_pagos p
        JOIN v2_notas n ON n.id=p.nota_id
        JOIN v2_empresas e ON e.id=p.empresa_id
        LEFT JOIN v2_usuarios u ON u.id=p.usuario_id
        WHERE p.id=?";
$stmt = db()->prepare($sql);
$stmt->execute(array($pago_id));
$p = $stmt->fetch();
if (!$p) {
    flash('danger', 'Pago no encontrado.');
    redirect_to('notas.php');
}

if (function_exists('role_in') && role_in(array('disenador','externo')) && (int)($p['usuario_id'] ?? 0) !== (int)current_user()['id']) {
    $stmt = db()->prepare('SELECT disenador_id FROM v2_notas WHERE id=?');
    $stmt->execute(array($p['nota_id']));
    if ((int)$stmt->fetchColumn() !== (int)current_user()['id']) {
        flash('danger','No tienes permiso para ver este ticket.');
        redirect_to('mis_notas.php');
    }
}

$publicCode = '';
$publicUrl = '';
if ($hasPublicCode) {
    $publicCode = viprint_ensure_public_code((int)$p['nota_id'], $p['public_code'] ?? '');
    $publicUrl = viprint_public_url($p['folio'], $publicCode);
    if ($hasMostrarCliente && isset($p['mostrar_cliente']) && (int)$p['mostrar_cliente'] !== 1) {
        $stmt = db()->prepare('UPDATE v2_notas SET mostrar_cliente = 1 WHERE id = ?');
        $stmt->execute(array((int)$p['nota_id']));
    }
}

$conceptoLabels = array(
    'anticipo' => 'Anticipo',
    'abono' => 'Abono',
    'liquidacion' => 'Liquidacion',
    'devolucion' => 'Devolucion',
    'venta' => 'Venta'
);
$formaLabels = array(
    'efectivo' => 'Efectivo',
    'transferencia' => 'Transferencia',
    'tarjeta' => 'Tarjeta',
    'otro' => 'Otro'
);

$formaPagoLabel = ($p['forma_pago'] === 'otro' && !empty($p['forma_pago_otro']))
    ? $p['forma_pago_otro']
    : ($formaLabels[$p['forma_pago']] ?? $p['forma_pago']);

$empresaNombre = trim((string)($p['empresa'] ?? ''));
$empresaClave = strtolower(trim((string)($p['empresa_clave'] ?? '')));
$esImagen = ($empresaClave === 'imagen') || (stripos($empresaNombre, 'imagen') !== false);
$lineaEmpresaTicket = $esImagen ? 'IMAGEN' : '';

$ticketData = array(
    'empresa' => 'ViPrint Publicidad',
    'empresa_nombre_sistema' => $empresaNombre,
    'empresa_clave' => $empresaClave,
    'linea_empresa_ticket' => $lineaEmpresaTicket,
    'folio' => $p['folio'],
    'pago_id' => (int)$p['id'],
    'nota_id' => (int)$p['nota_id'],
    'empresa_id' => (int)$p['empresa_id'],
    'fecha_pago' => $p['fecha_pago'],
    'concepto' => $p['concepto'],
    'concepto_label' => $conceptoLabels[$p['concepto']] ?? $p['concepto'],
    'forma_pago' => $p['forma_pago'],
    'forma_pago_label' => $formaPagoLabel,
    'cliente_nombre' => $p['cliente_nombre'],
    'negocio' => $p['negocio'],
    'telefono' => $p['telefono'],
    'monto' => (float)$p['monto'],
    'total' => (float)$p['total'],
    'pagado' => (float)$p['pagado'],
    'saldo' => (float)$p['saldo'],
    'referencia' => $p['referencia'] ?? '',
    'consulta_url' => $publicUrl,
    'consulta_base_url' => rtrim(viprint_public_base_url(), '/'),
    'consulta_codigo' => $publicCode,
    'qr_enabled' => true
);

$auto = isset($_GET['auto']) && $_GET['auto'] === '1';
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div>
    <h1 class="h3 mb-0">Ticket de pago</h1>
    <div class="text-muted">Nota <?= h($p['folio']) ?> · Pago #<?= (int)$p['id'] ?></div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('nota_ver.php?id='.$p['nota_id']) ?>">Volver a nota</a>
    <button class="btn btn-outline-primary" onclick="window.print()">Imprimir con navegador</button>
  </div>
</div>

<?php if (!$hasPublicCode): ?>
<div class="alert alert-warning no-print">
  Para imprimir el código corto de consulta, primero ejecuta <strong>instalar_codigo_cliente_v2.php?clave=qr2026</strong>. El ticket de pago puede imprimir, pero todavía no tendrá consulta pública para el cliente.
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="ticket58 ticket58-compact print-area mx-auto">
      <div class="ticket-title">ViPrint Publicidad</div>
      <?php if($lineaEmpresaTicket): ?><div class="ticket-subtitle" style="font-weight:800; letter-spacing:1px;"><?= h($lineaEmpresaTicket) ?></div><?php endif; ?>
      <div class="ticket-subtitle">TICKET DE PAGO</div>
      <div class="ticket-line"></div>
      <div class="ticket-row"><span>No. nota</span><strong><?= h($p['folio']) ?></strong></div>
      <div class="ticket-row"><span>Pago</span><strong>#<?= (int)$p['id'] ?></strong></div>
      <div class="ticket-row"><span>Fecha</span><strong><?= date_mx($p['fecha_pago']) ?></strong></div>
      <div class="ticket-row"><span>Concepto</span><strong><?= h($ticketData['concepto_label']) ?></strong></div>
      <div class="ticket-row"><span>Método</span><strong><?= h($ticketData['forma_pago_label']) ?></strong></div>
      <div class="ticket-line"></div>
      <div class="ticket-text"><strong>Cliente:</strong> <?= h($p['cliente_nombre']) ?></div>
      <?php if(!empty($p['telefono'])): ?><div class="ticket-text"><strong>Tel:</strong> <?= h($p['telefono']) ?></div><?php endif; ?>
      <div class="ticket-line"></div>
      <div class="ticket-row ticket-total"><span>Recibido</span><strong><?= money($p['monto']) ?></strong></div>
      <div class="ticket-row"><span>Total nota</span><strong><?= money($p['total']) ?></strong></div>
      <div class="ticket-row"><span>Pagado</span><strong><?= money($p['pagado']) ?></strong></div>
      <div class="ticket-row"><span>Saldo</span><strong><?= money($p['saldo']) ?></strong></div>
      <?php if(!empty($p['referencia'])): ?><div class="ticket-text small"><strong>Ref:</strong> <?= h($p['referencia']) ?></div><?php endif; ?>
      <?php if($publicCode): ?>
        <div class="ticket-line"></div>
        <div class="ticket-footer">Consulta tu pedido</div>
        <div class="ticket-text small text-center"><?= h(rtrim(viprint_public_base_url(), '/')) ?></div>
        <div class="ticket-row"><span>Folio</span><strong><?= h($p['folio']) ?></strong></div>
        <div class="ticket-row"><span>Código</span><strong><?= h($publicCode) ?></strong></div>
      <?php endif; ?>
      <div class="ticket-line"></div>
      <div class="ticket-footer">Gracias por su pago<br>ViPrint Publicidad<?php if($lineaEmpresaTicket): ?><br><?= h($lineaEmpresaTicket) ?><?php endif; ?></div>
    </div>
  </div>

  <div class="col-lg-7 no-print">
    <div class="card mb-3">
      <div class="card-header">Impresora térmica y cajón</div>
      <div class="card-body">
        <div data-qz-status class="alert alert-secondary">QZ Tray debe estar abierto. Impresora esperada: 58mm Series Printer.</div>
        <div class="d-grid gap-2 d-md-flex">
          <button class="btn btn-outline-primary" id="btnQzConectar">Conectar QZ Tray</button>
          <button class="btn btn-primary" id="btnTicketQz">Imprimir ticket y abrir cajón</button>
          <button class="btn btn-outline-danger" id="btnAbrirCajon">Solo abrir cajón</button>
          <?php if($publicUrl): ?><button class="btn btn-outline-secondary" id="btnPruebaQr">Probar QR</button><?php endif; ?>
        </div>
        <p class="small text-muted mt-3 mb-0">El ticket imprimirá el QR grande, enlace, folio y código corto. Si QZ no conecta, revisa que Chrome tenga permitido el acceso a red local para viprint.com.mx.</p>
      </div>
    </div>
    <?php if($publicUrl): ?>
    <div class="card mb-3">
      <div class="card-header">Consulta pública para el cliente</div>
      <div class="card-body small">
        <div><strong>Enlace:</strong> <a href="<?= h($publicUrl) ?>" target="_blank" rel="noopener"><?= h($publicUrl) ?></a></div>
        <div><strong>Folio:</strong> <?= h($p['folio']) ?></div>
        <div><strong>Código corto:</strong> <?= h($publicCode) ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
window.VIPRINT_TICKET_DATA = <?= json_encode($ticketData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
window.VIPRINT_QZ_CONFIG = {
  logUrl: <?= json_encode(url('caja_apertura_guardar.php')) ?>,
  auto: <?= $auto ? 'true' : 'false' ?>
};
</script>
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>
<script src="<?= url('assets/js/qz-viprint.js?v=marca-imagen-1') ?>"></script>
<script>
(function(){
  async function connect(){
    try { await VIPRINT_QZ.connect(); VIPRINT_QZ.setStatus('QZ Tray conectado correctamente.', 'success'); }
    catch(e){ VIPRINT_QZ.setStatus(e.message || String(e), 'danger'); }
  }
  async function printAndOpen(){
    try {
      VIPRINT_QZ.setStatus('Enviando ticket a la impresora...', 'info');
      await VIPRINT_QZ.printTicket(window.VIPRINT_TICKET_DATA, {openDrawer: true});
      await VIPRINT_QZ.logDrawer({empresa_id: VIPRINT_TICKET_DATA.empresa_id, nota_id: VIPRINT_TICKET_DATA.nota_id, pago_id: VIPRINT_TICKET_DATA.pago_id, tipo: 'pago', motivo: 'Apertura por pago ' + VIPRINT_TICKET_DATA.concepto_label, impresora: VIPRINT_QZ.printerName, comando: VIPRINT_QZ.drawerCommand.join(',')});
      VIPRINT_QZ.setStatus('Ticket impreso y cajón abierto. Apertura registrada.', 'success');
    } catch(e){ VIPRINT_QZ.setStatus(e.message || String(e), 'danger'); }
  }
  async function openOnly(){
    try {
      VIPRINT_QZ.setStatus('Abriendo cajón...', 'info');
      await VIPRINT_QZ.openDrawer();
      await VIPRINT_QZ.logDrawer({empresa_id: VIPRINT_TICKET_DATA.empresa_id, nota_id: VIPRINT_TICKET_DATA.nota_id, pago_id: VIPRINT_TICKET_DATA.pago_id, tipo: 'pago', motivo: 'Apertura manual desde ticket de pago', impresora: VIPRINT_QZ.printerName, comando: VIPRINT_QZ.drawerCommand.join(',')});
      VIPRINT_QZ.setStatus('Cajón abierto y registrado.', 'success');
    } catch(e){ VIPRINT_QZ.setStatus(e.message || String(e), 'danger'); }
  }
  async function pruebaQr(){
    try {
      VIPRINT_QZ.setStatus('Probando QR ESC/POS...', 'info');
      await VIPRINT_QZ.printQrTest(window.VIPRINT_TICKET_DATA.consulta_url);
      VIPRINT_QZ.setStatus('Prueba de QR enviada correctamente.', 'success');
    } catch(e){ VIPRINT_QZ.setStatus(e.message || String(e), 'danger'); }
  }
  document.getElementById('btnQzConectar').addEventListener('click', connect);
  document.getElementById('btnTicketQz').addEventListener('click', printAndOpen);
  document.getElementById('btnAbrirCajon').addEventListener('click', openOnly);
  var btnQr = document.getElementById('btnPruebaQr');
  if (btnQr) btnQr.addEventListener('click', pruebaQr);
  if (window.VIPRINT_QZ_CONFIG.auto) {
    setTimeout(function(){ VIPRINT_QZ.setStatus('Pago registrado. Presiona Imprimir ticket y abrir cajón.', 'warning'); }, 250);
  }
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
