<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$id=(int)($_POST['id']??0);
$stmt=db()->prepare("SELECT * FROM v2_notas WHERE id=?"); $stmt->execute([$id]); $old=$stmt->fetch();
if(!$old){ flash('danger','Nota no encontrada.'); redirect_to('notas.php'); }
$fields=['estado_contacto','estado_diseno','estado_aprobacion_impresion','estado_produccion','estado_instalacion','estado_entrega'];
$comment=trim($_POST['comentario']??'');
try{
 db()->beginTransaction();
 foreach($fields as $f){
   if(isset($_POST[$f]) && $_POST[$f] !== $old[$f]){
     $stmt=db()->prepare("UPDATE v2_notas SET $f=?, actualizado_por=? WHERE id=?");
     $stmt->execute([$_POST[$f],current_user()['id'],$id]);
     $stmt=db()->prepare("INSERT INTO v2_estado_historial (nota_id,campo,valor_anterior,valor_nuevo,comentario,usuario_id) VALUES (?,?,?,?,?,?)");
     $stmt->execute([$id,$f,$old[$f],$_POST[$f],$comment,current_user()['id']]);
   }
 }
 db()->commit(); flash('success','Estados actualizados.');
}catch(Exception $e){ db()->rollBack(); flash('danger','Error al actualizar estados: '.$e->getMessage()); }
redirect_to('nota_ver.php?id='.$id);
