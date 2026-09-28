<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/empleados.php');
}
verifyCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$name = normalizeName((string)($_POST['nombre'] ?? ''));
$position = trim((string)($_POST['puesto'] ?? ''));
$hireDate = validDate($_POST['fecha_ingreso'] ?? null);

if ($name === '' || mb_strlen($name) > 160 || mb_strlen($position) > 120 || !$hireDate) {
    flash('danger', 'Completa correctamente los datos del empleado.');
    redirect('/empleado_form.php' . ($id ? '?id=' . $id : ''));
}

try {
    if ($id) {
        $active = isset($_POST['activo']) ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE empleados SET nombre = :nombre, puesto = :puesto, activo = :activo WHERE id = :id');
        $stmt->execute([
            'nombre' => $name,
            'puesto' => $position ?: null,
            'activo' => $active,
            'id' => $id,
        ]);
        flash('success', 'Empleado actualizado.');
        redirect('/empleado.php?id=' . $id);
    }

    $initialBalance = filter_input(INPUT_POST, 'saldo_inicial', FILTER_VALIDATE_INT);
    if ($initialBalance === false || $initialBalance === null || $initialBalance < 0 || $initialBalance > 365) {
        flash('danger', 'El saldo inicial debe ser un número válido de días.');
        redirect('/empleado_form.php');
    }

    if ($hireDate > date('Y-m-d')) {
        flash('danger', 'La fecha de ingreso no puede ser futura.');
        redirect('/empleado_form.php');
    }

    $completedYears = completedServiceYears($hireDate);
    $stmt = $pdo->prepare(
        'INSERT INTO empleados
        (nombre, puesto, fecha_ingreso, saldo_inicial, fecha_corte_saldo, ultimo_anio_procesado)
        VALUES (:nombre, :puesto, :fecha_ingreso, :saldo_inicial, CURDATE(), :ultimo_anio)'
    );
    $stmt->execute([
        'nombre' => $name,
        'puesto' => $position ?: null,
        'fecha_ingreso' => $hireDate,
        'saldo_inicial' => $initialBalance,
        'ultimo_anio' => $completedYears,
    ]);
    $savedId = (int)$pdo->lastInsertId();

    flash('success', 'Empleado registrado. Los aniversarios futuros se actualizarán automáticamente.');
    redirect('/empleado.php?id=' . $savedId);
} catch (Throwable $e) {
    flash('danger', 'No fue posible guardar al empleado.');
    redirect('/empleado_form.php' . ($id ? '?id=' . $id : ''));
}
