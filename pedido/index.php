<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/notas-viprint-imagen-v2/consulta_pedido.php';
if ($query !== '') {
    $target .= '?' . $query;
}
header('Location: ' . $target);
exit;
