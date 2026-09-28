CREATE DATABASE IF NOT EXISTS notas_viprint_imagen
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE notas_viprint_imagen;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    usuario VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','operativo','disenador') NOT NULL DEFAULT 'disenador',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalogo_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa ENUM('viprint','imagen') NOT NULL DEFAULT 'viprint',
    tipo ENUM('promocion','articulo','bandera','otro') NOT NULL DEFAULT 'articulo',
    nombre VARCHAR(160) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa ENUM('viprint','imagen') NOT NULL DEFAULT 'viprint',
    folio VARCHAR(40) NOT NULL,
    fecha_nota DATE NOT NULL,
    cliente_nombre VARCHAR(180) NOT NULL,
    negocio VARCHAR(180) NULL,
    domicilio VARCHAR(255) NULL,
    telefono VARCHAR(60) NULL,
    origen ENUM('whatsapp','mostrador','vendedor','llamada','facebook','otro') NOT NULL DEFAULT 'mostrador',
    vendedor_nombre VARCHAR(150) NULL,
    disenador_id INT UNSIGNED NULL,
    fecha_promesa DATE NULL,
    fecha_instalacion DATE NULL,
    estado ENUM(
        'recibida',
        'pendiente_contacto',
        'contactado',
        'en_diseno',
        'en_aprobacion',
        'aprobado_para_imprimir',
        'impresa',
        'sublimada',
        'en_instalacion',
        'instalada',
        'entregada',
        'cancelada'
    ) NOT NULL DEFAULT 'recibida',
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    anticipo DECIMAL(10,2) NOT NULL DEFAULT 0,
    saldo DECIMAL(10,2) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    creado_por INT UNSIGNED NULL,
    actualizado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_empresa_folio (empresa, folio),
    INDEX idx_fecha (fecha_nota),
    INDEX idx_estado (estado),
    INDEX idx_disenador (disenador_id),
    CONSTRAINT fk_notas_disenador FOREIGN KEY (disenador_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_notas_creado_por FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_notas_actualizado_por FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nota_detalles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id INT UNSIGNED NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    tipo_item ENUM('promocion','articulo','bandera','otro') NOT NULL DEFAULT 'articulo',
    catalogo_id INT UNSIGNED NULL,
    descripcion TEXT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    importe DECIMAL(10,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_detalles_nota FOREIGN KEY (nota_id) REFERENCES notas(id) ON DELETE CASCADE,
    CONSTRAINT fk_detalles_catalogo FOREIGN KEY (catalogo_id) REFERENCES catalogo_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS abonos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id INT UNSIGNED NOT NULL,
    fecha_pago DATE NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    forma_pago ENUM('efectivo','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
    referencia VARCHAR(180) NULL,
    usuario_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_abonos_nota FOREIGN KEY (nota_id) REFERENCES notas(id) ON DELETE CASCADE,
    CONSTRAINT fk_abonos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estado_historial (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id INT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(60) NULL,
    estado_nuevo VARCHAR(60) NOT NULL,
    comentario TEXT NULL,
    usuario_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historial_nota FOREIGN KEY (nota_id) REFERENCES notas(id) ON DELETE CASCADE,
    CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contraseña inicial para todos: 123456
-- Cambiar contraseñas después de instalar.
INSERT INTO usuarios (nombre, usuario, password_hash, rol, activo) VALUES
('Administrador', 'admin', '$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG', 'admin', 1),
('Danae', 'danae', '$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG', 'operativo', 1),
('Angel Uriel Peña Salazar', 'angel', '$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG', 'disenador', 1)
ON DUPLICATE KEY UPDATE usuario = VALUES(usuario);

INSERT INTO catalogo_items (empresa, tipo, nombre, descripcion, precio, activo) VALUES
('viprint', 'promocion', 'Promo Light', 'Promoción Light de ViPrint. Ajustar descripción y precio según la promoción vigente.', 2790.00, 1),
('viprint', 'promocion', 'Promo Nebula', 'Promoción Nebula de ViPrint. Editar condiciones en catálogo si cambian.', 0.00, 1),
('viprint', 'promocion', 'Promo Glass', 'Promoción Glass de ViPrint. Editar condiciones en catálogo si cambian.', 1400.00, 1),
('viprint', 'promocion', 'Promo Beta', 'Promoción Beta de ViPrint. Editar condiciones en catálogo si cambian.', 0.00, 1),
('viprint', 'articulo', 'Lona', 'Lona publicitaria. Capturar medidas y acabado en la descripción de la nota.', 0.00, 1),
('viprint', 'articulo', 'Vinil', 'Vinil impreso o de corte. Capturar medidas y especificaciones.', 0.00, 1),
('viprint', 'articulo', 'Banner', 'Banner publicitario. Capturar medida, material y acabado.', 0.00, 1),
('imagen', 'bandera', 'Bandera por unidad', 'Bandera publicitaria por unidad. Capturar medida, modelo y especificaciones.', 0.00, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
