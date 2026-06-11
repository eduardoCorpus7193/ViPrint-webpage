<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/empleados.php');
}
verifyCsrf();

$employeeId = filter_input(INPUT_POST, 'empleado_id', FILTER_VALIDATE_INT) ?: 0;
$days = filter_input(INPUT_POST, 'dias', FILTER_VALIDATE_INT);
$description = trim((string)($_POST['descripcion'] ?? ''));

if (!$employeeId || $days === false || $days === null || $days === 0 || abs($days) > 365 || $description === '' || mb_strlen($description) > 255) {
    flash('danger', 'Completa correctamente el ajuste.');
    redirect('/empleado.php?id=' . $employeeId);
}

$balance = employeeBalance($pdo, $employeeId);
if (($balance['disponibles'] + $days) < 0) {
    flash('danger', 'El ajuste dejaría el saldo disponible en un valor negativo.');
    redirect('/empleado.php?id=' . $employeeId);
}

$stmt = $pdo->prepare('INSERT INTO movimientos_vacaciones (empleado_id, tipo, fecha, dias, descripcion) VALUES (:empleado_id, "AJUSTE", CURDATE(), :dias, :descripcion)');
$stmt->execute(['empleado_id' => $employeeId, 'dias' => $days, 'descripcion' => $description]);
flash('success', 'Ajuste registrado.');
redirect('/empleado.php?id=' . $employeeId);
