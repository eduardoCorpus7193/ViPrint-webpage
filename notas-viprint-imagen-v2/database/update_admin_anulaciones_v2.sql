-- Referencia: se recomienda usar instalar_admin_anulaciones_v2.php porque evita errores por columnas duplicadas.
CREATE TABLE IF NOT EXISTS v2_auditoria_admin (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accion VARCHAR(80) NOT NULL,
  entidad VARCHAR(80) NOT NULL,
  entidad_id INT UNSIGNED NOT NULL,
  nota_id INT UNSIGNED NULL,
  motivo TEXT NOT NULL,
  datos_antes LONGTEXT NULL,
  usuario_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_entidad (entidad, entidad_id),
  INDEX idx_audit_nota (nota_id),
  INDEX idx_audit_usuario (usuario_id),
  INDEX idx_audit_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
