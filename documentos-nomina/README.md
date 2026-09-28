# ViPrint · Documentos internos de nómina

Sistema sencillo en PHP, MySQL, Bootstrap, HTML, CSS y JavaScript para generar dos tipos de documentos internos:

1. Pago normal por transferencia.
2. Bono semanal en efectivo con importe variable.

## Funciones

- Catálogo editable de empleados.
- Combo de empleados en el formulario.
- Captura de fecha trabajada y cantidad pagada.
- Folio automático.
- Dos textos de recibo según la forma de pago.
- Firma del empleado y del empleador.
- Historial con filtros.
- Edición, eliminación e impresión.
- Sin inicio de sesión.
- Diseño ViPrint con rojo `#A92624` y fondo `#F8F6F8`.

## Requisitos

- XAMPP con Apache y MySQL/MariaDB.
- PHP 8.1 o superior.
- Extensión PDO MySQL habilitada.

## Instalación

1. Copia la carpeta `documentos-nomina` dentro de:

   `C:\xampp\htdocs\`

2. Inicia Apache y MySQL desde XAMPP.

3. Abre phpMyAdmin:

   `http://localhost/phpmyadmin`

4. Importa el archivo:

   `documentos-nomina/database/schema.sql`

5. Revisa la conexión en:

   `documentos-nomina/config/database.php`

   Configuración inicial:

   - Base: `documentos_nomina`
   - Usuario: `root`
   - Contraseña: vacía

6. Abre el sistema:

   `http://localhost/documentos-nomina/`

7. Diagnóstico:

   `http://localhost/documentos-nomina/diagnostico.php`

## Flujo de uso

1. Entra a **Empleados** y registra los nombres.
2. Entra a **Nuevo documento**.
3. Selecciona transferencia o bono en efectivo.
4. Selecciona al empleado.
5. Captura fecha trabajada y cantidad.
6. Guarda, imprime y recaba las firmas.

## Nota importante

Los documentos generados son controles internos. No sustituyen el CFDI de nómina ni las obligaciones laborales, fiscales o de seguridad social aplicables.
