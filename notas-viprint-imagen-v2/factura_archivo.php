<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/facturacion_helpers.php';
require_login();
if (!factura_can_manage()) { http_response_code(403); exit('No tienes permiso.'); }
$id = (int)($_GET['id'] ?? 0);
$tipo = (string)($_GET['tipo'] ?? 'constancia');
$fields = array('constancia'=>'constancia_archivo','pdf'=>'factura_pdf','xml'=>'factura_xml');
if (!isset($fields[$tipo])) { http_response_code(400); exit('Tipo de archivo no válido.'); }
$stmt = db()->prepare('SELECT ' . $fields[$tipo] . ' archivo FROM v2_factura_solicitudes WHERE id = ?');
$stmt->execute(array($id));
$rel = (string)$stmt->fetchColumn();
if ($rel === '') { http_response_code(404); exit('Archivo no encontrado.'); }
factura_stream_file($rel);
