<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$params = [];
$sql = 'SELECT * FROM registros_horas_extra';

if ($q !== '') {
    $sql .= ' WHERE trabajador LIKE :q OR folio LIKE :q OR fecha LIKE :q';
    $params['q'] = '%' . $q . '%';
}

$sql .= ' ORDER BY fecha DESC, id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Registros de horas extras';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Registros de horas extras</h1>
        <p class="text-secondary mb-0">Consulta, edita o vuelve a imprimir cualquier hoja diaria.</p>
    </div>
    <a class="btn btn-viprint" href="<?= BASE_URL ?>/formulario.php">Nuevo registro</a>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-10">
                <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar por trabajador, folio o fecha">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-secondary">Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Trabajador</th>
                    <th>Horario extra</th>
                    <th>Total</th>
                    <th class="text-end no-print">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6" class="text-center text-secondary py-5">No se encontraron registros.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['folio']) ?></td>
                    <td><?= e(formatDate($row['fecha'])) ?></td>
                    <td class="fw-semibold"><?= e($row['trabajador']) ?></td>
                    <td><?= e(timeShort($row['hora_inicio'])) ?> a <?= e(timeShort($row['hora_fin'])) ?></td>
                    <td><?= e(formatHours((float)$row['total_horas'])) ?></td>
                    <td class="text-end no-print text-nowrap">
                        <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/ver.php?id=<?= (int)$row['id'] ?>">Ver / imprimir</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/formulario.php?id=<?= (int)$row['id'] ?>">Editar</a>
                        <form action="<?= BASE_URL ?>/eliminar.php" method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este registro?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
