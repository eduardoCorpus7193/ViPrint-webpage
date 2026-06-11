CREATE DATABASE IF NOT EXISTS viprint_vacaciones
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE viprint_vacaciones;

CREATE TABLE IF NOT EXISTS empleados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(160) NOT NULL,
    puesto VARCHAR(120) NULL,
    fecha_ingreso DATE NOT NULL,
    saldo_inicial INT NOT NULL DEFAULT 0,
    fecha_corte_saldo DATE NOT NULL,
    ultimo_anio_procesado INT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empleados_nombre (nombre),
    INDEX idx_empleados_activo (activo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS movimientos_vacaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT UNSIGNED NOT NULL,
    tipo ENUM('ASIGNACION_ANUAL', 'AJUSTE') NOT NULL,
    fecha DATE NOT NULL,
    dias INT NOT NULL,
    anio_servicio INT UNSIGNED NULL,
    descripcion VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimientos_empleado
        FOREIGN KEY (empleado_id) REFERENCES empleados(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_asignacion_anual (empleado_id, tipo, anio_servicio),
    INDEX idx_movimientos_empleado_fecha (empleado_id, fecha)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitudes_vacaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(30) NULL UNIQUE,
    empleado_id INT UNSIGNED NOT NULL,
    fecha_solicitud DATE NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    dias_solicitados INT UNSIGNED NOT NULL,
    estado ENUM('PENDIENTE', 'AUTORIZADA', 'RECHAZADA', 'CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
    observaciones VARCHAR(500) NULL,
    fecha_resolucion DATE NULL,
    resuelto_por VARCHAR(160) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitudes_empleado
        FOREIGN KEY (empleado_id) REFERENCES empleados(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_solicitudes_estado (estado),
    INDEX idx_solicitudes_fecha_inicio (fecha_inicio),
    INDEX idx_solicitudes_empleado (empleado_id)
) ENGINE=InnoDB;
