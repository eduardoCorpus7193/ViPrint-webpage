<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!is_operativo()) { http_response_code(403); echo 'No autorizado.'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $empresa = in_array($_POST['empresa'] ?? 'viprint', array('viprint','imagen'), true) ? $_POST['empresa'] : 'viprint';
        $tipo = in_array($_POST['tipo'] ?? 'articulo', array('promocion','articulo','bandera','otro'), true) ? $_POST['tipo'] : 'articulo';
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = max(0, (float)($_POST['precio'] ?? 0));
        $activo = isset($_POST['activo']) ? 1 : 0;
        if ($nombre !== '') {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE catalogo_items SET empresa=?, tipo=?, nombre=?, descripcion=?, precio=?, activo=? WHERE id=?');
                $stmt->execute(array($empresa, $tipo, $nombre, $descripcion, $precio, $activo, $id));
            } else {
                $stmt = $pdo->prepare('INSERT INTO catalogo_items (empresa, tipo, nombre, descripcion, precio, activo) VALUES (?,?,?,?,?,?)');
                $stmt->execute(array($empresa, $tipo, $nombre, $descripcion, $precio, $activo));
            }
            $_SESSION['flash'] = array('type'=>'success', 'message'=>'Catálogo actualizado.');
        }
        redirect('catalogo.php');
    }
}

$stmt = $pdo->query('SELECT * FROM catalogo_items ORDER BY empresa, tipo, nombre');
$items = $stmt->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM catalogo_items WHERE id = ?');
    $stmt->execute(array((int)$_GET['edit']));
    $edit = $stmt->fetch();
}
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4 flex-column flex-md-row">
    <div><h1 class="h3 fw-bold mb-1">Catálogo</h1><p class="text-muted mb-0">Promociones de ViPrint, artículos libres y banderas de Imagen.</p></div>
</div>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header"><?php echo $edit ? 'Editar item' : 'Nuevo item'; ?></div>
            <div class="card-body">
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo h($edit['id'] ?? 0); ?>">
                    <div class="mb-3"><label class="form-label">Empresa</label><select name="empresa" class="form-select"><option value="viprint" <?php echo (($edit['empresa'] ?? '')==='viprint')?'selected':''; ?>>ViPrint</option><option value="imagen" <?php echo (($edit['empresa'] ?? '')==='imagen')?'selected':''; ?>>Imagen</option></select></div>
                    <div class="mb-3"><label class="form-label">Tipo</label><select name="tipo" class="form-select"><?php foreach (array('promocion'=>'Promoción','articulo'=>'Artículo','bandera'=>'Bandera','otro'=>'Otro') as $k=>$v): ?><option value="<?php echo h($k); ?>" <?php echo (($edit['tipo'] ?? '')===$k)?'selected':''; ?>><?php echo h($v); ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label required">Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo h($edit['nombre'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Descripción</label><textarea name="descripcion" class="form-control" rows="3"><?php echo h($edit['descripcion'] ?? ''); ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Precio sugerido</label><input type="number" step="0.01" min="0" name="precio" class="form-control" value="<?php echo h($edit['precio'] ?? '0.00'); ?>"></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="activo" id="activo" <?php echo (!$edit || (int)$edit['activo']===1)?'checked':''; ?>><label for="activo" class="form-check-label">Activo</label></div>
                    <button class="btn btn-primary w-100">Guardar</button>
                    <?php if ($edit): ?><a href="<?php echo h(url('catalogo.php')); ?>" class="btn btn-outline-secondary w-100 mt-2">Cancelar edición</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">Items registrados</div>
            <div class="card-body p-0">
                <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Empresa</th><th>Tipo</th><th>Nombre</th><th>Precio</th><th>Estado</th><th></th></tr></thead><tbody>
                <?php foreach ($items as $i): ?><tr><td><?php echo h(empresa_label($i['empresa'])); ?></td><td><?php echo h($i['tipo']); ?></td><td><strong><?php echo h($i['nombre']); ?></strong><div class="small text-muted"><?php echo h($i['descripcion']); ?></div></td><td><?php echo h(money($i['precio'])); ?></td><td><?php echo (int)$i['activo'] ? '<span class="badge text-bg-success">Activo</span>' : '<span class="badge text-bg-secondary">Inactivo</span>'; ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo h(url('catalogo.php?edit=' . $i['id'])); ?>">Editar</a></td></tr><?php endforeach; ?>
                </tbody></table></div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
