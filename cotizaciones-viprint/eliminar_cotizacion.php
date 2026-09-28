<?php
require_once __DIR__ . '/includes/bootstrap.php';
$token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403); exit('Token inválido.');
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM cotizaciones WHERE id=?");
    $stmt->execute(array($id));
    flash_set('success', 'Cotización eliminada.');
}
redirect_to('/');
