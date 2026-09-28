CREATE DATABASE IF NOT EXISTS horas_extras
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE horas_extras;

CREATE TABLE IF NOT EXISTS registros_horas_extra (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(25) NULL UNIQUE,
    trabajador VARCHAR(150) NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    total_horas DECIMAL(6,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_trabajador (trabajador),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB;
