<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

verifyCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$trabajador = trim((string)($_POST['trabajador'] ?? ''));
$fecha = validDate($_POST['fecha'] ?? null);
$horaInicio = validTime($_POST['hora_inicio'] ?? null);
$horaFin = validTime($_POST['hora_fin'] ?? null);

if ($trabajador === '' || mb_strlen($trabajador) > 150 || !$fecha || !$horaInicio || !$horaFin) {
    flash('danger', 'Completa correctamente todos los campos.');
    redirect('/formulario.php' . ($id ? '?id=' . $id : ''));
}

$total = hoursBetween($horaInicio, $horaFin);
if ($total <= 0 || $total > 12) {
    flash('danger', 'El horario capturado no genera una cantidad válida de horas extras.');
    redirect('/formulario.php' . ($id ? '?id=' . $id : ''));
}

try {
    if ($id) {
        $stmt = $pdo->prepare(
            'UPDATE registros_horas_extra
             SET trabajador = :trabajador,
                 fecha = :fecha,
                 hora_inicio = :hora_inicio,
                 hora_fin = :hora_fin,
                 total_horas = :total_horas
             WHERE id = :id'
        );
        $stmt->execute([
            'trabajador' => $trabajador,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'total_horas' => $total,
            'id' => $id,
        ]);
        $savedId = $id;
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO registros_horas_extra
             (trabajador, fecha, hora_inicio, hora_fin, total_horas)
             VALUES (:trabajador, :fecha, :hora_inicio, :hora_fin, :total_horas)'
        );
        $stmt->execute([
            'trabajador' => $trabajador,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'total_horas' => $total,
        ]);

        $savedId = (int)$pdo->lastInsertId();
        $folioStmt = $pdo->prepare('UPDATE registros_horas_extra SET folio = :folio WHERE id = :id');
        $folioStmt->execute([
            'folio' => generateFolio($savedId, $fecha),
            'id' => $savedId,
        ]);
    }

    flash('success', 'Registro guardado. Ya puedes imprimir la hoja.');
    redirect('/ver.php?id=' . $savedId);
} catch (Throwable $e) {
    flash('danger', 'No fue posible guardar el registro.');
    redirect('/formulario.php' . ($id ? '?id=' . $id : ''));
}
