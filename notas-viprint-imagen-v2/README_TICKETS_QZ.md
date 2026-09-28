# Actualización: Tickets 58mm + cajón RJ11 con QZ Tray

Esta actualización agrega impresión de tickets térmicos de 58mm y apertura de cajón registradora usando QZ Tray.

## Configuración confirmada

- Impresora: NE 510 Nextep
- Nombre en Windows: `58mm Series Printer`
- Conexión: USB
- Cajón: conectado a impresora por RJ11/RJ12
- Comando de cajón: `27,112,48,55,121`
- El cajón se abre en todos los pagos: anticipo, abono, liquidación, devolución y venta completa.

## Archivos incluidos

Subir/reemplazar estos archivos en la carpeta actual del sistema V2:

- `includes/bootstrap.php`
- `includes/header.php`
- `login.php`
- `index.php`
- `pago_guardar.php`
- `ticket_pago.php`
- `tickets.php`
- `caja_abrir.php`
- `caja_aperturas.php`
- `caja_apertura_guardar.php`
- `assets/js/qz-viprint.js`
- `assets/css/styles.css`
- `database/update_tickets_cajon_v2.sql`

## Base de datos

Ejecutar en phpMyAdmin, en la misma base de datos del sistema V2:

```sql
source database/update_tickets_cajon_v2.sql
```

Si usas phpMyAdmin, simplemente importa el archivo `database/update_tickets_cajon_v2.sql`.

No borra registros existentes. Solo crea la tabla:

- `v2_caja_aperturas`

## Instalar QZ Tray en Windows

1. Instala QZ Tray en la computadora del mostrador.
2. Deja QZ Tray abierto en segundo plano.
3. Confirma que Windows tenga la impresora con el nombre exacto: `58mm Series Printer`.
4. Entra al sistema y abre `caja_abrir.php`.
5. Presiona `Conectar QZ Tray`.
6. Acepta los permisos que pida QZ Tray.
7. Haz prueba con `Imprimir ticket de prueba y abrir cajón`.

## Uso operativo

Cuando se registra un pago desde una nota:

1. El sistema guarda el pago.
2. Registra el movimiento de caja.
3. Redirige a `ticket_pago.php`.
4. En esa pantalla presiona `Imprimir ticket y abrir cajón`.
5. El ticket sale por la impresora térmica y el cajón abre.
6. La apertura queda registrada en `v2_caja_aperturas`.

Para aperturas manuales:

- Ir a `Caja > Abrir cajón`.
- Escribir motivo.
- Presionar `Abrir cajón y registrar`.

Para ver aperturas:

- Ir a `caja_aperturas.php`.

## Nota sobre impresión silenciosa

Con QZ Tray gratuito pueden aparecer permisos o advertencias de seguridad. Para impresión completamente silenciosa se requieren certificados de firma configurados para el sitio.
