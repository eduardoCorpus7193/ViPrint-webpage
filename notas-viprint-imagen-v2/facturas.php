<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/facturacion_helpers.php';
require_login();
if (!factura_can_manage()) { http_response_code(403); exit('No tienes permiso para revisar solicitudes de factura.'); }
require_once __DIR__ . '/includes/header.php';

$q = trim((string)($_GET['q'] ?? ''));
$estado = trim((string)($_GET['estado'] ?? 'pendiente'));
$allowedEstados = array('pendiente','en_revision','datos_incorrectos','facturada','cancelada','todos');
if (!in_array($estado, $allowedEstados, true)) $estado = 'pendiente';

$notas = array();
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = db()->prepare("SELECT n.id, n.folio, n.cliente_nombre, n.telefono, n.total, n.pagado, n.saldo, n.requiere_factura, n.factura_limite_datos, e.nombre empresa
        FROM v2_notas n
        JOIN v2_empresas e ON e.id = n.empresa_id
        WHERE n.folio LIKE ? OR n.cliente_nombre LIKE ? OR n.telefono LIKE ? OR n.negocio LIKE ?
        ORDER BY n.id DESC
        LIMIT 25");
    $stmt->execute(array($like,$like,$like,$like));
    $notas = $stmt->fetchAll();
}

$sql = "SELECT fs.*, n.folio AS nota_folio, n.cliente_nombre, n.total, n.pagado, n.saldo, n.requiere_factura, n.factura_limite_datos
        FROM v2_factura_solicitudes fs
        JOIN v2_notas n ON n.id = fs.nota_id";
$params = array();
if ($estado !== 'todos') {
    $sql .= " WHERE fs.estado = ?";
    $params[] = $estado;
}
$sql .= " ORDER BY fs.fecha_solicitud DESC, fs.id DESC LIMIT 100";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$solicitudes = $stmt->fetchAll();

$resumen = db()->query("SELECT estado, COUNT(*) total FROM v2_factura_solicitudes GROUP BY estado")->fetchAll();
$totales = array();
foreach($resumen as $r){ $totales[$r['estado']] = (int)$r['total']; }
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h3 mb-0">Facturación</h1>
    <div class="text-muted">Solicitudes de factura y control de notas que requieren factura.</div>
  </div>
  <a class="btn btn-outline-secondary" href="<?= url('index.php') ?>">Inicio</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Pendientes</div><div class="h4 mb-0"><?= (int)($totales['pendiente'] ?? 0) ?></div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">En revisión</div><div class="h4 mb-0"><?= (int)($totales['en_revision'] ?? 0) ?></div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Datos incorrectos</div><div class="h4 mb-0"><?= (int)($totales['datos_incorrectos'] ?? 0) ?></div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Facturadas</div><div class="h4 mb-0"><?= (int)($totales['facturada'] ?? 0) ?></div></div></div></div>
</div>

<div class="card mb-3">
  <div class="card-header"><strong>Buscar nota para activar o quitar factura</strong></div>
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-8">
        <label class="form-label">Folio, cliente, teléfono o negocio</label>
        <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Ej. VP-000123, cliente o teléfono">
      </div>
      <div class="col-md-2">
        <label class="form-label">Estado solicitudes</label>
        <select class="form-select" name="estado">
          <?php foreach($allowedEstados as $e): ?><option value="<?= h($e) ?>" <?= $estado===$e?'selected':'' ?>><?= h($e==='todos'?'Todos':factura_status_label($e)) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid"><button class="btn btn-primary">Buscar</button></div>
    </form>
    <?php if($q !== ''): ?>
      <hr>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Folio</th><th>Cliente</th><th>Empresa</th><th>Total</th><th>Factura</th><th>Límite datos</th><th class="text-end">Acción</th></tr></thead>
          <tbody>
          <?php foreach($notas as $n): ?>
            <tr>
              <td><a href="<?= url('nota_ver.php?id='.(int)$n['id']) ?>"><?= h($n['folio']) ?></a></td>
              <td><?= h($n['cliente_nombre']) ?><br><span class="small text-muted"><?= h($n['telefono']) ?></span></td>
              <td><?= h($n['empresa']) ?></td>
              <td><?= money($n['total']) ?></td>
              <td><?= ((int)$n['requiere_factura']===1) ? '<span class="badge text-bg-success">Sí requiere</span>' : '<span class="badge text-bg-secondary">No requiere</span>' ?></td>
              <td><?= h(factura_limite_text($n)) ?></td>
              <td class="text-end">
                <form method="post" action="<?= url('factura_requiere_guardar.php') ?>" class="d-inline" onsubmit="return confirm('¿Confirmas el cambio de factura para esta nota?')">
                  <input type="hidden" name="nota_id" value="<?= (int)$n['id'] ?>">
                  <input type="hidden" name="requiere_factura" value="<?= ((int)$n['requiere_factura']===1)?0:1 ?>">
                  <button class="btn btn-sm <?= ((int)$n['requiere_factura']===1)?'btn-outline-danger':'btn-outline-success' ?>"><?= ((int)$n['requiere_factura']===1)?'Quitar factura':'Activar factura' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(!$notas): ?><tr><td colspan="7" class="text-muted">No se encontraron notas.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <strong>Solicitudes recibidas</strong>
    <div class="small text-muted">El cliente debe mandar datos hasta 72 horas después de solicitar factura.</div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Fecha</th><th>Folio</th><th>Cliente</th><th>RFC</th><th>Razón social</th><th>Estado</th><th class="text-end">Acción</th></tr></thead>
        <tbody>
        <?php foreach($solicitudes as $s): ?>
          <tr>
            <td><?= date_mx($s['fecha_solicitud']) ?></td>
            <td><a href="<?= url('nota_ver.php?id='.(int)$s['nota_id']) ?>"><?= h($s['nota_folio']) ?></a></td>
            <td><?= h($s['cliente_nombre']) ?></td>
            <td><?= h($s['rfc']) ?></td>
            <td><?= h($s['razon_social']) ?></td>
            <td><span class="badge text-bg-<?= factura_status_class($s['estado']) ?>"><?= h(factura_status_label($s['estado'])) ?></span></td>
            <td class="text-end"><a class="btn btn-sm btn-primary" href="<?= url('factura_ver.php?id='.(int)$s['id']) ?>">Ver</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if(!$solicitudes): ?><tr><td colspan="7" class="text-muted p-3">No hay solicitudes en este filtro.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
