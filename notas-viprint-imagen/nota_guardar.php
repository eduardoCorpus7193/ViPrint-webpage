<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!is_operativo()) {
    http_response_code(403);
    echo 'No autorizado.';
    exit;
}
verify_csrf();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$empresa = in_array($_POST['empresa'] ?? 'viprint', array('viprint','imagen'), true) ? $_POST['empresa'] : 'viprint';
$folio = trim($_POST['folio'] ?? '');
$fecha_nota = $_POST['fecha_nota'] ?? date('Y-m-d');
$cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
$negocio = trim($_POST['negocio'] ?? '');
$domicilio = trim($_POST['domicilio'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$origen = $_POST['origen'] ?? 'mostrador';
if (!in_array($origen, array('whatsapp','mostrador','vendedor','llamada','facebook','otro'), true)) $origen = 'mostrador';
$vendedor_nombre = trim($_POST['vendedor_nombre'] ?? '');
$disenador_id = ($_POST['disenador_id'] ?? '') !== '' ? (int)$_POST['disenador_id'] : null;
$fecha_promesa = ($_POST['fecha_promesa'] ?? '') ?: null;
$fecha_instalacion = ($_POST['fecha_instalacion'] ?? '') ?: null;
$estado = $_POST['estado'] ?? 'recibida';
if (!array_key_exists($estado, estado_options())) $estado = 'recibida';
$total = max(0, (float)($_POST['total'] ?? 0));
$anticipo = max(0, (float)($_POST['anticipo'] ?? 0));
$observaciones = trim($_POST['observaciones'] ?? '');
$saldo = max(0, $total - $anticipo);

if ($folio === '' || $cliente_nombre === '') {
    $_SESSION['flash'] = array('type'=>'danger', 'message'=>'Folio y nombre del cliente son obligatorios.');
    redirect($id ? 'nota_form.php?id=' . $id : 'nota_form.php');
}

try {
    $pdo->beginTransaction();

    $estadoAnterior = null;
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT estado FROM notas WHERE id = ?');
        $stmt->execute(array($id));
        $estadoAnterior = $stmt->fetchColumn();
        if ($estadoAnterior === false) throw new Exception('La nota no existe.');

        $stmt = $pdo->prepare('UPDATE notas SET empresa=?, folio=?, fecha_nota=?, cliente_nombre=?, negocio=?, domicilio=?, telefono=?, origen=?, vendedor_nombre=?, disenador_id=?, fecha_promesa=?, fecha_instalacion=?, estado=?, total=?, anticipo=?, saldo=?, observaciones=?, actualizado_por=? WHERE id=?');
        $stmt->execute(array($empresa, $folio, $fecha_nota, $cliente_nombre, $negocio, $domicilio, $telefono, $origen, $vendedor_nombre, $disenador_id, $fecha_promesa, $fecha_instalacion, $estado, $total, $anticipo, $saldo, $observaciones, current_user()['id'], $id));

        $del = $pdo->prepare('DELETE FROM nota_detalles WHERE nota_id = ?');
        $del->execute(array($id));
    } else {
        $stmt = $pdo->prepare('INSERT INTO notas (empresa, folio, fecha_nota, cliente_nombre, negocio, domicilio, telefono, origen, vendedor_nombre, disenador_id, fecha_promesa, fecha_instalacion, estado, total, anticipo, saldo, observaciones, creado_por, actualizado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array($empresa, $folio, $fecha_nota, $cliente_nombre, $negocio, $domicilio, $telefono, $origen, $vendedor_nombre, $disenador_id, $fecha_promesa, $fecha_instalacion, $estado, $total, $anticipo, $saldo, $observaciones, current_user()['id'], current_user()['id']));
        $id = (int)$pdo->lastInsertId();
        $estadoAnterior = null;
    }

    $detalles = $_POST['detalles'] ?? array();
    $ins = $pdo->prepare('INSERT INTO nota_detalles (nota_id, cantidad, tipo_item, catalogo_id, descripcion, precio_unitario, importe) VALUES (?,?,?,?,?,?,?)');
    foreach ($detalles as $d) {
        $descripcion = trim($d['descripcion'] ?? '');
        if ($descripcion === '') continue;
        $cantidad = max(0, (float)($d['cantidad'] ?? 1));
        $precio = max(0, (float)($d['precio_unitario'] ?? 0));
        $importe = max(0, (float)($d['importe'] ?? ($cantidad * $precio)));
        $tipo = $d['tipo_item'] ?? 'articulo';
        if (!in_array($tipo, array('promocion','articulo','bandera','otro'), true)) $tipo = 'articulo';
        $catalogo_id = ($d['catalogo_id'] ?? '') !== '' ? (int)$d['catalogo_id'] : null;
        $ins->execute(array($id, $cantidad, $tipo, $catalogo_id, $descripcion, $precio, $importe));
    }

    if ($estadoAnterior !== $estado) {
        add_estado_historial($pdo, $id, $estadoAnterior, $estado, 'Estado actualizado desde formulario de nota.');
    }

    recalculate_saldo($pdo, $id);
    $pdo->commit();
    $_SESSION['flash'] = array('type'=>'success', 'message'=>'Nota guardada correctamente.');
    redirect('nota_ver.php?id=' . $id);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash'] = array('type'=>'danger', 'message'=>'No se pudo guardar. Revisa si el folio ya existe para esa empresa. Detalle: ' . $e->getMessage());
    redirect($id ? 'nota_form.php?id=' . $id : 'nota_form.php');
}
