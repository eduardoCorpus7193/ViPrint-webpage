<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/solicitudes.php');
}
verifyCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$employeeId = filter_input(INPUT_POST, 'empleado_id', FILTER_VALIDATE_INT) ?: 0;
$requestDate = validDate($_POST['fecha_solicitud'] ?? null);
$startDate = validDate($_POST['fecha_inicio'] ?? null);
$endDate = validDate($_POST['fecha_fin'] ?? null);
$days = filter_input(INPUT_POST, 'dias_solicitados', FILTER_VALIDATE_INT);
$notes = trim((string)($_POST['observaciones'] ?? ''));

$back = '/solicitud_form.php' . ($id ? '?id=' . $id : ($employeeId ? '?empleado_id=' . $employeeId : ''));

if (!$employeeId || !$requestDate || !$startDate || !$endDate || $days === false || $days === null || $days < 1 || $days > 365 || mb_strlen($notes) > 500) {
    flash('danger', 'Completa correctamente todos los datos de la solicitud.');
    redirect($back);
}

if ($endDate < $startDate) {
    flash('danger', 'La fecha final no puede ser anterior a la fecha inicial.');
    redirect($back);
}

$stmt = $pdo->prepare('SELECT * FROM empleados WHERE id = :id AND activo = 1');
$stmt->execute(['id' => $employeeId]);
$employee = $stmt->fetch();
if (!$employee) {
    flash('danger', 'El empleado seleccionado no está disponible.');
    redirect($back);
}

$balance = employeeBalance($pdo, $employeeId);
$previousDays = 0;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM solicitudes_vacaciones WHERE id = :id AND estado = "PENDIENTE"');
    $stmt->execute(['id' => $id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash('danger', 'La solicitud no puede editarse.');
        redirect('/solicitudes.php');
    }
    if ((int)$existing['empleado_id'] !== $employeeId) {
        flash('danger', 'No es posible cambiar al empleado de una solicitud existente.');
        redirect('/solicitudes.php');
    }
    $previousDays = (int)$existing['dias_solicitados'];
}

$availableToRequest = $balance['solicitables'] + $previousDays;
if ($days > $availableToRequest) {
    flash('danger', "La solicitud excede el saldo solicitables de {$availableToRequest} día(s).");
    redirect($back);
}

try {
    if ($id) {
        $stmt = $pdo->prepare(
            'UPDATE solicitudes_vacaciones
             SET fecha_solicitud = :fecha_solicitud, fecha_inicio = :fecha_inicio,
                 fecha_fin = :fecha_fin, dias_solicitados = :dias, observaciones = :observaciones
             WHERE id = :id'
        );
        $stmt->execute([
            'fecha_solicitud' => $requestDate,
            'fecha_inicio' => $startDate,
            'fecha_fin' => $endDate,
            'dias' => $days,
            'observaciones' => $notes ?: null,
            'id' => $id,
        ]);
        $savedId = $id;
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO solicitudes_vacaciones
             (empleado_id, fecha_solicitud, fecha_inicio, fecha_fin, dias_solicitados, observaciones)
             VALUES (:empleado_id, :fecha_solicitud, :fecha_inicio, :fecha_fin, :dias, :observaciones)'
        );
        $stmt->execute([
            'empleado_id' => $employeeId,
            'fecha_solicitud' => $requestDate,
            'fecha_inicio' => $startDate,
            'fecha_fin' => $endDate,
            'dias' => $days,
            'observaciones' => $notes ?: null,
        ]);
        $savedId = (int)$pdo->lastInsertId();
        $stmt = $pdo->prepare('UPDATE solicitudes_vacaciones SET folio = :folio WHERE id = :id');
        $stmt->execute(['folio' => generateVacationFolio($savedId, $requestDate), 'id' => $savedId]);
    }

    flash('success', 'Solicitud guardada. Ya puedes imprimirla y recabar las firmas.');
    redirect('/ver_solicitud.php?id=' . $savedId);
} catch (Throwable $e) {
    flash('danger', 'No fue posible guardar la solicitud.');
    redirect($back);
}
