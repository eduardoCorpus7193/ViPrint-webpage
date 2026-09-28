<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$editId = (int)($_GET['editar'] ?? 0);
$editEmployee = $editId > 0 ? getEmployee($pdo, $editId) : null;
$employees = $pdo->query(
    'SELECT e.*,
            (SELECT COUNT(*) FROM permisos_salida p WHERE p.empleado_id = e.id) AS permisos
     FROM empleados e
     ORDER BY e.nombre ASC'
)->fetchAll();

$pageTitle = 'Empleados';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Empleados</h1>
        <p class="text-secondary mb-0">Los nombres guardados aparecerán en el combo de los permisos.</p>
    </div>
    <a href="<?= BASE_URL ?>/permiso_form.php" class="btn btn-viprint">Nuevo permiso</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0"><?= $editEmployee ? 'Editar empleado' : 'Agregar empleado' ?></h2>
            </div>
            <div class="card-body">
                <form method="post" action="<?= BASE_URL ?>/guardar_empleado.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)($editEmployee['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label for="nombre" class="form-label required">Nombre completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" maxlength="150" required autofocus value="<?= e($editEmployee['nombre'] ?? '') ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-viprint" type="submit"><?= $editEmployee ? 'Guardar cambios' : 'Agregar empleado' ?></button>
                        <?php if ($editEmployee): ?>
                            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/empleados.php">Cancelar edición</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Lista de empleados</h2></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Nombre</th><th>Permisos</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    <?php if (!$employees): ?>
                        <tr><td colspan="3" class="text-center text-secondary py-5">Agrega el primer empleado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($employee['nombre']) ?></td>
                            <td><?= (int)$employee['permisos'] ?></td>
                            <td class="text-end action-buttons">
                                <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/empleados.php?editar=<?= (int)$employee['id'] ?>">Editar</a>
                                <form method="post" action="<?= BASE_URL ?>/eliminar_empleado.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$employee['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm-delete="¿Deseas eliminar a este empleado?">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
