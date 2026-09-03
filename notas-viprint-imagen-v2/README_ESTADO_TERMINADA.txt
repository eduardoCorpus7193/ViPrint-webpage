ACTUALIZACIÓN: ESTADO DE PRODUCCIÓN 'TERMINADA'

Qué agrega:
- En Producción se agrega la opción: Terminada
- Queda después de: En costura

Instalación recomendada:
1. Sube instalar_estado_terminada_v2.php a la raíz del sistema:
   /public_html/notas-viprint-imagen-v2/

2. Entra con usuario admin, dirección o administración.

3. Abre:
   https://viprint.com.mx/notas-viprint-imagen-v2/instalar_estado_terminada_v2.php?clave=terminada2026

4. Verifica que diga que la base de datos y los archivos fueron actualizados.

5. Elimina del servidor:
   instalar_estado_terminada_v2.php

Notas:
- No reemplaza config/database.php.
- No toca tickets, caja, QR ni corte diario.
- El instalador crea respaldos .bak de los archivos que modifique.
- Si el servidor no deja modificar archivos desde PHP, ejecuta el SQL de database/update_estado_terminada_v2.sql y avísame para darte los reemplazos manuales.
