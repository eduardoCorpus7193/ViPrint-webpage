<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['estado'] ?? '');
$from = validDate($_GET['desde'] ?? null);
$to = validDate($_GET['hasta'] ?? null);

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(e.nombre LIKE ? OR p.folio LIKE ? OR p.motivo_detalle LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if (in_array($status, ['pendiente', 'autorizado', 'cancelado'], true)) {
    $where[] = 'p.estado = ?';
    $params[] = $status;
}
if ($from) {
    $where[] = 'p.fecha_permiso >= ?';
    $params[] = $from;
}
if ($to) {
    $where[] = 'p.fecha_permiso <= ?';
    $params[] = $to;
}

$sql = 'SELECT p.*, e.nombre AS empleado_nombre
        FROM permisos_salida p
        INNER JOIN empleados e ON e.id = p.empleado_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY p.fecha_permiso DESC, p.hora_salida DESC, p.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$permits = $stmt->fetchAll();

$pageTitle = 'Historial de permisos';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Historial de permisos</h1>
        <p class="text-secondary mb-0">Consulta, edita, imprime o elimina los permisos generados.</p>
    </div>
    <a class="btn btn-viprint" href="<?= BASE_URL ?>/permiso_form.php">Nuevo permiso</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Buscar</label>
                <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Empleado, folio o motivo">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="estado">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="pendiente" <?= $status === 'pendiente' ? 'selected' : '' ?>>Pendiente de firma</option>
                    <option value="autorizado" <?= $status === 'autorizado' ? 'selected' : '' ?>>Autorizado</option>
                    <option value="cancelado" <?= $status === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="desde">Desde</label>
                <input class="form-control" type="date" id="desde" name="desde" value="<?= e($from) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="hasta">Hasta</label>
                <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($to) ?>">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-viprint" type="submit">Filtrar</button>
            </div>
            <?php if ($q !== '' || $status !== '' || $from || $to): ?>
                <div class="col-12"><a href="<?= BASE_URL ?>/permisos.php" class="btn btn-sm btn-outline-secondary">Limpiar filtros</a></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Empleado</th>
                    <th>Fecha</th>
                    <th>Salida</th>
                    <th>Regreso previsto</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$permits): ?>
                <tr><td colspan="8" class="text-center text-secondary py-5">No se encontraron permisos.</td></tr>
            <?php endif; ?>
            <?php foreach ($permits as $permit): ?>
                <tr>
                    <td><?= e($permit['folio']) ?></td>
                    <td class="fw-semibold"><?= e($permit['empleado_nombre']) ?></td>
                    <td><?= e(formatDate($permit['fecha_permiso'])) ?></td>
                    <td><?= e(substr($permit['hora_salida'], 0, 5)) ?></td>
                    <td><?= e(substr($permit['hora_regreso_prevista'], 0, 5)) ?></td>
                    <td><?= e(reasonLabel($permit['motivo_tipo'])) ?></td>
                    <td><span class="badge text-bg-<?= e(statusBadge($permit['estado'])) ?>"><?= e(statusLabel($permit['estado'])) ?></span></td>
                    <td class="text-end action-buttons">
                        <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/ver_permiso.php?id=<?= (int)$permit['id'] ?>">Ver</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/permiso_form.php?id=<?= (int)$permit['id'] ?>">Editar</a>
                        <form method="post" action="<?= BASE_URL ?>/eliminar_permiso.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$permit['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm-delete="¿Deseas eliminar este permiso?">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
