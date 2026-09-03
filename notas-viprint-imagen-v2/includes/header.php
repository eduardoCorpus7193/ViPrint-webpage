<?php require_once __DIR__ . '/bootstrap.php'; ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/styles.css') ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark vip-navbar sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('index.php') ?>">
      <span class="nav-logo-box"><img src="<?= h(logo_src()) ?>" alt="ViPrint"></span><span>Notas V2</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navMain">
      <?php if (is_logged_in()): ?>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('notas.php') ?>">Notas</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('nota_form.php') ?>">Nueva nota</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('tickets.php') ?>">Tickets</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('mis_notas.php') ?>">Mis notas</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('catalogo.php') ?>">Catálogo</a></li>
        <?php if (can_finance()): ?><li class="nav-item"><a class="nav-link" href="<?= url('reportes.php') ?>">Reportes</a></li><?php endif; ?>
        <?php if (can_cash()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('caja.php') ?>">Caja</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('corte_diario.php') ?>">Corte diario</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('caja_abrir.php') ?>">Abrir cajón</a></li>
        <?php endif; ?>
        <?php if (role_in(array('admin','direccion','asesor'))): ?><li class="nav-item"><a class="nav-link" href="<?= url('usuarios.php') ?>">Usuarios</a></li><?php endif; ?>
          <?php if (current_user() && current_user()['rol'] === 'admin'): ?>
    <a class="nav-link" href="admin_correcciones.php">Correcciones admin</a>
<?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2 text-white small">
        <span><?= h(current_user()['nombre']) ?></span>
        <a class="btn btn-sm btn-light" href="<?= url('logout.php') ?>">Salir</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container-fluid py-4">
<?php foreach (flashes() as $f): ?>
  <div class="alert alert-<?= h($f['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($f['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>
