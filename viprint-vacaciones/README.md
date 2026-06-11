# ViPrint · Sistema sencillo de vacaciones

Aplicación local desarrollada con PHP, MySQL, Bootstrap, JavaScript, HTML y CSS. No utiliza Composer.

## Funciones

- Registro de empleados con nombre, puesto, fecha de ingreso y días disponibles actuales.
- Actualización automática al cumplir cada aniversario laboral.
- Tabla legal de vacaciones:
  - 1 año: 12 días.
  - 2 años: 14 días.
  - 3 años: 16 días.
  - 4 años: 18 días.
  - 5 años: 20 días.
  - 6 a 10 años: 22 días.
  - 11 a 15 años: 24 días.
  - Continúa aumentando 2 días por cada 5 años.
- El sistema no borra el saldo anterior al llegar un aniversario; agrega la nueva asignación.
- Solicitudes pendientes, autorizadas, rechazadas o canceladas.
- Descuento del saldo únicamente al autorizar una solicitud.
- Devolución automática de días al cancelar una autorización.
- Formato imprimible con firmas del trabajador y del patrón.
- Constancia anual de antigüedad y saldo.
- Ajustes manuales de saldo con motivo documentado.
- Diseño ViPrint con rojo `#A92624` y fondo `#F8F6F8`.

## Cómo funciona la actualización anual

Cuando se registra por primera vez a una persona, se captura el saldo real disponible en ese momento. El sistema considera que los aniversarios anteriores ya están incluidos en ese saldo.

Cada vez que se abre el sistema, revisa si algún empleado cumplió un nuevo aniversario desde el último registro. Si ocurrió, crea automáticamente una asignación con los días correspondientes al nuevo año de servicio.

Ejemplo: una persona que cumplirá su sexto año recibirá automáticamente 22 días en la fecha de ese aniversario.

## Instalación en XAMPP

1. Copia la carpeta `viprint-vacaciones` dentro de:

   `C:\xampp\htdocs\`

2. En XAMPP inicia Apache y MySQL.

3. Abre phpMyAdmin:

   `http://localhost/phpmyadmin`

4. Importa el archivo:

   `database/schema.sql`

5. Revisa la conexión en:

   `config/database.php`

   Configuración inicial:

   ```php
   $host = '127.0.0.1';
   $db   = 'viprint_vacaciones';
   $user = 'root';
   $pass = '';
   ```

6. Abre el diagnóstico:

   `http://localhost/viprint-vacaciones/diagnostico.php`

7. Abre el sistema:

   `http://localhost/viprint-vacaciones/`

## Uso inicial recomendado

1. Registra a cada empleado.
2. Captura su fecha real de ingreso.
3. Captura los días que realmente tiene disponibles al día de la instalación.
4. En adelante, el sistema agregará los días de cada nuevo aniversario.
5. Crea la solicitud, imprímela y recaba firmas.
6. Autoriza la solicitud dentro del sistema para descontar los días.

## Conteo de días solicitados

El formulario calcula automáticamente los días comprendidos entre la fecha inicial y final, excluyendo domingos porque ViPrint trabaja de lunes a sábado. El campo permanece editable para corregirlo cuando exista un día festivo, permiso especial o una condición distinta.

## Consideraciones

- La actualización automática ocurre cuando alguien abre cualquier página del sistema. Si permanece meses sin utilizarse, se pondrá al corriente al volver a abrirlo.
- No expongas este proyecto directamente a internet sin implementar inicio de sesión, HTTPS, respaldos y controles de acceso.
- Realiza respaldos periódicos de la base `viprint_vacaciones`.
- El sistema es una herramienta administrativa; cualquier ajuste de saldo debe sustentarse con los documentos laborales correspondientes.
