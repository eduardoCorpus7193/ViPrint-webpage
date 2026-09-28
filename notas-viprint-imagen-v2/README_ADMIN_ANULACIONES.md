# Actualización: correcciones de admin

Agrega una pantalla exclusiva para el usuario con rol `admin` para corregir errores de captura.

## Qué permite

- Buscar una nota por folio o ID.
- Ver pagos registrados en la nota.
- Anular pagos duplicados con motivo obligatorio.
- Revertir saldo y caja dejando el pago en monto 0.
- Marcar movimientos de caja relacionados como anulados y monto 0.
- Eliminar notas de forma lógica, no física.
- Al eliminar una nota, se anulan sus pagos y movimientos de caja relacionados.
- Se guarda historial en `v2_auditoria_admin`.

## Archivos a subir

Sube estos archivos a `/public_html/notas-viprint-imagen-v2/`:

- `admin_correcciones.php`
- `pago_anular.php`
- `nota_eliminar.php`
- `instalar_admin_anulaciones_v2.php`

También se incluye `database/update_admin_anulaciones_v2.sql` solo como referencia, pero lo recomendado es usar el instalador PHP.

No reemplaces:

- `config/database.php`
- `ticket_pago.php`
- `assets/js/qz-viprint.js`
- archivos de corte diario

## Instalación

1. Haz respaldo de base de datos y carpeta del sistema.
2. Sube los archivos.
3. Entra con el usuario `admin`.
4. Abre:

`https://viprint.com.mx/notas-viprint-imagen-v2/instalar_admin_anulaciones_v2.php?clave=admin2026`

5. Cuando diga que quedó instalado, elimina del servidor:

`instalar_admin_anulaciones_v2.php`

## Uso

Abre:

`https://viprint.com.mx/notas-viprint-imagen-v2/admin_correcciones.php`

Busca la nota por folio o ID. Para corregir un pago duplicado:

1. Ubica el pago duplicado.
2. Presiona Anular.
3. Escribe el motivo, por ejemplo: `Pago duplicado por error de captura`.
4. Confirma.

El sistema pondrá el pago y el movimiento de caja en cero, recalculará la nota y dejará historial.

## Nota importante

Esta actualización no borra físicamente pagos ni notas. Los anula o marca como eliminados para conservar evidencia de la corrección.
