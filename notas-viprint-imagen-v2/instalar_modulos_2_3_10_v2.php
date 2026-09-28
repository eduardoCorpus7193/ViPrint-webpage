<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$u = current_user();
if (!$u || !in_array($u['rol'], array('admin','direccion','administracion','asesor'), true)) {
    http_response_code(403);
    exit('No tienes permiso para instalar esta actualización. Entra como admin, Luis, Mafer o Eduardo.');
}
if (($_GET['clave'] ?? '') !== 'modulos2310') {
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instalador</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#F8F6F8"><div class="container py-5" style="max-width:900px"><div class="card shadow-sm"><div class="card-body p-4"><h1 class="h4 text-danger">Clave requerida</h1><p>Abre el instalador así:</p><code>instalar_modulos_2_3_10_v2.php?clave=modulos2310</code></div></div></div></body></html>';
    exit;
}

function m2310_inst_add_col($table, $column, $definition) {
    if (!column_exists($table, $column)) {
        db()->exec("ALTER TABLE `$table` ADD COLUMN $definition");
        return 'Agregada columna ' . $table . '.' . $column;
    }
    return 'Ya existe columna ' . $table . '.' . $column;
}

$messages = array();
$warnings = array();
try {
    $pdo = db();

    if (!table_exists('v2_caja_movimientos')) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS v2_caja_movimientos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT UNSIGNED NOT NULL,
            nota_id INT UNSIGNED NULL,
            pago_id INT UNSIGNED NULL,
            fecha_operacion DATE NOT NULL,
            hora_operacion TIME NULL,
            tipo ENUM('entrada','salida') NOT NULL DEFAULT 'entrada',
            concepto ENUM('pago_cliente','devolucion_cliente','gasto','uber_envio','entrega_luis','prestamo_cambio','compra_menor','ajuste_caja','retiro','ajuste','otro') NOT NULL DEFAULT 'pago_cliente',
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
            UNIQUE KEY uq_v2_caja_pago (pago_id),
            INDEX idx_v2_caja_fecha (fecha_operacion),
            INDEX idx_v2_caja_empresa_fecha (empresa_id, fecha_operacion),
            INDEX idx_v2_caja_forma (forma_pago),
            INDEX idx_v2_caja_tipo (tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $messages[] = 'Tabla v2_caja_movimientos creada.';
    } else {
        $messages[] = 'Tabla v2_caja_movimientos encontrada.';
    }

    try {
        $pdo->exec("ALTER TABLE v2_caja_movimientos MODIFY COLUMN concepto ENUM('pago_cliente','devolucion_cliente','gasto','uber_envio','entrega_luis','prestamo_cambio','compra_menor','ajuste_caja','retiro','ajuste','otro') NOT NULL DEFAULT 'pago_cliente'");
        $messages[] = 'Conceptos de caja actualizados.';
    } catch (Exception $e) {
        $warnings[] = 'No se pudo modificar el ENUM de conceptos de caja: ' . $e->getMessage();
    }

    if (!table_exists('v2_cortes_caja')) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS v2_cortes_caja (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $messages[] = 'Tabla v2_cortes_caja creada.';
    } else {
        $messages[] = 'Tabla v2_cortes_caja encontrada.';
        $cols = array(
            'fondo_inicial' => "fondo_inicial DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER fecha_corte",
            'fondo_base' => "fondo_base DECIMAL(12,2) NOT NULL DEFAULT 800 AFTER fondo_inicial",
            'entradas_efectivo' => "entradas_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0",
            'salidas_efectivo' => "salidas_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0",
            'salidas_efectivo_operativas' => "salidas_efectivo_operativas DECIMAL(12,2) NOT NULL DEFAULT 0",
            'entrega_luis_sistema' => "entrega_luis_sistema DECIMAL(12,2) NOT NULL DEFAULT 0",
            'entradas_transferencia' => "entradas_transferencia DECIMAL(12,2) NOT NULL DEFAULT 0",
            'entradas_tarjeta' => "entradas_tarjeta DECIMAL(12,2) NOT NULL DEFAULT 0",
            'entradas_otro' => "entradas_otro DECIMAL(12,2) NOT NULL DEFAULT 0",
            'salidas_transferencia' => "salidas_transferencia DECIMAL(12,2) NOT NULL DEFAULT 0",
            'salidas_tarjeta' => "salidas_tarjeta DECIMAL(12,2) NOT NULL DEFAULT 0",
            'salidas_otro' => "salidas_otro DECIMAL(12,2) NOT NULL DEFAULT 0",
            'total_entradas' => "total_entradas DECIMAL(12,2) NOT NULL DEFAULT 0",
            'total_salidas' => "total_salidas DECIMAL(12,2) NOT NULL DEFAULT 0",
            'caja_esperada' => "caja_esperada DECIMAL(12,2) NOT NULL DEFAULT 0",
            'efectivo_contado' => "efectivo_contado DECIMAL(12,2) NOT NULL DEFAULT 0",
            'diferencia_efectivo' => "diferencia_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0",
            'entrega_luis_sugerida' => "entrega_luis_sugerida DECIMAL(12,2) NOT NULL DEFAULT 0",
            'entrega_luis_real' => "entrega_luis_real DECIMAL(12,2) NOT NULL DEFAULT 0",
            'fondo_final' => "fondo_final DECIMAL(12,2) NOT NULL DEFAULT 0",
            'observaciones' => "observaciones TEXT NULL",
            'entrega_nombre' => "entrega_nombre VARCHAR(160) NULL",
            'recibe_nombre' => "recibe_nombre VARCHAR(160) NULL",
            'hora_entrega' => "hora_entrega TIME NULL",
            'cerrado' => "cerrado TINYINT(1) NOT NULL DEFAULT 0",
            'realizado_por' => "realizado_por INT UNSIGNED NULL",
            'cerrado_por' => "cerrado_por INT UNSIGNED NULL",
            'cerrado_at' => "cerrado_at DATETIME NULL",
            'entrega_movimiento_id' => "entrega_movimiento_id INT UNSIGNED NULL"
        );
        foreach ($cols as $c => $def) {
            $messages[] = m2310_inst_add_col('v2_cortes_caja', $c, $def);
        }
    }

    try { $pdo->exec("CREATE INDEX idx_v2_cortes_fecha_cerrado ON v2_cortes_caja (fecha_corte, cerrado)"); } catch (Exception $e) {}
    try { $pdo->exec("CREATE INDEX idx_v2_caja_fecha_concepto ON v2_caja_movimientos (fecha_operacion, concepto)"); } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE v2_notas MODIFY COLUMN estado_contacto ENUM('pendiente','contactado','cliente_no_contesta','no_aplica') NOT NULL DEFAULT 'pendiente'");
        $messages[] = 'Contacto permite Cliente no contesta.';
    } catch (Exception $e) { $warnings[] = 'No se pudo actualizar estado_contacto: ' . $e->getMessage(); }

    try {
        $pdo->exec("ALTER TABLE v2_notas MODIFY COLUMN estado_produccion ENUM('pendiente','para_imprimir','impresa','sublimada','en_costura','terminada','problema','no_aplica') NOT NULL DEFAULT 'pendiente'");
        $messages[] = 'Producción permite En costura y Terminada.';
    } catch (Exception $e) { $warnings[] = 'No se pudo actualizar estado_produccion: ' . $e->getMessage(); }

    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Módulos instalados</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#F8F6F8"><div class="container py-5" style="max-width:980px"><div class="card shadow-sm"><div class="card-body p-4">';
    echo '<h1 class="h4 text-success">Actualización instalada</h1>';
    echo '<p>Quedaron listos los módulos: corte diario mejorado, estado de pedidos y reportes generales.</p>';
    echo '<div class="row g-2 mb-3"><div class="col-md-4"><a class="btn btn-primary w-100" href="'.h(url('corte_diario.php')).'">Corte diario</a></div><div class="col-md-4"><a class="btn btn-outline-primary w-100" href="'.h(url('estado_pedidos.php')).'">Estado pedidos</a></div><div class="col-md-4"><a class="btn btn-outline-primary w-100" href="'.h(url('reportes_generales.php')).'">Reportes generales</a></div></div>';
    echo '<h2 class="h6">Resultado</h2><pre class="bg-light border rounded p-3 small" style="white-space:pre-wrap">'.h(implode("\n", $messages)).'</pre>';
    if ($warnings) echo '<h2 class="h6 text-warning">Avisos</h2><pre class="bg-warning-subtle border rounded p-3 small" style="white-space:pre-wrap">'.h(implode("\n", $warnings)).'</pre>';
    echo '<p class="text-danger mb-0"><strong>Importante:</strong> elimina del servidor <code>instalar_modulos_2_3_10_v2.php</code> cuando termines.</p>';
    echo '</div></div></div></body></html>';
} catch (Exception $e) {
    app_error_page('No se pudo instalar la actualización', 'Revisa el error antes de seguir.', $e->getMessage());
}
