<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/facturacion_helpers.php';
require_login();
if (!factura_can_manage()) { http_response_code(403); exit('No tienes permiso.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_to('facturas.php');
$id = (int)($_POST['id'] ?? 0);
$estado = (string)($_POST['estado'] ?? 'pendiente');
$allowed = array('pendiente','en_revision','datos_incorrectos','facturada','cancelada');
if (!in_array($estado, $allowed, true)) $estado = 'pendiente';
try {
    $stmt = db()->prepare('SELECT * FROM v2_factura_solicitudes WHERE id = ?');
    $stmt->execute(array($id));
    $sol = $stmt->fetch();
    if (!$sol) throw new Exception('Solicitud no encontrada.');

    $pdf = factura_safe_upload('factura_pdf', 'emitidas', array('pdf'), 'factura_pdf_' . $id);
    $xml = factura_safe_upload('factura_xml', 'emitidas', array('xml'), 'factura_xml_' . $id);

    $sets = array('estado = ?', 'uuid = ?', 'comentarios_internos = ?', 'fecha_actualizacion = NOW()', 'revisado_por = ?');
    $params = array($estado, trim((string)($_POST['uuid'] ?? '')), trim((string)($_POST['comentarios_internos'] ?? '')), current_user()['id'] ?? null);
    if ($estado === 'facturada') {
        $sets[] = 'facturada_por = COALESCE(facturada_por, ?)';
        $params[] = current_user()['id'] ?? null;
    }
    if ($pdf !== '') { $sets[] = 'factura_pdf = ?'; $params[] = $pdf; }
    if ($xml !== '') { $sets[] = 'factura_xml = ?'; $params[] = $xml; }
    $params[] = $id;
    $sql = 'UPDATE v2_factura_solicitudes SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    flash('success','Solicitud actualizada correctamente.');
} catch (Exception $e) {
    flash('danger','No se pudo actualizar: ' . $e->getMessage());
}
redirect_to('factura_ver.php?id=' . $id);
