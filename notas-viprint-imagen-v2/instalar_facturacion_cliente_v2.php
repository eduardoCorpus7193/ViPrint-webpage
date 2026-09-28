<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/facturacion_helpers.php';
require_login();

$u = current_user();
if (!$u || !in_array($u['rol'], array('admin','direccion','administracion','asesor'), true)) {
    http_response_code(403);
    exit('No tienes permiso para ejecutar esta actualización. Entra como admin, dirección, administración o Eduardo.');
}
$clave = isset($_GET['clave']) ? $_GET['clave'] : '';
if ($clave !== 'factura2026') {
    exit('Clave incorrecta. Usa ?clave=factura2026');
}

function ih($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function add_column_if_missing($table, $column, $definition) {
    if (column_exists($table, $column)) return array($column, 'sin cambios', 'La columna ya existía.');
    db()->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $definition);
    return array($column, 'agregado', 'Columna agregada correctamente.');
}
function patch_file_factura($path, $callback) {
    $label = str_replace(__DIR__ . '/', '', $path);
    if (!file_exists($path)) return array($label, 'omitido', 'No se encontró el archivo.');
    if (!is_readable($path) || !is_writable($path)) return array($label, 'error', 'No tiene permisos de lectura/escritura.');
    $original = file_get_contents($path);
    $updated = $callback($original);
    if ($updated === false || $updated === $original) return array($label, 'sin cambios', 'Ya estaba actualizado o no se encontró el patrón.');
    @copy($path, $path . '.bak_factura_' . date('Ymd_His'));
    file_put_contents($path, $updated);
    return array($label, 'actualizado', 'Archivo actualizado.');
}

$messages = array();
try {
    if (!column_exists('v2_notas','requiere_factura')) {
        $messages[] = add_column_if_missing('v2_notas', 'requiere_factura', 'requiere_factura TINYINT(1) NOT NULL DEFAULT 0 AFTER fecha_instalacion');
    } else {
        $messages[] = array('requiere_factura','sin cambios','La columna ya existía.');
    }
    $messages[] = add_column_if_missing('v2_notas', 'factura_requiere_fecha', 'factura_requiere_fecha DATETIME NULL AFTER requiere_factura');
    $messages[] = add_column_if_missing('v2_notas', 'factura_limite_datos', 'factura_limite_datos DATETIME NULL AFTER factura_requiere_fecha');
    $messages[] = add_column_if_missing('v2_notas', 'factura_requiere_por', 'factura_requiere_por INT NULL AFTER factura_limite_datos');

    db()->exec("CREATE TABLE IF NOT EXISTS v2_factura_solicitudes (
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
    )");
    $messages[] = array('v2_factura_solicitudes','agregado','Tabla de solicitudes lista.');

    $cols = array(
        'public_code' => 'public_code VARCHAR(20) NULL',
        'codigo_postal_fiscal' => 'codigo_postal_fiscal VARCHAR(10) NULL',
        'uuid' => 'uuid VARCHAR(100) NULL',
        'factura_pdf' => 'factura_pdf VARCHAR(255) NULL',
        'factura_xml' => 'factura_xml VARCHAR(255) NULL',
        'comentarios_internos' => 'comentarios_internos TEXT NULL',
        'revisado_por' => 'revisado_por INT NULL',
        'facturada_por' => 'facturada_por INT NULL',
        'fecha_actualizacion' => 'fecha_actualizacion DATETIME NULL'
    );
    foreach ($cols as $col => $def) {
        if (!column_exists('v2_factura_solicitudes',$col)) {
            db()->exec('ALTER TABLE v2_factura_solicitudes ADD COLUMN ' . $def);
            $messages[] = array('v2_factura_solicitudes.'.$col,'agregado','Columna agregada.');
        }
    }

    db()->exec("UPDATE v2_notas
        SET factura_requiere_fecha = COALESCE(factura_requiere_fecha, NOW()),
            factura_limite_datos = COALESCE(factura_limite_datos, DATE_ADD(NOW(), INTERVAL 72 HOUR))
        WHERE requiere_factura = 1");
    $messages[] = array('notas existentes','actualizado','Las notas que ya estaban marcadas con factura recibieron fecha límite si no tenían.');

    factura_ensure_upload_dirs();
    $messages[] = array('uploads/facturas','listo','Carpetas de constancias y facturas emitidas listas.');
} catch (Exception $e) {
    $messages[] = array('base de datos','error',$e->getMessage());
}

$messages[] = patch_file_factura(__DIR__ . '/includes/header.php', function($s) {
    if (strpos($s, 'facturas.php') !== false) return $s;
    $line = "        <li class=\"nav-item\"><a class=\"nav-link\" href=\"<?= url('facturas.php') ?>\">Facturas</a></li>\n";
    $needle = "        <li class=\"nav-item\"><a class=\"nav-link\" href=\"<?= url('tickets.php') ?>\">Tickets</a></li>\n";
    if (strpos($s, $needle) !== false) return str_replace($needle, $needle . $line, $s);
    $needle2 = "        <li class=\"nav-item\"><a class=\"nav-link\" href=\"<?= url('notas.php') ?>\">Notas</a></li>\n";
    if (strpos($s, $needle2) !== false) return str_replace($needle2, $needle2 . $line, $s);
    return false;
});

$messages[] = patch_file_factura(__DIR__ . '/nota_guardar.php', function($s) {
    if (strpos($s, 'FACTURA_VIPRINT_AUTO_DEADLINE') !== false) return $s;
    $block = <<<'TXT'

 // FACTURA_VIPRINT_AUTO_DEADLINE
 if (function_exists('column_exists') && column_exists('v2_notas','factura_limite_datos')) {
   $reqFactura = (int)($_POST['requiere_factura'] ?? 0);
   if ($reqFactura === 1) {
     $stmtFactura = db()->prepare("UPDATE v2_notas SET factura_requiere_fecha = COALESCE(factura_requiere_fecha, NOW()), factura_limite_datos = COALESCE(factura_limite_datos, DATE_ADD(NOW(), INTERVAL 72 HOUR)), factura_requiere_por = COALESCE(factura_requiere_por, ?) WHERE id = ?");
     $stmtFactura->execute([current_user()['id'] ?? null, $id]);
   }
 }
TXT;
    $needle = ' recalcular_nota($id);';
    if (strpos($s, $needle) !== false) return str_replace($needle, $block . $needle, $s);
    return false;
});

$messages[] = patch_file_factura(__DIR__ . '/nota_form.php', function($s) {
    if (strpos($s, 'Recuerda capturar el precio con IVA incluido') !== false) return $s;
    $old = '<div class="col-md-3"><label class="form-label">Factura</label><select class="form-select" name="requiere_factura"><option value="0" <?= sel($nota[\'requiere_factura\']??0,0) ?>>No</option><option value="1" <?= sel($nota[\'requiere_factura\']??0,1) ?>>Sí</option></select></div>';
    $new = '<div class="col-md-3"><label class="form-label">¿Requiere factura?</label><select class="form-select" name="requiere_factura"><option value="0" <?= sel($nota[\'requiere_factura\']??0,0) ?>>No</option><option value="1" <?= sel($nota[\'requiere_factura\']??0,1) ?>>Sí</option></select><div class="form-text">Recuerda capturar el precio con IVA incluido cuando el cliente requiera factura.</div></div>';
    if (strpos($s, $old) !== false) return str_replace($old, $new, $s);
    return false;
});

?><!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Facturación instalada</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body style="background:#F8F6F8"><div class="container py-5"><div class="card shadow-sm"><div class="card-body"><h1 class="h4 text-success">Módulo de facturación instalado</h1><p>Se agregó la solicitud de factura para clientes, panel interno y aviso en tickets cuando la nota requiere factura.</p><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Elemento</th><th>Estado</th><th>Detalle</th></tr></thead><tbody><?php foreach($messages as $m): ?><tr><td><?= ih($m[0]) ?></td><td><span class="badge text-bg-<?= ($m[1]==='error')?'danger':(($m[1]==='agregado'||$m[1]==='actualizado'||$m[1]==='listo')?'success':'secondary') ?>"><?= ih($m[1]) ?></span></td><td><?= ih($m[2]) ?></td></tr><?php endforeach; ?></tbody></table></div><p><strong>Prueba:</strong> abre <code>facturas.php</code>, busca una nota y márcala como requiere factura. Luego imprime un ticket y revisa <code>viprint.com.mx/factura</code>.</p><p class="text-danger mb-0"><strong>Importante:</strong> elimina este archivo del servidor: <code>instalar_facturacion_cliente_v2.php</code></p></div></div></div></body></html>
