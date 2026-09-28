# Actualización módulos 2, 3 y 10 - Sistema Notas V2

Esta actualización agrega tres módulos separados:

1. Corte diario de caja mejorado
2. Estado de pedidos más claro
3. Reportes generales

## Archivos incluidos

Subir y reemplazar en `/public_html/notas-viprint-imagen-v2/`:

- `includes/header.php`
- `includes/modulos_2_3_10_helpers.php`
- `corte_diario.php`
- `corte_guardar.php`
- `corte_ticket.php`
- `corte_reabrir.php`
- `cortes_historial.php`
- `estado_pedidos.php`
- `reportes_generales.php`
- `assets/js/qz-corte.js`
- `instalar_modulos_2_3_10_v2.php`

Archivo de referencia SQL:

- `database/update_modulos_2_3_10_v2.sql`

## No reemplazar

No reemplazar estos archivos:

- `config/database.php`
- `config/app.php`
- `ticket_pago.php`
- `assets/js/qz-viprint.js`
- `consulta_pedido.php`

Así no se toca lo que ya funciona del ticket, QR y consulta del cliente.

## Instalación

1. Haz respaldo de la carpeta actual del sistema y de la base de datos.
2. Sube los archivos incluidos a la raíz del sistema.
3. Entra con usuario admin, Luis, Mafer o Eduardo.
4. Abre:

`https://viprint.com.mx/notas-viprint-imagen-v2/instalar_modulos_2_3_10_v2.php?clave=modulos2310`

5. Verifica que diga actualización instalada.
6. Elimina del servidor:

`instalar_modulos_2_3_10_v2.php`

## Permisos

- Corte diario: Mafer, Danae, Eduardo, Luis y admin.
- Cerrar corte con diferencia: solo admin.
- Reabrir corte: solo admin.
- Reportes generales: Luis, Mafer, Eduardo y admin.
- Estado de pedidos: usuarios con sesión. Los diseñadores siguen limitados a sus notas si el sistema ya los limita así.

## Uso básico

### Corte diario

Entrar a `Corte diario`, seleccionar fecha, revisar entradas y salidas, capturar fondo inicial, efectivo contado y entrega a Luis. Se puede guardar borrador o cerrar el corte. Al cerrar, se puede imprimir ticket térmico del corte.

### Estado pedidos

Entrar a `Estado pedidos`. Ahí aparecen pedidos abiertos, atrasados, en costura, terminados, con saldo, sin diseñador o con cliente que no contesta.

### Reportes generales

Entrar a `Reportes generales`, elegir rango de fechas y empresa. Muestra ventas, cobros, saldos, métodos de pago, producción, productos más vendidos y clientes con saldo.
