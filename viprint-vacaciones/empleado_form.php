<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$employee = [];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM empleados WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $employee = $stmt->fetch() ?: [];
    if (!$employee) {
        flash('danger', 'El empleado no existe.');
        redirect('/empleados.php');
    }
}

$value = static fn(string $field, string $default = ''): string => (string)($employee[$field] ?? $default);
$pageTitle = $id ? 'Editar empleado' : 'Registrar empleado';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1"><?= $id ? 'Editar empleado' : 'Registrar empleado' ?></h1>
                <p class="text-secondary mb-0"><?= $id ? 'Actualiza nombre, puesto o estado.' : 'El saldo inicial debe ser el disponible real a la fecha de registro.' ?></p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/empleados.php">Volver</a>
        </div>

        <form action="<?= BASE_URL ?>/guardar_empleado.php" method="post" class="card" novalidate>
            <div class="card-body p-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="row g-4">
                    <div class="col-md-7">
                        <label class="form-label required" for="nombre">Nombre completo</label>
                        <input class="form-control form-control-lg" id="nombre" name="nombre" value="<?= e($value('nombre')) ?>" required autofocus>
                        <div class="invalid-feedback">Escribe el nombre del empleado.</div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="puesto">Puesto</label>
                        <input class="form-control form-control-lg" id="puesto" name="puesto" value="<?= e($value('puesto')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="fecha_ingreso">Fecha de ingreso</label>
                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" value="<?= e($value('fecha_ingreso')) ?>" required <?= $id ? 'readonly' : '' ?>>
                        <?php if ($id): ?><div class="form-text">Para corregir esta fecha, respalda primero la información porque afecta los aniversarios.</div><?php endif; ?>
                    </div>
                    <?php if (!$id): ?>
                    <div class="col-md-6">
                        <label class="form-label required" for="saldo_inicial">Días disponibles actualmente</label>
                        <input type="number" min="0" max="365" class="form-control" id="saldo_inicial" name="saldo_inicial" value="0" required>
                        <div class="form-text">Captura el saldo real existente hoy; no necesariamente los días legales completos del año.</div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-6">
                        <label class="form-label">Saldo inicial registrado</label>
                        <input class="form-control" value="<?= (int)$employee['saldo_inicial'] ?> día(s)" readonly>
                        <div class="form-text">Los cambios de saldo se realizan mediante ajustes en el expediente del empleado.</div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= $employee['activo'] ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="activo">Empleado activo</label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2 p-3">
                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/empleados.php">Cancelar</a>
                <button class="btn btn-viprint px-4" type="submit">Guardar</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
