<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/facturacion_helpers.php';
require_login();
if (!factura_can_manage()) { http_response_code(403); exit('No tienes permiso para revisar solicitudes de factura.'); }
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT fs.*, n.folio AS nota_folio, n.cliente_nombre, n.negocio, n.telefono AS nota_telefono, n.total, n.pagado, n.saldo, n.fecha_nota, n.requiere_factura, n.factura_limite_datos, e.nombre empresa
    FROM v2_factura_solicitudes fs
    JOIN v2_notas n ON n.id = fs.nota_id
    JOIN v2_empresas e ON e.id = n.empresa_id
    WHERE fs.id = ?");
$stmt->execute(array($id));
$s = $stmt->fetch();
if (!$s) { flash('danger','Solicitud no encontrada.'); redirect_to('facturas.php'); }

$stmt = db()->prepare('SELECT descripcion, cantidad, precio_unitario, total FROM v2_nota_partidas WHERE nota_id = ? ORDER BY id');
$stmt->execute(array((int)$s['nota_id']));
$partidas = $stmt->fetchAll();
$stmt = db()->prepare('SELECT fecha_pago, concepto, monto, forma_pago, forma_pago_otro, referencia FROM v2_pagos WHERE nota_id = ? ORDER BY fecha_pago, id');
$stmt->execute(array((int)$s['nota_id']));
$pagos = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h3 mb-0">Solicitud de factura</h1>
    <div class="text-muted">Nota <?= h($s['nota_folio']) ?> · <?= h($s['cliente_nombre']) ?></div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('facturas.php') ?>">Volver</a>
    <a class="btn btn-outline-primary" href="<?= url('nota_ver.php?id='.(int)$s['nota_id']) ?>">Ver nota</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header"><strong>Datos fiscales enviados por el cliente</strong></div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-4"><div class="text-muted small">RFC</div><div class="fw-semibold"><?= h($s['rfc']) ?></div></div>
          <div class="col-md-8"><div class="text-muted small">Razón social</div><div class="fw-semibold"><?= h($s['razon_social']) ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Código postal fiscal</div><div><?= h($s['codigo_postal_fiscal']) ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Régimen fiscal</div><div><?= h($s['regimen_fiscal']) ?></div></div>
          <div class="col-md-4"><div class="text-muted small">Uso CFDI</div><div><?= h($s['uso_cfdi']) ?></div></div>
          <div class="col-md-6"><div class="text-muted small">Correo</div><div><?= h($s['correo']) ?></div></div>
          <div class="col-md-6"><div class="text-muted small">Teléfono</div><div><?= h($s['telefono']) ?></div></div>
          <?php if($s['comentarios_cliente']): ?><div class="col-12"><div class="text-muted small">Comentarios del cliente</div><div><?= nl2br(h($s['comentarios_cliente'])) ?></div></div><?php endif; ?>
        </div>
        <hr>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= url('factura_archivo.php?id='.(int)$s['id'].'&tipo=constancia') ?>">Ver constancia</a>
          <?php if($s['factura_pdf']): ?><a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= url('factura_archivo.php?id='.(int)$s['id'].'&tipo=pdf') ?>">Ver PDF factura</a><?php endif; ?>
          <?php if($s['factura_xml']): ?><a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= url('factura_archivo.php?id='.(int)$s['id'].'&tipo=xml') ?>">Ver XML factura</a><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><strong>Artículos de la nota</strong></div>
      <div class="card-body p-0">
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Descripción</th><th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Total</th></tr></thead><tbody>
        <?php foreach($partidas as $p): ?><tr><td><?= nl2br(h($p['descripcion'])) ?></td><td class="text-end"><?= h($p['cantidad']) ?></td><td class="text-end"><?= money($p['precio_unitario']) ?></td><td class="text-end"><?= money($p['total']) ?></td></tr><?php endforeach; ?>
        <?php if(!$partidas): ?><tr><td colspan="4" class="text-muted p-3">Sin artículos.</td></tr><?php endif; ?>
        </tbody></table></div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><strong>Pagos registrados</strong></div>
      <div class="card-body p-0">
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Fecha</th><th>Concepto</th><th>Método</th><th>Referencia</th><th class="text-end">Monto</th></tr></thead><tbody>
        <?php foreach($pagos as $p): ?><tr><td><?= date_mx($p['fecha_pago']) ?></td><td><?= h($p['concepto']) ?></td><td><?= h($p['forma_pago']==='otro' && $p['forma_pago_otro'] ? $p['forma_pago_otro'] : $p['forma_pago']) ?></td><td><?= h($p['referencia']) ?></td><td class="text-end"><?= money($p['monto']) ?></td></tr><?php endforeach; ?>
        <?php if(!$pagos): ?><tr><td colspan="5" class="text-muted p-3">Sin pagos.</td></tr><?php endif; ?>
        </tbody></table></div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><strong>Resumen</strong></div>
      <div class="card-body">
        <div class="d-flex justify-content-between"><span>Total nota</span><strong><?= money($s['total']) ?></strong></div>
        <div class="d-flex justify-content-between"><span>Pagado</span><strong><?= money($s['pagado']) ?></strong></div>
        <div class="d-flex justify-content-between"><span>Saldo</span><strong><?= money($s['saldo']) ?></strong></div>
        <hr>
        <div class="d-flex justify-content-between"><span>Estado solicitud</span><span class="badge text-bg-<?= factura_status_class($s['estado']) ?>"><?= h(factura_status_label($s['estado'])) ?></span></div>
        <div class="d-flex justify-content-between"><span>Límite datos</span><strong><?= h(factura_limite_text($s)) ?></strong></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><strong>Actualizar solicitud</strong></div>
      <div class="card-body">
        <form method="post" action="<?= url('factura_actualizar.php') ?>" enctype="multipart/form-data">
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado" required>
              <?php foreach(array('pendiente','en_revision','datos_incorrectos','facturada','cancelada') as $e): ?><option value="<?= h($e) ?>" <?= $s['estado']===$e?'selected':'' ?>><?= h(factura_status_label($e)) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">UUID / folio fiscal</label>
            <input class="form-control" name="uuid" value="<?= h($s['uuid']) ?>" placeholder="Opcional">
          </div>
          <div class="mb-3">
            <label class="form-label">Subir PDF de factura</label>
            <input type="file" class="form-control" name="factura_pdf" accept="application/pdf,.pdf">
            <div class="form-text">Opcional.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Subir XML de factura</label>
            <input type="file" class="form-control" name="factura_xml" accept="application/xml,text/xml,.xml">
            <div class="form-text">Opcional.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Comentarios internos</label>
            <textarea class="form-control" name="comentarios_internos" rows="4"><?= h($s['comentarios_internos']) ?></textarea>
          </div>
          <button class="btn btn-primary w-100">Guardar cambios</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
