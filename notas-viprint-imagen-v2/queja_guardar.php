<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/actualizacion_20260924_helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_to('consulta_pedido.php');
$nota_id = (int)($_POST['nota_id'] ?? 0);
$folio = strtoupper(trim((string)($_POST['folio'] ?? '')));
$codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
try {
    if (empty($_POST['confirma'])) throw new Exception('Debes confirmar que es tu nota.');
    $stmt = db()->prepare('SELECT n.*, e.id empresa_id FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.id=? AND UPPER(n.folio)=?');
    $stmt->execute(array($nota_id, $folio));
    $nota = $stmt->fetch();
    if (!$nota) throw new Exception('Nota no encontrada.');
    if (function_exists('column_exists') && column_exists('v2_notas','public_code') && strtoupper((string)$nota['public_code']) !== $codigo) throw new Exception('Código incorrecto.');
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $telefono = trim((string)($_POST['telefono'] ?? ''));
    $tipo = trim((string)($_POST['tipo'] ?? 'comentario'));
    if (!in_array($tipo, array('queja','sugerencia','comentario','duda'), true)) $tipo = 'comentario';
    $mensaje = trim((string)($_POST['mensaje'] ?? ''));
    if ($nombre === '' || $telefono === '' || $mensaje === '') throw new Exception('Faltan datos obligatorios.');
    $foto = vp_queja_save_photo('foto', $folio);
    $stmt = db()->prepare("INSERT INTO v2_quejas_sugerencias (nota_id,empresa_id,folio,public_code,nombre,telefono,tipo,mensaje,foto_path,estado,created_at) VALUES (?,?,?,?,?,?,?,?,?,'nueva',NOW())");
    $stmt->execute(array($nota_id,(int)$nota['empresa_id'],$folio,$codigo,$nombre,$telefono,$tipo,$mensaje,$foto));
} catch (Exception $e) {
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Error</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#F8F6F8"><div class="container py-5" style="max-width:720px"><div class="alert alert-danger"><strong>No se pudo enviar.</strong><br>'.h($e->getMessage()).'</div><a class="btn btn-secondary" href="javascript:history.back()">Volver</a></div></body></html>'; exit;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mensaje enviado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#F8F6F8"><div class="container py-5" style="max-width:720px"><div class="card shadow-sm"><div class="card-body p-4 text-center"><h1 class="h4 text-success">Mensaje enviado</h1><p>Gracias. ViPrint recibió tu mensaje y lo revisará internamente.</p><a class="btn btn-primary" href="<?= h(vp_public_pedido_url($nota)) ?>">Volver a mi pedido</a></div></div></div></body></html>
