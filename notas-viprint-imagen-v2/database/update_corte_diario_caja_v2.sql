-- Actualización: corte diario de caja conjunto ViPrint / Imagen.
-- Ejecutar una sola vez en la base del sistema V2 si prefieres hacerlo por phpMyAdmin.
-- Recomendado: usar instalar_corte_diario_caja_v2.php?clave=corte2026 porque muestra errores con más claridad.

CREATE TABLE IF NOT EXISTS v2_cortes_caja (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fecha_corte DATE NOT NULL,
    fondo_inicial DECIMAL(12,2) NOT NULL DEFAULT 0,
    fondo_base DECIMAL(12,2) NOT NULL DEFAULT 800,
    entradas_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0,
    salidas_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0,
    salidas_efectivo_operativas DECIMAL(12,2) NOT NULL DEFAULT 0,
    entrega_luis_sistema DECIMAL(12,2) NOT NULL DEFAULT 0,
    entradas_transferencia DECIMAL(12,2) NOT NULL DEFAULT 0,
    entradas_tarjeta DECIMAL(12,2) NOT NULL DEFAULT 0,
    entradas_otro DECIMAL(12,2) NOT NULL DEFAULT 0,
    salidas_transferencia DECIMAL(12,2) NOT NULL DEFAULT 0,
    salidas_tarjeta DECIMAL(12,2) NOT NULL DEFAULT 0,
    salidas_otro DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_entradas DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_salidas DECIMAL(12,2) NOT NULL DEFAULT 0,
    caja_esperada DECIMAL(12,2) NOT NULL DEFAULT 0,
    efectivo_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
    diferencia_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0,
    entrega_luis_sugerida DECIMAL(12,2) NOT NULL DEFAULT 0,
    entrega_luis_real DECIMAL(12,2) NOT NULL DEFAULT 0,
    fondo_final DECIMAL(12,2) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    entrega_nombre VARCHAR(160) NULL,
    recibe_nombre VARCHAR(160) NULL,
    hora_entrega TIME NULL,
    cerrado TINYINT(1) NOT NULL DEFAULT 0,
    realizado_por INT UNSIGNED NULL,
    cerrado_por INT UNSIGNED NULL,
    cerrado_at DATETIME NULL,
    entrega_movimiento_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_v2_cortes_caja_fecha (fecha_corte),
    INDEX idx_v2_cortes_caja_cerrado (cerrado),
    INDEX idx_v2_cortes_caja_realizado (realizado_por),
    INDEX idx_v2_cortes_caja_cerrado_por (cerrado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE v2_caja_movimientos
MODIFY COLUMN concepto ENUM('pago_cliente','devolucion_cliente','gasto','uber_envio','entrega_luis','prestamo_cambio','compra_menor','ajuste_caja','retiro','ajuste','otro') NOT NULL DEFAULT 'pago_cliente';
