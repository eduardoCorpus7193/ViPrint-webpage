# Horas Extras — ViPrint Publicidad

Sistema sencillo para registrar e imprimir una hoja por cada día en que un trabajador realiza horas extras.

El diseño sigue la misma identidad visual y distribución general del sistema de vacaciones de ViPrint.

## Funciones

- Panel de inicio con indicadores de la semana.
- Registro diario por trabajador.
- Cálculo automático de la cantidad de horas.
- Folio automático.
- Listado y búsqueda de registros.
- Edición y eliminación.
- Hoja limpia para imprimir y firmar.
- Diseño con rojo ViPrint `#A92624` y fondo `#F8F6F8`.

El sistema no calcula pagos, horas dobles ni horas triples. Al final de la semana se reúnen las hojas impresas para efectuar el cálculo correspondiente.

## Instalación en XAMPP

1. Copia la carpeta `horas-extras` dentro de `C:\xampp\htdocs\`.
2. Inicia Apache y MySQL desde XAMPP.
3. Abre `http://localhost/phpmyadmin`.
4. Importa `database/schema.sql`.
5. Revisa usuario y contraseña en `config/database.php`.
6. Abre `http://localhost/horas-extras/`.

## Base de datos

- Base: `horas_extras`
- Tabla: `registros_horas_extra`

## Diagnóstico

Abre:

`http://localhost/horas-extras/diagnostico.php`

## Cambio de carpeta

Si cambias el nombre de la carpeta, también debes actualizar `BASE_URL` en `config/app.php`.
