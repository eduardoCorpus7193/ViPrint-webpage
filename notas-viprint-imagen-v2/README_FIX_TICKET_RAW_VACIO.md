# Fix ViPrint - ticket RAW vacío

Este ajuste corrige el caso donde el ticket de prueba imprime bien, pero el ticket real muestra:

`El texto RAW del ticket está vacío. No se mandó nada a imprimir.`

## Archivos a reemplazar

Subir y reemplazar en la carpeta actual del sistema:

- `ticket_pago.php`
- `assets/js/qz-viprint.js`

No ejecutar SQL. No tocar `config/database.php`.

## Después de subir

1. Abrir el ticket de una nota.
2. Presionar `Ctrl + F5`.
3. Revisar que en la pantalla aparezca el recuadro `Vista previa del texto enviado a la térmica` con datos.
4. Presionar `Conectar QZ Tray`.
5. Presionar `Imprimir ticket y abrir cajón`.

El fix manda el ticket como texto generado por PHP en Base64 y además tiene respaldo para generarlo desde JavaScript usando los datos del pago.
