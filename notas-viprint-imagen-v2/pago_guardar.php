<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if(!can_register_payments()){ flash('danger','No tienes permiso para registrar pagos.'); redirect_to('notas.php'); }
$nota_id=(int)($_POST['nota_id']??0); $empresa_id=(int)($_POST['empresa_id']??0);
try{
 $stmt=db()->prepare("INSERT INTO v2_pagos (nota_id,empresa_id,fecha_pago,concepto,monto,forma_pago,forma_pago_otro,referencia,comprobante,observaciones,usuario_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
 $stmt->execute([$nota_id,$empresa_id,$_POST['fecha_pago']?:date('Y-m-d'),$_POST['concepto']??'abono',(float)($_POST['monto']??0),$_POST['forma_pago']??'efectivo',trim($_POST['forma_pago_otro']??''),trim($_POST['referencia']??''),trim($_POST['comprobante']??''),trim($_POST['observaciones']??''),current_user()['id']]);
 recalcular_nota($nota_id); flash('success','Pago registrado.');
}catch(Exception $e){ flash('danger','Error al registrar pago: '.$e->getMessage()); }
redirect_to('nota_ver.php?id='.$nota_id);
