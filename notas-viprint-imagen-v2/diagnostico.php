<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Diagnóstico</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#F8F6F8"><div class="container py-4">';
echo '<div class="card"><div class="card-body"><h1 class="h4">Diagnóstico Notas V2</h1>';
echo '<p><strong>APP:</strong> '.h(APP_NAME).'</p>';
echo '<p><strong>BASE_URL:</strong> '.h(BASE_URL).'</p>';
echo '<p><strong>PHP:</strong> '.h(PHP_VERSION).'</p>';
echo '<p><strong>PDO:</strong> '.(extension_loaded('pdo')?'Disponible':'No disponible').'</p>';
echo '<p><strong>PDO MySQL:</strong> '.(extension_loaded('pdo_mysql')?'Disponible':'No disponible').'</p>';
try{
 echo '<div class="alert alert-success">Conexión con base de datos: correcta</div>';
 $tables = array('v2_empresas','v2_usuarios','v2_catalogo_items','v2_notas','v2_nota_partidas','v2_pagos','v2_mermas','v2_comisiones','v2_estado_historial','v2_caja_movimientos','v2_cortes_diarios');
 echo '<table class="table table-sm table-bordered"><thead><tr><th>Tabla</th><th>Estado</th><th>Registros</th></tr></thead><tbody>';
 foreach($tables as $t){
   $exists = table_exists($t);
   $count = '';
   if($exists){
     try{ $count = db()->query('SELECT COUNT(*) FROM `'.$t.'`')->fetchColumn(); }catch(Exception $e){ $count='?'; }
   }
   echo '<tr><td>'.h($t).'</td><td>'.($exists?'<span class="text-success">Existe</span>':'<span class="text-danger">NO existe</span>').'</td><td>'.h($count).'</td></tr>';
 }
 echo '</tbody></table>';
 if(!table_exists('v2_caja_movimientos') || !table_exists('v2_cortes_diarios')){
   echo '<div class="alert alert-warning"><strong>Falta caja.</strong> Ejecuta <code>database/update_caja_v2.sql</code> en phpMyAdmin sobre la misma base de datos.</div>';
 }
}catch(Exception $e){ echo '<div class="alert alert-danger">Error: '.h($e->getMessage()).'</div>'; }
echo '<p class="text-muted small mb-0">Cuando todo funcione, elimina este archivo o déjalo protegido.</p></div></div></div></body></html>';
