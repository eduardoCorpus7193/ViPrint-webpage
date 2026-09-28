<?php
require_once __DIR__."/includes/bootstrap.php";
require_once __DIR__."/includes/actualizacion_20260924_helpers.php";
require_login();
$id=(int)($_POST["id"]??0);
$estado=$_POST["estado"]??"nueva";
if(!in_array($estado,["nueva","en_revision","resuelta","descartada"],true)) $estado="nueva";
$respuesta=trim($_POST["respuesta_interna"]??"");
try{
  $stmt=db()->prepare("UPDATE v2_quejas_sugerencias SET estado=?, respuesta_interna=?, atendido_por_id=?, updated_at=NOW() WHERE id=?");
  $stmt->execute([$estado,$respuesta,current_user()["id"]??null,$id]);
  flash("success","Registro actualizado.");
}catch(Exception $e){
  flash("danger","No se pudo actualizar: ".$e->getMessage());
}
redirect_to("quejas.php");
