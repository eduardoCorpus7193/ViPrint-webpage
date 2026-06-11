<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = $pdo->prepare(
    'SELECT s.*, e.nombre AS empleado_nombre, e.puesto, e.fecha_ingreso
     FROM solicitudes_vacaciones s
     INNER JOIN empleados e ON e.id = s.empleado_id
     WHERE s.id = :id'
);
$stmt->execute(['id' => $id]);
$request = $stmt->fetch();
if (!$request) {
    flash('danger', 'La solicitud no existe.');
    redirect('/solicitudes.php');
}

$balance = employeeBalance($pdo, (int)$request['empleado_id']);
$years = completedServiceYears($request['fecha_ingreso'], $request['fecha_solicitud']);
$returnDate = nextWorkingDay($request['fecha_fin']);
$pageTitle = 'Solicitud ' . $request['folio'];
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-center gap-2 mb-3 no-print flex-wrap">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/solicitudes.php">Volver</a>
    <?php if ($request['estado'] === 'PENDIENTE'): ?>
        <a class="btn btn-outline-dark" href="<?= BASE_URL ?>/solicitud_form.php?id=<?= $id ?>">Editar</a>
        <form method="post" action="<?= BASE_URL ?>/cambiar_estado.php" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="estado" value="AUTORIZADA">
            <button class="btn btn-success" onclick="return confirm('¿Autorizar esta solicitud y descontar los días del saldo?');">Autorizar</button>
        </form>
        <form method="post" action="<?= BASE_URL ?>/cambiar_estado.php" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="estado" value="RECHAZADA">
            <button class="btn btn-outline-danger" onclick="return confirm('¿Rechazar esta solicitud?');">Rechazar</button>
        </form>
    <?php elseif ($request['estado'] === 'AUTORIZADA'): ?>
        <form method="post" action="<?= BASE_URL ?>/cambiar_estado.php" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="estado" value="CANCELADA">
            <button class="btn btn-outline-danger" onclick="return confirm('¿Cancelar la autorización y devolver los días al saldo?');">Cancelar autorización</button>
        </form>
    <?php endif; ?>
    <button class="btn btn-viprint" onclick="window.print()">Imprimir</button>
</div>

<article class="print-sheet">
    <header class="print-header d-flex justify-content-between align-items-start gap-3 pb-3 mb-4">
        <div>
            <div class="company-name"><?= e(COMPANY_NAME) ?></div>
            <div class="small text-secondary"><?= e(COMPANY_ADDRESS) ?></div>
            <h1 class="h4 mt-3 mb-0">Solicitud de vacaciones</h1>
        </div>
        <div class="folio-box">
            <span>Folio</span>
            <strong><?= e($request['folio']) ?></strong>
            <hr class="my-2">
            <span>Estado</span>
            <strong><?= e(statusLabel($request['estado'])) ?></strong>
        </div>
    </header>

    <table class="table table-bordered detail-table mb-4">
        <tbody>
            <tr><th>Nombre del trabajador</th><td><?= e($request['empleado_nombre']) ?></td></tr>
            <tr><th>Puesto</th><td><?= e($request['puesto']) ?></td></tr>
            <tr><th>Fecha de ingreso</th><td><?= e(formatDate($request['fecha_ingreso'])) ?></td></tr>
            <tr><th>Antigüedad a la solicitud</th><td><?= $years ?> año(s)</td></tr>
            <tr><th>Fecha de solicitud</th><td><?= e(formatDate($request['fecha_solicitud'])) ?></td></tr>
            <tr><th>Periodo solicitado</th><td>Del <strong><?= e(formatDate($request['fecha_inicio'])) ?></strong> al <strong><?= e(formatDate($request['fecha_fin'])) ?></strong></td></tr>
            <tr><th>Días solicitados</th><td class="fs-5 fw-bold text-viprint"><?= (int)$request['dias_solicitados'] ?> día(s) laborables</td></tr>
            <tr><th>Fecha estimada de regreso</th><td><?= e(formatDate($returnDate)) ?></td></tr>
            <tr><th>Saldo disponible actual</th><td><?= $balance['disponibles'] ?> día(s)</td></tr>
            <tr><th>Observaciones</th><td><?= e($request['observaciones'] ?: 'Sin observaciones') ?></td></tr>
        </tbody>
    </table>

    <p class="mb-3">Por medio de la presente, solicito disfrutar el periodo de vacaciones señalado. Entiendo que la solicitud estará sujeta a autorización de la empresa y a la coordinación necesaria para mantener la operación del centro de trabajo.</p>

    <?php if ($request['estado'] === 'AUTORIZADA'): ?>
        <div class="alert alert-success border-0"><strong>Solicitud autorizada</strong><br>Fecha de resolución: <?= e(formatDate($request['fecha_resolucion'])) ?> · Autorizó: <?= e($request['resuelto_por'] ?: OWNER_NAME) ?></div>
    <?php elseif ($request['estado'] === 'RECHAZADA'): ?>
        <div class="alert alert-danger border-0"><strong>Solicitud rechazada</strong><br>Fecha de resolución: <?= e(formatDate($request['fecha_resolucion'])) ?></div>
    <?php else: ?>
        <div class="alert alert-warning border-0"><strong>Pendiente de autorización.</strong> La impresión y firma del trabajador no significan aprobación automática.</div>
    <?php endif; ?>

    <div class="row signature-section g-5">
        <div class="col-6"><div class="signature-line"><?= e($request['empleado_nombre']) ?><span>Firma del trabajador</span></div></div>
        <div class="col-6"><div class="signature-line"><?= e(OWNER_NAME) ?><span>Firma de autorización del patrón</span></div></div>
    </div>

    <p class="legal-note mt-5 mb-0">Control interno de ViPrint Publicidad. Los días indicados deben revisarse contra el saldo del trabajador y los días laborables aplicables. La autorización electrónica en el sistema y las firmas de esta hoja forman parte del expediente.</p>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
