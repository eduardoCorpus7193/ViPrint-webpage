<?php
require_once __DIR__ . '/includes/bootstrap.php';
$clave = $_GET['clave'] ?? $_POST['clave'] ?? '';
if($clave !== 'migrar2026'){
  echo '<h1>Migración V1 a V2</h1><p>Por seguridad, entra con: <code>migrar_v1_a_v2.php?clave=migrar2026</code></p><p>El script no borra tablas antiguas; crea tablas V2 y copia registros existentes.</p>'; exit;
}
function run_sql_file($file){
  $sql=file_get_contents($file);
  $stmts=array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
  foreach($stmts as $s){ if($s!=='') db()->exec($s); }
}
function v2_user_from_old($old_id){
  if(!$old_id) return null;
  $stmt=db()->prepare("SELECT usuario FROM usuarios WHERE id=?"); $stmt->execute([$old_id]); $u=$stmt->fetchColumn();
  if(!$u) return null;
  $stmt=db()->prepare("SELECT id FROM v2_usuarios WHERE usuario=?"); $stmt->execute([$u]); return $stmt->fetchColumn() ?: null;
}
$log=[];
try{
  run_sql_file(__DIR__.'/database/schema_v2.sql');
  $log[]='Tablas V2 creadas/verificadas.';
  if(table_exists('usuarios')){
    $oldUsers=db()->query("SELECT * FROM usuarios")->fetchAll();
    foreach($oldUsers as $u){
      $rol='disenador'; $fin=0; $precio=0; $borrar=0; $es=0;
      if($u['rol']==='admin'){ $rol='admin'; $fin=1; $precio=1; $borrar=1; }
      elseif($u['rol']==='operativo'){ $rol='operativo'; }
      else { $rol='disenador'; $es=1; }
      db()->prepare("INSERT INTO v2_usuarios (nombre,usuario,password_hash,rol,puede_ver_finanzas,puede_editar_precios,puede_borrar,es_disenador,activo) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), password_hash=VALUES(password_hash), activo=VALUES(activo)")->execute([$u['nombre'],$u['usuario'],$u['password_hash'],$rol,$fin,$precio,$borrar,$es,$u['activo']]);
    }
    $log[]='Usuarios V1 copiados a V2.';
  }
  if(!table_exists('notas')){ $log[]='No encontré tabla antigua notas. Solo se inicializó V2.'; }
  else{
    $emp=[]; foreach(db()->query("SELECT id,clave FROM v2_empresas") as $e){ $emp[$e['clave']]=$e['id']; }
    $count=0;
    $notes=db()->query("SELECT * FROM notas ORDER BY id")->fetchAll();
    foreach($notes as $n){
      $empresa_id=$emp[$n['empresa']] ?? $emp['viprint'];
      $stmt=db()->prepare("SELECT id FROM v2_notas WHERE empresa_id=? AND folio_v1=? LIMIT 1"); $stmt->execute([$empresa_id,$n['id']]);
      if($stmt->fetchColumn()) continue;
      $ec='pendiente'; $ed='sin_asignar'; $ei='pendiente'; $ep='pendiente'; $eins='no_aplica'; $ee='pendiente';
      switch($n['estado']){
        case 'pendiente_contacto': $ec='pendiente'; $ed='pendiente_contacto'; break;
        case 'contactado': $ec='contactado'; break;
        case 'en_diseno': $ec='contactado'; $ed='en_diseno'; break;
        case 'en_aprobacion': $ed='en_aprobacion'; break;
        case 'aprobado_para_imprimir': $ed='aprobado'; $ei='autorizada'; $ep='para_imprimir'; break;
        case 'impresa': $ed='aprobado'; $ei='autorizada'; $ep='impresa'; break;
        case 'sublimada': $ed='aprobado'; $ei='autorizada'; $ep='sublimada'; break;
        case 'en_instalacion': $eins='en_instalacion'; break;
        case 'instalada': $eins='instalada'; break;
        case 'entregada': $ee='entregada'; break;
        case 'cancelada': $ee='cancelada'; break;
      }
      $stmt=db()->prepare("INSERT INTO v2_notas (empresa_id,folio,folio_v1,fecha_nota,cliente_nombre,negocio,domicilio,telefono,origen,vendedor_nombre,disenador_id,fecha_promesa,fecha_instalacion,estado_contacto,estado_diseno,estado_aprobacion_impresion,estado_produccion,estado_instalacion,estado_entrega,total,pagado,saldo,observaciones,migrado_v1,creado_por,actualizado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
      $stmt->execute([$empresa_id,$n['folio'],$n['id'],$n['fecha_nota'],$n['cliente_nombre'],$n['negocio'],$n['domicilio'],$n['telefono'],$n['origen'],$n['vendedor_nombre'],v2_user_from_old($n['disenador_id']),$n['fecha_promesa'],$n['fecha_instalacion'],$ec,$ed,$ei,$ep,$eins,$ee,$n['total'],0,$n['saldo'],$n['observaciones'],1,v2_user_from_old($n['creado_por']),v2_user_from_old($n['actualizado_por'])]);
      $newId=(int)db()->lastInsertId();
      if(table_exists('nota_detalles')){
        $stmtD=db()->prepare("SELECT * FROM nota_detalles WHERE nota_id=?"); $stmtD->execute([$n['id']]);
        foreach($stmtD->fetchAll() as $d){
          db()->prepare("INSERT INTO v2_nota_partidas (nota_id,empresa_id,tipo,descripcion,cantidad,precio_unitario,total) VALUES (?,?,?,?,?,?,?)")->execute([$newId,$empresa_id,$d['tipo_item'],$d['descripcion'],$d['cantidad'],$d['precio_unitario'],$d['importe']]);
        }
      }
      if(table_exists('abonos')){
        $stmtA=db()->prepare("SELECT * FROM abonos WHERE nota_id=?"); $stmtA->execute([$n['id']]); $abs=$stmtA->fetchAll();
        foreach($abs as $a){ db()->prepare("INSERT INTO v2_pagos (nota_id,empresa_id,fecha_pago,concepto,monto,forma_pago,referencia,usuario_id) VALUES (?,?,?,?,?,?,?,?)")->execute([$newId,$empresa_id,$a['fecha_pago'],'abono',$a['monto'],$a['forma_pago'],$a['referencia'],v2_user_from_old($a['usuario_id'])]); }
        if(!$abs && (float)$n['anticipo']>0){ db()->prepare("INSERT INTO v2_pagos (nota_id,empresa_id,fecha_pago,concepto,monto,forma_pago,referencia) VALUES (?,?,?,?,?,?,?)")->execute([$newId,$empresa_id,$n['fecha_nota'],'anticipo',$n['anticipo'],'efectivo','Migrado desde anticipo V1']); }
      }
      recalcular_nota($newId); $count++;
    }
    $log[]="Notas migradas: $count";
  }
}catch(Exception $e){ $log[]='ERROR: '.$e->getMessage(); }
?><!doctype html><html lang="es"><head><meta charset="utf-8"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><title>Migración</title></head><body class="p-4"><h1>Migración V1 a V2</h1><?php foreach($log as $l): ?><div class="alert alert-info"><?= h($l) ?></div><?php endforeach; ?><p><a href="<?= url('login.php') ?>">Ir al sistema</a></p><p class="text-danger">Cuando termines, elimina o renombra este archivo del hosting.</p></body></html>
