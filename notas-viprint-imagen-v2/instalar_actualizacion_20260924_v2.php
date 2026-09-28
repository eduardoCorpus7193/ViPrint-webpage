<?php
require_once __DIR__."/includes/bootstrap.php";
require_once __DIR__."/includes/actualizacion_20260924_helpers.php";
require_login();
if(!role_in(["admin","direccion","administracion","asesor"])) { http_response_code(403); exit("No tienes permiso para instalar."); }
$clave=$_GET["clave"]??"";
if($clave!=="actualizacion2026"){ exit("Clave incorrecta."); }
$mensajes=[];
function add_col_20260924($table,$col,$def){ global $mensajes; if(!column_exists($table,$col)){ db()->exec("ALTER TABLE ".$table." ADD COLUMN ".$col." ".$def); $mensajes[]="Columna agregada: ".$table.".".$col; } else { $mensajes[]="Ya existe: ".$table.".".$col; } }
try{
add_col_20260924("v2_notas","subtotal_sin_iva","DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total");
add_col_20260924("v2_notas","iva_monto","DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER subtotal_sin_iva");
db()->exec("ALTER TABLE v2_notas MODIFY estado_contacto VARCHAR(40) NOT NULL DEFAULT \"pendiente\"");
db()->exec("ALTER TABLE v2_notas MODIFY estado_produccion VARCHAR(40) NOT NULL DEFAULT \"pendiente\"");
$mensajes[]="Estados habilitados como texto flexible.";
$sql="CREATE TABLE IF NOT EXISTS v2_quejas_sugerencias (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nota_id INT UNSIGNED NOT NULL, empresa_id INT UNSIGNED NOT NULL, folio VARCHAR(60) NOT NULL, public_code VARCHAR(80) NULL, nombre VARCHAR(180) NOT NULL, telefono VARCHAR(80) NOT NULL, tipo VARCHAR(30) NOT NULL DEFAULT \"comentario\", mensaje TEXT NOT NULL, foto_path VARCHAR(255) NULL, estado VARCHAR(30) NOT NULL DEFAULT \"nueva\", respuesta_interna TEXT NULL, atendido_por_id INT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL, INDEX idx_nota (nota_id), INDEX idx_estado (estado), INDEX idx_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
db()->exec($sql);
$mensajes[]="Tabla de quejas/sugerencias lista.";
vp_queja_ensure_upload_dir();
}catch(Exception $e){ $mensajes[]="ERROR: ".$e->getMessage(); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instalación</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#F8F6F8"><div class="container py-5" style="max-width:900px"><div class="card"><div class="card-body"><h1 class="h4">Actualización 24/09 instalada</h1><ul><?php foreach($mensajes as $m): ?><li><?= h($m) ?></li><?php endforeach; ?></ul><p class="text-danger">Elimina este archivo del servidor: instalar_actualizacion_20260924_v2.php</p><a class="btn btn-primary" href="<?= url("index.php") ?>">Volver al sistema</a></div></div></div></body></html>
