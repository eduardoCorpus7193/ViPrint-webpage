<?php
require_once __DIR__ . '/includes/bootstrap.php';
$current = 'promociones';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM promociones WHERE id=?");
    $stmt->execute(array($editId));
    $edit = $stmt->fetch();
}
$promos = get_promociones($pdo, false);
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="page-title mb-1">Promociones y artículos base</h1>
        <div class="small-muted">Registra nuevas promociones, modifica precios o desactiva las que ya no se usen.</div>
    </div>
    <a href="<?php echo h(BASE_URL); ?>/cotizacion_form.php" class="btn btn-vip">Nueva cotización</a>
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-vip">
            <div class="card-header card-header-vip"><?php echo $edit ? 'Editar promoción' : 'Nueva promoción'; ?></div>
            <div class="card-body">
                <form method="post" action="<?php echo h(BASE_URL); ?>/guardar_promocion.php">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $edit ? (int)$edit['id'] : 0; ?>">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" name="nombre" required value="<?php echo h($edit ? $edit['nombre'] : ''); ?>" placeholder="Ej. Promo Light, Promo Glass, Bandera por unidad">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="4" placeholder="Incluye, medidas, instalación, diseño, material, restricciones..."><?php echo h($edit ? $edit['descripcion'] : ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="precio" value="<?php echo h($edit ? $edit['precio'] : '0.00'); ?>">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo" <?php echo (!$edit || $edit['activo'])?'checked':''; ?>>
                        <label class="form-check-label fw-bold" for="activo">Promoción activa</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-vip" type="submit">Guardar promoción</button>
                        <?php if ($edit): ?><a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/promociones.php">Cancelar edición</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-vip">
            <div class="card-header card-header-vip">Catálogo actual</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Nombre</th><th>Precio</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($promos as $promo): ?>
                        <tr>
                            <td><div class="fw-bold"><?php echo h($promo['nombre']); ?></div><div class="small-muted"><?php echo h(mb_strimwidth($promo['descripcion'],0,90,'...','UTF-8')); ?></div></td>
                            <td class="fw-bold"><?php echo h(money($promo['precio'])); ?></td>
                            <td><span class="badge text-bg-<?php echo $promo['activo']?'success':'secondary'; ?>"><?php echo $promo['activo']?'Activa':'Inactiva'; ?></span></td>
                            <td class="text-end"><a class="btn btn-outline-vip btn-sm" href="<?php echo h(BASE_URL); ?>/promociones.php?edit=<?php echo (int)$promo['id']; ?>">Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
