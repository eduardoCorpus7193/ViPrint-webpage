<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$permit = $id > 0 ? getPermit($pdo, $id) : null;
if ($id > 0 && !$permit) {
    flash('danger', 'El permiso solicitado no existe.');
    redirect('/permisos.php');
}

$employees = $pdo->query('SELECT * FROM empleados ORDER BY nombre ASC')->fetchAll();
$pageTitle = $permit ? 'Editar permiso' : 'Nuevo permiso';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $permit ? 'Editar permiso' : 'Nuevo permiso de salida' ?></h1>
        <p class="text-secondary mb-0">Captura los datos de la salida temporal y genera la hoja para autorización.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/permisos.php">Ver historial</a>
</div>

<?php if (!$employees): ?>
    <div class="alert alert-warning">
        Primero debes registrar al menos un empleado.
        <a href="<?= BASE_URL ?>/empleados.php" class="alert-link">Ir a empleados</a>
    </div>
<?php else: ?>
<form method="post" action="<?= BASE_URL ?>/guardar_permiso.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= (int)($permit['id'] ?? 0) ?>">

    <div class="card mb-4">
        <div class="card-header bg-white py-3"><h2 class="h5 mb-0">1. Empleado y horario</h2></div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="empleado_id" class="form-label required">Empleado</label>
                    <select class="form-select" id="empleado_id" name="empleado_id" required>
                        <option value="">Selecciona un empleado</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int)$employee['id'] ?>" <?= (int)($permit['empleado_id'] ?? 0) === (int)$employee['id'] ? 'selected' : '' ?>>
                                <?= e($employee['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><a href="<?= BASE_URL ?>/empleados.php">Agregar o editar empleados</a></div>
                </div>
                <div class="col-md-3">
                    <label for="fecha_permiso" class="form-label required">Fecha del permiso</label>
                    <input type="date" class="form-control" id="fecha_permiso" name="fecha_permiso" required value="<?= e($permit['fecha_permiso'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label for="estado" class="form-label required">Estado</label>
                    <select class="form-select" id="estado" name="estado" required>
                        <?php $currentStatus = $permit['estado'] ?? 'pendiente'; ?>
                        <option value="pendiente" <?= $currentStatus === 'pendiente' ? 'selected' : '' ?>>Pendiente de firma</option>
                        <option value="autorizado" <?= $currentStatus === 'autorizado' ? 'selected' : '' ?>>Autorizado</option>
                        <option value="cancelado" <?= $currentStatus === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="hora_salida" class="form-label required">Hora de salida</label>
                    <input type="time" class="form-control" id="hora_salida" name="hora_salida" required data-start-time value="<?= e(isset($permit['hora_salida']) ? substr($permit['hora_salida'], 0, 5) : '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="hora_regreso_prevista" class="form-label required">Regreso previsto</label>
                    <input type="time" class="form-control" id="hora_regreso_prevista" name="hora_regreso_prevista" required data-end-time value="<?= e(isset($permit['hora_regreso_prevista']) ? substr($permit['hora_regreso_prevista'], 0, 5) : '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="hora_regreso_real" class="form-label">Regreso real</label>
                    <input type="time" class="form-control" id="hora_regreso_real" name="hora_regreso_real" value="<?= e(!empty($permit['hora_regreso_real']) ? substr($permit['hora_regreso_real'], 0, 5) : '') ?>">
                    <div class="form-text">Puede registrarse después de que regrese.</div>
                </div>
                <div class="col-12">
                    <div class="duration-preview" data-duration-preview>Captura las horas para calcular la duración</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3"><h2 class="h5 mb-0">2. Motivo y condiciones</h2></div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-5">
                    <label for="motivo_tipo" class="form-label required">Tipo de motivo</label>
                    <?php $currentReason = $permit['motivo_tipo'] ?? 'personal'; ?>
                    <select class="form-select" id="motivo_tipo" name="motivo_tipo" required>
                        <option value="personal" <?= $currentReason === 'personal' ? 'selected' : '' ?>>Otro asunto personal</option>
                        <option value="medico" <?= $currentReason === 'medico' ? 'selected' : '' ?>>Consulta o asunto médico</option>
                        <option value="familiar" <?= $currentReason === 'familiar' ? 'selected' : '' ?>>Asunto familiar</option>
                        <option value="tramite" <?= $currentReason === 'tramite' ? 'selected' : '' ?>>Trámite personal</option>
                        <option value="laboral" <?= $currentReason === 'laboral' ? 'selected' : '' ?>>Actividad o diligencia de trabajo</option>
                        <option value="emergencia" <?= $currentReason === 'emergencia' ? 'selected' : '' ?>>Emergencia</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label for="destino" class="form-label">Destino o lugar al que se dirige</label>
                    <input type="text" class="form-control" id="destino" name="destino" maxlength="200" value="<?= e($permit['destino'] ?? '') ?>" placeholder="Ej. clínica, banco, escuela, domicilio">
                </div>
                <div class="col-12">
                    <label for="motivo_detalle" class="form-label required">Descripción breve del motivo</label>
                    <textarea class="form-control" id="motivo_detalle" name="motivo_detalle" rows="3" maxlength="500" required placeholder="Describe brevemente por qué necesita salir"><?= e($permit['motivo_detalle'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="tratamiento_tiempo" class="form-label required">Tratamiento del tiempo</label>
                    <?php $currentTreatment = $permit['tratamiento_tiempo'] ?? 'por_definir'; ?>
                    <select class="form-select" id="tratamiento_tiempo" name="tratamiento_tiempo" required>
                        <option value="por_definir" <?= $currentTreatment === 'por_definir' ? 'selected' : '' ?>>Por definir por el patrón</option>
                        <option value="con_goce" <?= $currentTreatment === 'con_goce' ? 'selected' : '' ?>>Con goce de sueldo</option>
                        <option value="sin_goce" <?= $currentTreatment === 'sin_goce' ? 'selected' : '' ?>>Sin goce de sueldo</option>
                        <option value="reposicion" <?= $currentTreatment === 'reposicion' ? 'selected' : '' ?>>Tiempo sujeto a reposición</option>
                        <option value="salida_laboral" <?= $currentTreatment === 'salida_laboral' ? 'selected' : '' ?>>Salida por actividad de trabajo</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <input type="text" class="form-control" id="observaciones" name="observaciones" maxlength="500" value="<?= e($permit['observaciones'] ?? '') ?>" placeholder="Indicaciones o acuerdos adicionales">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-end gap-2 py-3">
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/permisos.php">Cancelar</a>
            <button class="btn btn-viprint" type="submit">Guardar y ver permiso</button>
        </div>
    </div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
