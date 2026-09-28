<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$q = trim($_GET['q'] ?? '');
$empresa = $_GET['empresa'] ?? '';
$estado = $_GET['estado'] ?? '';
$disenador = $_GET['disenador'] ?? '';

$where = array('1=1');
$params = array();

if ($user['rol'] === 'disenador') {
    $where[] = 'n.disenador_id = ?';
    $params[] = $user['id'];
}
if ($q !== '') {
    $where[] = '(n.folio LIKE ? OR n.cliente_nombre LIKE ? OR n.negocio LIKE ? OR n.telefono LIKE ?)';
    for ($i=0; $i<4; $i++) $params[] = '%' . $q . '%';
}
if (in_array($empresa, array('viprint','imagen'), true)) {
    $where[] = 'n.empresa = ?';
    $params[] = $empresa;
}
if ($estado !== '' && array_key_exists($estado, estado_options())) {
    $where[] = 'n.estado = ?';
    $params[] = $estado;
}
if ($disenador !== '' && ctype_digit($disenador) && $user['rol'] !== 'disenador') {
    $where[] = 'n.disenador_id = ?';
    $params[] = (int)$disenador;
}

$sql = 'SELECT n.*, u.nombre AS disenador_nombre
        FROM notas n
        LEFT JOIN usuarios u ON u.id = n.disenador_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY n.fecha_nota DESC, n.id DESC
        LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notas = $stmt->fetchAll();
$designers = get_designers($pdo);

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4 flex-column flex-md-row">
    <div>
        <h1 class="h3 fw-bold mb-1">Notas</h1>
        <p class="text-muted mb-0">Busca por folio, cliente, negocio o teléfono.</p>
    </div>
    <?php if (is_operativo()): ?><a href="<?php echo h(url('nota_form.php')); ?>" class="btn btn-primary">+ Nueva nota</a><?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3" method="get">
            <div class="col-12 col-lg-4">
                <label class="form-label">Buscar</label>
                <input type="text" name="q" value="<?php echo h($q); ?>" class="form-control" placeholder="Folio, cliente, negocio, teléfono">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Empresa</label>
                <select name="empresa" class="form-select">
                    <option value="">Todas</option>
                    <option value="viprint" <?php echo $empresa==='viprint'?'selected':''; ?>>ViPrint</option>
                    <option value="imagen" <?php echo $empresa==='imagen'?'selected':''; ?>>Imagen</option>
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (estado_options() as $key => $label): ?>
                        <option value="<?php echo h($key); ?>" <?php echo $estado===$key?'selected':''; ?>><?php echo h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!is_disenador()): ?>
            <div class="col-6 col-lg-2">
                <label class="form-label">Diseñador</label>
                <select name="disenador" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($designers as $d): ?>
                    <option value="<?php echo h($d['id']); ?>" <?php echo (string)$disenador===(string)$d['id']?'selected':''; ?>><?php echo h($d['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-6 col-lg-1 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Resultados: <?php echo count($notas); ?></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Nota</th><th>Cliente</th><th>Fecha</th><th>Diseñador</th><th>Estado</th><th>Total</th><th>Saldo</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($notas as $n): ?>
                <tr>
                    <td><strong><?php echo h(empresa_label($n['empresa'])); ?> #<?php echo h($n['folio']); ?></strong><div class="small text-muted"><?php echo h($n['origen']); ?></div></td>
                    <td><?php echo h($n['cliente_nombre']); ?><div class="small text-muted"><?php echo h($n['negocio']); ?> · <?php echo h($n['telefono']); ?></div></td>
                    <td><?php echo h(date_mx($n['fecha_nota'])); ?></td>
                    <td><?php echo h($n['disenador_nombre'] ?: 'Sin asignar'); ?></td>
                    <td><span class="status-badge <?php echo h(estado_badge_class($n['estado'])); ?>"><?php echo h(estado_label($n['estado'])); ?></span></td>
                    <td><?php echo h(money($n['total'])); ?></td>
                    <td class="fw-bold <?php echo ((float)$n['saldo'] > 0) ? 'text-danger' : 'text-success'; ?>"><?php echo h(money($n['saldo'])); ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="<?php echo h(url('nota_ver.php?id=' . $n['id'])); ?>">Ver</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$notas): ?><tr><td colspan="8" class="text-center text-muted py-4">No hay resultados.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
