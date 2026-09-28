<?php
require_once __DIR__ . '/bootstrap.php';
if (file_exists(__DIR__ . '/modulos_2_3_10_helpers.php')) require_once __DIR__ . '/modulos_2_3_10_helpers.php';
if (file_exists(__DIR__ . '/facturacion_helpers.php')) require_once __DIR__ . '/facturacion_helpers.php';
if (file_exists(__DIR__ . '/actualizacion_20260924_helpers.php')) require_once __DIR__ . '/actualizacion_20260924_helpers.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/styles.css') ?>">
    <style>
      .vip-navbar .nav-link,.vip-navbar .dropdown-item{white-space:nowrap}
      .vip-navbar .dropdown-menu{border:0;box-shadow:0 10px 28px rgba(0,0,0,.16);border-radius:14px;padding:.5rem}
      .vip-navbar .dropdown-item{border-radius:10px;padding:.55rem .8rem}
      .vip-navbar .dropdown-item:active{background:#A92624}
      .module-pill{font-size:.78rem;border-radius:999px;padding:.15rem .55rem;background:rgba(255,255,255,.16);color:#fff}
    </style>
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

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Notas</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= url('nota_form.php') ?>">Nueva nota</a></li>
            <li><a class="dropdown-item" href="<?= url('notas.php') ?>">Ver notas</a></li>
            <li><a class="dropdown-item" href="<?= url('estado_pedidos.php') ?>">Estado pedidos</a></li>
            <li><a class="dropdown-item" href="<?= url('mis_notas.php') ?>">Mis notas</a></li>
          </ul>
        </li>

        <?php if (function_exists('m2310_can_use_corte') ? m2310_can_use_corte() : can_cash()): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Caja</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= url('caja.php') ?>">Caja</a></li>
            <li><a class="dropdown-item" href="<?= url('corte_diario.php') ?>">Corte diario</a></li>
            <li><a class="dropdown-item" href="<?= url('cortes_historial.php') ?>">Historial cortes</a></li>
            <li><a class="dropdown-item" href="<?= url('caja_abrir.php') ?>">Abrir cajón</a></li>
            <li><a class="dropdown-item" href="<?= url('tickets.php') ?>">Tickets</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Clientes</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= url('consulta_pedido.php') ?>">Consulta cliente</a></li>
            <?php if (function_exists('factura_can_manage') ? factura_can_manage() : role_in(array('admin','direccion','administracion','operativo','asesor'))): ?>
              <li><a class="dropdown-item" href="<?= url('facturas.php') ?>">Solicitudes de factura</a></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="<?= url('quejas.php') ?>">Quejas y sugerencias</a></li>
          </ul>
        </li>

        <?php if (function_exists('m2310_can_view_reports') ? m2310_can_view_reports() : can_finance()): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Reportes</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= url('reportes_generales.php') ?>">Reportes generales</a></li>
            <li><a class="dropdown-item" href="<?= url('reportes.php') ?>">Reportes V2</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Administración</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= url('catalogo.php') ?>">Catálogo</a></li>
            <?php if (role_in(array('admin','direccion','asesor'))): ?><li><a class="dropdown-item" href="<?= url('usuarios.php') ?>">Usuarios</a></li><?php endif; ?>
            <?php if (current_user() && current_user()['rol'] === 'admin'): ?><li><a class="dropdown-item" href="<?= url('admin_correcciones.php') ?>">Correcciones admin</a></li><?php endif; ?>
          </ul>
        </li>
      </ul>
      <div class="d-flex align-items-center gap-2 text-white small">
        <span class="d-none d-xl-inline module-pill">Sistema ViPrint</span>
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
