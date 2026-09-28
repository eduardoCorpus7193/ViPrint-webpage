<?php
require_once __DIR__ . '/includes/bootstrap.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quote = fetch_quote($pdo, $id);
if (!$quote) { http_response_code(404); exit('Cotización no encontrada.'); }
$items = fetch_quote_items($pdo, $id);
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3 no-print">
    <div>
        <h1 class="page-title mb-1">Cotización <?php echo h($quote['folio']); ?></h1>
        <div class="small-muted">Vista formal para imprimir, guardar o descargar PDF.</div>
    </div>
    <div class="d-flex flex-wrap gap-2 print-actions">
        <a href="<?php echo h(BASE_URL); ?>/" class="btn btn-outline-secondary">Volver</a>
        <a href="<?php echo h(BASE_URL); ?>/cotizacion_form.php?id=<?php echo (int)$quote['id']; ?>" class="btn btn-outline-vip">Editar</a>
        <a href="<?php echo h(BASE_URL); ?>/descargar_pdf.php?id=<?php echo (int)$quote['id']; ?>" class="btn btn-vip">Descargar PDF</a>
        <button onclick="window.print()" class="btn btn-dark">Imprimir / Guardar PDF</button>
    </div>
</div>

<div class="quote-page">
    <div class="quote-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-start">
        <div>
            <img src="<?php echo h(BUSINESS_LOGO_URL); ?>" alt="ViPrint" class="quote-logo mb-2" onerror="this.style.display='none'">
            <div class="fw-bold fs-5" style="color:#A92624"><?php echo h(BUSINESS_NAME); ?></div>
            <div class="small">RFC: <?php echo h(BUSINESS_RFC); ?></div>
            <div class="small"><?php echo h(BUSINESS_ADDRESS); ?></div>
            <div class="small">Tel. <?php echo h(BUSINESS_PHONE); ?> · <?php echo h(BUSINESS_EMAIL); ?></div>
        </div>
        <div class="quote-folio">
            <div class="text-muted small">FOLIO</div>
            <strong><?php echo h($quote['folio']); ?></strong>
            <div class="small mt-2">Fecha: <?php echo h(date_mx($quote['fecha'])); ?></div>
            <div class="small">Vigencia: <?php echo (int)$quote['validez_dias']; ?> días</div>
        </div>
    </div>

    <h2 class="quote-title mb-4">COTIZACIÓN FORMAL</h2>

    <div class="row g-3 mb-4">
        <div class="col-md-7">
            <table class="table table-bordered">
                <tbody>
                    <tr><th style="width:32%">Cliente</th><td><?php echo h($quote['cliente_nombre']); ?></td></tr>
                    <tr><th>Negocio</th><td><?php echo h($quote['cliente_negocio']); ?></td></tr>
                    <tr><th>Teléfono</th><td><?php echo h($quote['cliente_telefono']); ?></td></tr>
                    <?php if ($quote['cliente_email']): ?><tr><th>Correo</th><td><?php echo h($quote['cliente_email']); ?></td></tr><?php endif; ?>
                    <?php if ($quote['cliente_domicilio']): ?><tr><th>Domicilio</th><td><?php echo h($quote['cliente_domicilio']); ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-5">
            <div class="border rounded-3 p-3 h-100" style="background:#fff7f7">
                <div class="fw-bold mb-1" style="color:#A92624">Estatus</div>
                <span class="badge text-bg-<?php echo h(badge_class($quote['estatus'])); ?>"><?php echo h(quote_status_label($quote['estatus'])); ?></span>
                <div class="small-muted mt-3">Documento interno comercial. Los datos pueden ajustarse antes de aprobación final.</div>
            </div>
        </div>
    </div>

    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead><tr><th style="width:10%">Cant.</th><th>Descripción</th><th style="width:18%">P. unitario</th><th style="width:18%">Importe</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?php echo h(number_format((float)$it['cantidad'], 2)); ?></td>
                    <td><?php echo nl2br(h($it['descripcion'])); ?></td>
                    <td><?php echo h(money($it['precio_unitario'])); ?></td>
                    <td class="fw-bold"><?php echo h(money($it['importe'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="3" class="text-end">Subtotal</th><th><?php echo h(money($quote['subtotal'])); ?></th></tr>
                <?php if ($quote['aplicar_iva']): ?><tr><th colspan="3" class="text-end">IVA <?php echo h($quote['porcentaje_iva']); ?>%</th><th><?php echo h(money($quote['iva'])); ?></th></tr><?php endif; ?>
                <tr><th colspan="3" class="text-end fs-5">Total</th><th class="fs-5" style="color:#A92624"><?php echo h(money($quote['total'])); ?></th></tr>
            </tfoot>
        </table>
    </div>

    <?php if ($quote['observaciones']): ?>
    <div class="mb-4">
        <div class="fw-bold" style="color:#A92624">Observaciones</div>
        <div><?php echo nl2br(h($quote['observaciones'])); ?></div>
    </div>
    <?php endif; ?>

    <div class="mb-4">
        <div class="fw-bold" style="color:#A92624">Condiciones comerciales</div>
        <div class="small"><?php echo nl2br(h($quote['condiciones'])); ?></div>
    </div>

    <div class="row g-5 mt-4">
        <div class="col-6"><div class="signature-line">Firma / aceptación del cliente</div></div>
        <div class="col-6"><div class="signature-line"><?php echo h(BUSINESS_OWNER); ?><br><span class="fw-normal">ViPrint Publicidad</span></div></div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
