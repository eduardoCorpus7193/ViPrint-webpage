# Actualización: ticket compacto y permisos de cajón

## Archivos incluidos

Subir estos archivos respetando rutas:

- `ticket_pago.php`
- `assets/js/qz-viprint.js`
- `assets/css/styles.css`
- `includes/bootstrap.php`

## Qué cambia

1. El ticket térmico queda más compacto.
2. Se eliminan del ticket: promesa, contactado, vendedor/intermediario y diseñador.
3. Se agrega el campo `Número de nota`.
4. Se reduce el espacio y la cantidad de información enviada a la impresora.
5. Se ajusta `can_cash()` para que usuarios operativos/diseñadores autorizados puedan abrir el cajón.

## Permisos

Si no quieres reemplazar completo `includes/bootstrap.php`, solo cambia la función `can_cash()` por esta:

```php
function can_cash() {
    $u = current_user();
    if (!$u) return false;
    if (can_finance()) return true;
    return in_array($u['rol'], array('admin','direccion','administracion','operativo','asesor','disenador'), true);
}
function can_manual_drawer() {
    return can_cash();
}
```

Esto permite abrir caja a Luis, Mafer, Eduardo, Danae y Ángel si sus roles corresponden a dirección, administración, asesor, operativo o diseñador. Excluye usuarios externos.

## QZ Tray

QZ Tray no abre una ventana principal normal. Debe quedar abierto en segundo plano y mostrarse como ícono junto al reloj de Windows. Desde ahí se puede dar clic derecho para opciones.

## Logo en ticket

El ticket térmico de QZ imprime texto por ahora. El logo gráfico sí es posible, pero conviene usar un PNG negro/blanco optimizado para 58mm, de máximo 384px de ancho. Si se imprime el logo a color o muy grande, la térmica puede imprimirlo lento, oscuro o con mucho espacio.
