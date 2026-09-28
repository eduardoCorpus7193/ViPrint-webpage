<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if(!role_in(['admin','direccion','administracion','operativo','asesor'])){ flash('danger','No tienes permiso para registrar mermas.'); redirect_to('notas.php'); }
$nota_id=(int)($_POST['nota_id']??0); $empresa_id=(int)($_POST['empresa_id']??0);
try{
 $stmt=db()->prepare("INSERT INTO v2_mermas (nota_id,empresa_id,fecha_merma,tipo,area,descripcion,responsable_probable_id,reportado_por_id,costo_estimado,costo_real,afecta_ganancia,solucion) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
 $stmt->execute([$nota_id,$empresa_id,$_POST['fecha_merma']?:date('Y-m-d'),$_POST['tipo']??'otro',$_POST['area']??'otro',trim($_POST['descripcion']??''),($_POST['responsable_probable_id']!==''?(int)$_POST['responsable_probable_id']:null),current_user()['id'],(float)($_POST['costo_estimado']??0),(float)($_POST['costo_real']??0),1,trim($_POST['solucion']??'')]);
 recalcular_nota($nota_id); flash('success','Merma registrada.');
}catch(Exception $e){ flash('danger','Error al registrar merma: '.$e->getMessage()); }
redirect_to('nota_ver.php?id='.$nota_id);
