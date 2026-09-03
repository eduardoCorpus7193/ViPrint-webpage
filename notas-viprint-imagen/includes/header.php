<?php $user = current_user(); ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h(APP_NAME); ?> | ViPrint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo h(url('assets/css/styles.css')); ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light app-navbar sticky-top">
    <div class="container-fluid container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo h(url('index.php')); ?>">
            <img src="https://www.viprint.com.mx/img/logo.png" alt="ViPrint" class="brand-logo">
            <span class="brand-text">Control de notas</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <?php if ($user): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?php echo h(url('index.php')); ?>">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h(url('notas.php')); ?>">Notas</a></li>
                <?php if (is_disenador()): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo h(url('mis_notas.php')); ?>">Mis notas</a></li>
                <?php endif; ?>
                <?php if (is_operativo()): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo h(url('nota_form.php')); ?>">Nueva nota</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h(url('catalogo.php')); ?>">Catálogo</a></li>
                <?php endif; ?>
                <?php if (is_admin()): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo h(url('usuarios.php')); ?>">Usuarios</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-lg-center gap-2 flex-column flex-lg-row">
                <span class="small text-muted"><?php echo h($user['nombre']); ?> · <?php echo h($user['rol']); ?></span>
                <a class="btn btn-outline-danger btn-sm" href="<?php echo h(url('logout.php')); ?>">Salir</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container-fluid container-xl py-4">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-<?php echo h($_SESSION['flash']['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo h($_SESSION['flash']['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
