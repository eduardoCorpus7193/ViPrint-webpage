<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
echo '<h1>Diagnóstico Notas V2</h1>';
echo '<p>PHP: '.h(PHP_VERSION).'</p>';
echo '<p>PDO: '.(extension_loaded('pdo')?'Disponible':'No disponible').'</p>';
echo '<p>PDO MySQL: '.(extension_loaded('pdo_mysql')?'Disponible':'No disponible').'</p>';
try{
 echo '<p>Conexión: correcta</p>';
 foreach(['v2_empresas','v2_usuarios','v2_notas','v2_nota_partidas','v2_pagos','v2_mermas','v2_comisiones'] as $t){ echo '<p>'.h($t).': '.(table_exists($t)?'existe':'NO existe').'</p>'; }
}catch(Exception $e){ echo '<p>Error: '.h($e->getMessage()).'</p>'; }
