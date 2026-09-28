<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/solicitudes.php');
}
verifyCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$newStatus = (string)($_POST['estado'] ?? '');
if (!$id || !in_array($newStatus, ['AUTORIZADA', 'RECHAZADA', 'CANCELADA'], true)) {
    flash('danger', 'La acción solicitada no es válida.');
    redirect('/solicitudes.php');
}

$stmt = $pdo->prepare('SELECT * FROM solicitudes_vacaciones WHERE id = :id');
$stmt->execute(['id' => $id]);
$request = $stmt->fetch();
if (!$request) {
    flash('danger', 'La solicitud no existe.');
    redirect('/solicitudes.php');
}

if (in_array($newStatus, ['AUTORIZADA', 'RECHAZADA'], true) && $request['estado'] !== 'PENDIENTE') {
    flash('warning', 'La solicitud ya fue resuelta.');
    redirect('/ver_solicitud.php?id=' . $id);
}
if ($newStatus === 'CANCELADA' && $request['estado'] !== 'AUTORIZADA') {
    flash('warning', 'Solo se puede cancelar una solicitud autorizada.');
    redirect('/ver_solicitud.php?id=' . $id);
}

if ($newStatus === 'AUTORIZADA') {
    $balance = employeeBalance($pdo, (int)$request['empleado_id']);
    if ((int)$request['dias_solicitados'] > $balance['disponibles']) {
        flash('danger', 'No se puede autorizar porque el saldo disponible es insuficiente.');
        redirect('/ver_solicitud.php?id=' . $id);
    }
}

$stmt = $pdo->prepare(
    'UPDATE solicitudes_vacaciones
     SET estado = :estado, fecha_resolucion = CURDATE(), resuelto_por = :resuelto_por
     WHERE id = :id'
);
$stmt->execute([
    'estado' => $newStatus,
    'resuelto_por' => OWNER_NAME,
    'id' => $id,
]);

$message = match ($newStatus) {
    'AUTORIZADA' => 'Solicitud autorizada. Los días ya fueron descontados del saldo disponible.',
    'RECHAZADA' => 'Solicitud rechazada.',
    'CANCELADA' => 'Solicitud cancelada. Los días fueron devueltos al saldo.',
};
flash('success', $message);
redirect('/ver_solicitud.php?id=' . $id);
