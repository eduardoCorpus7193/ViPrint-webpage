<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/permisos.php');
}
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('DELETE FROM permisos_salida WHERE id = ?');
$stmt->execute([$id]);
flash('success', 'Permiso eliminado.');
redirect('/permisos.php');
