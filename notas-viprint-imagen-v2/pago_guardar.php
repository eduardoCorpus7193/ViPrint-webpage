<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if(!can_register_payments()){ flash('danger','No tienes permiso para registrar pagos.'); redirect_to('notas.php'); }

$nota_id=(int)($_POST['nota_id']??0);
$empresa_id=(int)($_POST['empresa_id']??0);
$fecha_pago = $_POST['fecha_pago'] ?: date('Y-m-d');
$concepto = $_POST['concepto'] ?? 'abono';
$monto = (float)($_POST['monto'] ?? 0);
$forma_pago = $_POST['forma_pago'] ?? 'efectivo';
$forma_pago_otro = trim($_POST['forma_pago_otro'] ?? '');
$referencia = trim($_POST['referencia'] ?? '');
$comprobante = trim($_POST['comprobante'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');
$pago_id = 0;

try{
    if ($nota_id <= 0 || $empresa_id <= 0 || $monto <= 0) {
        throw new Exception('Datos de pago incompletos.');
    }

    if (table_exists('v2_cortes_caja')) {
        $stmt = db()->prepare('SELECT cerrado FROM v2_cortes_caja WHERE fecha_corte=?');
        $stmt->execute(array($fecha_pago));
        if ((int)$stmt->fetchColumn() === 1) {
            throw new Exception('No puedes registrar pagos en esta fecha porque el corte diario ya está cerrado. Usa otra fecha o pide revisión a admin.');
        }
    }

    $pdo = db();
    $pdo->beginTransaction();

    $stmt=$pdo->prepare("INSERT INTO v2_pagos (nota_id,empresa_id,fecha_pago,concepto,monto,forma_pago,forma_pago_otro,referencia,comprobante,observaciones,usuario_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute(array($nota_id,$empresa_id,$fecha_pago,$concepto,$monto,$forma_pago,$forma_pago_otro,$referencia,$comprobante,$observaciones,current_user()['id']));
    $pago_id = (int)$pdo->lastInsertId();

    if (table_exists('v2_caja_movimientos')) {
        $tipo = ($concepto === 'devolucion') ? 'salida' : 'entrada';
        $concepto_caja = ($concepto === 'devolucion') ? 'devolucion_cliente' : 'pago_cliente';
        $descripcion = 'Movimiento generado desde pago: ' . $concepto;
        $stmt=$pdo->prepare("INSERT INTO v2_caja_movimientos (empresa_id,nota_id,pago_id,fecha_operacion,hora_operacion,tipo,concepto,forma_pago,forma_pago_otro,descripcion,monto,referencia,comprobante,creado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute(array($empresa_id,$nota_id,$pago_id,$fecha_pago,date('H:i:s'),$tipo,$concepto_caja,$forma_pago,$forma_pago_otro,$descripcion,$monto,$referencia,$comprobante,current_user()['id']));
    }

    recalcular_nota($nota_id);
    $pdo->commit();
    flash('success','Pago registrado. Puedes imprimir ticket y abrir cajón.');
}catch(Exception $e){
    if (db()->inTransaction()) { db()->rollBack(); }
    flash('danger','Error al registrar pago: '.$e->getMessage());
    redirect_to('nota_ver.php?id='.$nota_id);
}

if ($pago_id > 0) {
    redirect_to('ticket_pago.php?pago_id='.$pago_id.'&auto=1');
}
redirect_to('nota_ver.php?id='.$nota_id);
