# Fix reporte general imprimible

Este ajuste corrige el problema donde el reporte general se imprime o se guarda en PDF en blanco.

## Qué cambia

- Agrega una versión especial para impresión dentro de `reportes_generales.php`.
- El navegador seguirá mostrando el reporte normal.
- Al presionar **Imprimir reporte**, se imprime una versión compacta y limpia.
- Funciona para:
  - Todas las empresas
  - ViPrint
  - Imagen

## Archivo a subir

Subir y reemplazar en:

`/public_html/notas-viprint-imagen-v2/`

Archivo:

`reportes_generales.php`

## No subir ni reemplazar

No tocar:

- `config/database.php`
- `ticket_pago.php`
- `assets/js/qz-viprint.js`
- Archivos de caja o corte diario

## Después de subir

1. Abrir `reportes_generales.php`.
2. Presionar `Ctrl + F5`.
3. Seleccionar empresa y fechas.
4. Presionar **Imprimir reporte**.

Si se guarda como PDF, ya debe mostrar contenido y no páginas en blanco.
