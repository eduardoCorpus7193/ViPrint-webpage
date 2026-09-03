<?php
require_once __DIR__ . '/includes/bootstrap.php';

function public_label($value) {
    $map = array(
        'recibida' => 'Recibida',
        'pendiente_contacto' => 'Pendiente de contacto',
        'contactado' => 'Cliente contactado',
        'en_diseno' => 'En diseño',
        'en_aprobacion' => 'En aprobación del cliente',
        'aprobado' => 'Diseño aprobado',
        'para_imprimir' => 'Para impresión',
        'impresa' => 'Impresa',
        'sublimada' => 'Sublimada',
        'programada' => 'Programada',
        'en_instalacion' => 'En instalación',
        'instalada' => 'Instalada',
        'entregada' => 'Entregada',
        'pendiente' => 'Pendiente',
        'parcial' => 'Pago parcial',
        'liquidada' => 'Liquidada',
        'devolucion' => 'Devolución',
        'cancelada' => 'Cancelada'
    );
    return $map[$value] ?? ucfirst(str_replace('_', ' ', (string)$value));
}

function public_estado_general($n) {
    if (($n['estado_entrega'] ?? '') === 'cancelada' || ($n['estado_pago'] ?? '') === 'cancelada') return 'Pedido cancelado';
    if (($n['estado_entrega'] ?? '') === 'entregada') return 'Pedido entregado';
    if (($n['estado_instalacion'] ?? '') === 'instalada') return 'Instalación realizada';
    if (($n['estado_instalacion'] ?? '') === 'en_instalacion') return 'En instalación';
    if (($n['estado_instalacion'] ?? '') === 'programada') return 'Instalación programada';
    if (in_array(($n['estado_produccion'] ?? ''), array('impresa','sublimada'), true)) return 'En producción avanzada';
    if (($n['estado_produccion'] ?? '') === 'para_imprimir') return 'Para impresión';
    if (($n['estado_diseno'] ?? '') === 'aprobado') return 'Diseño aprobado';
    if (($n['estado_diseno'] ?? '') === 'en_aprobacion') return 'En aprobación de diseño';
    if (($n['estado_diseno'] ?? '') === 'en_diseno') return 'En diseño';
    return 'Pedido recibido';
}

function metodo_label_public($p) {
    $map = array('efectivo'=>'Efectivo','transferencia'=>'Transferencia','tarjeta'=>'Tarjeta','otro'=>'Otro');
    if (($p['forma_pago'] ?? '') === 'otro' && !empty($p['forma_pago_otro'])) return $p['forma_pago_otro'];
    return $map[$p['forma_pago'] ?? ''] ?? ($p['forma_pago'] ?? '');
}

$codigo = strtoupper(trim((string)($_GET['codigo'] ?? $_GET['c'] ?? $_POST['codigo'] ?? '')));
$folio = strtoupper(trim((string)($_GET['folio'] ?? $_POST['folio'] ?? '')));
$nota = null;
$partidas = array();
$pagos = array();
$error = '';

$hasPublicCode = column_exists('v2_notas', 'public_code');
$hasMostrarCliente = column_exists('v2_notas', 'mostrar_cliente');

if (!$hasPublicCode) {
    $error = 'La consulta pública todavía no está instalada. Contacta a ViPrint.';
} elseif ($codigo !== '' && $folio !== '') {
    $sql = "SELECT n.*, e.nombre empresa, e.clave empresa_clave
            FROM v2_notas n
            JOIN v2_empresas e ON e.id = n.empresa_id
            WHERE UPPER(n.folio) = ? AND UPPER(n.public_code) = ?";
    $params = array($folio, $codigo);
    if ($hasMostrarCliente) {
        $sql .= " AND n.mostrar_cliente = 1";
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $nota = $stmt->fetch();
    if (!$nota) {
        $error = 'No encontramos una nota activa con ese folio y código. Revisa los datos impresos en tu ticket.';
    }
} elseif ($codigo !== '' || $folio !== '') {
    $error = 'Escribe el folio y el código que aparecen en tu ticket.';
}

if ($nota) {
    $stmt = db()->prepare('SELECT descripcion, cantidad, precio_unitario, total FROM v2_nota_partidas WHERE nota_id = ? ORDER BY id');
    $stmt->execute(array((int)$nota['id']));
    $partidas = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT fecha_pago, concepto, monto, forma_pago, forma_pago_otro FROM v2_pagos WHERE nota_id = ? ORDER BY fecha_pago, id');
    $stmt->execute(array((int)$nota['id']));
    $pagos = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consulta de pedido | ViPrint</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{--vp:#A92624;--bg:#F8F6F8;--ink:#171717}
    body{background:var(--bg);color:var(--ink)}
    .brand-bar{background:var(--vp);height:8px}
    .logo{max-height:70px;max-width:220px;object-fit:contain}
    .status-card{border-left:6px solid var(--vp)}
    .money-card{background:#fff;border:1px solid #eee;border-radius:16px;padding:16px}
    .badge-vp{background:var(--vp);color:#fff}
    .table td,.table th{vertical-align:middle}
    .btn-vp{background:var(--vp);border-color:var(--vp);color:#fff}
    .btn-vp:hover{background:#8f1f1d;border-color:#8f1f1d;color:#fff}
  </style>
</head>
<body>
<div class="brand-bar"></div>
<div class="container py-4 py-md-5" style="max-width:980px">
  <div class="text-center mb-4">
    <img src="<?= h(logo_src()) ?>" class="logo mb-2" alt="ViPrint Publicidad">
    <h1 class="h3 mb-1">Consulta el estado de tu pedido</h1>
    <p class="text-muted mb-0">Revisa tu nota, pagos registrados y saldo pendiente.</p>
  </div>

  <?php if (!$nota): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-body p-4">
        <?php if ($error): ?><div class="alert alert-warning"><?= h($error) ?></div><?php endif; ?>
        <form method="get" class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Folio de nota</label>
            <input class="form-control text-uppercase" name="folio" value="<?= h($folio) ?>" placeholder="Ej. VP-000123" required>
          </div>
          <div class="col-md-5">
            <label class="form-label">Código del ticket</label>
            <input class="form-control" name="codigo" value="<?= h($codigo) ?>" placeholder="Ej. 4827" inputmode="numeric" required>
          </div>
          <div class="col-md-2 d-grid align-items-end">
            <button class="btn btn-vp">Consultar</button>
          </div>
        </form>
        <p class="small text-muted mt-3 mb-0">El folio y el código aparecen impresos en tu ticket.</p>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($nota): ?>
    <div class="card shadow-sm status-card mb-4">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3">
          <div>
            <div class="text-muted small">Folio</div>
            <h2 class="h4 mb-1"><?= h($nota['folio']) ?></h2>
            <div><?= h($nota['cliente_nombre']) ?><?= !empty($nota['negocio']) ? ' · '.h($nota['negocio']) : '' ?></div>
          </div>
          <div class="text-md-end">
            <div class="text-muted small">Estado general</div>
            <span class="badge badge-vp fs-6 px-3 py-2"><?= h(public_estado_general($nota)) ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4"><div class="money-card"><div class="text-muted small">Total</div><div class="h4 mb-0"><?= money($nota['total']) ?></div></div></div>
      <div class="col-md-4"><div class="money-card"><div class="text-muted small">Pagado</div><div class="h4 mb-0"><?= money($nota['pagado']) ?></div></div></div>
      <div class="col-md-4"><div class="money-card"><div class="text-muted small">Saldo pendiente</div><div class="h4 mb-0"><?= money($nota['saldo']) ?></div></div></div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-white"><strong>Estado del pedido</strong></div>
          <div class="card-body">
            <div class="d-flex justify-content-between border-bottom py-2"><span>Diseño</span><strong><?= h(public_label($nota['estado_diseno'])) ?></strong></div>
            <div class="d-flex justify-content-between border-bottom py-2"><span>Aprobación impresión</span><strong><?= h(public_label($nota['estado_aprobacion_impresion'])) ?></strong></div>
            <div class="d-flex justify-content-between border-bottom py-2"><span>Producción</span><strong><?= h(public_label($nota['estado_produccion'])) ?></strong></div>
            <div class="d-flex justify-content-between border-bottom py-2"><span>Instalación</span><strong><?= h(public_label($nota['estado_instalacion'])) ?></strong></div>
            <div class="d-flex justify-content-between py-2"><span>Entrega</span><strong><?= h(public_label($nota['estado_entrega'])) ?></strong></div>
            <?php if(!empty($nota['fecha_promesa'])): ?><div class="alert alert-light border mt-3 mb-0"><strong>Fecha prometida:</strong> <?= date_mx($nota['fecha_promesa']) ?></div><?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-white"><strong>Artículos / promoción</strong></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table mb-0">
                <thead><tr><th>Descripción</th><th class="text-end">Cant.</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                <?php foreach($partidas as $pa): ?>
                  <tr>
                    <td><?= nl2br(h($pa['descripcion'])) ?></td>
                    <td class="text-end"><?= h($pa['cantidad']) ?></td>
                    <td class="text-end"><?= money($pa['total']) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if(!$partidas): ?><tr><td colspan="3" class="text-muted">Sin artículos capturados.</td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white"><strong>Pagos registrados</strong></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead><tr><th>Fecha</th><th>Concepto</th><th>Método</th><th class="text-end">Monto</th></tr></thead>
            <tbody>
            <?php foreach($pagos as $pa): ?>
              <tr>
                <td><?= date_mx($pa['fecha_pago']) ?></td>
                <td><?= h(public_label($pa['concepto'])) ?></td>
                <td><?= h(metodo_label_public($pa)) ?></td>
                <td class="text-end"><?= money($pa['monto']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if(!$pagos): ?><tr><td colspan="4" class="text-muted">Sin pagos registrados.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="alert alert-light border small mb-0">
      La información se actualiza conforme ViPrint cambia el estado de la nota o registra nuevos pagos. Si detectas un dato incorrecto, comunícate directamente con ViPrint.
    </div>
  <?php endif; ?>
</div>
</body>
</html>
