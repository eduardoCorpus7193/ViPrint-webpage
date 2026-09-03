# Actualización ViPrint - Ticket con QR grande

Esta actualización reemplaza solo los archivos necesarios para que el ticket térmico imprima el QR grande dentro del ticket principal.

## Archivos a subir

Sube y reemplaza estos archivos en:

`/public_html/notas-viprint-imagen-v2/`

- `ticket_pago.php`
- `assets/js/qz-viprint.js`

No ejecutes SQL.
No reemplaces `config/database.php`.
No borres otros archivos del sistema.

## Qué cambia

- El ticket compacto ahora incluye QR grande.
- El QR apunta a la consulta pública del pedido.
- Se mantiene el respaldo en texto:
  - `viprint.com.mx/pedido`
  - Folio
  - Código corto
- Se mantiene apertura de cajón.
- Se mantiene el botón de prueba de QR.

## Importante en Chrome

En la computadora del mostrador, Chrome debe tener permitido el acceso a red local para `viprint.com.mx`:

Configuración del sitio > Acceso a red local > Permitir

Después de subir los archivos, abre el ticket y presiona `Ctrl + F5` para limpiar caché.

## Prueba recomendada

1. Abre un ticket de pago existente.
2. Presiona `Ctrl + F5`.
3. Presiona `Conectar QZ Tray`.
4. Presiona `Probar QR`.
5. Presiona `Imprimir ticket y abrir cajón`.

## Ajuste de tamaño del QR

El tamaño quedó en 8 dentro de `assets/js/qz-viprint.js`:

`this.qrRaw(data.consulta_url, 8, 50);`

Si lo quieres todavía más grande, cambia `8` por `9`.
No recomiendo pasar de 9 en impresora de 58 mm.
