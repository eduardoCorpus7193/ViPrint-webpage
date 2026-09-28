<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT n.*, u.nombre AS disenador_nombre, c.nombre AS creado_nombre
                       FROM notas n
                       LEFT JOIN usuarios u ON u.id = n.disenador_id
                       LEFT JOIN usuarios c ON c.id = n.creado_por
                       WHERE n.id = ?');
$stmt->execute(array($id));
$nota = $stmt->fetch();
if (!$nota) { http_response_code(404); echo 'Nota no encontrada.'; exit; }
if (!can_view_note($nota)) { http_response_code(403); echo 'No autorizado.'; exit; }

$stmt = $pdo->prepare('SELECT * FROM nota_detalles WHERE nota_id = ? ORDER BY id');
$stmt->execute(array($id));
$detalles = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT a.*, u.nombre AS usuario_nombre FROM abonos a LEFT JOIN usuarios u ON u.id = a.usuario_id WHERE a.nota_id = ? ORDER BY a.fecha_pago DESC, a.id DESC');
$stmt->execute(array($id));
$abonos = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT h.*, u.nombre AS usuario_nombre FROM estado_historial h LEFT JOIN usuarios u ON u.id = h.usuario_id WHERE h.nota_id = ? ORDER BY h.created_at DESC');
$stmt->execute(array($id));
$historial = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4 flex-column flex-md-row no-print">
    <div>
        <h1 class="h3 fw-bold mb-1"><?php echo h(empresa_label($nota['empresa'])); ?> · Nota #<?php echo h($nota['folio']); ?></h1>
        <p class="text-muted mb-0">Cliente: <?php echo h($nota['cliente_nombre']); ?></p>
    </div>
    <div class="d-flex gap-2 flex-column flex-md-row">
        <?php if (is_operativo()): ?><a class="btn btn-outline-primary" href="<?php echo h(url('nota_form.php?id=' . $nota['id'])); ?>">Editar</a><?php endif; ?>
        <a class="btn btn-outline-secondary" target="_blank" href="<?php echo h(url('nota_imprimir.php?id=' . $nota['id'])); ?>">Imprimir</a>
        <a class="btn btn-outline-primary" href="<?php echo h(url('notas.php')); ?>">Volver</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between flex-column flex-md-row gap-2">
                <span>Datos generales</span>
                <span class="status-badge <?php echo h(estado_badge_class($nota['estado'])); ?>"><?php echo h(estado_label($nota['estado'])); ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>Cliente:</strong><br><?php echo h($nota['cliente_nombre']); ?></div>
                    <div class="col-md-6"><strong>Negocio:</strong><br><?php echo h($nota['negocio'] ?: 'No especificado'); ?></div>
                    <div class="col-md-6"><strong>Domicilio:</strong><br><?php echo h($nota['domicilio'] ?: 'No especificado'); ?></div>
                    <div class="col-md-6"><strong>Teléfono:</strong><br><?php echo h($nota['telefono'] ?: 'No especificado'); ?></div>
                    <div class="col-md-4"><strong>Fecha nota:</strong><br><?php echo h(date_mx($nota['fecha_nota'])); ?></div>
                    <div class="col-md-4"><strong>Fecha prometida:</strong><br><?php echo h(date_mx($nota['fecha_promesa'])); ?></div>
                    <div class="col-md-4"><strong>Instalación:</strong><br><?php echo h(date_mx($nota['fecha_instalacion'])); ?></div>
                    <div class="col-md-4"><strong>Origen:</strong><br><?php echo h($nota['origen']); ?></div>
                    <div class="col-md-4"><strong>Vendedor/origen:</strong><br><?php echo h($nota['vendedor_nombre'] ?: 'No aplica'); ?></div>
                    <div class="col-md-4"><strong>Diseñador:</strong><br><?php echo h($nota['disenador_nombre'] ?: 'Sin asignar'); ?></div>
                </div>
                <?php if ($nota['observaciones']): ?>
                <hr><strong>Observaciones:</strong><br><?php echo nl2br(h($nota['observaciones'])); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Descripción del pedido</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Cant.</th><th>Tipo</th><th>Descripción</th><th>P. unit.</th><th>Importe</th></tr></thead>
                        <tbody>
                            <?php foreach ($detalles as $d): ?>
                            <tr>
                                <td><?php echo h($d['cantidad']); ?></td>
                                <td><?php echo h($d['tipo_item']); ?></td>
                                <td><?php echo nl2br(h($d['descripcion'])); ?></td>
                                <td><?php echo h(money($d['precio_unitario'])); ?></td>
                                <td><?php echo h(money($d['importe'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$detalles): ?><tr><td colspan="5" class="text-muted text-center py-4">Sin detalles.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Historial de estados</div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($historial as $hitem): ?>
                    <div class="timeline-item">
                        <div><strong><?php echo h(estado_label($hitem['estado_nuevo'])); ?></strong></div>
                        <div class="small text-muted"><?php echo h(date('d/m/Y H:i', strtotime($hitem['created_at']))); ?> · <?php echo h($hitem['usuario_nombre'] ?: 'Sistema'); ?></div>
                        <?php if ($hitem['comentario']): ?><div><?php echo nl2br(h($hitem['comentario'])); ?></div><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$historial): ?><div class="text-muted">Aún no hay historial.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card mb-4">
            <div class="card-header">Resumen de pago</div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Total</span><strong><?php echo h(money($nota['total'])); ?></strong></div>
                <div class="d-flex justify-content-between"><span>Anticipo</span><strong><?php echo h(money($nota['anticipo'])); ?></strong></div>
                <?php foreach ($abonos as $a): ?>
                <div class="d-flex justify-content-between small text-muted"><span>Abono <?php echo h(date_mx($a['fecha_pago'])); ?></span><span><?php echo h(money($a['monto'])); ?></span></div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between h5"><span>Saldo</span><strong class="<?php echo ((float)$nota['saldo'] > 0) ? 'text-danger' : 'text-success'; ?>"><?php echo h(money($nota['saldo'])); ?></strong></div>
            </div>
        </div>

        <?php if (can_edit_note($nota)): ?>
        <div class="card mb-4 no-print">
            <div class="card-header">Cambiar estado</div>
            <div class="card-body">
                <form method="post" action="<?php echo h(url('nota_estado.php')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo h($nota['id']); ?>">
                    <div class="mb-3">
                        <label class="form-label">Nuevo estado</label>
                        <select name="estado" class="form-select">
                            <?php foreach (estado_options() as $k=>$v): ?>
                            <option value="<?php echo h($k); ?>" <?php echo $nota['estado']===$k?'selected':''; ?>><?php echo h($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comentario</label>
                        <textarea name="comentario" class="form-control" rows="3" placeholder="Ej. Cliente aprobó diseño por WhatsApp."></textarea>
                    </div>
                    <button class="btn btn-primary w-100">Actualizar estado</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (is_operativo()): ?>
        <div class="card mb-4 no-print">
            <div class="card-header">Registrar abono</div>
            <div class="card-body">
                <form method="post" action="<?php echo h(url('abono_guardar.php')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="nota_id" value="<?php echo h($nota['id']); ?>">
                    <div class="mb-3"><label class="form-label">Fecha</label><input type="date" name="fecha_pago" class="form-control" value="<?php echo h(date('Y-m-d')); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Monto</label><input type="number" step="0.01" min="0" name="monto" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Forma de pago</label><select name="forma_pago" class="form-select"><option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="tarjeta">Tarjeta</option><option value="otro">Otro</option></select></div>
                    <div class="mb-3"><label class="form-label">Referencia</label><input type="text" name="referencia" class="form-control" placeholder="Opcional"></div>
                    <button class="btn btn-primary w-100">Guardar abono</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
