<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if(!can_finance()){ flash('danger','No tienes permiso para comisiones.'); redirect_to('notas.php'); }
$nota_id=(int)($_POST['nota_id']??0); $empresa_id=(int)($_POST['empresa_id']??0); $monto=(float)($_POST['monto']??0);
$estado=$_POST['estado']??'pendiente'; $aplica=($estado==='no_aplica'||$monto<=0)?0:1;
try{
 $stmt=db()->prepare("INSERT INTO v2_comisiones (nota_id,empresa_id,disenador_id,tipo,aplica,monto,estado,fecha_semana,fecha_pago,observaciones) VALUES (?,?,?,?,?,?,?,?,?,?)");
 $stmt->execute([$nota_id,$empresa_id,(int)$_POST['disenador_id'],$_POST['tipo']??'otro',$aplica,$monto,$estado,($_POST['fecha_semana']?:null),($estado==='pagada'?date('Y-m-d'):null),trim($_POST['observaciones']??'')]);
 recalcular_nota($nota_id); flash('success','Comisión registrada.');
}catch(Exception $e){ flash('danger','Error al registrar comisión: '.$e->getMessage()); }
redirect_to('nota_ver.php?id='.$nota_id);
