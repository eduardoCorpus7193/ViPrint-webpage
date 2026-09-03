<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $rol = $_POST['rol'] ?? 'disenador';
    if (!in_array($rol, array('admin','operativo','disenador'), true)) $rol = 'disenador';
    $activo = isset($_POST['activo']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    if ($nombre !== '' && $usuario !== '') {
        if ($id > 0) {
            if ($password !== '') {
                $stmt = $pdo->prepare('UPDATE usuarios SET nombre=?, usuario=?, rol=?, activo=?, password_hash=? WHERE id=?');
                $stmt->execute(array($nombre, $usuario, $rol, $activo, password_hash($password, PASSWORD_DEFAULT), $id));
            } else {
                $stmt = $pdo->prepare('UPDATE usuarios SET nombre=?, usuario=?, rol=?, activo=? WHERE id=?');
                $stmt->execute(array($nombre, $usuario, $rol, $activo, $id));
            }
        } else {
            if ($password === '') $password = '123456';
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, rol, activo, password_hash) VALUES (?,?,?,?,?)');
            $stmt->execute(array($nombre, $usuario, $rol, $activo, password_hash($password, PASSWORD_DEFAULT)));
        }
        $_SESSION['flash'] = array('type'=>'success', 'message'=>'Usuario guardado.');
    }
    redirect('usuarios.php');
}
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id=?');
    $stmt->execute(array((int)$_GET['edit']));
    $edit = $stmt->fetch();
}
$stmt = $pdo->query('SELECT * FROM usuarios ORDER BY activo DESC, rol, nombre');
$users = $stmt->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 fw-bold mb-4">Usuarios y diseñadores</h1>
<div class="row g-4">
    <div class="col-12 col-lg-4"><div class="card"><div class="card-header"><?php echo $edit ? 'Editar usuario' : 'Nuevo usuario'; ?></div><div class="card-body"><form method="post"><?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo h($edit['id'] ?? 0); ?>"><div class="mb-3"><label class="form-label required">Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo h($edit['nombre'] ?? ''); ?>" required></div><div class="mb-3"><label class="form-label required">Usuario</label><input type="text" name="usuario" class="form-control" value="<?php echo h($edit['usuario'] ?? ''); ?>" required></div><div class="mb-3"><label class="form-label">Rol</label><select name="rol" class="form-select"><?php foreach (array('admin'=>'Administrador','operativo'=>'Operativo / mostrador','disenador'=>'Diseñador') as $k=>$v): ?><option value="<?php echo h($k); ?>" <?php echo (($edit['rol'] ?? '')===$k)?'selected':''; ?>><?php echo h($v); ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label">Contraseña</label><input type="password" name="password" class="form-control" placeholder="Dejar vacío para conservar"></div><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="activo" id="activo" <?php echo (!$edit || (int)$edit['activo']===1)?'checked':''; ?>><label for="activo" class="form-check-label">Activo</label></div><button class="btn btn-primary w-100">Guardar</button><?php if ($edit): ?><a class="btn btn-outline-secondary w-100 mt-2" href="<?php echo h(url('usuarios.php')); ?>">Cancelar</a><?php endif; ?></form></div></div></div>
    <div class="col-12 col-lg-8"><div class="card"><div class="card-header">Usuarios registrados</div><div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach ($users as $u): ?><tr><td><?php echo h($u['nombre']); ?></td><td><?php echo h($u['usuario']); ?></td><td><?php echo h($u['rol']); ?></td><td><?php echo (int)$u['activo'] ? '<span class="badge text-bg-success">Activo</span>' : '<span class="badge text-bg-secondary">Inactivo</span>'; ?></td><td><a href="<?php echo h(url('usuarios.php?edit=' . $u['id'])); ?>" class="btn btn-sm btn-outline-primary">Editar</a></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
