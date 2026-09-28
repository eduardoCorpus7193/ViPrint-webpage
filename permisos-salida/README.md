# ViPrint · Permisos temporales de salida

Sistema sencillo en PHP, MySQL, Bootstrap, HTML, CSS y JavaScript para generar permisos internos de salida temporal.

## Funciones

- Catálogo editable de empleados.
- Empleados disponibles en un combo.
- Captura de fecha, hora de salida y regreso previsto.
- Registro posterior de la hora real de regreso.
- Motivo, destino, tratamiento del tiempo y observaciones.
- Estados: pendiente de firma, autorizado y cancelado.
- Folio automático.
- Historial con filtros.
- Edición, eliminación e impresión.
- Firma del empleado y autorización del patrón.
- Sin inicio de sesión.
- Diseño ViPrint con rojo `#A92624` y fondo `#F8F6F8`.

## Requisitos

- XAMPP con Apache y MySQL/MariaDB.
- PHP 8.1 o superior.
- Extensión PDO MySQL habilitada.

## Instalación

1. Copia la carpeta `permisos-salida` dentro de:

   `C:\xampp\htdocs\`

2. Inicia Apache y MySQL desde XAMPP.

3. Abre phpMyAdmin:

   `http://localhost/phpmyadmin`

4. Importa el archivo:

   `permisos-salida/database/schema.sql`

5. Revisa la conexión en:

   `permisos-salida/config/database.php`

   Configuración inicial:

   - Base: `permisos_salida`
   - Usuario: `root`
   - Contraseña: vacía

6. Abre el sistema:

   `http://localhost/permisos-salida/`

7. Diagnóstico:

   `http://localhost/permisos-salida/diagnostico.php`

## Flujo de uso

1. Entra a **Empleados** y registra los nombres.
2. Entra a **Nuevo permiso**.
3. Selecciona empleado, fecha, hora de salida y regreso previsto.
4. Captura el motivo y las condiciones del tiempo.
5. Guarda e imprime para recabar ambas firmas.
6. Al regresar el trabajador, edita el permiso y captura la hora real.

## Nota

Este sistema es un control interno sencillo y no incluye usuarios, contraseñas ni niveles de autorización digital. La autorización formal queda respaldada por la firma autógrafa del patrón en la hoja impresa.
