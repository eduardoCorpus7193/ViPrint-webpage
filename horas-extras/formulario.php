<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$row = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM registros_horas_extra WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch() ?: [];

    if (!$row) {
        flash('danger', 'El registro no existe.');
        redirect('/registros.php');
    }
}

$value = static fn(string $field, string $default = ''): string => (string)($row[$field] ?? $default);
$pageTitle = $id ? 'Editar horas extras' : 'Registrar horas extras';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1"><?= $id ? 'Editar registro' : 'Registro diario de horas extras' ?></h1>
                <p class="text-secondary mb-0">Captura el nombre, la fecha y el horario realmente trabajado.</p>
            </div>
            <a class="btn btn-outline-secondary no-print" href="<?= BASE_URL ?>/registros.php">Volver</a>
        </div>

        <form id="overtimeForm" action="<?= BASE_URL ?>/guardar.php" method="post" class="card" novalidate>
            <div class="card-body p-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label required" for="trabajador">Nombre del trabajador</label>
                        <input class="form-control form-control-lg" id="trabajador" name="trabajador" value="<?= e($value('trabajador')) ?>" required autofocus maxlength="150">
                        <div class="invalid-feedback">Escribe el nombre del trabajador.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required" for="fecha">Fecha</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?= e($value('fecha', date('Y-m-d'))) ?>" required>
                        <div class="invalid-feedback">Selecciona la fecha.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required" for="hora_inicio">De qué hora</label>
                        <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" value="<?= e(timeShort($value('hora_inicio'))) ?>" required>
                        <div class="invalid-feedback">Captura la hora de inicio.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required" for="hora_fin">A qué hora</label>
                        <input type="time" class="form-control" id="hora_fin" name="hora_fin" value="<?= e(timeShort($value('hora_fin'))) ?>" required>
                        <div class="invalid-feedback">Captura la hora de término.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="total_visual">Cantidad de horas extras</label>
                        <div class="total-box" id="total_visual"><?= $row ? e(formatHours((float)$row['total_horas'])) : '0 h' ?></div>
                        <input type="hidden" id="total_horas" name="total_horas" value="<?= e($value('total_horas', '0')) ?>">
                        <div class="form-text">El sistema calcula automáticamente la diferencia entre la hora de inicio y la hora de término.</div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2 p-3 no-print">
                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/registros.php">Cancelar</a>
                <button class="btn btn-viprint px-4" type="submit">Guardar e imprimir</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
