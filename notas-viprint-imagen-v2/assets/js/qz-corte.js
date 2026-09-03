(function(){
  'use strict';
  const printerName = '58mm Series Printer';
  const statusText = msg => {
    let el = document.getElementById('qzCorteStatus');
    if (!el) {
      el = document.createElement('div');
      el.id = 'qzCorteStatus';
      el.className = 'alert alert-info mt-3 no-print';
      const btn = document.getElementById('btnCorteQz');
      if (btn && btn.parentNode) btn.parentNode.parentNode.appendChild(el);
    }
    el.textContent = msg;
  };
  function sanitize(value){
    return String(value || '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[–—]/g, '-')
      .replace(/[“”]/g, '"')
      .replace(/[‘’]/g, "'")
      .replace(/[^\x20-\x7E\n\r]/g, '');
  }
  async function connectQz(){
    if (typeof qz === 'undefined') throw new Error('No se cargo qz-tray.js.');
    if (!qz.websocket.isActive()) await qz.websocket.connect({retries:3, delay:1});
  }
  async function printCorte(){
    const text = sanitize(window.CORTE_TICKET_TEXT || (document.getElementById('corteTicketText')?.innerText || ''));
    if (!text.trim()) throw new Error('El ticket de corte esta vacio.');
    statusText('Conectando con QZ Tray...');
    await connectQz();
    statusText('Buscando impresora ' + printerName + '...');
    let printer;
    try { printer = await qz.printers.find(printerName); } catch(e) { printer = printerName; }
    const config = qz.configs.create(printer, { encoding: 'CP850', spool: { size: 1 } });
    statusText('Enviando ticket de corte...');
    await qz.print(config, [{ type: 'raw', format: 'plain', data: text }]);
    statusText('Ticket de corte enviado a la impresora.');
  }
  document.addEventListener('DOMContentLoaded', function(){
    const btn = document.getElementById('btnCorteQz');
    if (btn) btn.addEventListener('click', function(){
      printCorte().catch(function(err){
        console.error(err);
        alert('No se pudo imprimir con QZ Tray: ' + (err && err.message ? err.message : err));
        statusText('Error: ' + (err && err.message ? err.message : err));
      });
    });
  });
})();
