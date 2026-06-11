<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$employees = $pdo->query('SELECT * FROM empleados WHERE activo = 1 ORDER BY nombre')->fetchAll();
$totalAvailable = 0;
foreach ($employees as $employee) {
    $totalAvailable += employeeBalance($pdo, (int)$employee['id'])['disponibles'];
}

$pending = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes_vacaciones WHERE estado = "PENDIENTE"')->fetchColumn();
$approvedThisYear = (int)$pdo->query('SELECT COALESCE(SUM(dias_solicitados), 0) FROM solicitudes_vacaciones WHERE estado = "AUTORIZADA" AND YEAR(fecha_inicio) = YEAR(CURDATE())')->fetchColumn();

$recent = $pdo->query(
    'SELECT s.*, e.nombre AS empleado_nombre
     FROM solicitudes_vacaciones s
     INNER JOIN empleados e ON e.id = s.empleado_id
     ORDER BY s.created_at DESC
     LIMIT 8'
)->fetchAll();

$pageTitle = 'Control de vacaciones';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Control de vacaciones</h1>
        <p class="text-secondary mb-0">Saldos, aniversarios y solicitudes en un solo lugar.</p>
    </div>
    <div class="d-flex gap-2 no-print">
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/empleado_form.php">Registrar empleado</a>
        <a class="btn btn-viprint" href="<?= BASE_URL ?>/solicitud_form.php">Nueva solicitud</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= count($employees) ?></div>
            <div class="metric-label">Empleados activos</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $totalAvailable ?></div>
            <div class="metric-label">Días disponibles totales</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $pending ?></div>
            <div class="metric-label">Solicitudes pendientes</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $approvedThisYear ?></div>
            <div class="metric-label">Días autorizados este año</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h2 class="h5 mb-0">Solicitudes recientes</h2>
                <a href="<?= BASE_URL ?>/solicitudes.php" class="btn btn-sm btn-outline-secondary no-print">Ver todas</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Folio</th><th>Empleado</th><th>Periodo</th><th>Días</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$recent): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-5">Todavía no hay solicitudes.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><?= e($row['folio']) ?></td>
                            <td class="fw-semibold"><?= e($row['empleado_nombre']) ?></td>
                            <td><?= e(formatDate($row['fecha_inicio'])) ?> al <?= e(formatDate($row['fecha_fin'])) ?></td>
                            <td><?= (int)$row['dias_solicitados'] ?></td>
                            <td><span class="badge bg-<?= e(statusBadge($row['estado'])) ?>"><?= e(statusLabel($row['estado'])) ?></span></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/ver_solicitud.php?id=<?= (int)$row['id'] ?>">Ver</a></td>
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
                <h2 class="h5">Actualización automática</h2>
                <p class="text-secondary">Al abrir el sistema se revisan los aniversarios de ingreso. Cuando corresponde, se agregan automáticamente los días del nuevo año de servicio.</p>
                <div class="helper-panel small">
                    <strong>Importante:</strong> el saldo anterior no se elimina. El sistema suma la nueva asignación y descuenta únicamente las solicitudes autorizadas.
                </div>
                <hr>
                <p class="small text-secondary mb-0">Al registrar por primera vez a un empleado, captura los días que realmente tiene disponibles en ese momento. A partir de ahí, el sistema continuará el control anual.</p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
