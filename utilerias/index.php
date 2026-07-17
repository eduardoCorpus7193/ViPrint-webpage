<?php
declare(strict_types=1);

$systems = [
    [
        'title' => 'Vacaciones',
        'description' => 'Consulta saldos, registra empleados y genera solicitudes de vacaciones.',
        'url' => 'https://viprint.com.mx/viprint-vacaciones/',
        'icon' => 'calendar',
        'label' => 'Abrir vacaciones',
    ],
    [
        'title' => 'Permisos de salida',
        'description' => 'Registra y consulta permisos para ausentarse durante la jornada laboral.',
        'url' => 'https://viprint.com.mx/permisos-salida/',
        'icon' => 'exit',
        'label' => 'Abrir permisos',
    ],
    [
        'title' => 'Horas extras',
        'description' => 'Captura las horas adicionales trabajadas y genera los formatos para firma.',
        'url' => 'https://viprint.com.mx/horas-extras/',
        'icon' => 'clock',
        'label' => 'Abrir horas extras',
    ],
    [
        'title' => 'Documentos de nómina',
        'description' => 'Accede a documentos, comprobantes y formatos relacionados con nómina.',
        'url' => 'https://viprint.com.mx/documentos-nomina/',
        'icon' => 'document',
        'label' => 'Abrir documentos',
    ],
    [
        'title' => 'Cotizaciones',
        'description' => 'Genera cotizaciones formales para clientes.',
        'url' => 'https://viprint.com.mx/cotizaciones-viprint/',
        'icon' => 'document',
        'label' => 'Abrir documentos',
    ],
];

function iconSvg(string $icon): string
{
    return match ($icon) {
        'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v3M17 2v3M3.5 9h17M5.5 4h13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/><path d="m9 15 2 2 4-5"/></svg>',
        'exit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h5M14 8l4 4-4 4M8 12h10"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>',
        default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>',
    };
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Accesos internos a los sistemas administrativos de ViPrint Publicidad.">
    <meta name="theme-color" content="#A92624">
    <title>Sistemas internos | ViPrint Publicidad</title>
    <link href="../img/favicon.ico" rel="icon">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <div class="brand">
                <img src="assets/img/logo.png" alt="ViPrint Publicidad" class="brand-logo">
                <div class="brand-copy">
                    <span class="eyebrow">Panel interno</span>
                    <strong>ViPrint Publicidad</strong>
                </div>
            </div>
            <span class="status-pill"><span class="status-dot"></span>Sistemas administrativos</span>
        </header>

        <main class="content">
            <section class="hero" aria-labelledby="page-title">
                <span class="hero-kicker">Accesos rápidos</span>
                <h1 id="page-title">Sistemas internos</h1>
                <p>Selecciona el módulo que necesitas. Cada acceso se abrirá en la misma ventana.</p>
            </section>

            <section class="systems-grid" aria-label="Módulos disponibles">
                <?php foreach ($systems as $system): ?>
                    <article class="system-card">
                        <div class="system-icon">
                            <?= iconSvg($system['icon']) ?>
                        </div>
                        <div class="system-content">
                            <h2><?= htmlspecialchars($system['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><?= htmlspecialchars($system['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <a class="system-link"
                           href="<?= htmlspecialchars($system['url'], ENT_QUOTES, 'UTF-8') ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                           aria-label="<?= htmlspecialchars($system['label'], ENT_QUOTES, 'UTF-8') ?>">
                            <span><?= htmlspecialchars($system['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="notice">
                <div class="notice-icon" aria-hidden="true">i</div>
                <p>Estos accesos son para uso administrativo de ViPrint. No compartas información interna, contraseñas ni documentos con personas no autorizadas.</p>
            </aside>
        </main>

        <footer class="footer">
            <span>ViPrint Publicidad</span>
            <span>© <?= date('Y') ?> · Panel de sistemas internos</span>
        </footer>
    </div>
</body>
</html>
