# Sistema de Notas ViPrint / Imagen V2

Sistema web en PHP, MySQL, HTML, CSS, JS y Bootstrap para controlar notas de ViPrint e Imagen con separación por empresa, pagos, saldos, costos, ganancias, mermas, comisiones y estados por proceso.

## Cambios principales de la V2

- ViPrint e Imagen en el mismo sistema, pero con filtros y reportes separados.
- Folios separados por empresa según el folio físico de cada nota.
- Catálogo propio por empresa.
- ViPrint incluye promociones editables:
  - Promo Glass, Buzz, Beta, Sky, Pixel, Nube, Nebula, Maxiventas, Light, Rush, Activación, Super Promo y Gamma.
- Imagen maneja productos sin promociones:
  - Bandera grande, mediana, jumbo y tela sin estructura.
- Promociones y precios editables.
- Partidas libres para artículos no catalogados.
- Costos estimados y reales por producto/partida:
  - Material.
  - Mano de obra.
  - Maquila.
  - Instalación.
- Cálculo de utilidad:
  - Total cobrado menos costos.
  - Total cobrado menos costos y comisiones.
  - Total cobrado menos costos, comisiones y mermas.
- Registro de pagos/abonos/liquidaciones/devoluciones con:
  - Fecha.
  - Monto.
  - Método: efectivo, transferencia, tarjeta u otro.
  - Campo libre para especificar método cuando sea “otro”.
  - Referencia o comprobante.
- Fecha de liquidación automática cuando el saldo llega a cero.
- Estados separados:
  - Contacto.
  - Diseño.
  - Aprobación para impresión.
  - Producción.
  - Instalación.
  - Entrega.
  - Pago.
- Registro de mermas:
  - Papel perdido.
  - Tela perdida.
  - Tinta desperdiciada.
  - Reimpresión.
  - Error de diseño.
  - Error de impresión.
  - Cliente canceló.
  - Otro.
- Registro de responsable probable, área, descripción y costo de merma.
- Comisiones por diseñador:
  - Ángel puede aparecer sin comisión por banderas.
  - Diseños extra, logos, lonas u otros sí pueden registrar comisión.
  - Andrea y Jaquelin pueden manejar comisiones variables.
- Reporte de ingresos por método de pago por día y periodo.
- Reporte de comisiones pendientes y pagadas.
- Reportes financieros visibles solo para usuarios autorizados.

## Usuarios iniciales

Contraseña inicial para todos: `123456`

- `admin` - Administrador.
- `luis` - Dirección, finanzas, precios y borrar registros.
- `danae` - Operativo, notas, pagos y producción.
- `mafer` - Administración, finanzas, precios, mermas, adeudos y reportes.
- `eduardo` - Asesor externo, sistemas, finanzas y precios.
- `angel` - Diseñador.
- `andrea` - Diseñadora externa.
- `jaquelin` - Diseñadora externa.

Cambia las contraseñas después de instalar.

## Instalación nueva

1. Sube la carpeta a tu hosting, por ejemplo:

```text
public_html/notas-viprint-imagen-v2/
```

2. Configura la base de datos en:

```text
config/database.php
```

3. Importa en phpMyAdmin:

```text
database/schema_v2.sql
```

4. Abre:

```text
https://viprint.com.mx/notas-viprint-imagen-v2/
```

## Migrar datos del sistema anterior

Este paquete incluye un migrador para conservar los registros ya existentes del sistema anterior.

### Antes de migrar

1. Haz respaldo de la base actual desde phpMyAdmin.
2. No borres las tablas antiguas: `notas`, `nota_detalles`, `abonos`, `usuarios`, etc.
3. Sube esta versión V2 a una carpeta nueva.
4. Configura `config/database.php` apuntando a la misma base donde está el sistema anterior.

### Ejecutar migración

Abre en el navegador:

```text
https://viprint.com.mx/notas-viprint-imagen-v2/migrar_v1_a_v2.php?clave=migrar2026
```

El script:

- Crea las tablas V2 con prefijo `v2_`.
- No borra las tablas viejas.
- Copia usuarios existentes.
- Copia notas existentes.
- Copia detalles de notas.
- Copia abonos existentes.
- Convierte los estados antiguos al nuevo esquema.
- Recalcula totales, saldos, pagos y utilidades.

Cuando termine la migración, elimina o renombra `migrar_v1_a_v2.php` por seguridad.

## Diagnóstico

Abre:

```text
https://viprint.com.mx/notas-viprint-imagen-v2/diagnostico.php
```

Debe mostrar:

- PHP activo.
- PDO activo.
- PDO MySQL activo.
- Tablas V2 creadas.

## Recomendaciones de uso

- Todo pedido debe registrarse como nota.
- Toda nota con diseño debe asignarse a un diseñador.
- El diseñador actualiza contacto, diseño y aprobación.
- Danae actualiza producción, instalación, entrega y pagos.
- Mafer revisa montos, saldos, mermas, ganancias y comisiones.
- Luis autoriza cancelaciones, salidas de dinero, precios especiales importantes y eliminación de registros.
- Eduardo mantiene el sistema y respaldos.

## Importante

Este sistema es administrativo. No sustituye contabilidad, facturación, CFDI ni obligaciones fiscales.
