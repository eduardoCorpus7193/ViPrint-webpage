<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? AND activo = 1 LIMIT 1');
    $stmt->execute(array($usuario));
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = array(
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'usuario' => $user['usuario'],
            'rol' => $user['rol']
        );
        redirect('index.php');
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar | Control de notas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo h(url('assets/css/styles.css')); ?>">
</head>
<body class="d-flex align-items-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-7 col-lg-5">
            <div class="card p-4">
                <div class="text-center mb-4">
                    <img src="https://www.viprint.com.mx/img/logo.png" alt="ViPrint" style="max-width:180px" class="mb-2">
                    <h1 class="h4 fw-bold text-brand">Control de notas</h1>
                    <p class="text-muted mb-0">ViPrint / Imagen</p>
                </div>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="usuario" class="form-control form-control-lg" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control form-control-lg" required>
                    </div>
                    <button class="btn btn-primary btn-lg w-100">Ingresar</button>
                </form>
                <div class="small text-muted mt-3">
                    Usuarios iniciales: admin, danae y angel. Contraseña inicial: 123456.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
