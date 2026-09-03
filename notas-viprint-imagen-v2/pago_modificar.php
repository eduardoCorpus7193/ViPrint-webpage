<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!current_user() || current_user()['rol'] !== 'admin') {
    flash('danger', 'Solo admin puede modificar pagos.');
    redirect_to('index.php');
}

$pago_id = (int)($_POST['pago_id'] ?? 0);
$fecha_pago = trim($_POST['fecha_pago'] ?? '');
$concepto = trim($_POST['concepto'] ?? '');
$monto = (float)($_POST['monto'] ?? 0);
$forma_pago = trim($_POST['forma_pago'] ?? '');
$forma_pago_otro = trim($_POST['forma_pago_otro'] ?? '');
$referencia = trim($_POST['referencia'] ?? '');
$comprobante = trim($_POST['comprobante'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');
$motivo = trim($_POST['motivo'] ?? '');

$conceptos_validos = array('anticipo','abono','liquidacion','devolucion');
$formas_validas = array('efectivo','transferencia','tarjeta','otro');

if ($pago_id <= 0 || $fecha_pago === '' || !in_array($concepto, $conceptos_validos, true) || $monto <= 0 || !in_array($forma_pago, $formas_validas, true) || $motivo === '') {
    flash('danger', 'Faltan datos obligatorios para modificar el pago.');
    redirect_to('admin_correcciones.php');
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT p.*, n.folio, n.id nota_id_real, n.empresa_id nota_empresa_id FROM v2_pagos p JOIN v2_notas n ON n.id=p.nota_id WHERE p.id=? FOR UPDATE");
    $stmt->execute(array($pago_id));
    $pago = $stmt->fetch();
    if (!$pago) { throw new Exception('No se encontró el pago.'); }
    if ((int)($pago['anulado'] ?? 0) === 1) { throw new Exception('No se puede modificar un pago anulado.'); }

    // Seguridad: no modificar directamente movimientos de un día que ya fue cerrado en corte diario.
    if (table_exists('v2_cortes_caja')) {
        $fechas = array_unique(array($pago['fecha_pago'], $fecha_pago));
        foreach ($fechas as $f) {
            $stmt = $pdo->prepare("SELECT cerrado FROM v2_cortes_caja WHERE fecha_corte=? LIMIT 1");
            $stmt->execute(array($f));
            $cerrado = $stmt->fetchColumn();
            if ($cerrado !== false && (int)$cerrado === 1) {
                throw new Exception('El corte del día '.$f.' ya está cerrado. No se puede modificar el pago directamente; primero debe resolverse el corte con autorización admin.');
            }
        }
    }

    $antes = array('pago'=>$pago, 'caja'=>array());
    if (table_exists('v2_caja_movimientos')) {
        $stmt = $pdo->prepare("SELECT * FROM v2_caja_movimientos WHERE pago_id=?");
        $stmt->execute(array($pago_id));
        $antes['caja'] = $stmt->fetchAll();
    }

    $despues = array(
        'fecha_pago'=>$fecha_pago,
        'concepto'=>$concepto,
        'monto'=>$monto,
        'forma_pago'=>$forma_pago,
        'forma_pago_otro'=>$forma_pago_otro,
        'referencia'=>$referencia,
        'comprobante'=>$comprobante,
        'observaciones'=>$observaciones
    );

    if (table_exists('v2_auditoria_admin')) {
        $datos = array('antes'=>$antes, 'despues'=>$despues);
        $stmt = $pdo->prepare("INSERT INTO v2_auditoria_admin (accion, entidad, entidad_id, nota_id, motivo, datos_antes, usuario_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute(array('modificar_pago','v2_pagos',$pago_id,$pago['nota_id'],$motivo,json_encode($datos, JSON_UNESCAPED_UNICODE),current_user()['id']));
    }

    $obsAdd = "\n[MODIFICADO POR ADMIN " . date('Y-m-d H:i:s') . "] " . $motivo;
    $obsFinal = $observaciones;
    if ($obsFinal !== '') {
        $obsFinal .= $obsAdd;
    } else {
        $obsFinal = trim($obsAdd);
    }

    $stmt = $pdo->prepare("UPDATE v2_pagos SET fecha_pago=?, concepto=?, monto=?, forma_pago=?, forma_pago_otro=?, referencia=?, comprobante=?, observaciones=?, autorizado_por_id=? WHERE id=?");
    $stmt->execute(array($fecha_pago,$concepto,$monto,$forma_pago,$forma_pago_otro,$referencia,$comprobante,$obsFinal,current_user()['id'],$pago_id));

    if (table_exists('v2_caja_movimientos')) {
        $tipo = ($concepto === 'devolucion') ? 'salida' : 'entrada';
        $concepto_caja = ($concepto === 'devolucion') ? 'devolucion_cliente' : 'pago_cliente';
        $descripcion = 'Movimiento actualizado desde corrección admin: ' . $concepto . $obsAdd;
        $stmt = $pdo->prepare("UPDATE v2_caja_movimientos SET empresa_id=?, nota_id=?, fecha_operacion=?, tipo=?, concepto=?, forma_pago=?, forma_pago_otro=?, descripcion=?, monto=?, referencia=?, comprobante=?, autorizado_por_id=? WHERE pago_id=?");
        $stmt->execute(array($pago['empresa_id'],$pago['nota_id'],$fecha_pago,$tipo,$concepto_caja,$forma_pago,$forma_pago_otro,$descripcion,$monto,$referencia,$comprobante,current_user()['id'],$pago_id));
    }

    recalcular_nota((int)$pago['nota_id']);
    $pdo->commit();

    flash('success', 'Pago modificado correctamente. Se actualizó el saldo de la nota y el movimiento de caja relacionado.');
    redirect_to('admin_correcciones.php?q='.urlencode($pago['folio']));
} catch (Exception $e) {
    if (db()->inTransaction()) { db()->rollBack(); }
    flash('danger', 'No se pudo modificar el pago: '.$e->getMessage());
    redirect_to('admin_correcciones.php');
}
