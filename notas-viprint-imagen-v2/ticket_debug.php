<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$pago_id = (int)($_GET['pago_id'] ?? ($_GET['id'] ?? 0));
header('Content-Type: text/plain; charset=utf-8');
echo "DEBUG TICKET VIPRINT\n";
echo "Pago ID recibido: " . $pago_id . "\n";
echo "Base de datos: " . db()->query('SELECT DATABASE()')->fetchColumn() . "\n\n";
if ($pago_id <= 0) {
    echo "ERROR: Falta pago_id. Abre ticket_debug.php?pago_id=ID_DEL_PAGO\n";
    exit;
}
$sql = "SELECT p.*, n.folio, n.cliente_nombre, n.telefono, n.total, n.pagado, n.saldo, e.nombre empresa
FROM v2_pagos p
JOIN v2_notas n ON n.id = p.nota_id
JOIN v2_empresas e ON e.id = p.empresa_id
WHERE p.id = ? LIMIT 1";
try {
    $stmt = db()->prepare($sql);
    $stmt->execute([$pago_id]);
    $p = $stmt->fetch();
    if (!$p) {
        echo "ERROR: No existe pago con ese ID en esta base.\n";
        exit;
    }
    echo "Pago encontrado.\n";
    echo "Empresa: " . ($p['empresa'] ?? '') . "\n";
    echo "Nota: " . ($p['folio'] ?? '') . "\n";
    echo "Cliente: " . ($p['cliente_nombre'] ?? '') . "\n";
    echo "Fecha pago: " . ($p['fecha_pago'] ?? '') . "\n";
    echo "Concepto: " . ($p['concepto'] ?? '') . "\n";
    echo "Forma pago: " . ($p['forma_pago'] ?? '') . "\n";
    echo "Monto: " . ($p['monto'] ?? '') . "\n";
    echo "Total: " . ($p['total'] ?? '') . "\n";
    echo "Pagado: " . ($p['pagado'] ?? '') . "\n";
    echo "Saldo: " . ($p['saldo'] ?? '') . "\n\n";
    echo "Si estos datos aparecen, la consulta funciona. El problema era que ticket_pago.php o qz-viprint.js no estaban actualizados o estaban en cache.\n";
} catch (Throwable $e) {
    echo "ERROR SQL/PHP:\n" . $e->getMessage() . "\n";
}
