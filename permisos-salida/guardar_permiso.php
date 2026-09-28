<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/permisos.php');
}
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$employeeId = (int)($_POST['empleado_id'] ?? 0);
$date = validDate($_POST['fecha_permiso'] ?? null);
$start = validTime($_POST['hora_salida'] ?? null);
$plannedReturn = validTime($_POST['hora_regreso_prevista'] ?? null);
$actualReturnRaw = trim((string)($_POST['hora_regreso_real'] ?? ''));
$actualReturn = $actualReturnRaw === '' ? null : validTime($actualReturnRaw);
$reasonType = (string)($_POST['motivo_tipo'] ?? 'personal');
$reasonDetail = trim((string)($_POST['motivo_detalle'] ?? ''));
$destination = trim((string)($_POST['destino'] ?? ''));
$treatment = (string)($_POST['tratamiento_tiempo'] ?? 'por_definir');
$status = (string)($_POST['estado'] ?? 'pendiente');
$observations = trim((string)($_POST['observaciones'] ?? ''));

$validReasons = ['personal', 'medico', 'familiar', 'tramite', 'laboral', 'emergencia'];
$validTreatments = ['por_definir', 'con_goce', 'sin_goce', 'reposicion', 'salida_laboral'];
$validStatuses = ['pendiente', 'autorizado', 'cancelado'];

$back = $id > 0 ? '/permiso_form.php?id=' . $id : '/permiso_form.php';

if (!$employeeId || !getEmployee($pdo, $employeeId)) {
    flash('danger', 'Selecciona un empleado válido.');
    redirect($back);
}
if (!$date || !$start || !$plannedReturn) {
    flash('danger', 'Captura una fecha y horarios válidos.');
    redirect($back);
}
if (minutesBetween($start, $plannedReturn) <= 0) {
    flash('danger', 'La hora de regreso prevista debe ser posterior a la hora de salida.');
    redirect($back);
}
if ($actualReturnRaw !== '' && !$actualReturn) {
    flash('danger', 'La hora real de regreso no es válida.');
    redirect($back);
}
if (!in_array($reasonType, $validReasons, true) || !in_array($treatment, $validTreatments, true) || !in_array($status, $validStatuses, true)) {
    flash('danger', 'Uno de los valores seleccionados no es válido.');
    redirect($back);
}
if ($reasonDetail === '' || mb_strlen($reasonDetail) > 500) {
    flash('danger', 'Captura una descripción breve del motivo.');
    redirect($back);
}
if (mb_strlen($destination) > 200 || mb_strlen($observations) > 500) {
    flash('danger', 'El destino o las observaciones son demasiado extensos.');
    redirect($back);
}

if ($id > 0) {
    $stmt = $pdo->prepare(
        'UPDATE permisos_salida
         SET empleado_id = ?, fecha_permiso = ?, hora_salida = ?, hora_regreso_prevista = ?,
             hora_regreso_real = ?, motivo_tipo = ?, motivo_detalle = ?, destino = ?,
             tratamiento_tiempo = ?, estado = ?, observaciones = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $employeeId, $date, $start, $plannedReturn, $actualReturn, $reasonType,
        $reasonDetail, $destination !== '' ? $destination : null, $treatment,
        $status, $observations !== '' ? $observations : null, $id
    ]);
    flash('success', 'Permiso actualizado correctamente.');
} else {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO permisos_salida
             (empleado_id, fecha_permiso, hora_salida, hora_regreso_prevista, hora_regreso_real,
              motivo_tipo, motivo_detalle, destino, tratamiento_tiempo, estado, observaciones)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $employeeId, $date, $start, $plannedReturn, $actualReturn, $reasonType,
            $reasonDetail, $destination !== '' ? $destination : null, $treatment,
            $status, $observations !== '' ? $observations : null
        ]);
        $id = (int)$pdo->lastInsertId();
        $folio = generateFolio($id, $date);
        $folioStmt = $pdo->prepare('UPDATE permisos_salida SET folio = ? WHERE id = ?');
        $folioStmt->execute([$folio, $id]);
        $pdo->commit();
        flash('success', 'Permiso creado correctamente. Ya puedes imprimirlo.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

redirect('/ver_permiso.php?id=' . $id);
