/* ViPrint QZ Tray - ticket compacto con QR grande + consulta por folio/codigo.
   Impresora: 58mm Series Printer
   Cajon RJ11: 27,112,48,55,121
   Requiere permitir Acceso a red local en Chrome para viprint.com.mx.
*/
(function () {
  'use strict';

  const VIPRINT_QZ = {
    printerName: '58mm Series Printer',
    drawerCommand: [27, 112, 48, 55, 121],
    lastPrinter: null,

    isLoaded() {
      return typeof window.qz !== 'undefined';
    },

    async connect() {
      if (!this.isLoaded()) {
        throw new Error('No se cargo qz-tray.js. Revisa internet o el archivo de QZ Tray.');
      }
      if (!qz.websocket.isActive()) {
        await qz.websocket.connect({ retries: 3, delay: 1 });
      }
      return true;
    },

    async findPrinter() {
      await this.connect();
      if (this.lastPrinter) return this.lastPrinter;
      try {
        this.lastPrinter = await qz.printers.find(this.printerName);
      } catch (err) {
        this.lastPrinter = this.printerName;
      }
      return this.lastPrinter;
    },

    config(printer) {
      return qz.configs.create(printer, {
        encoding: 'CP850',
        spool: { size: 1 }
      });
    },

    chr(n) {
      return String.fromCharCode(n);
    },

    sanitize(value) {
      return String(value ?? '')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[–—]/g, '-')
        .replace(/[“”]/g, '"')
        .replace(/[‘’]/g, "'")
        .replace(/[^\x20-\x7E\n\r]/g, '');
    },

    line(char = '-') {
      return char.repeat(32) + '\n';
    },

    center(text) {
      text = this.sanitize(text).trim();
      if (text.length >= 32) return text.substring(0, 32) + '\n';
      const left = Math.floor((32 - text.length) / 2);
      return ' '.repeat(left) + text + '\n';
    },

    pair(left, right) {
      left = this.sanitize(left);
      right = this.sanitize(right);
      if (right.length > 15) right = right.substring(0, 15);
      const maxLeft = 32 - right.length - 1;
      if (left.length > maxLeft) left = left.substring(0, maxLeft);
      return left + ' '.repeat(Math.max(1, 32 - left.length - right.length)) + right + '\n';
    },

    wrap(text, width = 32) {
      text = this.sanitize(text).replace(/\s+/g, ' ').trim();
      if (!text) return '';
      const words = text.split(' ');
      let out = '';
      let line = '';
      for (const word of words) {
        if ((line + ' ' + word).trim().length > width) {
          out += line.trim() + '\n';
          line = word;
        } else {
          line += ' ' + word;
        }
      }
      if (line.trim()) out += line.trim() + '\n';
      return out;
    },

    money(n) {
      const value = Number(n || 0);
      return '$' + value.toFixed(2);
    },

    dateMx(date) {
      if (!date) return '';
      const parts = String(date).split('-');
      if (parts.length === 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
      return String(date);
    },

    buildTicket(data) {
      if (!data || typeof data !== 'object') {
        throw new Error('No llegaron los datos del ticket.');
      }

      const ESC = '\x1B';
      let out = '';
      out += ESC + '@';
      out += ESC + 'a' + '\x01';
      out += this.center(data.empresa || 'ViPrint Publicidad');
      if (data.linea_empresa_ticket) out += this.center(data.linea_empresa_ticket);
      out += this.center('TICKET DE PAGO');
      out += ESC + 'a' + '\x00';
      out += this.line('=');
      out += this.pair('No. nota:', data.folio || '');
      out += this.pair('Pago:', '#' + (data.pago_id || ''));
      out += this.pair('Fecha:', this.dateMx(data.fecha_pago));
      out += this.pair('Concepto:', data.concepto_label || data.concepto || '');
      out += this.pair('Metodo:', data.forma_pago_label || data.forma_pago || '');
      out += this.line('-');
      out += this.wrap('Cliente: ' + (data.cliente_nombre || ''));
      if (data.telefono) out += this.wrap('Tel: ' + data.telefono);
      out += this.line('-');
      out += this.pair('Recibido:', this.money(data.monto));
      out += this.pair('Total nota:', this.money(data.total));
      out += this.pair('Pagado:', this.money(data.pagado));
      out += this.pair('Saldo:', this.money(data.saldo));
      if (data.referencia) out += this.wrap('Ref: ' + data.referencia);

      // Consulta para cliente: QR grande + respaldo en texto.
      if (data.consulta_base_url && data.consulta_codigo) {
        out += this.line('=');
        out += ESC + 'a' + '\x01';
        out += this.center('CONSULTA TU PEDIDO');
        out += '\n';
        if (data.consulta_url) {
          out += this.qrRaw(data.consulta_url, 8, 50);
        }
        out += this.wrap(String(data.consulta_base_url).replace(/^https?:\/\//, '').replace(/\/$/, ''), 32);
        out += this.pair('Folio:', data.folio || '');
        out += this.pair('Codigo:', data.consulta_codigo || '');
        out += ESC + 'a' + '\x00';
      }

      out += this.line('=');
      out += this.center('Gracias por su pago');
      out += this.center(data.empresa || 'ViPrint Publicidad');
      if (data.linea_empresa_ticket) out += this.center(data.linea_empresa_ticket);
      out += '\n\n';
      return out;
    },

    // QR ESC/POS. size 8 es grande pero seguro para 58 mm.
    // errorLevel: 48=L, 49=M, 50=Q, 51=H.
    qrRaw(text, size = 8, errorLevel = 50) {
      text = this.sanitize(text).trim();
      if (!text) return '';
      size = Math.max(1, Math.min(9, Number(size || 8)));
      errorLevel = Number(errorLevel || 50);
      const ESC = '\x1B';
      const GS = '\x1D';
      const len = text.length + 3;
      const pL = len % 256;
      const pH = Math.floor(len / 256);
      let out = '';
      out += ESC + 'a' + '\x01';
      out += GS + '(k' + this.chr(4) + this.chr(0) + this.chr(49) + this.chr(65) + this.chr(50) + this.chr(0);
      out += GS + '(k' + this.chr(3) + this.chr(0) + this.chr(49) + this.chr(67) + this.chr(size);
      out += GS + '(k' + this.chr(3) + this.chr(0) + this.chr(49) + this.chr(69) + this.chr(errorLevel);
      out += GS + '(k' + this.chr(pL) + this.chr(pH) + this.chr(49) + this.chr(80) + this.chr(48) + text;
      out += GS + '(k' + this.chr(3) + this.chr(0) + this.chr(49) + this.chr(81) + this.chr(48);
      out += '\n';
      return out;
    },

    drawerRaw() {
      return String.fromCharCode.apply(null, this.drawerCommand);
    },

    async printRaw(raw) {
      if (!raw || !String(raw).trim()) {
        throw new Error('El ticket esta vacio. Revisa que el pago tenga datos.');
      }
      const printer = await this.findPrinter();
      const config = this.config(printer);
      return qz.print(config, [raw]);
    },

    async printTicket(ticketData, options = {}) {
      const raw = this.buildTicket(ticketData);
      await this.printRaw(raw);
      if (options.openDrawer) {
        await this.openDrawer();
      }
    },

    async printQrTest(url) {
      if (!url) throw new Error('No hay URL para probar el QR.');
      await this.printRaw(this.center('PRUEBA QR PEDIDO') + this.qrRaw(url, 8, 50) + this.wrap(url, 32));
    },

    async openDrawer() {
      const printer = await this.findPrinter();
      const config = this.config(printer);
      return qz.print(config, [this.drawerRaw()]);
    },

    async logDrawer(payload) {
      try {
        const form = new FormData();
        Object.keys(payload || {}).forEach((k) => form.append(k, payload[k] ?? ''));
        await fetch(VIPRINT_QZ_CONFIG.logUrl, {
          method: 'POST',
          body: form,
          credentials: 'same-origin'
        });
      } catch (err) {
        console.warn('No se pudo registrar la apertura del cajon:', err);
      }
    },

    setStatus(msg, type = 'secondary') {
      const el = document.querySelector('[data-qz-status]');
      if (!el) return;
      el.className = 'alert alert-' + type;
      el.textContent = msg;
    }
  };

  window.VIPRINT_QZ = VIPRINT_QZ;
})();
