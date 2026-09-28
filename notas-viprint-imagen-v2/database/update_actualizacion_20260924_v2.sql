-- Actualización ViPrint 2026-09-24
ALTER TABLE v2_notas ADD COLUMN subtotal_sin_iva DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total;
ALTER TABLE v2_notas ADD COLUMN iva_monto DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER subtotal_sin_iva;
ALTER TABLE v2_notas MODIFY estado_contacto VARCHAR(40) NOT NULL DEFAULT "pendiente";
ALTER TABLE v2_notas MODIFY estado_produccion VARCHAR(40) NOT NULL DEFAULT "pendiente";
CREATE TABLE IF NOT EXISTS v2_quejas_sugerencias (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nota_id INT UNSIGNED NOT NULL, empresa_id INT UNSIGNED NOT NULL, folio VARCHAR(60) NOT NULL, public_code VARCHAR(80) NULL, nombre VARCHAR(180) NOT NULL, telefono VARCHAR(80) NOT NULL, tipo VARCHAR(30) NOT NULL DEFAULT "comentario", mensaje TEXT NOT NULL, foto_path VARCHAR(255) NULL, estado VARCHAR(30) NOT NULL DEFAULT "nueva", respuesta_interna TEXT NULL, atendido_por_id INT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL, INDEX idx_nota (nota_id), INDEX idx_estado (estado), INDEX idx_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
