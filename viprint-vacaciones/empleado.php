<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = $pdo->prepare('SELECT * FROM empleados WHERE id = :id');
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch();
if (!$employee) {
    flash('danger', 'El empleado no existe.');
    redirect('/empleados.php');
}

syncVacationAllocations($pdo, $id);
$balance = employeeBalance($pdo, $id);
$years = completedServiceYears($employee['fecha_ingreso']);
$cycle = currentVacationCycle($employee['fecha_ingreso']);
$next = nextAnniversary($employee['fecha_ingreso'], (int)$employee['ultimo_anio_procesado']);

$stmt = $pdo->prepare('SELECT * FROM movimientos_vacaciones WHERE empleado_id = :id ORDER BY fecha DESC, id DESC');
$stmt->execute(['id' => $id]);
$movements = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM solicitudes_vacaciones WHERE empleado_id = :id ORDER BY fecha_solicitud DESC, id DESC');
$stmt->execute(['id' => $id]);
$requests = $stmt->fetchAll();

$pageTitle = $employee['nombre'];
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h3 mb-0"><?= e($employee['nombre']) ?></h1>
            <?php if (!$employee['activo']): ?><span class="badge bg-secondary">Inactivo</span><?php endif; ?>
        </div>
        <p class="text-secondary mb-0"><?= e($employee['puesto']) ?> · Ingreso: <?= e(formatDate($employee['fecha_ingreso'])) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2 no-print">
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/empleados.php">Volver</a>
        <a class="btn btn-outline-dark" href="<?= BASE_URL ?>/constancia.php?id=<?= $id ?>">Constancia</a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/empleado_form.php?id=<?= $id ?>">Editar</a>
        <?php if ($employee['activo']): ?><a class="btn btn-viprint" href="<?= BASE_URL ?>/solicitud_form.php?empleado_id=<?= $id ?>">Nueva solicitud</a><?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="balance-box"><strong><?= $balance['disponibles'] ?></strong><span>Días disponibles</span></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="h3 text-viprint mb-1"><?= $balance['pendientes'] ?></div><div class="text-secondary">Días pendientes</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="h3 text-viprint mb-1"><?= $years ?></div><div class="text-secondary">Años de antigüedad</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="fw-bold text-viprint mb-1"><?= e(formatDate($next->format('Y-m-d'))) ?></div><div class="text-secondary">Próximo aniversario</div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Resumen del saldo</h2></div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md"><div class="small text-secondary">Saldo capturado</div><strong><?= $balance['saldo_inicial'] ?></strong></div>
                    <div class="col-6 col-md"><div class="small text-secondary">Asignaciones anuales</div><strong>+<?= $balance['asignaciones'] ?></strong></div>
                    <div class="col-6 col-md"><div class="small text-secondary">Ajustes</div><strong><?= $balance['ajustes'] >= 0 ? '+' : '' ?><?= $balance['ajustes'] ?></strong></div>
                    <div class="col-6 col-md"><div class="small text-secondary">Días autorizados</div><strong>-<?= $balance['autorizados'] ?></strong></div>
                    <div class="col-12 col-md"><div class="small text-secondary">Disponible</div><strong class="text-viprint fs-4"><?= $balance['disponibles'] ?></strong></div>
                </div>
                <hr>
                <p class="small text-secondary mb-0">Periodo actual de antigüedad: <?= e(formatDate($cycle['start'])) ?> al <?= e(formatDate($cycle['end'])) ?>. Referencia legal del año de servicio actual: <?= (int)$cycle['entitlement'] ?> día(s).</p>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Ajustar saldo</h2></div>
            <div class="card-body">
                <form method="post" action="<?= BASE_URL ?>/guardar_ajuste.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="empleado_id" value="<?= $id ?>">
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label required">Días</label>
                            <input type="number" class="form-control" name="dias" min="-365" max="365" required placeholder="+/-">
                        </div>
                        <div class="col-8">
                            <label class="form-label required">Motivo</label>
                            <input class="form-control" name="descripcion" maxlength="255" required placeholder="Ej. corrección de saldo">
                        </div>
                        <div class="col-12 d-grid"><button class="btn btn-outline-dark">Registrar ajuste</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Solicitudes</h2></div>
    <div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Folio</th><th>Solicitud</th><th>Vacaciones</th><th>Días</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php if (!$requests): ?><tr><td colspan="6" class="text-center text-secondary py-4">Sin solicitudes.</td></tr><?php endif; ?>
        <?php foreach ($requests as $request): ?>
            <tr>
                <td><?= e($request['folio']) ?></td>
                <td><?= e(formatDate($request['fecha_solicitud'])) ?></td>
                <td><?= e(formatDate($request['fecha_inicio'])) ?> al <?= e(formatDate($request['fecha_fin'])) ?></td>
                <td><?= (int)$request['dias_solicitados'] ?></td>
                <td><span class="badge bg-<?= e(statusBadge($request['estado'])) ?>"><?= e(statusLabel($request['estado'])) ?></span></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/ver_solicitud.php?id=<?= (int)$request['id'] ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-header bg-white py-3"><h2 class="h5 mb-0">Movimientos de saldo</h2></div>
    <div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th class="text-end">Días</th></tr></thead>
        <tbody>
        <tr><td><?= e(formatDate($employee['fecha_corte_saldo'])) ?></td><td><span class="badge bg-secondary">Saldo inicial</span></td><td>Saldo disponible capturado al registrar al empleado</td><td class="text-end fw-bold"><?= (int)$employee['saldo_inicial'] ?></td></tr>
        <?php foreach ($movements as $move): ?>
            <tr>
                <td><?= e(formatDate($move['fecha'])) ?></td>
                <td><span class="badge bg-<?= $move['tipo'] === 'ASIGNACION_ANUAL' ? 'success' : 'dark' ?>"><?= $move['tipo'] === 'ASIGNACION_ANUAL' ? 'Asignación anual' : 'Ajuste' ?></span></td>
                <td><?= e($move['descripcion']) ?></td>
                <td class="text-end fw-bold <?= (int)$move['dias'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= (int)$move['dias'] >= 0 ? '+' : '' ?><?= (int)$move['dias'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
