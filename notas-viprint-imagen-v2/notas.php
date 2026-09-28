<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require_once __DIR__ . '/includes/header.php';

$q = trim($_GET['q'] ?? '');
$empresa = $_GET['empresa'] ?? '';
$diseno = $_GET['diseno'] ?? '';

$params = [];
$where = '1=1';

if ($q !== '') {
    $where .= " AND (n.folio LIKE ? OR n.cliente_nombre LIKE ? OR n.negocio LIKE ? OR n.telefono LIKE ? OR p.productos LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($empresa !== '') {
    $where .= " AND e.clave = ?";
    $params[] = $empresa;
}
if ($diseno !== '') {
    $where .= " AND n.estado_diseno = ?";
    $params[] = $diseno;
}
if (role_in(['disenador','externo'])) {
    $where .= " AND n.disenador_id = ?";
    $params[] = current_user()['id'];
}

$sql = "
SELECT
    n.*,
    e.nombre AS empresa,
    u.nombre AS disenador,
    COALESCE(p.productos, '') AS productos
FROM v2_notas n
JOIN v2_empresas e ON e.id = n.empresa_id
LEFT JOIN v2_usuarios u ON u.id = n.disenador_id
LEFT JOIN (
    SELECT
        nota_id,
        GROUP_CONCAT(
            TRIM(CONCAT(
                CASE
                    WHEN cantidad IS NULL OR cantidad = 1 THEN ''
                    WHEN cantidad = FLOOR(cantidad) THEN CONCAT(CAST(cantidad AS UNSIGNED), ' x ')
                    ELSE CONCAT(TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM CAST(cantidad AS CHAR))), ' x ')
                END,
                descripcion
            ))
            ORDER BY id
            SEPARATOR ' · '
        ) AS productos
    FROM v2_nota_partidas
    GROUP BY nota_id
) p ON p.nota_id = n.id
WHERE $where
ORDER BY n.fecha_nota DESC, n.id DESC
LIMIT 300";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$titulo = role_in(['disenador','externo']) ? 'Mis notas' : 'Notas';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><?= h($titulo) ?></h1>
  <a class="btn btn-primary" href="<?= url('nota_form.php') ?>">Nueva nota</a>
</div>

<form class="card card-body mb-3 no-print">
  <div class="row g-2">
    <div class="col-md-4">
      <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Buscar folio, negocio, cliente, teléfono o producto">
    </div>
    <div class="col-md-3">
      <select class="form-select" name="empresa">
        <option value="">Empresa</option>
        <option value="viprint" <?= $empresa==='viprint'?'selected':'' ?>>ViPrint</option>
        <option value="imagen" <?= $empresa==='imagen'?'selected':'' ?>>Imagen</option>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="diseno">
        <option value="">Diseño</option>
        <option value="sin_asignar" <?= $diseno==='sin_asignar'?'selected':'' ?>>Sin asignar</option>
        <option value="pendiente_contacto" <?= $diseno==='pendiente_contacto'?'selected':'' ?>>Pendiente contacto</option>
        <option value="en_diseno" <?= $diseno==='en_diseno'?'selected':'' ?>>En diseño</option>
        <option value="en_aprobacion" <?= $diseno==='en_aprobacion'?'selected':'' ?>>En aprobación</option>
        <option value="aprobado" <?= $diseno==='aprobado'?'selected':'' ?>>Aprobado</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-outline-primary w-100">Filtrar</button>
    </div>
  </div>
</form>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th>Folio</th>
            <th>Empresa</th>
            <th>Negocio</th>
            <th>Productos / promociones</th>
            <th>Diseñador</th>
            <th>Seguimiento</th>
            <th>Total</th>
            <th>Saldo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <?php $nombreVisible = trim($r['negocio'] ?? '') !== '' ? $r['negocio'] : $r['cliente_nombre']; ?>
          <tr>
            <td>
              <strong><?= h($r['folio']) ?></strong><br>
              <span class="text-muted small"><?= date_mx($r['fecha_nota']) ?></span>
            </td>
            <td><?= h($r['empresa']) ?></td>
            <td>
              <strong><?= h($nombreVisible) ?></strong>
              <?php if (!empty($r['negocio'])): ?>
                <br><span class="text-muted small">Cliente: <?= h($r['cliente_nombre']) ?></span>
              <?php endif; ?>
              <?php if (!empty($r['telefono'])): ?>
                <br><span class="text-muted small"><?= h($r['telefono']) ?></span>
              <?php endif; ?>
            </td>
            <td style="min-width:260px; max-width:420px;">
              <?php if (!empty($r['productos'])): ?>
                <span class="small"><?= h($r['productos']) ?></span>
              <?php else: ?>
                <span class="text-muted small">Sin productos capturados</span>
              <?php endif; ?>
            </td>
            <td><?= h($r['disenador'] ?? 'Sin asignar') ?></td>
            <td>
              <div class="d-flex flex-wrap gap-1">
                <?= estado_badge($r['estado_contacto']) ?>
                <?= estado_badge($r['estado_diseno']) ?>
                <?= estado_badge($r['estado_entrega']) ?>
              </div>
            </td>
            <td><?= money($r['total']) ?></td>
            <td><?= money($r['saldo']) ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="<?= url('nota_ver.php?id='.$r['id']) ?>">Abrir</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">No hay resultados.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
