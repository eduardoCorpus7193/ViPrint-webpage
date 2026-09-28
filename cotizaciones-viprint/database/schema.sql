CREATE DATABASE IF NOT EXISTS cotizaciones_viprint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cotizaciones_viprint;

CREATE TABLE IF NOT EXISTS promociones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(30) NOT NULL UNIQUE,
    fecha DATE NOT NULL,
    cliente_nombre VARCHAR(180) NOT NULL,
    cliente_negocio VARCHAR(180) NULL,
    cliente_telefono VARCHAR(60) NULL,
    cliente_email VARCHAR(180) NULL,
    cliente_domicilio VARCHAR(255) NULL,
    validez_dias INT UNSIGNED NOT NULL DEFAULT 7,
    moneda VARCHAR(10) NOT NULL DEFAULT 'MXN',
    aplicar_iva TINYINT(1) NOT NULL DEFAULT 0,
    porcentaje_iva DECIMAL(5,2) NOT NULL DEFAULT 16.00,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    iva DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    condiciones TEXT NULL,
    observaciones TEXT NULL,
    estatus ENUM('borrador','enviada','aprobada','rechazada','cancelada') NOT NULL DEFAULT 'borrador',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha),
    INDEX idx_cliente (cliente_nombre),
    INDEX idx_estatus (estatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cotizacion_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT UNSIGNED NOT NULL,
    orden INT UNSIGNED NOT NULL DEFAULT 1,
    tipo ENUM('promocion','articulo') NOT NULL DEFAULT 'articulo',
    promocion_id INT UNSIGNED NULL,
    descripcion TEXT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    importe DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_cotizacion FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_promocion FOREIGN KEY (promocion_id) REFERENCES promociones(id) ON DELETE SET NULL,
    INDEX idx_cotizacion (cotizacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO promociones (nombre, descripcion, precio, activo) VALUES
('Promo Light', 'Promoción editable. Ajustar descripción real antes de usar.', 2790.00, 1),
('Promo Glass', '2 banderas 3 m, estructura 3.5 m, lona 1 x 1 m de regalo, diseño incluido e instalación gratis en Aguascalientes.', 1400.00, 1),
('Promo Nebula', '2 banderas más banner 0.60 x 1.60 m, doble tela, estructura 3 m y tela 2.5 m.', 1950.00, 1),
('Promo Gamma', '4 playeras full print, 1 bandera doble vista, tela 3 m, estructura 3.5 m, diseño e instalación gratis.', 2100.00, 1),
('Bandera por unidad', 'Bandera publicitaria por unidad. Ajustar medida y características en la cotización.', 0.00, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
