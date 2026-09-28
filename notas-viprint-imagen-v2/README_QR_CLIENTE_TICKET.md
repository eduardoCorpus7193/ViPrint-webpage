# Actualización: QR de consulta del cliente en ticket

Esta actualización agrega un QR en todos los tickets de pago. El QR lleva a una vista pública donde el cliente puede consultar:

- Folio de nota.
- Nombre del cliente.
- Artículos / promoción.
- Total.
- Pagado.
- Saldo pendiente exacto.
- Pagos realizados.
- Estado del pedido.
- Fecha prometida, si está capturada.

No muestra costos, ganancias, comisiones, mermas ni comentarios internos.

## 1. Archivos a subir dentro del sistema

Sube estos archivos a la carpeta actual del sistema:

- `ticket_pago.php`
- `consulta_pedido.php`
- `instalar_qr_cliente_v2.php`
- `assets/js/qz-viprint.js`

Ejemplo de destino:

- `/public_html/notas-viprint-imagen-v2/ticket_pago.php`
- `/public_html/notas-viprint-imagen-v2/consulta_pedido.php`
- `/public_html/notas-viprint-imagen-v2/instalar_qr_cliente_v2.php`
- `/public_html/notas-viprint-imagen-v2/assets/js/qz-viprint.js`

No reemplaces `config/database.php`.

## 2. URL corta /pedido

Para que funcione `https://viprint.com.mx/pedido/`, sube esta carpeta:

- `public_html/pedido/index.php`

Al destino real:

- `/public_html/pedido/index.php`

Ese archivo llama internamente a:

- `/public_html/notas-viprint-imagen-v2/consulta_pedido.php`

Si tu carpeta del sistema tiene otro nombre, edita la ruta dentro de `public_html/pedido/index.php`.

## 3. Instalar columnas y tokens

Después de subir archivos, entra con usuario administrador, Luis, Mafer o Eduardo y abre:

`https://viprint.com.mx/notas-viprint-imagen-v2/instalar_qr_cliente_v2.php?clave=qr2026`

Esto agrega a `v2_notas`:

- `public_token`
- `mostrar_cliente`

También genera códigos para notas existentes.

Cuando confirme que quedó instalado, elimina del servidor:

- `instalar_qr_cliente_v2.php`

## 4. Probar ticket

1. Abre una nota.
2. Registra un pago.
3. Ve al ticket.
4. Presiona `Ctrl + F5`.
5. Conecta QZ Tray.
6. Imprime el ticket.

Debe imprimir:

- Ticket compacto.
- QR de consulta.
- URL `viprint.com.mx/pedido`.
- Folio.
- Código.

## 5. Si el QR no se lee

Algunas impresoras térmicas de 58 mm no interpretan el comando QR ESC/POS. Si pasa eso, el ticket aún imprime URL, folio y código para que el cliente entre manualmente.

El enlace directo del QR usa el token seguro, por ejemplo:

`https://viprint.com.mx/pedido/?t=ABC234XYZ789`

La consulta manual puede hacerse entrando a:

`https://viprint.com.mx/pedido/`

Y capturando el folio y el código impresos.
