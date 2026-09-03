# Fix ticket de nota en blanco

Este ajuste corrige el caso donde la prueba de texto imprime bien, pero el ticket real de una nota sale en blanco.

## Archivos a reemplazar

Sube estos archivos respetando las rutas:

- ticket_pago.php
- assets/js/qz-viprint.js

No ejecutes SQL y no toques config/database.php.

## Qué cambia

El ticket real ahora se genera desde PHP y se coloca en un textarea visible en la pantalla del ticket.
QZ Tray imprime exactamente ese texto, evitando problemas por JSON, datos vacíos o JavaScript anterior en caché.

Después de subir los archivos, abre el ticket y presiona Ctrl más F5 o usa una ventana incógnito.

## Cómo verificar

En la pantalla de ticket debe aparecer un recuadro llamado Vista previa del texto enviado a la térmica.
Si el recuadro tiene texto, ese es el contenido que se manda a QZ Tray.
