<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$document = getDocument($pdo, $id);
if (!$document) {
    flash('danger', 'El documento solicitado no existe.');
    redirect('/documentos.php');
}

$isCashBonus = $document['tipo'] === 'bono_efectivo';
$title = $isCashBonus ? 'Recibo de bono semanal en efectivo' : 'Recibo de pago por transferencia';
$paymentMethod = $isCashBonus ? 'Efectivo' : 'Transferencia bancaria';

$pageTitle = $title;
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4 no-print">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/documentos.php">Volver al historial</a>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <a class="btn btn-outline-dark" href="<?= BASE_URL ?>/documento_form.php?id=<?= (int)$document['id'] ?>">Editar</a>
        <button class="btn btn-viprint" type="button" onclick="window.print()">Imprimir</button>
    </div>
</div>

<article class="print-sheet">
    <header class="print-header d-flex justify-content-between align-items-start gap-3 pb-3 mb-4">
        <div>
            <div class="company-name"><?= e(COMPANY_NAME) ?></div>
            <div class="small text-secondary">Documentos internos de nómina</div>
            <div class="small text-secondary">RFC: <?= e(COMPANY_RFC) ?></div>
        </div>
        <div class="folio-box">
            <span>Folio</span>
            <strong><?= e($document['folio']) ?></strong>
        </div>
    </header>

    <h1 class="h4 text-center receipt-title text-uppercase mb-4"><?= e($title) ?></h1>

    <table class="table table-bordered receipt-data mb-4">
        <tbody>
            <tr><th>Empleado</th><td><?= e($document['empleado_nombre']) ?></td></tr>
            <tr><th>Fecha trabajada</th><td><?= e(formatDate($document['fecha_trabajada'])) ?></td></tr>
            <tr><th>Forma de pago</th><td><?= e($paymentMethod) ?></td></tr>
        </tbody>
    </table>

    <div class="receipt-amount mb-4"><?= e(formatMoney((float)$document['cantidad'])) ?></div>

    <?php if ($isCashBonus): ?>
        <p class="declaration">
            Por medio del presente, yo, <strong><?= e($document['empleado_nombre']) ?></strong>, hago constar que recibí de
            <strong><?= e(COMPANY_NAME) ?></strong> la cantidad de <strong><?= e(formatMoney((float)$document['cantidad'])) ?></strong>,
            pagada en efectivo por concepto de <strong>bono semanal adicional</strong> correspondiente al trabajo realizado
            en la fecha <strong><?= e(formatDate($document['fecha_trabajada'])) ?></strong>.
        </p>
    <?php else: ?>
        <p class="declaration">
            Por medio del presente, yo, <strong><?= e($document['empleado_nombre']) ?></strong>, hago constar que recibí de
            <strong><?= e(COMPANY_NAME) ?></strong> la cantidad de <strong><?= e(formatMoney((float)$document['cantidad'])) ?></strong>,
            pagada mediante <strong>transferencia bancaria</strong>, correspondiente al pago por el trabajo realizado
            en la fecha <strong><?= e(formatDate($document['fecha_trabajada'])) ?></strong>.
        </p>
    <?php endif; ?>

    <p class="declaration">
        Firmo el presente documento como constancia de la entrega y recepción de la cantidad indicada.
    </p>

    <div class="row g-5 signature-section">
        <div class="col-6">
            <div class="signature-line">
                <?= e($document['empleado_nombre']) ?>
                <span>Firma del empleado</span>
            </div>
        </div>
        <div class="col-6">
            <div class="signature-line">
                <?= e(OWNER_NAME) ?>
                <span>Firma del empleador</span>
            </div>
        </div>
    </div>

    <div class="internal-note">
        Documento interno de control. No sustituye el CFDI de nómina ni las obligaciones laborales y fiscales aplicables.
    </div>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
