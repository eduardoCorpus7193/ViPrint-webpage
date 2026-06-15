<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/empleados.php');
}
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$nombre = trim((string)($_POST['nombre'] ?? ''));

if ($nombre === '' || mb_strlen($nombre) > 150) {
    flash('danger', 'Captura un nombre válido.');
    redirect($id > 0 ? '/empleados.php?editar=' . $id : '/empleados.php');
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE empleados SET nombre = ? WHERE id = ?');
        $stmt->execute([$nombre, $id]);
        flash('success', 'El nombre del empleado fue actualizado.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO empleados (nombre) VALUES (?)');
        $stmt->execute([$nombre]);
        flash('success', 'Empleado agregado correctamente.');
    }
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23000') {
        flash('warning', 'Ya existe un empleado con ese nombre.');
    } else {
        throw $e;
    }
}

redirect('/empleados.php');
