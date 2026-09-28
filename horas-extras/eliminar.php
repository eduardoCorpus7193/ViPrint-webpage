<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/registros.php');
}

verifyCsrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($id) {
    $stmt = $pdo->prepare('DELETE FROM registros_horas_extra WHERE id = :id');
    $stmt->execute(['id' => $id]);
    flash('success', 'Registro eliminado.');
}

redirect('/registros.php');
