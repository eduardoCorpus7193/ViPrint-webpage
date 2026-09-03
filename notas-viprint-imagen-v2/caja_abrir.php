<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!can_cash()) { flash('danger','No tienes permiso para abrir caja.'); redirect_to('index.php'); }
$empresas = empresas();
$empresa_id = (int)($_GET['empresa_id'] ?? ($empresas[0]['id'] ?? 0));
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div><h1 class="h3 mb-0">Abrir cajón</h1><div class="text-muted">Apertura manual, prueba de impresora y registro de aperturas.</div></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="<?= url('caja.php') ?>">Volver a caja</a><a class="btn btn-outline-secondary" href="<?= url('caja_aperturas.php') ?>">Ver aperturas</a></div>
</div>
<?php if(!table_exists('v2_caja_aperturas')): ?>
<div class="alert alert-warning"><strong>Falta instalar la actualización de tickets y cajón.</strong><br>Ejecuta en phpMyAdmin el archivo <code>database/update_tickets_cajon_v2.sql</code>. No borra registros.</div>
<?php endif; ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card"><div class="card-header">Apertura manual</div><div class="card-body">
      <div data-qz-status class="alert alert-secondary">Instala y abre QZ Tray en la computadora del mostrador. Impresora esperada: 58mm Series Printer.</div>
      <label class="form-label">Empresa</label>
      <select id="empresa_id" class="form-select mb-3">
        <?php foreach($empresas as $e): ?><option value="<?= (int)$e['id'] ?>" <?= $empresa_id===(int)$e['id']?'selected':'' ?>><?= h($e['nombre']) ?></option><?php endforeach; ?>
      </select>
      <label class="form-label">Motivo</label>
      <input id="motivo" class="form-control mb-3" value="Apertura manual autorizada" placeholder="Ej. cambio, revisión, prueba, retiro autorizado">
      <div class="d-grid gap-2">
        <button class="btn btn-outline-primary" id="btnQzConectar">Conectar QZ Tray</button>
        <button class="btn btn-primary" id="btnAbrirCajon">Abrir cajón y registrar</button>
        <button class="btn btn-outline-secondary" id="btnTicketPrueba">Imprimir ticket de prueba y abrir cajón</button>
      </div>
    </div></div>
  </div>
  <div class="col-lg-7">
    <div class="card"><div class="card-header">Configuración actual</div><div class="card-body">
      <table class="table table-sm"><tbody>
        <tr><th>Impresora Windows</th><td><code>58mm Series Printer</code></td></tr>
        <tr><th>Conexión</th><td>USB</td></tr>
        <tr><th>Cajón</th><td>RJ11 conectado a la impresora</td></tr>
        <tr><th>Comando cajón</th><td><code>27,112,48,55,121</code></td></tr>
      </tbody></table>
      <p class="small text-muted mb-0">Si el cajón no abre, revisa que la impresora esté encendida, que QZ Tray esté abierto y que el nombre de la impresora en Windows sea exactamente <strong>58mm Series Printer</strong>.</p>
    </div></div>
  </div>
</div>
<script>
window.VIPRINT_QZ_CONFIG = { logUrl: <?= json_encode(url('caja_apertura_guardar.php')) ?> };
</script>
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>
<script src="<?= url('assets/js/qz-viprint.js') ?>"></script>
<script>
(function(){
  const empresa = () => document.getElementById('empresa_id').value;
  const motivo = () => document.getElementById('motivo').value || 'Apertura manual';
  async function connect(){
    try { await VIPRINT_QZ.connect(); VIPRINT_QZ.setStatus('QZ Tray conectado correctamente.', 'success'); }
    catch(e){ VIPRINT_QZ.setStatus(e.message || String(e), 'danger'); }
  }
  async function openDrawer(tipo){
    try {
      VIPRINT_QZ.setStatus('Abriendo cajón...', 'info');
      await VIPRINT_QZ.openDrawer();
      await VIPRINT_QZ.logDrawer({empresa_id: empresa(), tipo: tipo || 'manual', motivo: motivo(), impresora: VIPRINT_QZ.printerName, comando: VIPRINT_QZ.drawerCommand.join(',')});
      VIPRINT_QZ.setStatus('Cajón abierto y registrado.', 'success');
    } catch(e){ VIPRINT_QZ.setStatus(e.message || String(e), 'danger'); }
  }
  async function testTicket(){
    try {
      const data = {empresa:'ViPrint Publicidad', folio:'PRUEBA', pago_id:'TEST', fecha_pago:new Date().toISOString().slice(0,10), concepto_label:'Prueba', forma_pago_label:'Prueba', cliente_nombre:'Prueba de impresora', monto:0, total:0, pagado:0, saldo:0, partidas:[]};
      await VIPRINT_QZ.printTicket(data, {openDrawer: true});
      await VIPRINT_QZ.logDrawer({empresa_id: empresa(), tipo: 'prueba', motivo: motivo(), impresora: VIPRINT_QZ.printerName, comando: VIPRINT_QZ.drawerCommand.join(',')});
      VIPRINT_QZ.setStatus('Ticket de prueba impreso y cajón abierto.', 'success');
    } catch(e){ VIPRINT_QZ.setStatus(e.message || String(e), 'danger'); }
  }
  document.getElementById('btnQzConectar').addEventListener('click', connect);
  document.getElementById('btnAbrirCajon').addEventListener('click', function(){ openDrawer('manual'); });
  document.getElementById('btnTicketPrueba').addEventListener('click', testTicket);
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
