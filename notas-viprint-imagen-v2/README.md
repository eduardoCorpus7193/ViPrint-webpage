# Sistema de Notas ViPrint / Imagen V2 estable con Caja

Esta versión incluye:

- Login con logo de ViPrint.
- Panel principal con logo de ViPrint.
- Notas ViPrint e Imagen en el mismo sistema.
- Filtros por empresa y búsqueda de notas.
- Catálogo de promociones/productos.
- Registro de partidas, pagos, mermas y comisiones.
- Caja diaria.
- Corte diario.
- Diagnóstico para evitar pantalla en blanco.

## Actualizar un sistema que ya tiene registros

1. Haz respaldo de la base de datos en phpMyAdmin.
2. Haz respaldo de la carpeta actual del sistema en el hosting.
3. Sube/reemplaza los archivos de este paquete en la misma carpeta del sistema.
4. Conserva tus credenciales reales en `config/database.php`.
5. Revisa `config/app.php` y confirma que `BASE_URL` coincida con la carpeta real.
6. Ejecuta en phpMyAdmin el archivo:

```text
database/update_caja_v2.sql
```

Ese archivo no borra notas, pagos ni usuarios. Solo crea:

```text
v2_caja_movimientos
v2_cortes_diarios
```

y copia los pagos existentes a caja sin duplicarlos.

7. Abre:

```text
https://viprint.com.mx/notas-viprint-imagen-v2/diagnostico.php
```

Debe mostrar que existen las tablas `v2_caja_movimientos` y `v2_cortes_diarios`.

8. Abre el sistema normalmente.

## Importante

No vuelvas a importar el archivo viejo `schema_v2(1).sql`, porque tenía una parte incorrecta en productos de Imagen. Si necesitas instalar desde cero, usa:

```text
database/schema_v2_CORREGIDO_FINAL.sql
```

## Si aparece pantalla en blanco

Esta versión muestra un mensaje de error visible. Revisa:

- `config/database.php`
- `config/app.php`
- que exista `v2_empresas`
- que exista `v2_usuarios`
- que exista `v2_notas`
- que ya se haya ejecutado `database/update_caja_v2.sql`

Cuando ya funcione, puedes cambiar en `config/app.php`:

```php
define('APP_DEBUG', false);
```
