-- Actualización de facturación para sistema ViPrint/Imagen V2.
-- Preferentemente usa instalar_facturacion_cliente_v2.php.
-- Este SQL queda como referencia si se necesita revisar la estructura.

-- Columnas nuevas en v2_notas:
-- requiere_factura TINYINT(1) NOT NULL DEFAULT 0
-- factura_requiere_fecha DATETIME NULL
-- factura_limite_datos DATETIME NULL
-- factura_requiere_por INT NULL

CREATE TABLE IF NOT EXISTS v2_factura_solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nota_id INT NOT NULL,
    folio VARCHAR(80) NOT NULL,
    public_code VARCHAR(20) NULL,
    rfc VARCHAR(20) NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    codigo_postal_fiscal VARCHAR(10) NULL,
    regimen_fiscal VARCHAR(180) NOT NULL,
    uso_cfdi VARCHAR(120) NOT NULL,
    correo VARCHAR(180) NOT NULL,
    telefono VARCHAR(40) NULL,
    constancia_archivo VARCHAR(255) NOT NULL,
    comentarios_cliente TEXT NULL,
    estado VARCHAR(40) NOT NULL DEFAULT 'pendiente',
    uuid VARCHAR(100) NULL,
    factura_pdf VARCHAR(255) NULL,
    factura_xml VARCHAR(255) NULL,
    comentarios_internos TEXT NULL,
    revisado_por INT NULL,
    facturada_por INT NULL,
    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NULL,
    INDEX idx_factura_nota (nota_id),
    INDEX idx_factura_estado (estado),
    INDEX idx_factura_fecha (fecha_solicitud),
    INDEX idx_factura_rfc (rfc)
);
