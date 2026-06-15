<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$permit = getPermit($pdo, $id);
if (!$permit) {
    flash('danger', 'El permiso solicitado no existe.');
    redirect('/permisos.php');
}

$plannedMinutes = minutesBetween($permit['hora_salida'], $permit['hora_regreso_prevista']);
$pageTitle = 'Permiso de salida ' . $permit['folio'];
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4 no-print">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/permisos.php">Volver al historial</a>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <a class="btn btn-outline-dark" href="<?= BASE_URL ?>/permiso_form.php?id=<?= (int)$permit['id'] ?>">Editar</a>
        <button class="btn btn-viprint" type="button" onclick="window.print()">Imprimir</button>
    </div>
</div>

<article class="print-sheet">
    <header class="print-header d-flex justify-content-between align-items-start gap-3 pb-3 mb-4">
        <div>
            <div class="company-name"><?= e(COMPANY_NAME) ?></div>
            <div class="small text-secondary">Permisos temporales de salida</div>
            <div class="small text-secondary">RFC: <?= e(COMPANY_RFC) ?></div>
            <div class="small text-secondary"><?= e(COMPANY_ADDRESS) ?></div>
        </div>
        <div class="folio-box">
            <span>Folio</span>
            <strong><?= e($permit['folio']) ?></strong>
        </div>
    </header>

    <h1 class="h4 text-center permit-title text-uppercase mb-4">Solicitud y autorización de salida temporal</h1>

    <table class="table table-bordered permit-data mb-4">
        <tbody>
            <tr><th>Empleado</th><td><?= e($permit['empleado_nombre']) ?></td></tr>
            <tr><th>Fecha del permiso</th><td><?= e(formatDate($permit['fecha_permiso'])) ?></td></tr>
            <tr><th>Hora de salida</th><td><?= e(formatTime($permit['hora_salida'])) ?></td></tr>
            <tr><th>Regreso previsto</th><td><?= e(formatTime($permit['hora_regreso_prevista'])) ?></td></tr>
            <tr><th>Duración estimada</th><td><?= e(formatDuration($plannedMinutes)) ?></td></tr>
            <tr><th>Regreso real</th><td><?= $permit['hora_regreso_real'] ? e(formatTime($permit['hora_regreso_real'])) : '<span class="return-line">&nbsp;</span> h' ?></td></tr>
            <tr><th>Tipo de motivo</th><td><?= e(reasonLabel($permit['motivo_tipo'])) ?></td></tr>
            <tr><th>Destino</th><td><?= e($permit['destino'] ?: 'No especificado') ?></td></tr>
            <tr><th>Tratamiento del tiempo</th><td><?= e(timeTreatmentLabel($permit['tratamiento_tiempo'])) ?></td></tr>
            <tr><th>Estado del registro</th><td><?= e(statusLabel($permit['estado'])) ?></td></tr>
        </tbody>
    </table>

    <div class="mb-4">
        <div class="fw-bold mb-1">Motivo de la salida:</div>
        <div class="border rounded p-3 bg-light"><?= nl2br(e($permit['motivo_detalle'])) ?></div>
    </div>

    <?php if ($permit['observaciones']): ?>
        <div class="mb-4">
            <div class="fw-bold mb-1">Observaciones:</div>
            <div class="border rounded p-3"><?= nl2br(e($permit['observaciones'])) ?></div>
        </div>
    <?php endif; ?>

    <p class="declaration">
        Por medio del presente, <strong><?= e($permit['empleado_nombre']) ?></strong> solicita autorización para ausentarse
        temporalmente del centro de trabajo en la fecha y horario indicados. La persona trabajadora se compromete a regresar
        a la hora prevista o avisar oportunamente si surge algún retraso. La autorización quedará formalizada con la firma del patrón.
    </p>

    <div class="row g-5 signature-section">
        <div class="col-6">
            <div class="signature-line">
                <?= e($permit['empleado_nombre']) ?>
                <span>Firma del empleado solicitante</span>
            </div>
        </div>
        <div class="col-6">
            <div class="signature-line">
                <?= e(OWNER_NAME) ?>
                <span>Firma del patrón que autoriza</span>
            </div>
        </div>
    </div>

    <div class="internal-note">
        Documento interno de control. La firma del patrón acredita la autorización para la fecha y horario señalados.
    </div>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
