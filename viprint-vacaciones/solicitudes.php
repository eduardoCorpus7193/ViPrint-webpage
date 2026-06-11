<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['estado'] ?? ''));
$params = [];
$where = [];

if ($q !== '') {
    $where[] = '(e.nombre LIKE :q OR s.folio LIKE :q OR s.fecha_inicio LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if (in_array($status, ['PENDIENTE', 'AUTORIZADA', 'RECHAZADA', 'CANCELADA'], true)) {
    $where[] = 's.estado = :estado';
    $params['estado'] = $status;
}

$sql = 'SELECT s.*, e.nombre AS empleado_nombre
        FROM solicitudes_vacaciones s
        INNER JOIN empleados e ON e.id = s.empleado_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY s.fecha_solicitud DESC, s.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Solicitudes de vacaciones';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Solicitudes de vacaciones</h1>
        <p class="text-secondary mb-0">Consulta, autoriza e imprime las solicitudes.</p>
    </div>
    <a class="btn btn-viprint" href="<?= BASE_URL ?>/solicitud_form.php">Nueva solicitud</a>
</div>

<div class="card mb-4 no-print"><div class="card-body">
    <form class="row g-2" method="get">
        <div class="col-md-7"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar por empleado, folio o fecha"></div>
        <div class="col-md-3">
            <select class="form-select" name="estado">
                <option value="">Todos los estados</option>
                <?php foreach (['PENDIENTE' => 'Pendiente', 'AUTORIZADA' => 'Autorizada', 'RECHAZADA' => 'Rechazada', 'CANCELADA' => 'Cancelada'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary">Filtrar</button></div>
    </form>
</div></div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Folio</th><th>Empleado</th><th>Solicitud</th><th>Vacaciones</th><th>Días</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-secondary py-5">No hay solicitudes que coincidan.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['folio']) ?></td>
                    <td class="fw-semibold"><?= e($row['empleado_nombre']) ?></td>
                    <td><?= e(formatDate($row['fecha_solicitud'])) ?></td>
                    <td><?= e(formatDate($row['fecha_inicio'])) ?> al <?= e(formatDate($row['fecha_fin'])) ?></td>
                    <td><?= (int)$row['dias_solicitados'] ?></td>
                    <td><span class="badge bg-<?= e(statusBadge($row['estado'])) ?>"><?= e(statusLabel($row['estado'])) ?></span></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/ver_solicitud.php?id=<?= (int)$row['id'] ?>">Ver / imprimir</a>
                        <?php if ($row['estado'] === 'PENDIENTE'): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/solicitud_form.php?id=<?= (int)$row['id'] ?>">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
