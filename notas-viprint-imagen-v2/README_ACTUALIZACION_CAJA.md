# Actualización V2: Caja y corte diario

Esta actualización agrega caja diaria y corte diario al sistema de notas V2.

## Qué agrega

- Menú nuevo: **Caja**.
- Registro automático en caja cuando se registra un pago, abono, liquidación o devolución.
- Migración de pagos ya existentes a movimientos de caja.
- Registro manual de movimientos de caja: gastos, retiros, ajustes u otros.
- Corte diario por empresa y fecha.
- Comparación entre total del sistema y total confirmado físicamente.
- Totales por método de pago: efectivo, transferencia, tarjeta y otro.
- Conserva todos los registros existentes.

## Instalación segura sin perder datos

1. Haz respaldo de la base de datos actual desde phpMyAdmin.
2. Sube/reescribe estos archivos en la carpeta del sistema V2:
   - `index.php`
   - `pago_guardar.php`
   - `caja.php`
   - `caja_movimiento_guardar.php`
   - `corte_diario.php`
   - `corte_guardar.php`
   - `includes/bootstrap.php`
   - `includes/header.php`
3. En phpMyAdmin, selecciona la misma base de datos del sistema V2.
4. Importa el archivo:

```text
notas-viprint-imagen-v2/database/update_caja_v2.sql
```

Ese archivo **no borra tablas ni notas**. Solo crea `v2_caja_movimientos`, `v2_cortes_diarios` y copia los pagos existentes a caja.

5. Entra a:

```text
https://viprint.com.mx/notas-viprint-imagen-v2/caja.php
```

## Sobre la pantalla principal

Antes el panel principal mostraba solo algunas notas porque tenía límite de 12 y además filtraba automáticamente por el mes actual. Ahora:

- No filtra por fecha a menos que tú pongas Desde/Hasta.
- Muestra las últimas 50 notas.
- Tiene botón **Ver todas** para buscar cualquier registro desde `notas.php`.

## Recomendación operativa

- Danae, Mafer, Ángel o Luis pueden seguir registrando pagos desde la nota.
- Cada pago se pasará automáticamente a caja.
- Las salidas de dinero, retiros o gastos deben registrarse manualmente desde Caja.
- El corte diario conviene hacerlo al cierre de cada día por empresa.
- Si existe diferencia, debe quedar en observaciones.
