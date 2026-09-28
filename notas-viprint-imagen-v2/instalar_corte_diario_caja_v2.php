<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$u = current_user();
if (!$u || !in_array($u['rol'], array('admin','direccion','administracion'), true)) {
    flash('danger', 'Solo admin, dirección o administración pueden instalar esta actualización.');
    redirect_to('index.php');
}

$clave = $_GET['clave'] ?? '';
if ($clave !== 'corte2026') {
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instalador corte diario</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#F8F6F8"><div class="container py-5" style="max-width:820px"><div class="card shadow-sm"><div class="card-body p-4"><h1 class="h4 text-danger">Clave requerida</h1><p>Abre el instalador con la clave correcta:</p><code>instalar_corte_diario_caja_v2.php?clave=corte2026</code></div></div></div></body></html>';
    exit;
}

$pdo = db();
$ok = array();
$warnings = array();

try {
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
    $ok[] = 'Tabla v2_cortes_caja lista.';

    if (table_exists('v2_caja_movimientos')) {
        $pdo->exec("ALTER TABLE v2_caja_movimientos MODIFY COLUMN concepto ENUM('pago_cliente','devolucion_cliente','gasto','uber_envio','entrega_luis','prestamo_cambio','compra_menor','ajuste_caja','retiro','ajuste','otro') NOT NULL DEFAULT 'pago_cliente'");
        $ok[] = 'Conceptos de caja actualizados: gasto, Uber/envío, devolución, entrega a Luis, préstamo/cambio, compra menor, ajuste y otro.';
        try { $pdo->exec("CREATE INDEX idx_v2_caja_concepto_fecha ON v2_caja_movimientos (concepto, fecha_operacion)"); } catch (Exception $e) {}
    } else {
        $warnings[] = 'No existe v2_caja_movimientos. Primero debe estar instalada la actualización de caja.';
    }

} catch (Exception $e) {
    app_error_page('Error al instalar corte diario', 'No se pudo completar la instalación.', $e->getMessage());
}

?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instalación corte diario</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body style="background:#F8F6F8"><div class="container py-5" style="max-width:900px"><div class="card shadow-sm"><div class="card-body p-4">
<h1 class="h4 text-success">Actualización instalada</h1>
<?php foreach($ok as $msg): ?><div class="alert alert-success mb-2"><?= h($msg) ?></div><?php endforeach; ?>
<?php foreach($warnings as $msg): ?><div class="alert alert-warning mb-2"><?= h($msg) ?></div><?php endforeach; ?>
<p class="mb-2">Ya puedes entrar a <a href="<?= h(url('corte_diario.php')) ?>">Corte diario</a>.</p>
<p class="text-danger mb-0"><strong>Importante:</strong> elimina del servidor este archivo: <code>instalar_corte_diario_caja_v2.php</code>.</p>
</div></div></div></body></html>
