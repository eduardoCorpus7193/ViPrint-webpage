<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/empleados.php');
}
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id < 1) {
    flash('danger', 'Empleado no válido.');
    redirect('/empleados.php');
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM permisos_salida WHERE empleado_id = ?');
$countStmt->execute([$id]);
if ((int)$countStmt->fetchColumn() > 0) {
    flash('warning', 'No se puede eliminar porque el empleado ya tiene permisos. Puedes editar su nombre.');
    redirect('/empleados.php');
}

$stmt = $pdo->prepare('DELETE FROM empleados WHERE id = ?');
$stmt->execute([$id]);
flash('success', 'Empleado eliminado.');
redirect('/empleados.php');
