<?php
require_once __DIR__ . '/includes/bootstrap.php';
$current = 'inicio';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = isset($_GET['estatus']) ? trim($_GET['estatus']) : '';
$where = array();
$params = array();
if ($q !== '') {
    $where[] = "(folio LIKE ? OR cliente_nombre LIKE ? OR cliente_negocio LIKE ? OR cliente_telefono LIKE ?)";
    $like = '%' . $q . '%';
    $params = array_merge($params, array($like, $like, $like, $like));
}
if ($status !== '') {
    $where[] = "estatus = ?";
    $params[] = $status;
}
$sql = "SELECT * FROM cotizaciones" . ($where ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY fecha DESC, id DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quotes = $stmt->fetchAll();

$stats = array('total' => 0, 'mes' => 0, 'aprobadas' => 0, 'monto' => 0);
$stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM cotizaciones")->fetchColumn();
$stats['mes'] = (int)$pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE YEAR(fecha)=YEAR(CURDATE()) AND MONTH(fecha)=MONTH(CURDATE())")->fetchColumn();
$stats['aprobadas'] = (int)$pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estatus='aprobada'")->fetchColumn();
$stats['monto'] = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM cotizaciones WHERE estatus IN ('enviada','aprobada')")->fetchColumn();

require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Cotizaciones</h1>
        <div class="small-muted">Consulta, edita, imprime y descarga PDF de cotizaciones para clientes.</div>
    </div>
    <a href="<?php echo h(BASE_URL); ?>/cotizacion_form.php" class="btn btn-vip btn-lg">+ Nueva cotización</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="small-muted">Cotizaciones</div><div class="stat-number"><?php echo $stats['total']; ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="small-muted">Este mes</div><div class="stat-number"><?php echo $stats['mes']; ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="small-muted">Aprobadas</div><div class="stat-number"><?php echo $stats['aprobadas']; ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="small-muted">Enviadas/aprobadas</div><div class="stat-number" style="font-size:1.15rem"><?php echo money($stats['monto']); ?></div></div></div>
</div>

<div class="card card-vip mb-4">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-6">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" name="q" value="<?php echo h($q); ?>" placeholder="Folio, cliente, negocio o teléfono">
            </div>
            <div class="col-md-3">
                <label class="form-label">Estatus</label>
                <select class="form-select" name="estatus">
                    <option value="">Todos</option>
                    <?php foreach (array('borrador','enviada','aprobada','rechazada','cancelada') as $st): ?>
                    <option value="<?php echo h($st); ?>" <?php echo $status===$st?'selected':''; ?>><?php echo h(quote_status_label($st)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-grid d-md-flex gap-2">
                <button class="btn btn-vip" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-vip">
    <div class="card-header card-header-vip">Registros recientes</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Folio</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Estatus</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php if (!$quotes): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No hay cotizaciones registradas.</td></tr>
            <?php endif; ?>
            <?php foreach ($quotes as $row): ?>
                <tr>
                    <td><strong><?php echo h($row['folio']); ?></strong></td>
                    <td><?php echo h(date_mx($row['fecha'])); ?></td>
                    <td>
                        <div class="fw-bold"><?php echo h($row['cliente_nombre']); ?></div>
                        <div class="small-muted"><?php echo h($row['cliente_negocio']); ?></div>
                    </td>
                    <td class="fw-bold"><?php echo h(money($row['total'])); ?></td>
                    <td><span class="badge text-bg-<?php echo h(badge_class($row['estatus'])); ?>"><?php echo h(quote_status_label($row['estatus'])); ?></span></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a class="btn btn-outline-vip" href="<?php echo h(BASE_URL); ?>/ver_cotizacion.php?id=<?php echo (int)$row['id']; ?>">Ver</a>
                            <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/cotizacion_form.php?id=<?php echo (int)$row['id']; ?>">Editar</a>
                            <a class="btn btn-outline-danger" data-confirm="¿Eliminar esta cotización?" href="<?php echo h(BASE_URL); ?>/eliminar_cotizacion.php?id=<?php echo (int)$row['id']; ?>&csrf_token=<?php echo h($_SESSION['csrf_token']); ?>">Eliminar</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
