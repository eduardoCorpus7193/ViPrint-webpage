# Sistema de Cotizaciones ViPrint

Sistema web sencillo para crear cotizaciones formales para clientes de ViPrint Publicidad.

## Funciones

- Crear cotizaciones con folio automático `COT-AÑO-00001`.
- Elegir fecha de cotización.
- Capturar datos del cliente: nombre, negocio, teléfono, correo y domicilio.
- Usar promociones registradas o escribir artículos libres manualmente.
- Registrar nuevas promociones y modificar precios.
- Agregar varias partidas por cotización.
- Calcular subtotal, IVA opcional y total.
- Guardar condiciones comerciales y observaciones.
- Estatus: borrador, enviada, aprobada, rechazada y cancelada.
- Vista formal para imprimir o guardar como PDF desde el navegador.
- Descarga directa de PDF básico generado por PHP, sin Composer.
- Diseño responsivo para computadora y celular.
- Identidad visual ViPrint: rojo `#A92624` y fondo `#F8F6F8`.

## Instalación en XAMPP

1. Copia la carpeta `cotizaciones-viprint` dentro de:

```text
C:\xampp\htdocs\
```

2. Abre phpMyAdmin:

```text
http://localhost/phpmyadmin
```

3. Importa el archivo:

```text
cotizaciones-viprint/database/schema.sql
```

4. Verifica la conexión en:

```text
cotizaciones-viprint/config/database.php
```

Configuración inicial:

```php
$host = '127.0.0.1';
$db   = 'cotizaciones_viprint';
$user = 'root';
$pass = '';
```

5. Abre el sistema:

```text
http://localhost/cotizaciones-viprint/
```

6. Diagnóstico:

```text
http://localhost/cotizaciones-viprint/diagnostico.php
```

## Instalación en hosting

1. Sube la carpeta a `public_html/cotizaciones-viprint/`.
2. Crea una base de datos desde el panel del hosting.
3. Crea un usuario MySQL y asígnalo a la base con todos los privilegios.
4. En phpMyAdmin, selecciona la base creada e importa el SQL.
   - Si el hosting no permite `CREATE DATABASE`, elimina las primeras dos líneas del SQL:

```sql
CREATE DATABASE IF NOT EXISTS cotizaciones_viprint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cotizaciones_viprint;
```

5. Ajusta `config/database.php` con el nombre real de la base, usuario, contraseña y host.
6. Ajusta `config/app.php` si cambiaste el nombre de la carpeta:

```php
define('BASE_URL', '/cotizaciones-viprint');
```

## Flujo recomendado

1. Registra o actualiza promociones en `Promociones`.
2. Crea una nueva cotización.
3. Elige promociones o escribe artículos libres.
4. Verifica subtotal, IVA y total.
5. Guarda la cotización.
6. Usa `Ver formato` para imprimir o guardar como PDF.
7. Usa `Descargar PDF` para generar un PDF directamente desde PHP.

## Notas

- El PDF directo usa un generador simple incluido en el proyecto. Para diseños más complejos, la vista de impresión del navegador suele verse mejor.
- Cambia las condiciones comerciales por defecto si ViPrint maneja tiempos, anticipos o instalación distintos.
- El sistema no sustituye CFDI ni facturación fiscal; es una herramienta comercial para cotizaciones.
