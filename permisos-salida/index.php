<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$employeeCount = (int)$pdo->query('SELECT COUNT(*) FROM empleados')->fetchColumn();
$permitsThisWeek = (int)$pdo->query(
    'SELECT COUNT(*) FROM permisos_salida
     WHERE YEARWEEK(fecha_permiso, 1) = YEARWEEK(CURDATE(), 1)'
)->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM permisos_salida WHERE estado = 'pendiente'")->fetchColumn();
$authorizedThisWeek = (int)$pdo->query(
    "SELECT COUNT(*) FROM permisos_salida
     WHERE estado = 'autorizado'
       AND YEARWEEK(fecha_permiso, 1) = YEARWEEK(CURDATE(), 1)"
)->fetchColumn();

$recent = $pdo->query(
    'SELECT p.*, e.nombre AS empleado_nombre
     FROM permisos_salida p
     INNER JOIN empleados e ON e.id = p.empleado_id
     ORDER BY p.fecha_permiso DESC, p.hora_salida DESC, p.id DESC
     LIMIT 8'
)->fetchAll();

$pageTitle = 'Permisos temporales de salida';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Permisos temporales de salida</h1>
        <p class="text-secondary mb-0">Genera, imprime y conserva las autorizaciones de salida del personal.</p>
    </div>
    <a class="btn btn-viprint" href="<?= BASE_URL ?>/permiso_form.php">Nuevo permiso</a>
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
            <div class="metric-number"><?= $permitsThisWeek ?></div>
            <div class="metric-label">Permisos esta semana</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $pendingCount ?></div>
            <div class="metric-label">Pendientes de firma</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-number"><?= $authorizedThisWeek ?></div>
            <div class="metric-label">Autorizados esta semana</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h2 class="h5 mb-0">Permisos recientes</h2>
                <a href="<?= BASE_URL ?>/permisos.php" class="btn btn-sm btn-outline-secondary">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Empleado</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$recent): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-5">Todavía no hay permisos.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><?= e($row['folio']) ?></td>
                            <td class="fw-semibold"><?= e($row['empleado_nombre']) ?></td>
                            <td><?= e(formatDate($row['fecha_permiso'])) ?></td>
                            <td><?= e(substr($row['hora_salida'], 0, 5)) ?>–<?= e(substr($row['hora_regreso_prevista'], 0, 5)) ?></td>
                            <td><span class="badge text-bg-<?= e(statusBadge($row['estado'])) ?>"><?= e(statusLabel($row['estado'])) ?></span></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/ver_permiso.php?id=<?= (int)$row['id'] ?>">Ver</a></td>
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
                <h2 class="h5">Flujo de uso</h2>
                <ol class="text-secondary ps-3 mb-4">
                    <li class="mb-2">Selecciona al empleado y la fecha.</li>
                    <li class="mb-2">Captura hora de salida, regreso previsto y motivo.</li>
                    <li class="mb-2">Imprime el permiso para recabar firmas.</li>
                    <li>Edita después para registrar el regreso real.</li>
                </ol>
                <div class="helper-panel small">
                    El sistema no requiere usuario ni contraseña. Los nombres pueden agregarse o editarse desde el catálogo de empleados.
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
