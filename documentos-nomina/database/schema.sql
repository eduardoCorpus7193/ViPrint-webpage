CREATE DATABASE IF NOT EXISTS documentos_nomina
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE documentos_nomina;

CREATE TABLE IF NOT EXISTS empleados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_empleado_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS documentos_nomina (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(30) NULL,
    empleado_id INT UNSIGNED NOT NULL,
    tipo ENUM('transferencia', 'bono_efectivo') NOT NULL,
    fecha_trabajada DATE NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_documento_folio (folio),
    KEY idx_documento_fecha (fecha_trabajada),
    KEY idx_documento_tipo (tipo),
    KEY idx_documento_empleado (empleado_id),
    CONSTRAINT fk_documento_empleado
        FOREIGN KEY (empleado_id) REFERENCES empleados(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
