# Actualización: corte diario de caja ViPrint / Imagen

Esta actualización agrega un módulo de corte diario conjunto para ViPrint e Imagen.

## Qué incluye

- Corte diario conjunto, no separado por empresa.
- Fondo base fijo sugerido de $800.
- Fondo inicial capturado manualmente cada día.
- Entradas por efectivo, transferencia, tarjeta y otro.
- Salidas manuales con conceptos:
  - Gasto
  - Uber / envío
  - Devolución
  - Entrega a Luis
  - Préstamo / cambio
  - Compra menor
  - Ajuste de caja
  - Otro
- Transferencia y tarjeta se reportan, pero no se consideran dinero físico entregado a Luis.
- Captura de efectivo contado físicamente.
- Diferencia entre caja esperada y efectivo contado.
- Entrega a Luis generada como salida de efectivo al cerrar corte.
- Firma de quien entrega y quien recibe.
- Observaciones generales.
- Ticket térmico de corte diario.
- Bloqueo de pagos y movimientos cuando el corte de esa fecha queda cerrado.
- Solo usuario con rol `admin` puede cerrar un corte con diferencia.

## Archivos a subir

Sube y reemplaza estos archivos en:

`/public_html/notas-viprint-imagen-v2/`

- `caja.php`
- `caja_movimiento_guardar.php`
- `corte_diario.php`
- `corte_guardar.php`
- `corte_ticket.php`
- `pago_guardar.php`
- `instalar_corte_diario_caja_v2.php`
- `assets/js/qz-corte.js`
- `includes/header.php`

También se incluye:

- `database/update_corte_diario_caja_v2.sql`

No reemplaces:

- `config/database.php`
- `assets/js/qz-viprint.js`
- `ticket_pago.php`

## Instalación recomendada

1. Haz respaldo de la carpeta del sistema y de la base de datos.
2. Sube los archivos anteriores.
3. Abre esta URL estando logueado:

`https://viprint.com.mx/notas-viprint-imagen-v2/instalar_corte_diario_caja_v2.php?clave=corte2026`

4. Verifica que diga “Actualización instalada”.
5. Elimina del servidor:

`instalar_corte_diario_caja_v2.php`

## Uso diario

1. Entra a `Caja`.
2. Selecciona la fecha.
3. Revisa entradas, salidas y pagos registrados.
4. Registra salidas manuales si faltan.
5. Entra a `Corte diario`.
6. Captura:
   - Fondo inicial
   - Fondo base
   - Efectivo contado
   - Entrega real a Luis
   - Entrega / recibe
   - Observaciones
7. Guarda borrador o cierra el corte.
8. Al cerrar, el sistema registra la salida “Entrega a Luis”.
9. Imprime el ticket de corte.

## Nota importante

No registres manualmente “Entrega a Luis” si la vas a capturar desde el cierre del corte, para no duplicarla.
