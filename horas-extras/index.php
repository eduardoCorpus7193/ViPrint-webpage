<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$recordsThisWeek = (int)$pdo->query(
    'SELECT COUNT(*) FROM registros_horas_extra
     WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)'
)->fetchColumn();

$hoursThisWeek = (float)$pdo->query(
    'SELECT COALESCE(SUM(total_horas), 0) FROM registros_horas_extra
     WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)'
)->fetchColumn();

$workersThisWeek = (int)$pdo->query(
    'SELECT COUNT(DISTINCT trabajador) FROM registros_horas_extra
     WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)'
)->fetchColumn();

$totalRecords = (int)$pdo->query('SELECT COUNT(*) FROM registros_horas_extra')->fetchColumn();

$recent = $pdo->query(
    'SELECT * FROM registros_horas_extra
     ORDER BY fecha DESC, id DESC
     LIMIT 8'
)->fetchAll();

$pageTitle = 'Control de horas extras';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Control de horas extras</h1>
        <p class="text-secondary mb-0">Registra e imprime una hoja por cada día trabajado fuera del horario ordinario.</p>
    </div>
    <a class="btn btn-viprint" href="<?= BASE_URL ?>/formulario.php">Nuevo registro</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $recordsThisWeek ?></div>
            <div class="metric-label">Registros esta semana</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= e(formatHours($hoursThisWeek)) ?></div>
            <div class="metric-label">Horas registradas esta semana</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $workersThisWeek ?></div>
            <div class="metric-label">Trabajadores esta semana</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $totalRecords ?></div>
            <div class="metric-label">Registros históricos</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h2 class="h5 mb-0">Registros recientes</h2>
                <a href="<?= BASE_URL ?>/registros.php" class="btn btn-sm btn-outline-secondary no-print">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Trabajador</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$recent): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-5">Todavía no hay registros.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><?= e($row['folio']) ?></td>
                            <td class="fw-semibold"><?= e($row['trabajador']) ?></td>
                            <td><?= e(formatDate($row['fecha'])) ?></td>
                            <td><?= e(timeShort($row['hora_inicio'])) ?> a <?= e(timeShort($row['hora_fin'])) ?></td>
                            <td><?= e(formatHours((float)$row['total_horas'])) ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/ver.php?id=<?= (int)$row['id'] ?>">Ver</a></td>
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
                <h2 class="h5">Proceso semanal</h2>
                <p class="text-secondary">Cada vez que una persona trabaje horas extra, genera e imprime un registro diario.</p>
                <div class="helper-panel small">
                    <strong>Al terminar la semana:</strong> reúne las hojas firmadas de cada trabajador y utiliza esos comprobantes para calcular el pago correspondiente.
                </div>
                <hr>
                <p class="small text-secondary mb-0">Este sistema muestra un total informativo de la semana, pero no calcula importes, horas dobles ni horas triples.</p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
