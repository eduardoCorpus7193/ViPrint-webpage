# Actualización: Mis notas con negocio y productos

## Qué cambia

- En la pantalla **Notas / Mis notas** ahora se muestra primero el campo **Negocio**.
- Si la nota no tiene negocio capturado, se muestra el nombre del cliente.
- Se agrega una columna **Productos / promociones** para ubicar más rápido qué lleva cada nota.
- Se quitan de esta vista los estados de **Aprobación de impresión**, **Producción** y **Pago**.
- Se conserva un seguimiento más limpio con: contacto, diseño y entrega.
- También se puede buscar por producto o promoción.

## Archivo que se debe subir

Subir y reemplazar en:

`/public_html/notas-viprint-imagen-v2/`

Archivo:

`notas.php`

## Importante

No ejecutar SQL.
No tocar `config/database.php`.
No reemplazar tickets, QR, caja, reportes ni facturación.

Después de subir el archivo, abrir el sistema y presionar `Ctrl + F5`.
