<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/facturacion_helpers.php';
require_login();
if (!factura_can_manage()) { http_response_code(403); exit('No tienes permiso.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_to('facturas.php');
$nota_id = (int)($_POST['nota_id'] ?? 0);
$requiere = (int)($_POST['requiere_factura'] ?? 0) === 1 ? 1 : 0;
try {
    factura_mark_note($nota_id, $requiere, current_user()['id'] ?? null);
    flash('success', $requiere ? 'La nota quedó marcada como requiere factura. El cliente ya puede subir sus datos.' : 'Se quitó la opción de factura para esta nota.');
} catch (Exception $e) {
    flash('danger', 'No se pudo actualizar factura: ' . $e->getMessage());
}
redirect_to('facturas.php?q=' . urlencode((string)($_POST['q'] ?? '')));
