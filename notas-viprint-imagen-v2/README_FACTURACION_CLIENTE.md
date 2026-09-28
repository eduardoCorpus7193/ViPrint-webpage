# Actualización: Solicitud de factura para clientes

Esta actualización agrega un módulo para que los clientes suban sus datos de facturación y su Constancia de Situación Fiscal.

## Qué agrega

- Nueva pantalla interna: `facturas.php`.
- Nueva página pública: `https://viprint.com.mx/factura/`.
- El cliente sube datos fiscales usando folio y código del ticket.
- La opción de factura solo aparece cuando la nota está marcada como "requiere factura".
- Límite de 72 horas para mandar los datos.
- Danae, Mafer, Luis, Eduardo y admin pueden revisar solicitudes.
- Opción para subir PDF/XML de la factura ya emitida, pero no es obligatorio.
- El ticket físico imprime el aviso de facturación solo cuando la nota requiere factura.
- La consulta del pedido muestra el botón para subir datos de facturación solo cuando aplica.

## Archivos para subir al sistema

Sube estos archivos a:

`/public_html/notas-viprint-imagen-v2/`

- `facturas.php`
- `factura_ver.php`
- `factura_actualizar.php`
- `factura_archivo.php`
- `factura_requiere_guardar.php`
- `consulta_pedido.php`
- `ticket_pago.php`
- `instalar_facturacion_cliente_v2.php`
- `includes/facturacion_helpers.php`
- `assets/js/qz-viprint.js`

No reemplaces:

- `config/database.php`
- `config/app.php`
- archivos de corte diario
- archivos de reportes generales

## Archivo público

Crea la carpeta:

`/public_html/factura/`

Y sube ahí:

- `public_html/factura/index.php`

La ruta final debe quedar:

`/public_html/factura/index.php`

## Instalación

Después de subir archivos, abre esta URL con usuario autorizado:

`https://viprint.com.mx/notas-viprint-imagen-v2/instalar_facturacion_cliente_v2.php?clave=factura2026`

Al terminar, elimina del servidor:

`instalar_facturacion_cliente_v2.php`

## Uso interno

1. Entrar a `Facturas` en el menú.
2. Buscar la nota por folio, cliente o teléfono.
3. Marcar la nota como `requiere factura` cuando el cliente lo pidió.
4. El ticket imprimirá la liga `viprint.com.mx/factura`.
5. El cliente sube sus datos desde esa página.
6. Danae/Mafer/Luis/Eduardo/admin revisan la solicitud.
7. Cuando se facture en el SAT, pueden marcar la solicitud como `Facturada` y subir PDF/XML si lo desean.

## Nota sobre IVA

El sistema no suma IVA automáticamente en esta actualización. Cuando una nota requiere factura, el precio debe capturarse ya con IVA incluido.
