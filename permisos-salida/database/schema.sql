CREATE DATABASE IF NOT EXISTS permisos_salida
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE permisos_salida;

CREATE TABLE IF NOT EXISTS empleados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_empleado_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permisos_salida (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(30) NULL,
    empleado_id INT UNSIGNED NOT NULL,
    fecha_permiso DATE NOT NULL,
    hora_salida TIME NOT NULL,
    hora_regreso_prevista TIME NOT NULL,
    hora_regreso_real TIME NULL,
    motivo_tipo ENUM('personal', 'medico', 'familiar', 'tramite', 'laboral', 'emergencia') NOT NULL DEFAULT 'personal',
    motivo_detalle VARCHAR(500) NOT NULL,
    destino VARCHAR(200) NULL,
    tratamiento_tiempo ENUM('por_definir', 'con_goce', 'sin_goce', 'reposicion', 'salida_laboral') NOT NULL DEFAULT 'por_definir',
    estado ENUM('pendiente', 'autorizado', 'cancelado') NOT NULL DEFAULT 'pendiente',
    observaciones VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permiso_folio (folio),
    KEY idx_permiso_fecha (fecha_permiso),
    KEY idx_permiso_estado (estado),
    KEY idx_permiso_empleado (empleado_id),
    CONSTRAINT fk_permiso_empleado
        FOREIGN KEY (empleado_id) REFERENCES empleados(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
