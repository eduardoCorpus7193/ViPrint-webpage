<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!current_user() || current_user()['rol'] !== 'admin') { flash('danger','Solo admin puede instalar esta actualización.'); redirect_to('index.php'); }
if (($_GET['clave'] ?? '') !== 'admin2026') { echo 'Clave incorrecta.'; exit; }

function add_col_if_missing($table, $column, $sql) {
    if (!column_exists($table, $column)) {
        db()->exec("ALTER TABLE $table ADD COLUMN $sql");
        return true;
    }
    return false;
}

$changes = array();
try {
    add_col_if_missing('v2_pagos','anulado', "anulado TINYINT(1) NOT NULL DEFAULT 0 AFTER monto") && $changes[]='v2_pagos.anulado';
    add_col_if_missing('v2_pagos','monto_original', "monto_original DECIMAL(12,2) NULL AFTER monto") && $changes[]='v2_pagos.monto_original';
    add_col_if_missing('v2_pagos','anulado_por', "anulado_por INT UNSIGNED NULL AFTER observaciones") && $changes[]='v2_pagos.anulado_por';
    add_col_if_missing('v2_pagos','anulado_at', "anulado_at DATETIME NULL AFTER anulado_por") && $changes[]='v2_pagos.anulado_at';
    add_col_if_missing('v2_pagos','anulacion_motivo', "anulacion_motivo TEXT NULL AFTER anulado_at") && $changes[]='v2_pagos.anulacion_motivo';

    if (table_exists('v2_caja_movimientos')) {
        add_col_if_missing('v2_caja_movimientos','anulado', "anulado TINYINT(1) NOT NULL DEFAULT 0 AFTER monto") && $changes[]='v2_caja_movimientos.anulado';
        add_col_if_missing('v2_caja_movimientos','monto_original', "monto_original DECIMAL(12,2) NULL AFTER monto") && $changes[]='v2_caja_movimientos.monto_original';
        add_col_if_missing('v2_caja_movimientos','anulado_por', "anulado_por INT UNSIGNED NULL AFTER autorizado_por_id") && $changes[]='v2_caja_movimientos.anulado_por';
        add_col_if_missing('v2_caja_movimientos','anulado_at', "anulado_at DATETIME NULL AFTER anulado_por") && $changes[]='v2_caja_movimientos.anulado_at';
        add_col_if_missing('v2_caja_movimientos','anulacion_motivo', "anulacion_motivo TEXT NULL AFTER anulado_at") && $changes[]='v2_caja_movimientos.anulacion_motivo';
    }

    add_col_if_missing('v2_notas','eliminada', "eliminada TINYINT(1) NOT NULL DEFAULT 0 AFTER observaciones") && $changes[]='v2_notas.eliminada';
    add_col_if_missing('v2_notas','eliminado_por', "eliminado_por INT UNSIGNED NULL AFTER eliminada") && $changes[]='v2_notas.eliminado_por';
    add_col_if_missing('v2_notas','eliminado_at', "eliminado_at DATETIME NULL AFTER eliminado_por") && $changes[]='v2_notas.eliminado_at';
    add_col_if_missing('v2_notas','eliminacion_motivo', "eliminacion_motivo TEXT NULL AFTER eliminado_at") && $changes[]='v2_notas.eliminacion_motivo';

    db()->exec("CREATE TABLE IF NOT EXISTS v2_auditoria_admin (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        accion VARCHAR(80) NOT NULL,
        entidad VARCHAR(80) NOT NULL,
        entidad_id INT UNSIGNED NOT NULL,
        nota_id INT UNSIGNED NULL,
        motivo TEXT NOT NULL,
        datos_antes LONGTEXT NULL,
        usuario_id INT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_entidad (entidad, entidad_id),
        INDEX idx_audit_nota (nota_id),
        INDEX idx_audit_usuario (usuario_id),
        INDEX idx_audit_accion (accion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $changes[]='v2_auditoria_admin';

    echo '<!doctype html><meta charset="utf-8"><body style="font-family:Arial;padding:30px">';
    echo '<h2>Actualización instalada</h2>';
    echo '<p>Se agregaron permisos y campos para anular pagos y eliminar notas con historial.</p>';
    echo '<pre>'.h(implode("\n", $changes)).'</pre>';
    echo '<p><strong>Elimina este archivo del servidor:</strong> instalar_admin_anulaciones_v2.php</p>';
    echo '<p><a href="'.h(url('admin_correcciones.php')).'">Ir a Correcciones admin</a></p>';
    echo '</body>';
} catch (Exception $e) {
    app_error_page('No se pudo instalar', 'Revisa el error y no sigas usando el instalador hasta corregirlo.', $e->getMessage());
}
