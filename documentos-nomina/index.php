<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$employeeCount = (int)$pdo->query('SELECT COUNT(*) FROM empleados')->fetchColumn();
$documentsThisWeek = (int)$pdo->query(
    'SELECT COUNT(*) FROM documentos_nomina
     WHERE YEARWEEK(fecha_trabajada, 1) = YEARWEEK(CURDATE(), 1)'
)->fetchColumn();
$transferThisWeek = (float)$pdo->query(
    "SELECT COALESCE(SUM(cantidad), 0) FROM documentos_nomina
     WHERE tipo = 'transferencia'
       AND YEARWEEK(fecha_trabajada, 1) = YEARWEEK(CURDATE(), 1)"
)->fetchColumn();
$cashThisWeek = (float)$pdo->query(
    "SELECT COALESCE(SUM(cantidad), 0) FROM documentos_nomina
     WHERE tipo = 'bono_efectivo'
       AND YEARWEEK(fecha_trabajada, 1) = YEARWEEK(CURDATE(), 1)"
)->fetchColumn();

$recent = $pdo->query(
    'SELECT d.*, e.nombre AS empleado_nombre
     FROM documentos_nomina d
     INNER JOIN empleados e ON e.id = d.empleado_id
     ORDER BY d.fecha_trabajada DESC, d.id DESC
     LIMIT 8'
)->fetchAll();


$pageTitle = 'Documentos internos de nómina';
require __DIR__ . '/includes/header.php';
?>

<!doctype html>
<html lang="es"></html>
<head>
<link href="../img/favicon.ico" rel="icon">
</head>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Documentos internos de nómina</h1>
        <p class="text-secondary mb-0">Genera recibos sencillos de pago por transferencia y bonos semanales en efectivo.</p>
    </div>
    <a class="btn btn-viprint" href="<?= BASE_URL ?>/documento_form.php">Nuevo documento</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $employeeCount ?></div>
            <div class="metric-label">Empleados registrados</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $documentsThisWeek ?></div>
            <div class="metric-label">Documentos esta semana</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number fs-4"><?= e(formatMoney($transferThisWeek)) ?></div>
            <div class="metric-label">Transferencias esta semana</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number fs-4"><?= e(formatMoney($cashThisWeek)) ?></div>
            <div class="metric-label">Bonos en efectivo esta semana</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h2 class="h5 mb-0">Documentos recientes</h2>
                <a href="<?= BASE_URL ?>/documentos.php" class="btn btn-sm btn-outline-secondary">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Empleado</th>
                            <th>Tipo</th>
                            <th>Fecha trabajada</th>
                            <th>Cantidad</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$recent): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-5">Todavía no hay documentos.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><?= e($row['folio']) ?></td>
                            <td class="fw-semibold"><?= e($row['empleado_nombre']) ?></td>
                            <td><?= e(documentTypeShort($row['tipo'])) ?></td>
                            <td><?= e(formatDate($row['fecha_trabajada'])) ?></td>
                            <td class="fw-semibold"><?= e(formatMoney((float)$row['cantidad'])) ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/ver_documento.php?id=<?= (int)$row['id'] ?>">Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body p-4">
                <h2 class="h5">Uso sencillo</h2>
                <ol class="text-secondary ps-3 mb-4">
                    <li class="mb-2">Registra una sola vez el nombre del empleado.</li>
                    <li class="mb-2">Selecciona el tipo de pago.</li>
                    <li class="mb-2">Captura la fecha trabajada y la cantidad.</li>
                    <li>Imprime el documento y recaba ambas firmas.</li>
                </ol>
                <div class="helper-panel small">
                    Los nombres se conservan para seleccionarlos después desde un combo. No se requiere iniciar sesión.
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
