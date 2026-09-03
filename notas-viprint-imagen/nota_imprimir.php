<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT n.*, u.nombre AS disenador_nombre FROM notas n LEFT JOIN usuarios u ON u.id = n.disenador_id WHERE n.id = ?');
$stmt->execute(array($id));
$nota = $stmt->fetch();
if (!$nota || !can_view_note($nota)) { http_response_code(404); echo 'Nota no encontrada.'; exit; }
$stmt = $pdo->prepare('SELECT * FROM nota_detalles WHERE nota_id = ? ORDER BY id');
$stmt->execute(array($id));
$detalles = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota <?php echo h($nota['folio']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo h(url('assets/css/styles.css')); ?>">
    <style>
        body{background:white}.print-box{max-width:900px;margin:20px auto;border:2px solid #A92624;border-radius:18px;padding:24px}.line{height:4px;background:#A92624;margin:16px 0}.signature{border-top:1px solid #222;text-align:center;padding-top:8px;margin-top:48px}
    </style>
</head>
<body>
<div class="no-print text-center my-3"><button onclick="window.print()" class="btn btn-primary">Imprimir</button></div>
<div class="print-box">
    <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
            <img src="https://www.viprint.com.mx/img/logo.png" alt="ViPrint" style="max-width:170px">
            <div class="fw-bold mt-2">Control interno de nota</div>
            <div>Empresa: <?php echo h(empresa_label($nota['empresa'])); ?></div>
        </div>
        <div class="text-end">
            <div class="text-muted">FOLIO</div>
            <div class="h3 fw-bold text-brand">#<?php echo h($nota['folio']); ?></div>
            <div><?php echo h(date_mx($nota['fecha_nota'])); ?></div>
        </div>
    </div>
    <div class="line"></div>
    <h1 class="h4 text-center text-brand fw-bold">NOTA DE VENTA / PEDIDO</h1>
    <div class="row g-2 mt-3">
        <div class="col-12 col-md-6"><strong>Cliente:</strong> <?php echo h($nota['cliente_nombre']); ?></div>
        <div class="col-12 col-md-6"><strong>Negocio:</strong> <?php echo h($nota['negocio']); ?></div>
        <div class="col-12 col-md-8"><strong>Domicilio:</strong> <?php echo h($nota['domicilio']); ?></div>
        <div class="col-12 col-md-4"><strong>Teléfono:</strong> <?php echo h($nota['telefono']); ?></div>
        <div class="col-12 col-md-4"><strong>Diseñador:</strong> <?php echo h($nota['disenador_nombre'] ?: 'Sin asignar'); ?></div>
        <div class="col-12 col-md-4"><strong>Estado:</strong> <?php echo h(estado_label($nota['estado'])); ?></div>
        <div class="col-12 col-md-4"><strong>Fecha prometida:</strong> <?php echo h(date_mx($nota['fecha_promesa'])); ?></div>
    </div>
    <table class="table table-bordered mt-4">
        <thead><tr><th>Cant.</th><th>Descripción</th><th>P. unit.</th><th>Importe</th></tr></thead>
        <tbody>
        <?php foreach ($detalles as $d): ?>
            <tr><td><?php echo h($d['cantidad']); ?></td><td><?php echo nl2br(h($d['descripcion'])); ?></td><td><?php echo h(money($d['precio_unitario'])); ?></td><td><?php echo h(money($d['importe'])); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="row justify-content-end">
        <div class="col-12 col-md-5">
            <div class="d-flex justify-content-between"><span>Total</span><strong><?php echo h(money($nota['total'])); ?></strong></div>
            <div class="d-flex justify-content-between"><span>Anticipo</span><strong><?php echo h(money($nota['anticipo'])); ?></strong></div>
            <div class="d-flex justify-content-between h5 border-top pt-2"><span>Saldo</span><strong><?php echo h(money($nota['saldo'])); ?></strong></div>
        </div>
    </div>
    <?php if ($nota['observaciones']): ?><div class="mt-3"><strong>Observaciones:</strong><br><?php echo nl2br(h($nota['observaciones'])); ?></div><?php endif; ?>
    <div class="row mt-5">
        <div class="col-6"><div class="signature">Firma cliente</div></div>
        <div class="col-6"><div class="signature">Firma ViPrint / Imagen</div></div>
    </div>
</div>
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
</body>
</html>
