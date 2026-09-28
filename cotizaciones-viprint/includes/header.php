<?php $current = isset($current) ? $current : ''; ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/styles.css">
</head>
<body>
<div class="app-shell">
    <header class="topbar no-print">
        <div class="container py-3">
            <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
                <a href="<?php echo h(BASE_URL); ?>/" class="d-flex align-items-center gap-3 text-decoration-none">
                    <img src="<?php echo h(BUSINESS_LOGO_URL); ?>" alt="ViPrint" class="brand-logo" onerror="this.style.display='none'">
                    <div>
                        <div class="brand-title">Cotizaciones ViPrint</div>
                        <div class="brand-subtitle">Sistema interno de cotizaciones formales</div>
                    </div>
                </a>
                <nav class="nav nav-pills gap-1">
                    <a class="nav-link <?php echo $current==='inicio'?'active':''; ?>" href="<?php echo h(BASE_URL); ?>/">Inicio</a>
                    <a class="nav-link <?php echo $current==='nueva'?'active':''; ?>" href="<?php echo h(BASE_URL); ?>/cotizacion_form.php">Nueva cotización</a>
                    <a class="nav-link <?php echo $current==='promociones'?'active':''; ?>" href="<?php echo h(BASE_URL); ?>/promociones.php">Promociones</a>
                </nav>
            </div>
        </div>
    </header>
    <main class="container py-4">
        <?php $flash = flash_get(); if ($flash): ?>
            <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show no-print" role="alert">
                <?php echo h($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
