<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!current_user() || current_user()['rol'] !== 'admin') {
    flash('danger','Solo el usuario admin puede usar correcciones.');
    redirect_to('index.php');
}
require_once __DIR__ . '/includes/header.php';

$q = trim($_GET['q'] ?? '');
$nota = null;
$pagos = array();
$audit = array();
if ($q !== '') {
    $stmt = db()->prepare("SELECT n.*, e.nombre empresa FROM v2_notas n JOIN v2_empresas e ON e.id=n.empresa_id WHERE n.id=? OR n.folio=? ORDER BY n.id DESC LIMIT 1");
    $stmt->execute(array((int)$q, $q));
    $nota = $stmt->fetch();
    if ($nota) {
        $stmt = db()->prepare("SELECT p.*, u.nombre usuario_nombre, ua.nombre anulado_nombre FROM v2_pagos p LEFT JOIN v2_usuarios u ON u.id=p.usuario_id LEFT JOIN v2_usuarios ua ON ua.id=p.anulado_por WHERE p.nota_id=? ORDER BY p.fecha_pago DESC, p.id DESC");
        $stmt->execute(array($nota['id']));
        $pagos = $stmt->fetchAll();
        if (table_exists('v2_auditoria_admin')) {
            $stmt = db()->prepare("SELECT a.*, u.nombre usuario_nombre FROM v2_auditoria_admin a LEFT JOIN v2_usuarios u ON u.id=a.usuario_id WHERE a.nota_id=? ORDER BY a.created_at DESC LIMIT 30");
            $stmt->execute(array($nota['id']));
            $audit = $stmt->fetchAll();
        }
    }
}
$formas_pago = array('efectivo'=>'Efectivo','transferencia'=>'Transferencia','tarjeta'=>'Tarjeta','otro'=>'Otro');
$conceptos_pago = array('anticipo'=>'Anticipo','abono'=>'Abono','liquidacion'=>'Liquidación','devolucion'=>'Devolución');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h3 mb-0">Correcciones admin</h1>
    <div class="text-muted">Modificar pagos, anular pagos duplicados y eliminar notas con historial.</div>
  </div>
</div>

<div class="alert alert-warning">
  Usa esta sección solo para corregir errores reales. Todas las correcciones requieren motivo y quedan registradas en historial. No uses esta pantalla para cambios normales de seguimiento.
</div>

<form class="card card-body mb-4" method="get">
  <label class="form-label">Buscar nota por folio o ID</label>
  <div class="input-group">
    <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Ej. VP-000123 o 123" required>
    <button class="btn btn-primary">Buscar</button>
  </div>
</form>

<?php if ($q !== '' && !$nota): ?>
  <div class="alert alert-danger">No encontré una nota con ese folio o ID.</div>
<?php endif; ?>

<?php if ($nota): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Nota <?= h($nota['folio']) ?></strong>
    <?php if ((int)($nota['eliminada'] ?? 0) === 1): ?><span class="badge text-bg-danger">Eliminada</span><?php endif; ?>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><div class="text-muted small">Empresa</div><div class="fw-bold"><?= h($nota['empresa']) ?></div></div>
      <div class="col-md-3"><div class="text-muted small">Cliente</div><div class="fw-bold"><?= h($nota['cliente_nombre']) ?></div></div>
      <div class="col-md-2"><div class="text-muted small">Total</div><div class="fw-bold"><?= money($nota['total']) ?></div></div>
      <div class="col-md-2"><div class="text-muted small">Pagado</div><div class="fw-bold"><?= money($nota['pagado']) ?></div></div>
      <div class="col-md-2"><div class="text-muted small">Saldo</div><div class="fw-bold"><?= money($nota['saldo']) ?></div></div>
    </div>
    <div class="mt-3 d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-primary" href="<?= url('nota_ver.php?id='.(int)$nota['id']) ?>">Ver nota</a>
      <?php if ((int)($nota['eliminada'] ?? 0) !== 1): ?>
      <button class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#eliminarNota" type="button">Eliminar nota</button>
      <?php endif; ?>
    </div>
    <div class="collapse mt-3" id="eliminarNota">
      <form method="post" action="<?= url('nota_eliminar.php') ?>" class="border rounded p-3 bg-light" onsubmit="return confirm('¿Seguro que deseas eliminar esta nota? Se anularán pagos y caja relacionados.');">
        <input type="hidden" name="nota_id" value="<?= (int)$nota['id'] ?>">
        <label class="form-label required">Motivo de eliminación</label>
        <textarea class="form-control mb-2" name="motivo" required placeholder="Ej. Nota capturada por error"></textarea>
        <button class="btn btn-danger">Confirmar eliminación de nota</button>
      </form>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header">Pagos registrados</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th>ID</th><th>Fecha</th><th>Concepto</th><th>Método</th><th>Monto</th><th>Usuario</th><th>Estado</th><th>Acción</th></tr></thead>
      <tbody>
        <?php foreach ($pagos as $p): $anulado=(int)($p['anulado'] ?? 0)===1; ?>
        <tr class="<?= $anulado?'table-danger':'' ?>">
          <td><?= (int)$p['id'] ?></td>
          <td><?= date_mx($p['fecha_pago']) ?></td>
          <td><?= h($conceptos_pago[$p['concepto']] ?? $p['concepto']) ?></td>
          <td><?= h($formas_pago[$p['forma_pago']] ?? $p['forma_pago']) ?><?= $p['forma_pago_otro']?' / '.h($p['forma_pago_otro']):'' ?></td>
          <td class="fw-bold"><?= money($p['monto']) ?><?php if($anulado && $p['monto_original']!==null): ?><div class="small text-muted">Original: <?= money($p['monto_original']) ?></div><?php endif; ?></td>
          <td><?= h($p['usuario_nombre'] ?? '') ?></td>
          <td><?php if($anulado): ?><span class="badge text-bg-danger">Anulado</span><div class="small text-muted"><?= h($p['anulacion_motivo'] ?? '') ?></div><?php else: ?><span class="badge text-bg-success">Activo</span><?php endif; ?></td>
          <td class="text-nowrap">
            <?php if(!$anulado): ?>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editarPago<?= (int)$p['id'] ?>">Modificar</button>
            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#anularPago<?= (int)$p['id'] ?>">Anular</button>
            <?php else: ?><span class="text-muted small">Sin acción</span><?php endif; ?>
          </td>
        </tr>
        <?php if(!$anulado): ?>
        <tr class="collapse" id="editarPago<?= (int)$p['id'] ?>"><td colspan="8">
          <form method="post" action="<?= url('pago_modificar.php') ?>" class="border rounded p-3 bg-light" onsubmit="return confirm('¿Seguro que deseas modificar este pago? Se ajustará caja y saldo.');">
            <input type="hidden" name="pago_id" value="<?= (int)$p['id'] ?>">
            <div class="row g-3">
              <div class="col-md-3"><label class="form-label required">Fecha</label><input type="date" class="form-control" name="fecha_pago" value="<?= h($p['fecha_pago']) ?>" required></div>
              <div class="col-md-3"><label class="form-label required">Concepto</label><select class="form-select" name="concepto" required><?php foreach($conceptos_pago as $k=>$label): ?><option value="<?= h($k) ?>" <?= $p['concepto']===$k?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
              <div class="col-md-3"><label class="form-label required">Método</label><select class="form-select" name="forma_pago" required><?php foreach($formas_pago as $k=>$label): ?><option value="<?= h($k) ?>" <?= $p['forma_pago']===$k?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
              <div class="col-md-3"><label class="form-label required">Monto</label><input type="number" step="0.01" min="0.01" class="form-control" name="monto" value="<?= h($p['monto']) ?>" required></div>
              <div class="col-md-3"><label class="form-label">Otro método</label><input class="form-control" name="forma_pago_otro" value="<?= h($p['forma_pago_otro'] ?? '') ?>"></div>
              <div class="col-md-3"><label class="form-label">Referencia</label><input class="form-control" name="referencia" value="<?= h($p['referencia'] ?? '') ?>"></div>
              <div class="col-md-3"><label class="form-label">Comprobante</label><input class="form-control" name="comprobante" value="<?= h($p['comprobante'] ?? '') ?>"></div>
              <div class="col-md-12"><label class="form-label">Observaciones del pago</label><textarea class="form-control" name="observaciones" rows="2"><?= h($p['observaciones'] ?? '') ?></textarea></div>
              <div class="col-md-12"><label class="form-label required">Motivo de corrección</label><textarea class="form-control" name="motivo" rows="2" required placeholder="Ej. Se capturó como tarjeta, pero realmente fue efectivo."></textarea></div>
              <div class="col-12"><button class="btn btn-primary">Guardar modificación</button></div>
            </div>
          </form>
        </td></tr>
        <tr class="collapse" id="anularPago<?= (int)$p['id'] ?>"><td colspan="8">
          <form method="post" action="<?= url('pago_anular.php') ?>" class="border rounded p-3 bg-light" onsubmit="return confirm('¿Seguro que deseas anular este pago?');">
            <input type="hidden" name="pago_id" value="<?= (int)$p['id'] ?>">
            <label class="form-label required">Motivo de anulación</label>
            <textarea class="form-control mb-2" name="motivo" required placeholder="Ej. Pago duplicado por error de captura"></textarea>
            <button class="btn btn-danger">Confirmar anulación</button>
          </form>
        </td></tr>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php if(!$pagos): ?><tr><td colspan="8" class="text-center text-muted py-4">Esta nota no tiene pagos registrados.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($audit): ?>
<div class="card mb-4">
  <div class="card-header">Historial de correcciones</div>
  <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Fecha</th><th>Acción</th><th>Entidad</th><th>Motivo</th><th>Usuario</th></tr></thead><tbody>
    <?php foreach($audit as $a): ?><tr><td><?= h($a['created_at']) ?></td><td><?= h($a['accion']) ?></td><td><?= h($a['entidad'].' #'.$a['entidad_id']) ?></td><td><?= nl2br(h($a['motivo'])) ?></td><td><?= h($a['usuario_nombre'] ?? '') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
