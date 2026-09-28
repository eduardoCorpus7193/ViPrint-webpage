-- Actualización V2: Caja y corte diario
-- Ejecutar una sola vez en la misma base de datos del sistema V2.
-- No borra registros existentes. Solo crea tablas nuevas y copia pagos existentes a movimientos de caja.

CREATE TABLE IF NOT EXISTS v2_caja_movimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nota_id INT UNSIGNED NULL,
    pago_id INT UNSIGNED NULL,
    fecha_operacion DATE NOT NULL,
    hora_operacion TIME NULL,
    tipo ENUM('entrada','salida') NOT NULL DEFAULT 'entrada',
    concepto ENUM('pago_cliente','devolucion_cliente','gasto','retiro','ajuste','otro') NOT NULL DEFAULT 'pago_cliente',
    forma_pago ENUM('efectivo','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
    forma_pago_otro VARCHAR(120) NULL,
    descripcion TEXT NULL,
    monto DECIMAL(12,2) NOT NULL DEFAULT 0,
    referencia VARCHAR(180) NULL,
    comprobante VARCHAR(255) NULL,
    creado_por INT UNSIGNED NULL,
    autorizado_por_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_v2_caja_empresa FOREIGN KEY (empresa_id) REFERENCES v2_empresas(id),
    CONSTRAINT fk_v2_caja_nota FOREIGN KEY (nota_id) REFERENCES v2_notas(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_caja_pago FOREIGN KEY (pago_id) REFERENCES v2_pagos(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_caja_creado FOREIGN KEY (creado_por) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_caja_autorizado FOREIGN KEY (autorizado_por_id) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY uq_v2_caja_pago (pago_id),
    INDEX idx_v2_caja_fecha (fecha_operacion),
    INDEX idx_v2_caja_empresa_fecha (empresa_id, fecha_operacion),
    INDEX idx_v2_caja_forma (forma_pago),
    INDEX idx_v2_caja_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS v2_cortes_diarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    fecha_corte DATE NOT NULL,
    efectivo_sistema DECIMAL(12,2) NOT NULL DEFAULT 0,
    transferencia_sistema DECIMAL(12,2) NOT NULL DEFAULT 0,
    tarjeta_sistema DECIMAL(12,2) NOT NULL DEFAULT 0,
    otro_sistema DECIMAL(12,2) NOT NULL DEFAULT 0,
    salidas_sistema DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_sistema DECIMAL(12,2) NOT NULL DEFAULT 0,
    efectivo_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
    transferencia_confirmada DECIMAL(12,2) NOT NULL DEFAULT 0,
    tarjeta_confirmada DECIMAL(12,2) NOT NULL DEFAULT 0,
    otro_confirmado DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_confirmado DECIMAL(12,2) NOT NULL DEFAULT 0,
    diferencia_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    realizado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_v2_corte_empresa FOREIGN KEY (empresa_id) REFERENCES v2_empresas(id),
    CONSTRAINT fk_v2_corte_usuario FOREIGN KEY (realizado_por) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY uq_v2_corte_empresa_fecha (empresa_id, fecha_corte),
    INDEX idx_v2_corte_fecha (fecha_corte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cargar pagos ya registrados en la caja, sin duplicarlos.
INSERT INTO v2_caja_movimientos (
    empresa_id,
    nota_id,
    pago_id,
    fecha_operacion,
    hora_operacion,
    tipo,
    concepto,
    forma_pago,
    forma_pago_otro,
    descripcion,
    monto,
    referencia,
    comprobante,
    creado_por,
    created_at
)
SELECT
    p.empresa_id,
    p.nota_id,
    p.id,
    p.fecha_pago,
    TIME(p.created_at),
    CASE WHEN p.concepto = 'devolucion' THEN 'salida' ELSE 'entrada' END,
    CASE WHEN p.concepto = 'devolucion' THEN 'devolucion_cliente' ELSE 'pago_cliente' END,
    p.forma_pago,
    p.forma_pago_otro,
    CONCAT('Movimiento generado desde pago: ', p.concepto),
    p.monto,
    p.referencia,
    p.comprobante,
    p.usuario_id,
    p.created_at
FROM v2_pagos p
LEFT JOIN v2_caja_movimientos cm ON cm.pago_id = p.id
WHERE cm.id IS NULL;
