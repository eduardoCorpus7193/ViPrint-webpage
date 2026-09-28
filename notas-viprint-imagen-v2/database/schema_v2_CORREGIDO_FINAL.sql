-- Sistema de Notas ViPrint / Imagen V2
-- Ejecutar en la base de datos existente o en una base nueva.
-- Si ya tienes registros del sistema anterior, NO borres las tablas antiguas. Ejecuta migrar_v1_a_v2.php después de configurar la conexión.

CREATE TABLE IF NOT EXISTS v2_empresas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    folio_prefijo VARCHAR(12) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS v2_usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(160) NOT NULL,
    usuario VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','direccion','administracion','operativo','disenador','externo','asesor') NOT NULL DEFAULT 'disenador',
    puede_ver_finanzas TINYINT(1) NOT NULL DEFAULT 0,
    puede_editar_precios TINYINT(1) NOT NULL DEFAULT 0,
    puede_borrar TINYINT(1) NOT NULL DEFAULT 0,
    es_disenador TINYINT(1) NOT NULL DEFAULT 0,
    comision_default DECIMAL(10,2) NULL DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rol (rol),
    INDEX idx_activo (activo),
    INDEX idx_disenador (es_disenador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS v2_catalogo_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    tipo ENUM('promocion','bandera','lona','vinil','diseno_extra','instalacion','articulo','otro') NOT NULL DEFAULT 'articulo',
    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    precio_base DECIMAL(10,2) NOT NULL DEFAULT 0,
    incluye_diseno TINYINT(1) NOT NULL DEFAULT 0,
    incluye_instalacion TINYINT(1) NOT NULL DEFAULT 0,
    permite_precio_especial TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_v2_cat_empresa FOREIGN KEY (empresa_id) REFERENCES v2_empresas(id) ON DELETE CASCADE,
    UNIQUE KEY uq_v2_cat_empresa_nombre (empresa_id, nombre),
    INDEX idx_empresa_tipo (empresa_id, tipo),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS v2_notas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    folio VARCHAR(60) NOT NULL,
    folio_v1 VARCHAR(60) NULL,
    fecha_nota DATE NOT NULL,
    cliente_nombre VARCHAR(180) NOT NULL,
    negocio VARCHAR(180) NULL,
    domicilio VARCHAR(255) NULL,
    telefono VARCHAR(80) NULL,
    origen ENUM('whatsapp','mostrador','vendedor','llamada','facebook','otro') NOT NULL DEFAULT 'mostrador',
    origen_otro VARCHAR(120) NULL,
    vendedor_nombre VARCHAR(160) NULL,
    intermediario_nombre VARCHAR(160) NULL,
    disenador_id INT UNSIGNED NULL,
    fecha_promesa DATE NULL,
    fecha_instalacion DATE NULL,
    fecha_entrega DATE NULL,
    fecha_liquidacion DATE NULL,
    requiere_factura TINYINT(1) NOT NULL DEFAULT 0,
    estado_contacto ENUM('pendiente','contactado','no_aplica') NOT NULL DEFAULT 'pendiente',
    estado_diseno ENUM('sin_asignar','pendiente_contacto','en_diseno','en_aprobacion','aprobado','no_aplica') NOT NULL DEFAULT 'sin_asignar',
    estado_aprobacion_impresion ENUM('pendiente','autorizada','rechazada','no_aplica') NOT NULL DEFAULT 'pendiente',
    estado_produccion ENUM('pendiente','para_imprimir','impresa','sublimada','problema','no_aplica') NOT NULL DEFAULT 'pendiente',
    estado_instalacion ENUM('no_aplica','pendiente','programada','en_instalacion','instalada') NOT NULL DEFAULT 'no_aplica',
    estado_entrega ENUM('pendiente','lista','entregada','cancelada') NOT NULL DEFAULT 'pendiente',
    estado_pago ENUM('sin_pago','anticipo','parcial','liquidada','devolucion','cancelada') NOT NULL DEFAULT 'sin_pago',
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    pagado DECIMAL(12,2) NOT NULL DEFAULT 0,
    saldo DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_estimado_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_real_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    comision_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    merma_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    utilidad_estimada DECIMAL(12,2) NOT NULL DEFAULT 0,
    utilidad_real DECIMAL(12,2) NOT NULL DEFAULT 0,
    cancelacion_motivo TEXT NULL,
    devolucion_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    migrado_v1 TINYINT(1) NOT NULL DEFAULT 0,
    creado_por INT UNSIGNED NULL,
    actualizado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_v2_empresa_folio (empresa_id, folio),
    CONSTRAINT fk_v2_notas_empresa FOREIGN KEY (empresa_id) REFERENCES v2_empresas(id),
    CONSTRAINT fk_v2_notas_disenador FOREIGN KEY (disenador_id) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_notas_creado FOREIGN KEY (creado_por) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_notas_actualizado FOREIGN KEY (actualizado_por) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_nota),
    INDEX idx_empresa_fecha (empresa_id, fecha_nota),
    INDEX idx_disenador (disenador_id),
    INDEX idx_pago (estado_pago),
    INDEX idx_entrega (estado_entrega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS v2_nota_partidas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id INT UNSIGNED NOT NULL,
    empresa_id INT UNSIGNED NOT NULL,
    catalogo_id INT UNSIGNED NULL,
    tipo ENUM('promocion','bandera','lona','vinil','diseno_extra','instalacion','articulo','otro') NOT NULL DEFAULT 'articulo',
    descripcion TEXT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    precio_especial TINYINT(1) NOT NULL DEFAULT 0,
    descuento_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_estimado_material DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_estimado_mano_obra DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_estimado_maquila DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_estimado_instalacion DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_real_material DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_real_mano_obra DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_real_maquila DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_real_instalacion DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_v2_part_nota FOREIGN KEY (nota_id) REFERENCES v2_notas(id) ON DELETE CASCADE,
    CONSTRAINT fk_v2_part_empresa FOREIGN KEY (empresa_id) REFERENCES v2_empresas(id),
    CONSTRAINT fk_v2_part_catalogo FOREIGN KEY (catalogo_id) REFERENCES v2_catalogo_items(id) ON DELETE SET NULL,
    INDEX idx_nota (nota_id),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS v2_pagos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id INT UNSIGNED NOT NULL,
    empresa_id INT UNSIGNED NOT NULL,
    fecha_pago DATE NOT NULL,
    concepto ENUM('anticipo','abono','liquidacion','devolucion') NOT NULL DEFAULT 'abono',
    monto DECIMAL(12,2) NOT NULL,
    forma_pago ENUM('efectivo','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
    forma_pago_otro VARCHAR(120) NULL,
    referencia VARCHAR(180) NULL,
    comprobante VARCHAR(255) NULL,
    observaciones TEXT NULL,
    usuario_id INT UNSIGNED NULL,
    autorizado_por_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_v2_pagos_nota FOREIGN KEY (nota_id) REFERENCES v2_notas(id) ON DELETE CASCADE,
    CONSTRAINT fk_v2_pagos_empresa FOREIGN KEY (empresa_id) REFERENCES v2_empresas(id),
    CONSTRAINT fk_v2_pagos_usuario FOREIGN KEY (usuario_id) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_pagos_autorizado FOREIGN KEY (autorizado_por_id) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_forma (forma_pago),
    INDEX idx_empresa_fecha (empresa_id, fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS v2_mermas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id INT UNSIGNED NOT NULL,
    empresa_id INT UNSIGNED NOT NULL,
    fecha_merma DATE NOT NULL,
    tipo ENUM('papel_perdido','tela_perdida','tinta_desperdiciada','reimpresion','error_diseno','error_impresion','cliente_cancelo','otro') NOT NULL DEFAULT 'otro',
    area ENUM('diseno','impresion','sublimacion','instalacion','administracion','cliente','otro') NOT NULL DEFAULT 'otro',
    descripcion TEXT NOT NULL,
    responsable_probable_id INT UNSIGNED NULL,
    reportado_por_id INT UNSIGNED NULL,
    costo_estimado DECIMAL(12,2) NOT NULL DEFAULT 0,
    costo_real DECIMAL(12,2) NOT NULL DEFAULT 0,
    afecta_ganancia TINYINT(1) NOT NULL DEFAULT 1,
    solucion TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_v2_merma_nota FOREIGN KEY (nota_id) REFERENCES v2_notas(id) ON DELETE CASCADE,
    CONSTRAINT fk_v2_merma_empresa FOREIGN KEY (empresa_id) REFERENCES v2_empresas(id),
    CONSTRAINT fk_v2_merma_resp FOREIGN KEY (responsable_probable_id) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_merma_reporta FOREIGN KEY (reportado_por_id) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_merma),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS v2_comisiones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id INT UNSIGNED NOT NULL,
    partida_id INT UNSIGNED NULL,
    empresa_id INT UNSIGNED NOT NULL,
    disenador_id INT UNSIGNED NOT NULL,
    tipo ENUM('no_aplica','bandera','diseno_extra','logo','lona','otro') NOT NULL DEFAULT 'no_aplica',
    aplica TINYINT(1) NOT NULL DEFAULT 0,
    monto DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado ENUM('pendiente','pagada','no_aplica') NOT NULL DEFAULT 'pendiente',
    fecha_semana DATE NULL,
    fecha_pago DATE NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_v2_com_nota FOREIGN KEY (nota_id) REFERENCES v2_notas(id) ON DELETE CASCADE,
    CONSTRAINT fk_v2_com_partida FOREIGN KEY (partida_id) REFERENCES v2_nota_partidas(id) ON DELETE SET NULL,
    CONSTRAINT fk_v2_com_empresa FOREIGN KEY (empresa_id) REFERENCES v2_empresas(id),
    CONSTRAINT fk_v2_com_disenador FOREIGN KEY (disenador_id) REFERENCES v2_usuarios(id),
    INDEX idx_disenador_estado (disenador_id, estado),
    INDEX idx_semana (fecha_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS v2_estado_historial (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nota_id INT UNSIGNED NOT NULL,
    campo VARCHAR(80) NOT NULL,
    valor_anterior VARCHAR(120) NULL,
    valor_nuevo VARCHAR(120) NOT NULL,
    comentario TEXT NULL,
    usuario_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_v2_hist_nota FOREIGN KEY (nota_id) REFERENCES v2_notas(id) ON DELETE CASCADE,
    CONSTRAINT fk_v2_hist_usuario FOREIGN KEY (usuario_id) REFERENCES v2_usuarios(id) ON DELETE SET NULL,
    INDEX idx_nota_fecha (nota_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO v2_empresas (clave, nombre, folio_prefijo, activo) VALUES
('viprint','ViPrint Publicidad','VP',1),
('imagen','Imagen','IMG',1)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), folio_prefijo=VALUES(folio_prefijo), activo=VALUES(activo);

-- Contraseña inicial: 123456. Cambiar contraseñas después de instalar.
INSERT INTO v2_usuarios (nombre, usuario, password_hash, rol, puede_ver_finanzas, puede_editar_precios, puede_borrar, es_disenador, activo) VALUES
('Administrador','admin','$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG','admin',1,1,1,0,1),
('Luis Alvaro Gomez Vinajera','luis','$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG','direccion',1,1,1,0,1),
('Fernanda Danae Rentería Enriquez','danae','$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG','operativo',0,0,0,0,1),
('María Fernanda Briones Rodríguez','mafer','$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG','administracion',1,1,0,0,1),
('Eduardo Corpus','eduardo','$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG','asesor',1,1,0,0,1),
('Angel Uriel Peña Salazar','angel','$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG','disenador',0,0,0,1,1),
('Andrea','andrea','$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG','externo',0,0,0,1,1),
('Jaquelin','jaquelin','$2y$12$B90/8ZLKUoSclTJj6BC/LuNUmq4GwEBlwRUS1XXwKFyK.iulyJoqG','externo',0,0,0,1,1)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), rol=VALUES(rol), puede_ver_finanzas=VALUES(puede_ver_finanzas), puede_editar_precios=VALUES(puede_editar_precios), puede_borrar=VALUES(puede_borrar), es_disenador=VALUES(es_disenador), activo=VALUES(activo);

-- Promociones ViPrint. Todas incluyen diseño e instalación gratis dentro de Aguascalientes.
INSERT INTO v2_catalogo_items (empresa_id, tipo, nombre, descripcion, precio_base, incluye_diseno, incluye_instalacion, permite_precio_especial, activo)
SELECT e.id, 'promocion', x.nombre, x.descripcion, x.precio, 1, 1, 1, 1
FROM v2_empresas e
JOIN (
    SELECT 'Promo Glass' nombre, '2 telas de 3 metros con impresión de una vista, más estructura de acero; con estructura queda de 3.5m.' descripcion, 1400.00 precio UNION ALL
    SELECT 'Promo Buzz', '2 banderas de 3m x 70cm, doble tela, doble impresión, estructura de acero, más lona de 1.5m x 1.5m.', 1950.00 UNION ALL
    SELECT 'Promo Beta', '2 banderas de 3m x 70cm, doble tela, doble impresión, estructura de acero.', 1450.00 UNION ALL
    SELECT 'Promo Sky', '4 banderas de 3.5m x 70cm, telas doble vista, una impresión, más estructura de acero.', 1999.00 UNION ALL
    SELECT 'Promo Pixel', '2 banderas de 4m x 70cm, doble vista, doble impresión, más estructura de acero.', 2100.00 UNION ALL
    SELECT 'Promo Nube', '2 banderas de 3.5m x 70cm, doble tela, doble impresión, más estructura de acero.', 1800.00 UNION ALL
    SELECT 'Promo Nebula', '2 banderas de 3m x 70cm, doble tela, doble impresión, estructura de acero, más banner de 1.60m x 60cm.', 2190.00 UNION ALL
    SELECT 'Promo Maxiventas', '4 banderas de 3.5m x 70cm, doble impresión, 4 estructuras de acero.', 2499.00 UNION ALL
    SELECT 'Promo Light', '2 banderas de 3m x 70cm, doble tela, doble impresión, estructura de acero, más orilla reflejante.', 2190.00 UNION ALL
    SELECT 'Promo Rush', '1 bandera publicitaria, doble tela, doble vista, más orilla reflejante.', 1490.00 UNION ALL
    SELECT 'Promo Activación', '1 carpa personalizada 3m x 3m, más 2 banderas de 2.5m x 70cm de una sola vista, más estructura de acero.', 6900.00 UNION ALL
    SELECT 'Super Promo', '2 skydancer de 3.5m de alto, más 2 motores, más una tela de repuesto.', 4500.00 UNION ALL
    SELECT 'Promo Gamma', '4 playeras full print personalizadas, más una bandera publicitaria doble vista, doble impresión.', 2100.00
) x
WHERE e.clave='viprint'
ON DUPLICATE KEY UPDATE precio_base=VALUES(precio_base);

-- Productos base Imagen. Imagen maneja productos similares a ViPrint, pero sin promociones.
INSERT INTO v2_catalogo_items (empresa_id, tipo, nombre, descripcion, precio_base, incluye_diseno, incluye_instalacion, permite_precio_especial, activo)
SELECT e.id, x.tipo, x.nombre, x.descripcion, x.precio, 1, 1, 1, 1
FROM v2_empresas e
JOIN (
    SELECT 'bandera' AS tipo, 'Bandera grande' AS nombre, 'Bandera grande con estructura de acero. Precio base por unidad.' AS descripcion, 1700.00 AS precio UNION ALL
    SELECT 'bandera' AS tipo, 'Bandera mediana' AS nombre, 'Bandera mediana con estructura de acero. Precio base por unidad.' AS descripcion, 1500.00 AS precio UNION ALL
    SELECT 'bandera' AS tipo, 'Bandera jumbo' AS nombre, 'Bandera jumbo con estructura de acero. Precio base por unidad.' AS descripcion, 1900.00 AS precio UNION ALL
    SELECT 'bandera' AS tipo, 'Tela de bandera sin estructura' AS nombre, 'Tela por bandera sin estructura de acero.' AS descripcion, 1000.00 AS precio UNION ALL
    SELECT 'lona' AS tipo, 'Lona' AS nombre, 'Lona publicitaria. Capturar medida, material y acabado.' AS descripcion, 0.00 AS precio UNION ALL
    SELECT 'articulo' AS tipo, 'Artículo libre' AS nombre, 'Artículo manual para productos no precargados.' AS descripcion, 0.00 AS precio
) AS x
WHERE e.clave='imagen'
ON DUPLICATE KEY UPDATE
    descripcion=VALUES(descripcion),
    precio_base=VALUES(precio_base),
    incluye_diseno=VALUES(incluye_diseno),
    incluye_instalacion=VALUES(incluye_instalacion),
    permite_precio_especial=VALUES(permite_precio_especial),
    activo=VALUES(activo);
