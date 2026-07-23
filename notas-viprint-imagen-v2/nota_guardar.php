<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_to('notas.php');
$id = (int)($_POST['id'] ?? 0);
$empresa_id = (int)($_POST['empresa_id'] ?? 0);
$folio = trim($_POST['folio'] ?? '');
$fecha_nota = $_POST['fecha_nota'] ?? date('Y-m-d');
$cliente = trim($_POST['cliente_nombre'] ?? '');
if(!$empresa_id || $folio==='' || $cliente===''){ flash('danger','Faltan campos obligatorios.'); redirect_to('nota_form.php'.($id?'?id='.$id:'')); }
$data = [
 $empresa_id,$folio,$fecha_nota,$cliente,trim($_POST['negocio']??''),trim($_POST['domicilio']??''),trim($_POST['telefono']??''),$_POST['origen']??'mostrador',trim($_POST['origen_otro']??''),trim($_POST['vendedor_nombre']??''),trim($_POST['intermediario_nombre']??''),($_POST['disenador_id']!==''?(int)$_POST['disenador_id']:null),($_POST['fecha_promesa']?:null),($_POST['fecha_instalacion']?:null),(int)($_POST['requiere_factura']??0),$_POST['estado_contacto']??'pendiente',$_POST['estado_diseno']??'sin_asignar',$_POST['estado_aprobacion_impresion']??'pendiente',$_POST['estado_produccion']??'pendiente',$_POST['estado_instalacion']??'no_aplica',$_POST['estado_entrega']??'pendiente',trim($_POST['observaciones']??''),current_user()['id']
];
try{
 db()->beginTransaction();
 if($id){
   $sql="UPDATE v2_notas SET empresa_id=?, folio=?, fecha_nota=?, cliente_nombre=?, negocio=?, domicilio=?, telefono=?, origen=?, origen_otro=?, vendedor_nombre=?, intermediario_nombre=?, disenador_id=?, fecha_promesa=?, fecha_instalacion=?, requiere_factura=?, estado_contacto=?, estado_diseno=?, estado_aprobacion_impresion=?, estado_produccion=?, estado_instalacion=?, estado_entrega=?, observaciones=?, actualizado_por=? WHERE id=?";
   $stmt=db()->prepare($sql); $stmt->execute(array_merge($data,[$id]));
   db()->prepare("DELETE FROM v2_nota_partidas WHERE nota_id=?")->execute([$id]);
 } else {
   $sql="INSERT INTO v2_notas (empresa_id,folio,fecha_nota,cliente_nombre,negocio,domicilio,telefono,origen,origen_otro,vendedor_nombre,intermediario_nombre,disenador_id,fecha_promesa,fecha_instalacion,requiere_factura,estado_contacto,estado_diseno,estado_aprobacion_impresion,estado_produccion,estado_instalacion,estado_entrega,observaciones,creado_por,actualizado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
   $stmt=db()->prepare($sql); $stmt->execute(array_merge($data,[current_user()['id']]));
   $id=(int)db()->lastInsertId();
 }
 $descs=$_POST['descripcion']??[]; $qtys=$_POST['cantidad']??[]; $prices=$_POST['precio_unitario']??[]; $tipos=$_POST['tipo']??[]; $cats=$_POST['catalogo_id']??[]; $especial=$_POST['precio_especial']??[];
 $cem=$_POST['costo_estimado_material']??[]; $ceo=$_POST['costo_estimado_mano_obra']??[]; $cemaq=$_POST['costo_estimado_maquila']??[]; $cei=$_POST['costo_estimado_instalacion']??[]; $crm=$_POST['costo_real_material']??[]; $cro=$_POST['costo_real_mano_obra']??[]; $crmaq=$_POST['costo_real_maquila']??[]; $cri=$_POST['costo_real_instalacion']??[];
 $stmt=db()->prepare("INSERT INTO v2_nota_partidas (nota_id,empresa_id,catalogo_id,tipo,descripcion,cantidad,precio_unitario,precio_especial,total,costo_estimado_material,costo_estimado_mano_obra,costo_estimado_maquila,costo_estimado_instalacion,costo_real_material,costo_real_mano_obra,costo_real_maquila,costo_real_instalacion) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
 for($i=0;$i<count($descs);$i++){
   $desc=trim($descs[$i]??''); if($desc==='') continue;
   $qty=(float)($qtys[$i]??1); $price=(float)($prices[$i]??0); $total=$qty*$price;
   $stmt->execute([$id,$empresa_id,($cats[$i]!==''?(int)$cats[$i]:null),$tipos[$i]??'articulo',$desc,$qty,$price,(int)($especial[$i]??0),$total,(float)($cem[$i]??0),(float)($ceo[$i]??0),(float)($cemaq[$i]??0),(float)($cei[$i]??0),(float)($crm[$i]??0),(float)($cro[$i]??0),(float)($crmaq[$i]??0),(float)($cri[$i]??0)]);
 }
 recalcular_nota($id);
 db()->commit();
 flash('success','Nota guardada correctamente.'); redirect_to('nota_ver.php?id='.$id);
}catch(Exception $e){ db()->rollBack(); flash('danger','Error al guardar: '.$e->getMessage()); redirect_to('nota_form.php'.($id?'?id='.$id:'')); }
