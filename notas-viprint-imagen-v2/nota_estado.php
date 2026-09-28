<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/actualizacion_20260924_helpers.php';
require_login();
$id=(int)($_POST['id']??0);
$stmt=db()->prepare("SELECT * FROM v2_notas WHERE id=?"); $stmt->execute(array($id)); $old=$stmt->fetch();
if(!$old){ flash('danger','Nota no encontrada.'); redirect_to('notas.php'); }
$fields=array('estado_contacto','estado_diseno','estado_aprobacion_impresion','estado_produccion','estado_instalacion','estado_entrega');
$comment=trim($_POST['comentario']??'');
try{
 db()->beginTransaction();
 $changed = false;
 foreach($fields as $f){
   if(isset($_POST[$f]) && $_POST[$f] !== $old[$f]){
     $stmt=db()->prepare("UPDATE v2_notas SET $f=?, actualizado_por=? WHERE id=?");
     $stmt->execute(array($_POST[$f],current_user()['id'],$id));
     $stmt=db()->prepare("INSERT INTO v2_estado_historial (nota_id,campo,valor_anterior,valor_nuevo,comentario,usuario_id) VALUES (?,?,?,?,?,?)");
     $stmt->execute(array($id,$f,$old[$f],$_POST[$f],$comment,current_user()['id']));
     $changed = true;
   }
 }
 if($comment !== '' && !$changed){
   $stmt=db()->prepare("INSERT INTO v2_estado_historial (nota_id,campo,valor_anterior,valor_nuevo,comentario,usuario_id) VALUES (?,?,?,?,?,?)");
   $stmt->execute(array($id,'comentario',null,'comentario',$comment,current_user()['id']));
   $changed = true;
 }
 db()->commit(); flash($changed ? 'success' : 'info', $changed ? 'Estados actualizados.' : 'No hubo cambios para guardar.');
}catch(Exception $e){ db()->rollBack(); flash('danger','Error al actualizar estados: '.$e->getMessage()); }
redirect_to('nota_ver.php?id='.$id);
