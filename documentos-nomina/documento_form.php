<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$document = $id > 0 ? getDocument($pdo, $id) : null;
if ($id > 0 && !$document) {
    flash('danger', 'El documento solicitado no existe.');
    redirect('/documentos.php');
}

$employees = $pdo->query('SELECT * FROM empleados ORDER BY nombre ASC')->fetchAll();
$type = $document['tipo'] ?? 'transferencia';

$pageTitle = $document ? 'Editar documento' : 'Nuevo documento';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $document ? 'Editar documento' : 'Nuevo documento' ?></h1>
        <p class="text-secondary mb-0">Captura solamente el empleado, la fecha trabajada y la cantidad pagada.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/documentos.php">Ver historial</a>
</div>

<?php if (!$employees): ?>
    <div class="alert alert-warning">
        Primero debes registrar al menos un empleado.
        <a href="<?= BASE_URL ?>/empleados.php" class="alert-link">Ir a empleados</a>
    </div>
<?php else: ?>
<form method="post" action="<?= BASE_URL ?>/guardar_documento.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= (int)($document['id'] ?? 0) ?>">

    <div class="card mb-4">
        <div class="card-header bg-white py-3"><h2 class="h5 mb-0">1. Tipo de documento</h2></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="type-option">
                        <input type="radio" id="tipo_transferencia" name="tipo" value="transferencia" <?= $type === 'transferencia' ? 'checked' : '' ?>>
                        <label for="tipo_transferencia">
                            <div class="type-title">Pago normal por transferencia</div>
                            <div class="type-description mt-2">Constancia del pago ordinario realizado mediante transferencia bancaria.</div>
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="type-option">
                        <input type="radio" id="tipo_bono" name="tipo" value="bono_efectivo" <?= $type === 'bono_efectivo' ? 'checked' : '' ?>>
                        <label for="tipo_bono">
                            <div class="type-title">Bono semanal en efectivo</div>
                            <div class="type-description mt-2">Constancia del bono adicional semanal cuyo importe puede variar.</div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3"><h2 class="h5 mb-0">2. Datos del pago</h2></div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="empleado_id" class="form-label required">Empleado</label>
                    <select class="form-select" id="empleado_id" name="empleado_id" required>
                        <option value="">Selecciona un empleado</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int)$employee['id'] ?>" <?= (int)($document['empleado_id'] ?? 0) === (int)$employee['id'] ? 'selected' : '' ?>>
                                <?= e($employee['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><a href="<?= BASE_URL ?>/empleados.php">Agregar o editar empleados</a></div>
                </div>
                <div class="col-md-3">
                    <label for="fecha_trabajada" class="form-label required">Fecha trabajada</label>
                    <input type="date" class="form-control" id="fecha_trabajada" name="fecha_trabajada" required value="<?= e($document['fecha_trabajada'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label for="cantidad" class="form-label required">Cantidad pagada</label>
                    <input type="number" class="form-control" id="cantidad" name="cantidad" min="0.01" max="99999999.99" step="0.01" required data-amount-input value="<?= e(isset($document['cantidad']) ? number_format((float)$document['cantidad'], 2, '.', '') : '') ?>" placeholder="0.00">
                </div>
                <div class="col-12">
                    <div class="amount-preview" data-amount-preview>$0.00 MXN</div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-end gap-2 py-3">
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/documentos.php">Cancelar</a>
            <button class="btn btn-viprint" type="submit">Guardar y ver documento</button>
        </div>
    </div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
