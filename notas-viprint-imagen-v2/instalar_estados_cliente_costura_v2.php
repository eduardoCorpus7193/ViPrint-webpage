<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$u = current_user();
if (!$u || !in_array($u['rol'], array('admin','direccion','administracion'), true)) {
    http_response_code(403);
    exit('No tienes permiso para ejecutar esta actualización. Entra como admin, dirección o administración.');
}

$clave = isset($_GET['clave']) ? $_GET['clave'] : '';
if ($clave !== 'estados2026') {
    exit('Clave incorrecta. Usa ?clave=estados2026');
}

try {
    db()->exec("ALTER TABLE v2_notas MODIFY COLUMN estado_contacto ENUM('pendiente','contactado','cliente_no_contesta','no_aplica') NOT NULL DEFAULT 'pendiente'");
    db()->exec("ALTER TABLE v2_notas MODIFY COLUMN estado_produccion ENUM('pendiente','para_imprimir','impresa','sublimada','en_costura','problema','no_aplica') NOT NULL DEFAULT 'pendiente'");
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Actualización aplicada</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#F8F6F8">';
    echo '<div class="container py-5"><div class="card shadow-sm"><div class="card-body">';
    echo '<h1 class="h4 text-success">Actualización aplicada correctamente</h1>';
    echo '<p>Se agregaron los estados <strong>Cliente no contesta</strong> y <strong>En costura</strong>.</p>';
    echo '<p class="text-danger mb-0"><strong>Importante:</strong> elimina este archivo del servidor: <code>instalar_estados_cliente_costura_v2.php</code></p>';
    echo '</div></div></div></body></html>';
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error al actualizar: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
