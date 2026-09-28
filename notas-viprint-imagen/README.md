# Sistema de Control de Notas - ViPrint / Imagen

Aplicación web sencilla en PHP, MySQL, HTML, CSS, JavaScript y Bootstrap para registrar notas de venta/pedido, asignarlas a diseñadores, controlar estado del trabajo y consultar saldo pendiente.

## Módulos incluidos

- Login de usuarios.
- Roles:
  - Administrador: acceso total.
  - Operativo / mostrador: crea notas, asigna diseñador, registra pagos y administra catálogo.
  - Diseñador: ve sus notas asignadas y actualiza el estado.
- Registro de notas para ViPrint e Imagen.
- Folio de libreta física.
- Datos de cliente, negocio, domicilio y teléfono.
- Origen del pedido: WhatsApp, mostrador, vendedor externo, llamada, Facebook u otro.
- Asignación de diseñador.
- Estados del proceso:
  - Recibida.
  - Pendiente de contacto.
  - Cliente contactado.
  - En diseño.
  - En aprobación del cliente.
  - Aprobado para imprimir.
  - Impresa.
  - Sublimada.
  - En instalación.
  - Instalada.
  - Entregada / cerrada.
  - Cancelada.
- Catálogo de promociones y artículos.
- ViPrint puede usar promociones o artículos escritos directamente.
- Imagen puede manejar banderas por unidad.
- Control de total, anticipo, abonos y saldo pendiente.
- Historial de cambios de estado.
- Vista imprimible de la nota.
- Diseño responsive para computadora y celular.

## Instalación en XAMPP

1. Copia la carpeta `notas-viprint-imagen` dentro de:

```text
C:\xampp\htdocs\
```

2. Abre XAMPP e inicia Apache y MySQL.

3. Entra a phpMyAdmin:

```text
http://localhost/phpmyadmin
```

4. Importa el archivo:

```text
notas-viprint-imagen/database/schema.sql
```

5. Abre el sistema:

```text
http://localhost/notas-viprint-imagen/
```

## Instalación en hosting

1. Sube la carpeta al hosting, por ejemplo:

```text
public_html/notas/
```

2. Crea una base de datos MySQL desde el panel del hosting.

3. Importa el archivo `database/schema.sql`.

Si tu hosting no permite `CREATE DATABASE`, abre el SQL y elimina o no ejecutes estas líneas:

```sql
CREATE DATABASE IF NOT EXISTS notas_viprint_imagen ...;
USE notas_viprint_imagen;
```

Después selecciona la base de datos correcta en phpMyAdmin e importa solo las tablas.

4. Edita:

```text
config/database.php
```

Con los datos reales del hosting:

```php
$DB_HOST = 'localhost';
$DB_NAME = 'tuusuario_notas';
$DB_USER = 'tuusuario_usuario';
$DB_PASS = 'tu_contraseña';
```

5. Abre:

```text
https://tudominio.com/notas/diagnostico.php
```

Debe indicar conexión correcta.

## Usuarios iniciales

Todos tienen contraseña inicial:

```text
123456
```

Usuarios:

```text
admin
```

```text
danae
```

```text
angel
```

Cambia las contraseñas después de instalar desde el módulo de Usuarios.

## Flujo recomendado

1. Danae o administración crea la nota.
2. Se captura el folio de la libreta física.
3. Se asigna diseñador.
4. El diseñador entra al sistema y ve sus notas.
5. El diseñador cambia el estado conforme avance:
   - contacto,
   - diseño,
   - aprobación,
   - aprobado para imprimir.
6. Producción/administración continúa con:
   - impresa,
   - sublimada,
   - en instalación,
   - instalada,
   - entregada.
7. Se registran anticipos y abonos para controlar saldo pendiente.

## Recomendación operativa

La regla interna más importante debe ser:

> Toda nota que requiera diseño debe quedar asignada a un diseñador el mismo día que se recibe.

El sistema muestra en inicio las notas sin diseñador para evitar que se queden olvidadas.

## Requisitos técnicos

- PHP 7.4 o superior. Recomendado PHP 8.1+.
- MySQL o MariaDB.
- Extensión PDO y pdo_mysql activa.
- Navegador moderno.

## Seguridad básica

- Usa consultas preparadas con PDO.
- Usa tokens CSRF en formularios.
- Incluye login y roles.
- No incluye acceso a banca ni pagos en línea.

