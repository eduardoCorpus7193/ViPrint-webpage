<?php
require_once __DIR__ . '/includes/bootstrap.php';
validate_csrf();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$fecha = isset($_POST['fecha']) && $_POST['fecha'] !== '' ? $_POST['fecha'] : date('Y-m-d');
$clienteNombre = trim(isset($_POST['cliente_nombre']) ? $_POST['cliente_nombre'] : '');
if ($clienteNombre === '') {
    flash_set('danger', 'El nombre del cliente es obligatorio.');
    redirect_to('/cotizacion_form.php' . ($id ? '?id=' . $id : ''));
}

$descripciones = isset($_POST['descripcion']) ? $_POST['descripcion'] : array();
$cantidades = isset($_POST['cantidad']) ? $_POST['cantidad'] : array();
$precios = isset($_POST['precio_unitario']) ? $_POST['precio_unitario'] : array();
$tipos = isset($_POST['tipo']) ? $_POST['tipo'] : array();
$promocionIds = isset($_POST['promocion_id']) ? $_POST['promocion_id'] : array();

$items = array();
$subtotal = 0.0;
$count = count($descripciones);
for ($i = 0; $i < $count; $i++) {
    $desc = trim($descripciones[$i]);
    if ($desc === '') continue;
    $qty = max(0, (float)$cantidades[$i]);
    $price = max(0, (float)$precios[$i]);
    $importe = $qty * $price;
    $tipo = (isset($tipos[$i]) && $tipos[$i] === 'promocion') ? 'promocion' : 'articulo';
    $promoId = isset($promocionIds[$i]) && $promocionIds[$i] !== '' ? (int)$promocionIds[$i] : null;
    if ($promoId) $tipo = 'promocion';
    $items[] = array(
        'orden' => count($items) + 1,
        'tipo' => $tipo,
        'promocion_id' => $promoId,
        'descripcion' => $desc,
        'cantidad' => $qty,
        'precio_unitario' => $price,
        'importe' => $importe,
    );
    $subtotal += $importe;
}

if (!$items) {
    flash_set('danger', 'Agrega al menos una partida a la cotización.');
    redirect_to('/cotizacion_form.php' . ($id ? '?id=' . $id : ''));
}

$aplicarIva = isset($_POST['aplicar_iva']) ? 1 : 0;
$porcentajeIva = isset($_POST['porcentaje_iva']) ? (float)$_POST['porcentaje_iva'] : 16.00;
$iva = $aplicarIva ? round($subtotal * ($porcentajeIva / 100), 2) : 0.00;
$total = round($subtotal + $iva, 2);
$estatusValidos = array('borrador','enviada','aprobada','rechazada','cancelada');
$estatus = in_array(isset($_POST['estatus']) ? $_POST['estatus'] : 'borrador', $estatusValidos, true) ? $_POST['estatus'] : 'borrador';

try {
    $pdo->beginTransaction();
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE cotizaciones SET fecha=?, cliente_nombre=?, cliente_negocio=?, cliente_telefono=?, cliente_email=?, cliente_domicilio=?, validez_dias=?, aplicar_iva=?, porcentaje_iva=?, subtotal=?, iva=?, total=?, condiciones=?, observaciones=?, estatus=? WHERE id=?");
        $stmt->execute(array(
            $fecha,
            $clienteNombre,
            trim(isset($_POST['cliente_negocio']) ? $_POST['cliente_negocio'] : ''),
            trim(isset($_POST['cliente_telefono']) ? $_POST['cliente_telefono'] : ''),
            trim(isset($_POST['cliente_email']) ? $_POST['cliente_email'] : ''),
            trim(isset($_POST['cliente_domicilio']) ? $_POST['cliente_domicilio'] : ''),
            (int)(isset($_POST['validez_dias']) ? $_POST['validez_dias'] : 7),
            $aplicarIva,
            $porcentajeIva,
            $subtotal,
            $iva,
            $total,
            trim(isset($_POST['condiciones']) ? $_POST['condiciones'] : ''),
            trim(isset($_POST['observaciones']) ? $_POST['observaciones'] : ''),
            $estatus,
            $id
        ));
        $pdo->prepare("DELETE FROM cotizacion_items WHERE cotizacion_id=?")->execute(array($id));
    } else {
        $folio = generate_quote_folio($pdo, $fecha);
        $stmt = $pdo->prepare("INSERT INTO cotizaciones (folio, fecha, cliente_nombre, cliente_negocio, cliente_telefono, cliente_email, cliente_domicilio, validez_dias, aplicar_iva, porcentaje_iva, subtotal, iva, total, condiciones, observaciones, estatus) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute(array(
            $folio,
            $fecha,
            $clienteNombre,
            trim(isset($_POST['cliente_negocio']) ? $_POST['cliente_negocio'] : ''),
            trim(isset($_POST['cliente_telefono']) ? $_POST['cliente_telefono'] : ''),
            trim(isset($_POST['cliente_email']) ? $_POST['cliente_email'] : ''),
            trim(isset($_POST['cliente_domicilio']) ? $_POST['cliente_domicilio'] : ''),
            (int)(isset($_POST['validez_dias']) ? $_POST['validez_dias'] : 7),
            $aplicarIva,
            $porcentajeIva,
            $subtotal,
            $iva,
            $total,
            trim(isset($_POST['condiciones']) ? $_POST['condiciones'] : ''),
            trim(isset($_POST['observaciones']) ? $_POST['observaciones'] : ''),
            $estatus
        ));
        $id = (int)$pdo->lastInsertId();
    }

    $stmtItem = $pdo->prepare("INSERT INTO cotizacion_items (cotizacion_id, orden, tipo, promocion_id, descripcion, cantidad, precio_unitario, importe) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($items as $item) {
        $stmtItem->execute(array($id, $item['orden'], $item['tipo'], $item['promocion_id'], $item['descripcion'], $item['cantidad'], $item['precio_unitario'], $item['importe']));
    }
    $pdo->commit();
    flash_set('success', 'Cotización guardada correctamente.');
    redirect_to('/ver_cotizacion.php?id=' . $id);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo '<h1>Error al guardar</h1><pre>' . h($e->getMessage()) . '</pre>';
}
