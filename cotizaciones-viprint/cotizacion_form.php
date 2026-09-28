<?php
require_once __DIR__ . '/includes/bootstrap.php';
$current = 'nueva';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quote = null;
$items = array();
if ($id > 0) {
    $quote = fetch_quote($pdo, $id);
    if (!$quote) { http_response_code(404); exit('Cotización no encontrada.'); }
    $items = fetch_quote_items($pdo, $id);
}
$promos = get_promociones($pdo, true);
if (!$items) {
    $items = array(array('tipo'=>'articulo','promocion_id'=>null,'descripcion'=>'','cantidad'=>'1.00','precio_unitario'=>'0.00','importe'=>'0.00'));
}
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1"><?php echo $quote ? 'Editar cotización' : 'Nueva cotización'; ?></h1>
        <div class="small-muted">Captura promociones, artículos libres, fecha y condiciones comerciales.</div>
    </div>
    <a href="<?php echo h(BASE_URL); ?>/" class="btn btn-outline-secondary">Volver</a>
</div>

<form method="post" action="<?php echo h(BASE_URL); ?>/guardar_cotizacion.php" id="quoteForm">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo $quote ? (int)$quote['id'] : 0; ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-vip mb-4">
                <div class="card-header card-header-vip">Datos del cliente</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Fecha de cotización</label>
                            <input type="date" class="form-control" name="fecha" required value="<?php echo h($quote ? $quote['fecha'] : date('Y-m-d')); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Validez en días</label>
                            <input type="number" class="form-control" name="validez_dias" min="1" value="<?php echo h($quote ? $quote['validez_dias'] : 7); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estatus</label>
                            <select class="form-select" name="estatus">
                                <?php foreach (array('borrador','enviada','aprobada','rechazada','cancelada') as $st): ?>
                                <option value="<?php echo h($st); ?>" <?php echo ($quote && $quote['estatus']===$st)?'selected':''; ?>><?php echo h(quote_status_label($st)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre del cliente *</label>
                            <input type="text" class="form-control" name="cliente_nombre" required value="<?php echo h($quote ? $quote['cliente_nombre'] : ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Negocio</label>
                            <input type="text" class="form-control" name="cliente_negocio" value="<?php echo h($quote ? $quote['cliente_negocio'] : ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="cliente_telefono" value="<?php echo h($quote ? $quote['cliente_telefono'] : ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" name="cliente_email" value="<?php echo h($quote ? $quote['cliente_email'] : ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Domicilio</label>
                            <input type="text" class="form-control" name="cliente_domicilio" value="<?php echo h($quote ? $quote['cliente_domicilio'] : ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-vip mb-4">
                <div class="card-header card-header-vip d-flex justify-content-between align-items-center">
                    <span>Partidas de la cotización</span>
                    <button type="button" id="addItemBtn" class="btn btn-light btn-sm fw-bold">+ Agregar partida</button>
                </div>
                <div class="card-body">
                    <div id="itemsWrap">
                        <?php foreach ($items as $item): ?>
                        <div class="quote-item item-card">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Usar promoción</label>
                                    <select class="form-select promo-select">
                                        <option value="">Artículo libre / escribir manualmente</option>
                                        <?php foreach ($promos as $promo): ?>
                                        <option value="<?php echo (int)$promo['id']; ?>" data-price="<?php echo h($promo['precio']); ?>" data-desc="<?php echo h($promo['nombre'] . ($promo['descripcion'] ? ' - ' . $promo['descripcion'] : '')); ?>" <?php echo ((int)$item['promocion_id']===(int)$promo['id'])?'selected':''; ?>><?php echo h($promo['nombre']); ?> — <?php echo h(money($promo['precio'])); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="tipo[]" value="<?php echo h($item['tipo']); ?>">
                                    <input type="hidden" name="promocion_id[]" value="<?php echo h($item['promocion_id']); ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Descripción *</label>
                                    <textarea class="form-control" name="descripcion[]" rows="2" required placeholder="Ej. Promo Light, lona 3x2 m, 2 banderas, instalación, vinil, etc."><?php echo h($item['descripcion']); ?></textarea>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="cantidad[]" value="<?php echo h($item['cantidad']); ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Precio unitario</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="precio_unitario[]" value="<?php echo h($item['precio_unitario']); ?>">
                                </div>
                                <div class="col-8 col-md-3">
                                    <label class="form-label">Importe</label>
                                    <div class="form-control fw-bold importe-view">$0.00</div>
                                    <input type="hidden" name="importe[]" value="<?php echo h($item['importe']); ?>">
                                </div>
                                <div class="col-4 col-md-3 d-grid">
                                    <button type="button" class="btn btn-outline-danger remove-item">Quitar</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card card-vip mb-4">
                <div class="card-header card-header-vip">Condiciones y observaciones</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Condiciones comerciales</label>
                        <textarea class="form-control" name="condiciones" rows="6"><?php echo h($quote ? $quote['condiciones'] : default_terms()); ?></textarea>
                    </div>
                    <div>
                        <label class="form-label">Observaciones internas o visibles</label>
                        <textarea class="form-control" name="observaciones" rows="3" placeholder="Ej. Incluye instalación en Aguascalientes, diseño incluido, entrega estimada, etc."><?php echo h($quote ? $quote['observaciones'] : ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-vip sticky-lg-top" style="top:18px;">
                <div class="card-header card-header-vip">Resumen</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="aplicar_iva" value="1" id="aplicarIva" <?php echo ($quote && $quote['aplicar_iva'])?'checked':''; ?>>
                        <label class="form-check-label fw-bold" for="aplicarIva">Aplicar IVA</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Porcentaje IVA</label>
                        <input type="number" step="0.01" class="form-control" name="porcentaje_iva" value="<?php echo h($quote ? $quote['porcentaje_iva'] : '16.00'); ?>">
                    </div>
                    <div class="total-box mb-3">
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong id="subtotalView">$0.00</strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>IVA</span><strong id="ivaView">$0.00</strong></div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center"><span class="fw-bold">Total</span><span class="total-amount" id="totalView">$0.00</span></div>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-vip btn-lg" type="submit">Guardar cotización</button>
                        <?php if ($quote): ?>
                        <a class="btn btn-outline-vip" href="<?php echo h(BASE_URL); ?>/ver_cotizacion.php?id=<?php echo (int)$quote['id']; ?>">Ver formato</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="itemTemplate">
    <div class="quote-item item-card">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Usar promoción</label>
                <select class="form-select promo-select">
                    <option value="">Artículo libre / escribir manualmente</option>
                    <?php foreach ($promos as $promo): ?>
                    <option value="<?php echo (int)$promo['id']; ?>" data-price="<?php echo h($promo['precio']); ?>" data-desc="<?php echo h($promo['nombre'] . ($promo['descripcion'] ? ' - ' . $promo['descripcion'] : '')); ?>"><?php echo h($promo['nombre']); ?> — <?php echo h(money($promo['precio'])); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="tipo[]" value="articulo">
                <input type="hidden" name="promocion_id[]" value="">
            </div>
            <div class="col-md-8">
                <label class="form-label">Descripción *</label>
                <textarea class="form-control" name="descripcion[]" rows="2" required></textarea>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Cantidad</label>
                <input type="number" step="0.01" min="0" class="form-control" name="cantidad[]" value="1.00">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Precio unitario</label>
                <input type="number" step="0.01" min="0" class="form-control" name="precio_unitario[]" value="0.00">
            </div>
            <div class="col-8 col-md-3">
                <label class="form-label">Importe</label>
                <div class="form-control fw-bold importe-view">$0.00</div>
                <input type="hidden" name="importe[]" value="0.00">
            </div>
            <div class="col-4 col-md-3 d-grid">
                <button type="button" class="btn btn-outline-danger remove-item">Quitar</button>
            </div>
        </div>
    </div>
</template>
<?php require __DIR__ . '/includes/footer.php'; ?>
