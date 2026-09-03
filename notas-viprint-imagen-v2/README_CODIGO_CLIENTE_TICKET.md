# Actualización ViPrint: ticket estable + consulta por folio y código corto

Esta versión corrige el problema causado por el QR RAW dentro del ticket.

## Qué cambia

- El ticket principal vuelve al método estable que ya imprimía correctamente con QZ Tray.
- Se elimina el QR gráfico del ticket principal para evitar errores como:
  - Unable to establish connection with QZ
  - Cannot read properties of undefined (reading '0')
- Se imprime una consulta sencilla para el cliente:
  - `viprint.com.mx/pedido`
  - Folio de la nota
  - Código corto de 4 dígitos
- La página pública permite consultar por folio + código.
- Se conserva un botón separado `Probar QR` para hacer pruebas ESC/POS sin romper el ticket principal.

## Archivos a subir a /public_html/notas-viprint-imagen-v2/

- `ticket_pago.php`
- `consulta_pedido.php`
- `instalar_codigo_cliente_v2.php`
- `assets/js/qz-viprint.js`

## Archivo para URL corta

Subir:

- `public_html/pedido/index.php`

A esta ruta del hosting:

- `/public_html/pedido/index.php`

Así funcionará:

- `https://viprint.com.mx/pedido/`

## Ejecutar instalador

Entrar como usuario con permiso financiero/admin y abrir:

`https://viprint.com.mx/notas-viprint-imagen-v2/instalar_codigo_cliente_v2.php?clave=qr2026`

El instalador agrega:

- `public_code`
- `mostrar_cliente`

Y genera códigos de 4 dígitos para notas existentes.

Después elimina:

- `instalar_codigo_cliente_v2.php`

## Prueba recomendada

1. Registra un pago en una nota.
2. Abre el ticket.
3. Presiona Ctrl + F5.
4. Presiona Conectar QZ Tray.
5. Presiona Imprimir ticket y abrir cajón.
6. Verifica que imprima:
   - Folio
   - Código corto
   - `viprint.com.mx/pedido`
7. Entra a `viprint.com.mx/pedido` y consulta con folio + código.

## Sobre el QR

La impresión de QR por comando RAW no es igual en todas las impresoras 58 mm. En la NE 510/driver actual rompió la impresión del ticket. Por eso el ticket principal queda estable con folio y código corto. El botón `Probar QR` sirve para hacer pruebas aisladas sin afectar el cobro.
