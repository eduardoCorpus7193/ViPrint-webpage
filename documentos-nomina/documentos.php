<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$type = (string)($_GET['tipo'] ?? '');
$from = validDate($_GET['desde'] ?? null);
$to = validDate($_GET['hasta'] ?? null);

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(e.nombre LIKE ? OR d.folio LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if (in_array($type, ['transferencia', 'bono_efectivo'], true)) {
    $where[] = 'd.tipo = ?';
    $params[] = $type;
}
if ($from) {
    $where[] = 'd.fecha_trabajada >= ?';
    $params[] = $from;
}
if ($to) {
    $where[] = 'd.fecha_trabajada <= ?';
    $params[] = $to;
}

$sql = 'SELECT d.*, e.nombre AS empleado_nombre
        FROM documentos_nomina d
        INNER JOIN empleados e ON e.id = d.empleado_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY d.fecha_trabajada DESC, d.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

$pageTitle = 'Historial de documentos';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Historial de documentos</h1>
        <p class="text-secondary mb-0">Consulta, edita, imprime o elimina los documentos generados.</p>
    </div>
    <a class="btn btn-viprint" href="<?= BASE_URL ?>/documento_form.php">Nuevo documento</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Buscar</label>
                <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Empleado o folio">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="tipo">Tipo</label>
                <select class="form-select" id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <option value="transferencia" <?= $type === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                    <option value="bono_efectivo" <?= $type === 'bono_efectivo' ? 'selected' : '' ?>>Bono en efectivo</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="desde">Desde</label>
                <input class="form-control" type="date" id="desde" name="desde" value="<?= e($from ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="hasta">Hasta</label>
                <input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($to ?? '') ?>">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-viprint" type="submit">Filtrar</button>
            </div>
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
                    <th>Tipo</th>
                    <th>Fecha trabajada</th>
                    <th>Cantidad</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$documents): ?>
                <tr><td colspan="6" class="text-center text-secondary py-5">No se encontraron documentos.</td></tr>
            <?php endif; ?>
            <?php foreach ($documents as $document): ?>
                <tr>
                    <td><?= e($document['folio']) ?></td>
                    <td class="fw-semibold"><?= e($document['empleado_nombre']) ?></td>
                    <td><?= e(documentTypeShort($document['tipo'])) ?></td>
                    <td><?= e(formatDate($document['fecha_trabajada'])) ?></td>
                    <td class="fw-semibold"><?= e(formatMoney((float)$document['cantidad'])) ?></td>
                    <td class="text-end action-buttons">
                        <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/ver_documento.php?id=<?= (int)$document['id'] ?>">Ver</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/documento_form.php?id=<?= (int)$document['id'] ?>">Editar</a>
                        <form method="post" action="<?= BASE_URL ?>/eliminar_documento.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$document['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm-delete="¿Deseas eliminar este documento?">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
