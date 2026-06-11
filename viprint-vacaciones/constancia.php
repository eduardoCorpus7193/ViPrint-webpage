<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = $pdo->prepare('SELECT * FROM empleados WHERE id = :id');
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch();
if (!$employee) {
    flash('danger', 'El empleado no existe.');
    redirect('/empleados.php');
}

$balance = employeeBalance($pdo, $id);
$years = completedServiceYears($employee['fecha_ingreso']);
$cycle = currentVacationCycle($employee['fecha_ingreso']);
$pageTitle = 'Constancia de vacaciones';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-center gap-2 mb-3 no-print">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/empleado.php?id=<?= $id ?>">Volver</a>
    <button class="btn btn-viprint" onclick="window.print()">Imprimir</button>
</div>

<article class="print-sheet">
    <header class="print-header pb-3 mb-4">
        <div class="company-name"><?= e(COMPANY_NAME) ?></div>
        <div class="small text-secondary"><?= e(COMPANY_ADDRESS) ?></div>
        <h1 class="h4 mt-3 mb-0">Constancia anual de antigüedad y vacaciones</h1>
    </header>

    <p>Aguascalientes, Aguascalientes, a <?= e(formatDate(date('Y-m-d'))) ?>.</p>
    <p>Por medio de la presente, <strong><?= e(COMPANY_NAME) ?></strong> hace constar la siguiente información de la persona trabajadora:</p>

    <table class="table table-bordered detail-table my-4">
        <tbody>
            <tr><th>Nombre</th><td><?= e($employee['nombre']) ?></td></tr>
            <tr><th>Puesto</th><td><?= e($employee['puesto']) ?></td></tr>
            <tr><th>Fecha de ingreso</th><td><?= e(formatDate($employee['fecha_ingreso'])) ?></td></tr>
            <tr><th>Antigüedad</th><td><?= $years ?> año(s)</td></tr>
            <tr><th>Periodo actual</th><td><?= e(formatDate($cycle['start'])) ?> al <?= e(formatDate($cycle['end'])) ?></td></tr>
            <tr><th>Días legales de referencia</th><td><?= (int)$cycle['entitlement'] ?> día(s) correspondientes al año de servicio actual</td></tr>
            <tr><th>Saldo disponible registrado</th><td class="fs-5 fw-bold text-viprint"><?= $balance['disponibles'] ?> día(s)</td></tr>
            <tr><th>Días pendientes de autorización</th><td><?= $balance['pendientes'] ?> día(s)</td></tr>
        </tbody>
    </table>

    <p>La fecha específica para disfrutar las vacaciones se determinará mediante la solicitud y autorización correspondiente, respetando los derechos aplicables y las necesidades de coordinación del centro de trabajo.</p>

    <div class="row signature-section g-5">
        <div class="col-6"><div class="signature-line"><?= e($employee['nombre']) ?><span>Recibí la constancia</span></div></div>
        <div class="col-6"><div class="signature-line"><?= e(OWNER_NAME) ?><span>Patrón</span></div></div>
    </div>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
