<?php
require_once __DIR__ . '/../notas-viprint-imagen-v2/includes/bootstrap.php';
require_once __DIR__ . '/../notas-viprint-imagen-v2/includes/facturacion_helpers.php';

function factura_public_lookup_note($folio, $codigo) {
    if (!column_exists('v2_notas','public_code')) return null;
    $hasMostrar = column_exists('v2_notas','mostrar_cliente');
    $sql = "SELECT n.*, e.nombre empresa, e.clave empresa_clave
            FROM v2_notas n
            JOIN v2_empresas e ON e.id = n.empresa_id
            WHERE UPPER(n.folio) = ? AND UPPER(n.public_code) = ?";
    if ($hasMostrar) $sql .= " AND n.mostrar_cliente = 1";
    $stmt = db()->prepare($sql);
    $stmt->execute(array(strtoupper(trim($folio)), strtoupper(trim($codigo))));
    $n = $stmt->fetch();
    return $n ?: null;
}

function factura_clean($v) {
    return trim((string)$v);
}

$folio = factura_clean($_GET['folio'] ?? $_POST['folio'] ?? '');
$codigo = factura_clean($_GET['codigo'] ?? $_POST['codigo'] ?? '');
$nota = null;
$error = '';
$success = '';
$already = null;

if ($folio !== '' && $codigo !== '') {
    $nota = factura_public_lookup_note($folio, $codigo);
    if (!$nota) {
        $error = 'No encontramos una nota con ese folio y código. Revisa los datos impresos en tu ticket.';
    } elseif ((int)($nota['requiere_factura'] ?? 0) !== 1) {
        $error = 'Esta nota no está marcada para factura. Comunícate con ViPrint para que activen la solicitud.';
        $nota = null;
    } else {
        $stmt = db()->prepare('SELECT * FROM v2_factura_solicitudes WHERE nota_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute(array((int)$nota['id']));
        $already = $stmt->fetch();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'enviar') {
    try {
        if (!$nota) throw new Exception('Primero consulta una nota válida.');
        if (factura_is_expired($nota) && !$already) {
            throw new Exception('El tiempo para enviar datos de facturación ya venció. Comunícate directamente con ViPrint.');
        }
        $rfc = strtoupper(factura_clean($_POST['rfc'] ?? ''));
        $razon = factura_clean($_POST['razon_social'] ?? '');
        $cp = factura_clean($_POST['codigo_postal_fiscal'] ?? '');
        $regimen = factura_clean($_POST['regimen_fiscal'] ?? '');
        $uso = factura_clean($_POST['uso_cfdi'] ?? '');
        $correo = factura_clean($_POST['correo'] ?? '');
        $telefono = factura_clean($_POST['telefono'] ?? '');
        $comentarios = factura_clean($_POST['comentarios_cliente'] ?? '');
        if ($rfc === '' || $razon === '' || $regimen === '' || $uso === '' || $correo === '') {
            throw new Exception('Faltan datos obligatorios.');
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El correo no parece válido.');
        }
        $constancia = factura_safe_upload('constancia', 'constancias', array('pdf','jpg','jpeg','png','webp'), 'constancia_nota_' . (int)$nota['id']);
        if ($constancia === '') throw new Exception('Debes subir tu Constancia de Situación Fiscal.');

        $stmt = db()->prepare("INSERT INTO v2_factura_solicitudes
            (nota_id, folio, public_code, rfc, razon_social, codigo_postal_fiscal, regimen_fiscal, uso_cfdi, correo, telefono, constancia_archivo, comentarios_cliente, estado, fecha_solicitud, fecha_actualizacion)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'pendiente', NOW(), NOW())");
        $stmt->execute(array((int)$nota['id'], $nota['folio'], $nota['public_code'], $rfc, $razon, $cp, $regimen, $uso, $correo, $telefono, $constancia, $comentarios));
        $success = 'Recibimos tus datos de facturación. ViPrint los revisará y te contactará si falta algo.';
        $stmt = db()->prepare('SELECT * FROM v2_factura_solicitudes WHERE id = ?');
        $stmt->execute(array((int)db()->lastInsertId()));
        $already = $stmt->fetch();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Solicitar factura | ViPrint</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{--vp:#A92624;--bg:#F8F6F8;--ink:#171717}
    body{background:var(--bg);color:var(--ink)}
    .brand-bar{background:var(--vp);height:8px}
    .logo{max-height:74px;max-width:230px;object-fit:contain}
    .btn-vp{background:var(--vp);border-color:var(--vp);color:#fff}
    .btn-vp:hover{background:#8f1f1d;border-color:#8f1f1d;color:#fff}
    .card{border-radius:18px}
    .required:after{content:' *';color:var(--vp);font-weight:bold}
  </style>
</head>
<body>
<div class="brand-bar"></div>
<div class="container py-4 py-md-5" style="max-width:980px">
  <div class="text-center mb-4">
    <img src="<?= h(logo_src()) ?>" class="logo mb-2" alt="ViPrint Publicidad">
    <h1 class="h3 mb-1">Solicita tu factura</h1>
    <p class="text-muted mb-0">Sube tus datos fiscales usando el folio y código de tu ticket.</p>
  </div>

  <?php if($error): ?><div class="alert alert-warning"><?= h($error) ?></div><?php endif; ?>
  <?php if($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

  <div class="card shadow-sm mb-4">
    <div class="card-body p-4">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-md-5"><label class="form-label required">Folio de nota</label><input class="form-control text-uppercase" name="folio" value="<?= h($folio) ?>" placeholder="Ej. VP-000123" required></div>
        <div class="col-md-5"><label class="form-label required">Código del ticket</label><input class="form-control" name="codigo" value="<?= h($codigo) ?>" placeholder="Ej. 4827" required></div>
        <div class="col-md-2 d-grid"><button class="btn btn-vp">Consultar</button></div>
      </form>
    </div>
  </div>

  <?php if($nota): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3">
          <div><div class="text-muted small">Nota</div><h2 class="h4 mb-1"><?= h($nota['folio']) ?></h2><div><?= h($nota['cliente_nombre']) ?></div></div>
          <div class="text-md-end"><div class="text-muted small">Total</div><div class="h4 mb-1"><?= money($nota['total']) ?></div><div class="small text-muted">Límite para datos: <?= h(factura_limite_text($nota)) ?></div></div>
        </div>
      </div>
    </div>

    <?php if($already): ?>
      <div class="alert alert-info">
        Ya existe una solicitud de factura para esta nota. Estado actual: <strong><?= h(factura_status_label($already['estado'])) ?></strong>.
        <?php if($already['estado']==='datos_incorrectos'): ?>Puedes enviar una nueva solicitud corrigiendo los datos.<?php else: ?>Si necesitas corregir algo, comunícate con ViPrint.<?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if(!factura_is_expired($nota) || ($already && $already['estado']==='datos_incorrectos')): ?>
    <div class="card shadow-sm">
      <div class="card-header bg-white"><strong>Datos fiscales</strong></div>
      <div class="card-body p-4">
        <form method="post" enctype="multipart/form-data" class="row g-3">
          <input type="hidden" name="accion" value="enviar">
          <input type="hidden" name="folio" value="<?= h($folio) ?>">
          <input type="hidden" name="codigo" value="<?= h($codigo) ?>">
          <div class="col-md-4"><label class="form-label required">RFC</label><input class="form-control text-uppercase" name="rfc" required></div>
          <div class="col-md-8"><label class="form-label required">Nombre o razón social</label><input class="form-control" name="razon_social" required></div>
          <div class="col-md-4"><label class="form-label">Código postal fiscal</label><input class="form-control" name="codigo_postal_fiscal" inputmode="numeric" placeholder="Opcional si viene en la constancia"></div>
          <div class="col-md-4"><label class="form-label required">Régimen fiscal</label><input class="form-control" name="regimen_fiscal" placeholder="Ej. Régimen Simplificado de Confianza" required></div>
          <div class="col-md-4"><label class="form-label required">Uso de CFDI</label><input class="form-control" name="uso_cfdi" placeholder="Ej. Gastos en general" required></div>
          <div class="col-md-6"><label class="form-label required">Correo para enviar factura</label><input type="email" class="form-control" name="correo" required></div>
          <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono"></div>
          <div class="col-12"><label class="form-label required">Constancia de Situación Fiscal</label><input type="file" class="form-control" name="constancia" accept="application/pdf,image/jpeg,image/png,image/webp,.pdf,.jpg,.jpeg,.png,.webp" required><div class="form-text">PDF o imagen. Máximo 8 MB.</div></div>
          <div class="col-12"><label class="form-label">Comentarios</label><textarea class="form-control" name="comentarios_cliente" rows="3" placeholder="Alguna indicación adicional para la factura"></textarea></div>
          <div class="col-12"><div class="alert alert-light border small mb-0">La información debe enviarse dentro de las 72 horas posteriores a que se marcó la nota como factura.</div></div>
          <div class="col-12 d-grid"><button class="btn btn-vp btn-lg">Enviar datos de facturación</button></div>
        </form>
      </div>
    </div>
    <?php else: ?>
      <div class="alert alert-danger">El tiempo para enviar datos de facturación ya venció. Comunícate directamente con ViPrint para revisar tu caso.</div>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
