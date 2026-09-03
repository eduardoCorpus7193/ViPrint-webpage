-- Actualización de estados para Sistema de Notas V2
-- Agrega:
-- Contacto: Cliente no contesta
-- Producción: En costura

ALTER TABLE v2_notas
MODIFY COLUMN estado_contacto ENUM('pendiente','contactado','cliente_no_contesta','no_aplica') NOT NULL DEFAULT 'pendiente';

ALTER TABLE v2_notas
MODIFY COLUMN estado_produccion ENUM('pendiente','para_imprimir','impresa','sublimada','en_costura','problema','no_aplica') NOT NULL DEFAULT 'pendiente';
