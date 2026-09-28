# Actualización: modificación de pagos por admin

Agrega a la pantalla `Correcciones admin` la opción de modificar pagos ya registrados.

## Qué permite modificar

Solo el usuario con rol `admin` puede modificar:

- Fecha del pago.
- Concepto: anticipo, abono, liquidación o devolución.
- Método de pago: efectivo, transferencia, tarjeta u otro.
- Monto.
- Referencia.
- Comprobante.
- Observaciones.

Cada cambio pide motivo obligatorio y queda registrado en `v2_auditoria_admin`.

## Qué corrige automáticamente

Al modificar un pago:

- Recalcula el saldo de la nota.
- Actualiza el movimiento de caja relacionado.
- Mueve el pago al método correcto, por ejemplo de tarjeta a efectivo.
- Deja historial con datos anteriores y nuevos.

## Protección de corte diario

Si el pago pertenece a una fecha cuyo corte diario ya está cerrado, el sistema bloquea la modificación directa para no descuadrar un corte ya firmado.

## Archivos a subir

Sube estos archivos a `/public_html/notas-viprint-imagen-v2/`:

- `admin_correcciones.php`
- `pago_modificar.php`
- `pago_anular.php`
- `nota_eliminar.php`
- `instalar_admin_modificar_pagos_v2.php`

No reemplaces:

- `config/database.php`
- `ticket_pago.php`
- `assets/js/qz-viprint.js`
- archivos de corte diario

## Instalación

1. Haz respaldo de la base de datos y carpeta del sistema.
2. Sube los archivos.
3. Entra con usuario `admin`.
4. Abre:

`https://viprint.com.mx/notas-viprint-imagen-v2/instalar_admin_modificar_pagos_v2.php?clave=admin2026`

5. Cuando termine, elimina del servidor:

`instalar_admin_modificar_pagos_v2.php`

## Uso

1. Entra a `admin_correcciones.php`.
2. Busca la nota por folio o ID.
3. En el pago equivocado, presiona `Modificar`.
4. Corrige el método, monto o dato necesario.
5. Escribe motivo, por ejemplo: `Se capturó como tarjeta, pero realmente fue efectivo`.
6. Guarda.

El sistema actualizará el saldo y caja.
