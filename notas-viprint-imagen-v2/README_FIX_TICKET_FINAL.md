# Fix final para ticket de nota vacío

Reemplaza solamente estos archivos en `public_html/notas-viprint-imagen-v2/`:

- `ticket_pago.php`
- `assets/js/qz-viprint.js`

Agrega este archivo de diagnóstico opcional en la raíz:

- `ticket_debug.php`

Después abre el ticket y presiona Ctrl + F5.

## Prueba

1. Abre un ticket de pago real.
2. Verifica que el recuadro "Vista previa del texto enviado a la térmica" tenga datos.
3. Presiona Conectar QZ Tray.
4. Presiona Imprimir ticket y abrir cajón.

Si la vista previa sigue vacía, abre:

`https://viprint.com.mx/notas-viprint-imagen-v2/ticket_debug.php?pago_id=ID_DEL_PAGO`

Cambia ID_DEL_PAGO por el número de pago que aparece en la URL del ticket.

No ejecutes SQL. No toques `config/database.php`.
