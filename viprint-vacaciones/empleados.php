<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$params = [];
$sql = 'SELECT * FROM empleados';
if ($q !== '') {
    $sql .= ' WHERE nombre LIKE :q OR puesto LIKE :q';
    $params['q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY activo DESC, nombre ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

$pageTitle = 'Empleados';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Empleados</h1>
        <p class="text-secondary mb-0">Registra la fecha de ingreso y el saldo disponible actual.</p>
    </div>
    <a class="btn btn-viprint" href="<?= BASE_URL ?>/empleado_form.php">Nuevo empleado</a>
</div>

<div class="card mb-4 no-print"><div class="card-body">
    <form class="row g-2" method="get">
        <div class="col-md-10"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o puesto"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary">Buscar</button></div>
    </form>
</div></div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Empleado</th><th>Ingreso</th><th>Antigüedad</th><th>Disponible</th><th>Pendiente</th><th>Próximo aniversario</th><th></th></tr></thead>
            <tbody>
            <?php if (!$employees): ?>
                <tr><td colspan="7" class="text-center text-secondary py-5">Todavía no hay empleados registrados.</td></tr>
            <?php endif; ?>
            <?php foreach ($employees as $employee):
                $balance = employeeBalance($pdo, (int)$employee['id']);
                $years = completedServiceYears($employee['fecha_ingreso']);
                $next = nextAnniversary($employee['fecha_ingreso'], (int)$employee['ultimo_anio_procesado']);
            ?>
                <tr class="<?= !$employee['activo'] ? 'table-light text-secondary' : '' ?>">
                    <td><strong><?= e($employee['nombre']) ?></strong><br><small><?= e($employee['puesto']) ?></small></td>
                    <td><?= e(formatDate($employee['fecha_ingreso'])) ?></td>
                    <td><?= $years ?> año(s)</td>
                    <td><span class="badge bg-success fs-6"><?= $balance['disponibles'] ?> día(s)</span></td>
                    <td><?= $balance['pendientes'] ?> día(s)</td>
                    <td><?= e(formatDate($next->format('Y-m-d'))) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/empleado.php?id=<?= (int)$employee['id'] ?>">Ver</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/empleado_form.php?id=<?= (int)$employee['id'] ?>">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
