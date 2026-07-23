<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) redirect_to('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare("SELECT * FROM v2_usuarios WHERE usuario=? AND activo=1 LIMIT 1");
    $stmt->execute([$usuario]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        $_SESSION['user'] = $u;
        redirect_to('index.php');
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Ingresar · Notas V2</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="<?= url('assets/css/styles.css') ?>"></head><body>
<div class="container py-5" style="max-width:460px">
  <div class="card p-4">
    <div class="text-center mb-3"><div class="brand-mark mx-auto mb-2" style="width:58px;height:58px;font-size:1.7rem">V</div><h1 class="h4">Notas ViPrint / Imagen V2</h1><p class="text-muted small mb-0">Control de pedidos, pagos, costos, mermas y comisiones.</p></div>
    <?php if($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <label class="form-label">Usuario</label><input class="form-control mb-3" name="usuario" required autofocus>
      <label class="form-label">Contraseña</label><input class="form-control mb-3" type="password" name="password" required>
      <button class="btn btn-primary w-100">Ingresar</button>
    </form>
    <p class="text-muted small mt-3 mb-0">Usuarios iniciales: admin, luis, danae, mafer, eduardo, angel, andrea, jaquelin. Contraseña: 123456.</p>
  </div>
</div>
</body></html>
