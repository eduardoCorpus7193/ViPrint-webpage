<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/documentos.php');
}
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$employeeId = (int)($_POST['empleado_id'] ?? 0);
$type = (string)($_POST['tipo'] ?? '');
$date = validDate($_POST['fecha_trabajada'] ?? null);
$amountRaw = trim((string)($_POST['cantidad'] ?? ''));
$amount = filter_var($amountRaw, FILTER_VALIDATE_FLOAT);

$errors = [];
if (!getEmployee($pdo, $employeeId)) {
    $errors[] = 'Selecciona un empleado válido.';
}
if (!in_array($type, ['transferencia', 'bono_efectivo'], true)) {
    $errors[] = 'Selecciona un tipo de documento válido.';
}
if (!$date) {
    $errors[] = 'Captura una fecha válida.';
}
if ($amount === false || $amount <= 0 || $amount > 99999999.99) {
    $errors[] = 'Captura una cantidad válida mayor a cero.';
}

if ($errors) {
    flash('danger', implode(' ', $errors));
    redirect($id > 0 ? '/documento_form.php?id=' . $id : '/documento_form.php');
}

if ($id > 0) {
    $current = getDocument($pdo, $id);
    if (!$current) {
        flash('danger', 'El documento no existe.');
        redirect('/documentos.php');
    }

    $stmt = $pdo->prepare(
        'UPDATE documentos_nomina
         SET empleado_id = ?, tipo = ?, fecha_trabajada = ?, cantidad = ?
         WHERE id = ?'
    );
    $stmt->execute([$employeeId, $type, $date, $amount, $id]);

    $folio = generateFolio($id, $date, $type);
    $folioStmt = $pdo->prepare('UPDATE documentos_nomina SET folio = ? WHERE id = ?');
    $folioStmt->execute([$folio, $id]);
    flash('success', 'Documento actualizado correctamente.');
} else {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO documentos_nomina (empleado_id, tipo, fecha_trabajada, cantidad)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$employeeId, $type, $date, $amount]);
        $id = (int)$pdo->lastInsertId();
        $folio = generateFolio($id, $date, $type);
        $folioStmt = $pdo->prepare('UPDATE documentos_nomina SET folio = ? WHERE id = ?');
        $folioStmt->execute([$folio, $id]);
        $pdo->commit();
        flash('success', 'Documento creado correctamente. Ya puedes imprimirlo.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

redirect('/ver_documento.php?id=' . $id);
