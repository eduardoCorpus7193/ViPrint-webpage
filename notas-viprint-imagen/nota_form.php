<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!is_operativo()) {
    http_response_code(403);
    echo 'Solo administración puede crear o editar notas.';
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nota = array(
    'id' => 0,
    'empresa' => 'viprint',
    'folio' => '',
    'fecha_nota' => date('Y-m-d'),
    'cliente_nombre' => '',
    'negocio' => '',
    'domicilio' => '',
    'telefono' => '',
    'origen' => 'mostrador',
    'vendedor_nombre' => '',
    'disenador_id' => '',
    'fecha_promesa' => '',
    'fecha_instalacion' => '',
    'estado' => 'recibida',
    'total' => '0.00',
    'anticipo' => '0.00',
    'saldo' => '0.00',
    'observaciones' => ''
);
$detalles = array(array('cantidad'=>1, 'tipo_item'=>'articulo', 'catalogo_id'=>'', 'descripcion'=>'', 'precio_unitario'=>'0.00', 'importe'=>'0.00'));

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM notas WHERE id = ?');
    $stmt->execute(array($id));
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); echo 'Nota no encontrada.'; exit; }
    $nota = $found;
    $stmt = $pdo->prepare('SELECT * FROM nota_detalles WHERE nota_id = ? ORDER BY id');
    $stmt->execute(array($id));
    $detalles = $stmt->fetchAll();
    if (!$detalles) $detalles = array(array('cantidad'=>1, 'tipo_item'=>'articulo', 'catalogo_id'=>'', 'descripcion'=>'', 'precio_unitario'=>'0.00', 'importe'=>'0.00'));
}

$designers = get_designers($pdo);
$stmt = $pdo->query('SELECT * FROM catalogo_items WHERE activo = 1 ORDER BY empresa, tipo, nombre');
$catalogo = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4 flex-column flex-md-row">
    <div>
        <h1 class="h3 fw-bold mb-1"><?php echo $id ? 'Editar nota' : 'Nueva nota'; ?></h1>
        <p class="text-muted mb-0">Registra el pedido, asigna diseñador y controla saldo pendiente.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?php echo h(url('notas.php')); ?>">Volver a notas</a>
</div>

<form method="post" action="<?php echo h(url('nota_guardar.php')); ?>" class="needs-validation">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo h($nota['id']); ?>">

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card mb-4">
                <div class="card-header">Datos de la nota</div>
                <div class="card-body row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label required">Empresa</label>
                        <select name="empresa" id="empresa" class="form-select" required>
                            <option value="viprint" <?php echo $nota['empresa']==='viprint'?'selected':''; ?>>ViPrint</option>
                            <option value="imagen" <?php echo $nota['empresa']==='imagen'?'selected':''; ?>>Imagen</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label required">Folio de libreta</label>
                        <input type="text" name="folio" class="form-control" value="<?php echo h($nota['folio']); ?>" required placeholder="0267">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label required">Fecha</label>
                        <input type="date" name="fecha_nota" class="form-control" value="<?php echo h($nota['fecha_nota']); ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label required">Nombre del cliente</label>
                        <input type="text" name="cliente_nombre" class="form-control" value="<?php echo h($nota['cliente_nombre']); ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Negocio</label>
                        <input type="text" name="negocio" class="form-control" value="<?php echo h($nota['negocio']); ?>">
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Domicilio</label>
                        <input type="text" name="domicilio" class="form-control" value="<?php echo h($nota['domicilio']); ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="<?php echo h($nota['telefono']); ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Origen</label>
                        <select name="origen" class="form-select">
                            <?php foreach (array('whatsapp'=>'WhatsApp','mostrador'=>'Mostrador','vendedor'=>'Vendedor externo','llamada'=>'Llamada','facebook'=>'Facebook','otro'=>'Otro') as $k=>$v): ?>
                                <option value="<?php echo h($k); ?>" <?php echo $nota['origen']===$k?'selected':''; ?>><?php echo h($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Vendedor / origen</label>
                        <input type="text" name="vendedor_nombre" class="form-control" value="<?php echo h($nota['vendedor_nombre']); ?>" placeholder="Opcional">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Diseñador asignado</label>
                        <select name="disenador_id" class="form-select">
                            <option value="">Sin asignar</option>
                            <?php foreach ($designers as $d): ?>
                                <option value="<?php echo h($d['id']); ?>" <?php echo (string)$nota['disenador_id']===(string)$d['id']?'selected':''; ?>><?php echo h($d['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <?php foreach (estado_options() as $k=>$v): ?>
                                <option value="<?php echo h($k); ?>" <?php echo $nota['estado']===$k?'selected':''; ?>><?php echo h($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Fecha prometida</label>
                        <input type="date" name="fecha_promesa" class="form-control" value="<?php echo h($nota['fecha_promesa']); ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Fecha instalación</label>
                        <input type="date" name="fecha_instalacion" class="form-control" value="<?php echo h($nota['fecha_instalacion']); ?>">
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Descripción del pedido</span>
                    <button class="btn btn-sm btn-outline-primary js-add-row">+ Agregar renglón</button>
                </div>
                <div class="card-body">
                    <div id="detalles-container">
                    <?php foreach ($detalles as $i => $d): ?>
                        <div class="detail-row">
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Promoción / artículo</label>
                                    <select name="detalles[<?php echo $i; ?>][catalogo_id]" class="form-select js-catalogo">
                                        <option value="">Escribir directamente</option>
                                        <?php foreach ($catalogo as $c): ?>
                                            <option value="<?php echo h($c['id']); ?>" data-empresa="<?php echo h($c['empresa']); ?>" data-tipo="<?php echo h($c['tipo']); ?>" data-nombre="<?php echo h($c['nombre']); ?>" data-precio="<?php echo h($c['precio']); ?>" <?php echo (string)$d['catalogo_id']===(string)$c['id']?'selected':''; ?>><?php echo h(empresa_label($c['empresa']) . ' · ' . $c['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label">Tipo</label>
                                    <select name="detalles[<?php echo $i; ?>][tipo_item]" class="form-select js-tipo">
                                        <?php foreach (array('promocion'=>'Promoción','articulo'=>'Artículo','bandera'=>'Bandera','otro'=>'Otro') as $k=>$v): ?>
                                            <option value="<?php echo h($k); ?>" <?php echo $d['tipo_item']===$k?'selected':''; ?>><?php echo h($v); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" min="0" step="0.01" name="detalles[<?php echo $i; ?>][cantidad]" class="form-control js-cantidad" value="<?php echo h($d['cantidad']); ?>">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label">P. unitario</label>
                                    <input type="number" min="0" step="0.01" name="detalles[<?php echo $i; ?>][precio_unitario]" class="form-control js-precio" value="<?php echo h($d['precio_unitario']); ?>">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label">Importe</label>
                                    <input type="number" min="0" step="0.01" name="detalles[<?php echo $i; ?>][importe]" class="form-control js-importe" value="<?php echo h($d['importe']); ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label required">Descripción</label>
                                    <textarea name="detalles[<?php echo $i; ?>][descripcion]" class="form-control js-descripcion" rows="2" required placeholder="Ej. Promo Light, lona 1x2 m, bandera unidad, etc."><?php echo h($d['descripcion']); ?></textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-sm btn-outline-danger js-remove-row">Quitar renglón</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-4 position-sticky" style="top:90px">
                <div class="card-header">Cobro y saldo</div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label required">Total</label>
                        <input type="number" min="0" step="0.01" name="total" id="total" class="form-control form-control-lg" value="<?php echo h($nota['total']); ?>" required>
                        <div class="form-text">Puedes escribir el total manualmente si el precio no sale del catálogo.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Anticipo</label>
                        <input type="number" min="0" step="0.01" name="anticipo" id="anticipo" class="form-control form-control-lg" value="<?php echo h($nota['anticipo']); ?>">
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-4 bg-light border">
                            <div class="text-muted small">Saldo estimado</div>
                            <div class="h4 text-brand fw-bold mb-0" id="saldo-preview"><?php echo h(money($nota['saldo'])); ?></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones internas</label>
                        <textarea name="observaciones" class="form-control" rows="5" placeholder="Instalación, colores, urgencia, indicaciones del cliente, etc."><?php echo h($nota['observaciones']); ?></textarea>
                    </div>
                    <div class="col-12 d-grid gap-2">
                        <button class="btn btn-primary btn-lg">Guardar nota</button>
                        <?php if ($id): ?><a class="btn btn-outline-primary" href="<?php echo h(url('nota_ver.php?id=' . $id)); ?>">Cancelar</a><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="detalle-template">
    <div class="detail-row">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <label class="form-label">Promoción / artículo</label>
                <select name="detalles[__INDEX__][catalogo_id]" class="form-select js-catalogo">
                    <option value="">Escribir directamente</option>
                    <?php foreach ($catalogo as $c): ?>
                        <option value="<?php echo h($c['id']); ?>" data-empresa="<?php echo h($c['empresa']); ?>" data-tipo="<?php echo h($c['tipo']); ?>" data-nombre="<?php echo h($c['nombre']); ?>" data-precio="<?php echo h($c['precio']); ?>"><?php echo h(empresa_label($c['empresa']) . ' · ' . $c['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Tipo</label>
                <select name="detalles[__INDEX__][tipo_item]" class="form-select js-tipo">
                    <option value="promocion">Promoción</option>
                    <option value="articulo" selected>Artículo</option>
                    <option value="bandera">Bandera</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="col-6 col-md-2"><label class="form-label">Cantidad</label><input type="number" min="0" step="0.01" name="detalles[__INDEX__][cantidad]" class="form-control js-cantidad" value="1"></div>
            <div class="col-6 col-md-2"><label class="form-label">P. unitario</label><input type="number" min="0" step="0.01" name="detalles[__INDEX__][precio_unitario]" class="form-control js-precio" value="0.00"></div>
            <div class="col-6 col-md-2"><label class="form-label">Importe</label><input type="number" min="0" step="0.01" name="detalles[__INDEX__][importe]" class="form-control js-importe" value="0.00" readonly></div>
            <div class="col-12"><label class="form-label required">Descripción</label><textarea name="detalles[__INDEX__][descripcion]" class="form-control js-descripcion" rows="2" required></textarea></div>
            <div class="col-12 text-end"><button class="btn btn-sm btn-outline-danger js-remove-row">Quitar renglón</button></div>
        </div>
    </div>
</template>
<?php include __DIR__ . '/includes/footer.php'; ?>
