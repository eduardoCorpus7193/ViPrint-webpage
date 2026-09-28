<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$designerCondition = '';
$params = array();
if ($user['rol'] === 'disenador') {
    $designerCondition = ' AND n.disenador_id = ? ';
    $params[] = $user['id'];
}

function scalar_query($pdo, $sql, $params = array()) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

$openStatuses = "'recibida','pendiente_contacto','contactado','en_diseno','en_aprobacion','aprobado_para_imprimir','impresa','sublimada','en_instalacion','instalada'";
$totalAbiertas = scalar_query($pdo, "SELECT COUNT(*) FROM notas n WHERE n.estado IN ($openStatuses) $designerCondition", $params);
$sinDisenador = is_disenador() ? 0 : scalar_query($pdo, "SELECT COUNT(*) FROM notas n WHERE n.disenador_id IS NULL AND n.estado NOT IN ('entregada','cancelada')");
$saldoPendiente = scalar_query($pdo, "SELECT COALESCE(SUM(n.saldo),0) FROM notas n WHERE n.estado <> 'cancelada' $designerCondition", $params);
$instalacion = scalar_query($pdo, "SELECT COUNT(*) FROM notas n WHERE n.estado IN ('en_instalacion','instalada') $designerCondition", $params);

$sqlRecent = "SELECT n.*, u.nombre AS disenador_nombre
              FROM notas n
              LEFT JOIN usuarios u ON u.id = n.disenador_id
              WHERE 1=1 $designerCondition
              ORDER BY n.updated_at DESC
              LIMIT 10";
$stmt = $pdo->prepare($sqlRecent);
$stmt->execute($params);
$recent = $stmt->fetchAll();

$sinAsignar = array();
if (!is_disenador()) {
    $stmt = $pdo->query("SELECT n.* FROM notas n WHERE n.disenador_id IS NULL AND n.estado NOT IN ('entregada','cancelada') ORDER BY n.fecha_nota DESC LIMIT 8");
    $sinAsignar = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4 flex-column flex-md-row">
    <div>
        <h1 class="h3 fw-black mb-1">Inicio</h1>
        <p class="text-muted mb-0">Control de notas, diseños, saldos e instalación.</p>
    </div>
    <?php if (is_operativo()): ?>
    <a href="<?php echo h(url('nota_form.php')); ?>" class="btn btn-primary">+ Nueva nota</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card stat-card p-3"><div class="text-muted small">Notas abiertas</div><div class="stat-number"><?php echo h($totalAbiertas); ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card p-3"><div class="text-muted small">Sin diseñador</div><div class="stat-number"><?php echo h($sinDisenador); ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card p-3"><div class="text-muted small">Saldo pendiente</div><div class="stat-number fs-4"><?php echo h(money($saldoPendiente)); ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card p-3"><div class="text-muted small">Instalación</div><div class="stat-number"><?php echo h($instalacion); ?></div></div></div>
</div>

<?php if (!is_disenador() && $sinAsignar): ?>
<div class="card mb-4 border-warning">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Notas sin diseñador asignado</span>
        <span class="badge text-bg-warning">Revisar hoy</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Folio</th><th>Cliente</th><th>Empresa</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($sinAsignar as $n): ?>
                    <tr>
                        <td><strong><?php echo h($n['folio']); ?></strong></td>
                        <td><?php echo h($n['cliente_nombre']); ?></td>
                        <td><?php echo h(empresa_label($n['empresa'])); ?></td>
                        <td><?php echo h(date_mx($n['fecha_nota'])); ?></td>
                        <td><span class="status-badge <?php echo h(estado_badge_class($n['estado'])); ?>"><?php echo h(estado_label($n['estado'])); ?></span></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?php echo h(url('nota_form.php?id=' . $n['id'])); ?>">Asignar</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">Notas recientes</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Folio</th><th>Cliente</th><th>Diseñador</th><th>Estado</th><th>Saldo</th><th>Actualizada</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recent as $n): ?>
                    <tr>
                        <td><strong><?php echo h(empresa_label($n['empresa'])); ?> #<?php echo h($n['folio']); ?></strong></td>
                        <td><?php echo h($n['cliente_nombre']); ?><div class="small text-muted"><?php echo h($n['telefono']); ?></div></td>
                        <td><?php echo h($n['disenador_nombre'] ?: 'Sin asignar'); ?></td>
                        <td><span class="status-badge <?php echo h(estado_badge_class($n['estado'])); ?>"><?php echo h(estado_label($n['estado'])); ?></span></td>
                        <td><?php echo h(money($n['saldo'])); ?></td>
                        <td class="small text-muted"><?php echo h(date('d/m/Y H:i', strtotime($n['updated_at']))); ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?php echo h(url('nota_ver.php?id=' . $n['id'])); ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?><tr><td colspan="7" class="text-center text-muted py-4">Aún no hay notas registradas.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
