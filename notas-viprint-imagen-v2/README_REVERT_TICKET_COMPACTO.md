# Actualización: volver al ticket funcional original, pero compacto

Esta actualización regresa el ticket al método de la primera versión que sí imprimía:
PHP entrega los datos como JSON y JavaScript arma el texto RAW para QZ Tray.

## Archivos a reemplazar

Sube y reemplaza:

- ticket_pago.php
- assets/js/qz-viprint.js

## Archivo opcional de CSS

El archivo `assets/css/ticket_compacto.css` es opcional. Sirve para hacer más compacta la vista visual del ticket cuando imprimes con navegador.

Si quieres usarlo, súbelo a:

- assets/css/ticket_compacto.css

Y agrega esta línea en el head global o en includes/header.php:

```html
<link rel="stylesheet" href="<?= url('assets/css/ticket_compacto.css') ?>">
```

Si no quieres editar header.php, no lo subas. La impresión por QZ Tray no lo necesita.

## Qué se quitó del ticket

- Promesa
- Contactado
- Vendedor/intermediario
- Diseñador
- Detalle largo de partidas

## Qué se agregó

- No. nota

## Después de subir

Abre el ticket y presiona Ctrl F5 para forzar recarga de JavaScript.
