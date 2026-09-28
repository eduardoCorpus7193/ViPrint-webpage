<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = $pdo->prepare('SELECT * FROM registros_horas_extra WHERE id = :id');
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    flash('danger', 'El registro no existe.');
    redirect('/registros.php');
}

$pageTitle = 'Formato ' . $row['folio'];
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/registros.php">Volver</a>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/formulario.php?id=<?= (int)$row['id'] ?>">Editar</a>
        <button class="btn btn-viprint" onclick="window.print()">Imprimir</button>
    </div>
</div>

<article class="print-sheet mx-auto">
    <header class="print-header mb-4 pb-3">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <div class="company-name"><?= e(COMPANY_NAME) ?></div>
                <h1 class="h4 mb-1">Registro diario de horas extras</h1>
                <p class="mb-0 text-secondary">Comprobante para integrar el cálculo semanal.</p>
            </div>
            <div class="folio-box">
                <span>Folio</span>
                <strong><?= e($row['folio']) ?></strong>
            </div>
        </div>
    </header>

    <table class="table detail-table mb-4">
        <tbody>
            <tr>
                <th>Nombre del trabajador</th>
                <td colspan="3"><?= e($row['trabajador']) ?></td>
            </tr>
            <tr>
                <th>Fecha</th>
                <td><?= e(formatDate($row['fecha'])) ?></td>
                <th>Horario extra</th>
                <td><?= e(timeShort($row['hora_inicio'])) ?> a <?= e(timeShort($row['hora_fin'])) ?></td>
            </tr>
            <tr class="total-row">
                <th>Cantidad de horas extras</th>
                <td colspan="3"><?= e(formatHours((float)$row['total_horas'])) ?></td>
            </tr>
        </tbody>
    </table>

    <p class="declaration">
        El trabajador manifiesta que el horario anterior corresponde al tiempo extraordinario efectivamente laborado durante la fecha indicada. Esta hoja deberá conservarse para integrar el control y cálculo semanal de horas extras.
    </p>

    <div class="row signature-section">
        <div class="col-6 px-4">
            <div class="signature-line">
                <?= e($row['trabajador']) ?><br>
                <span>Firma del trabajador</span>
            </div>
        </div>
        <div class="col-6 px-4">
            <div class="signature-line">
                <?= e(OWNER_NAME) ?><br>
                <span>Autorizó / patrón</span>
            </div>
        </div>
    </div>

    <div class="weekly-note mt-5">
        <strong>Control administrativo:</strong> anexar esta hoja con los demás registros del mismo trabajador al cierre de la semana.
    </div>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
