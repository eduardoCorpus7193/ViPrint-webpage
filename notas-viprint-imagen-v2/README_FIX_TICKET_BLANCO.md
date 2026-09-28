# Fix ticket en blanco ViPrint

Subir y reemplazar estos archivos en public_html/notas-viprint-imagen-v2/:

- ticket_pago.php
- assets/js/qz-viprint.js
- assets/css/styles.css

No ejecutar SQL.
No tocar config/database.php.

Después de subirlos:
1. Abrir la pantalla del ticket.
2. Presionar Ctrl + F5 o abrir en incógnito para evitar caché.
3. Conectar QZ Tray.
4. Probar primero el botón Prueba de texto.
5. Si imprime texto, probar Imprimir ticket y abrir cajón.

Esta versión usa texto plano ESC/POS sin imagen, sin ampliación de fuente y sin corte automático avanzado para evitar tickets en blanco en impresoras 58mm genéricas.
