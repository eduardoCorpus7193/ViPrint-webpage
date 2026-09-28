-- Actualización V2: tickets 58mm y registro de aperturas de cajón
-- Ejecutar en la misma base de datos del sistema V2.
-- No borra registros existentes.

CREATE TABLE IF NOT EXISTS v2_caja_aperturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nota_id INT UNSIGNED NULL,
    pago_id INT UNSIGNED NULL,
    fecha_apertura DATE NOT NULL,
    hora_apertura TIME NOT NULL,
    tipo ENUM('pago','manual','prueba') NOT NULL DEFAULT 'manual',
    motivo VARCHAR(255) NULL,
    impresora VARCHAR(180) NULL,
    comando VARCHAR(120) NULL,
    usuario_id INT UNSIGNED NULL,
    ip VARCHAR(80) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_v2_apertura_empresa FOREIGN KEY (empresa_id) REFERENCES v2_empresas(id),
    CONSTRAINT fk_v2_apertura_nota FOREIGN KEY (nota_id) REFERENCES v2_notas(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_apertura_pago FOREIGN KEY (pago_id) REFERENCES v2_pagos(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_apertura_usuario FOREIGN KEY (usuario_id) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    INDEX idx_v2_apertura_fecha (fecha_apertura),
    INDEX idx_v2_apertura_empresa_fecha (empresa_id, fecha_apertura),
    INDEX idx_v2_apertura_pago (pago_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
