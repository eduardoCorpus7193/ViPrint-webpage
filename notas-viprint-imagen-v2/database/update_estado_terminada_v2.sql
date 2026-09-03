-- Actualización: agregar Producción = Terminada
-- Ejecutar solo si no usas el instalador PHP.

ALTER TABLE v2_notas
MODIFY COLUMN estado_produccion ENUM('pendiente','para_imprimir','impresa','sublimada','en_costura','terminada','problema','no_aplica') NOT NULL DEFAULT 'pendiente';
