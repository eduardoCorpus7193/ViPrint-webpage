<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$preselectedEmployeeId = filter_input(INPUT_GET, 'empleado_id', FILTER_VALIDATE_INT) ?: 0;
$request = [];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM solicitudes_vacaciones WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $request = $stmt->fetch() ?: [];
    if (!$request) {
        flash('danger', 'La solicitud no existe.');
        redirect('/solicitudes.php');
    }
    if ($request['estado'] !== 'PENDIENTE') {
        flash('warning', 'Solo se pueden editar solicitudes pendientes.');
        redirect('/ver_solicitud.php?id=' . $id);
    }
}

$employees = $pdo->query('SELECT * FROM empleados WHERE activo = 1 ORDER BY nombre')->fetchAll();
$selectedEmployee = (int)($request['empleado_id'] ?? $preselectedEmployeeId);
$value = static fn(string $field, string $default = ''): string => (string)($request[$field] ?? $default);

$pageTitle = $id ? 'Editar solicitud' : 'Nueva solicitud de vacaciones';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1"><?= $id ? 'Editar solicitud' : 'Solicitud de vacaciones' ?></h1>
                <p class="text-secondary mb-0">Selecciona al empleado y el periodo solicitado.</p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/solicitudes.php">Volver</a>
        </div>

        <?php if (!$employees): ?>
            <div class="alert alert-warning">Primero debes <a href="<?= BASE_URL ?>/empleado_form.php" class="alert-link">registrar un empleado</a>.</div>
        <?php else: ?>
        <form action="<?= BASE_URL ?>/guardar_solicitud.php" method="post" class="card" novalidate>
            <div class="card-body p-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="row g-4">
                    <div class="col-md-8">
                        <label class="form-label required" for="empleado_id">Empleado</label>
                        <select class="form-select form-select-lg" id="empleado_id" name="empleado_id" required <?= $id ? 'disabled' : '' ?>>
                            <option value="">Selecciona...</option>
                            <?php foreach ($employees as $employee):
                                $balance = employeeBalance($pdo, (int)$employee['id']);
                                $years = completedServiceYears($employee['fecha_ingreso']);
                            ?>
                                <option value="<?= (int)$employee['id'] ?>"
                                    data-available="<?= $balance['solicitables'] ?>"
                                    data-hire="<?= e(formatDate($employee['fecha_ingreso'])) ?>"
                                    data-seniority="<?= $years ?>"
                                    <?= $selectedEmployee === (int)$employee['id'] ? 'selected' : '' ?>>
                                    <?= e($employee['nombre']) ?> — <?= $balance['solicitables'] ?> día(s) solicitables
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($id): ?><input type="hidden" name="empleado_id" value="<?= $selectedEmployee ?>"><?php endif; ?>
                        <div class="invalid-feedback">Selecciona un empleado.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="fecha_solicitud">Fecha de solicitud</label>
                        <input type="date" class="form-control form-control-lg" id="fecha_solicitud" name="fecha_solicitud" value="<?= e($value('fecha_solicitud', date('Y-m-d'))) ?>" required>
                    </div>
                    <div class="col-12">
                        <div id="employeeInfo" class="helper-panel">Selecciona un empleado para consultar su saldo.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="fecha_inicio">Primer día de vacaciones</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= e($value('fecha_inicio')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="fecha_fin">Último día de vacaciones</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= e($value('fecha_fin')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="dias_solicitados">Días solicitados</label>
                        <input type="number" min="1" max="365" class="form-control" id="dias_solicitados" name="dias_solicitados" value="<?= e($value('dias_solicitados')) ?>" required>
                        <div class="form-text">Se calcula excluyendo domingos. Corrige el número si hay un día festivo o un acuerdo distinto.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="observaciones">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3" maxlength="500" placeholder="Opcional"><?= e($value('observaciones')) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2 p-3">
                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/solicitudes.php">Cancelar</a>
                <button class="btn btn-viprint px-4" type="submit">Guardar e imprimir</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
