<?php
require_once __DIR__."/includes/bootstrap.php";
require_once __DIR__."/includes/actualizacion_20260924_helpers.php";
require_login();
require_once __DIR__."/includes/header.php";
$estado=$_GET["estado"]??""; $q=trim($_GET["q"]??"");
$where="1=1"; $params=[];
if($estado!==""){ $where.=" AND qs.estado=?"; $params[]=$estado; }
if($q!==""){ $where.=" AND (qs.folio LIKE ? OR qs.nombre LIKE ? OR qs.telefono LIKE ? OR n.cliente_nombre LIKE ? OR n.negocio LIKE ?)"; $like="%".$q."%"; array_push($params,$like,$like,$like,$like,$like); }
$sql="SELECT qs.*, n.cliente_nombre,n.negocio,e.nombre empresa,u.nombre atendio FROM v2_quejas_sugerencias qs LEFT JOIN v2_notas n ON n.id=qs.nota_id LEFT JOIN v2_empresas e ON e.id=qs.empresa_id LEFT JOIN v2_usuarios u ON u.id=qs.atendido_por_id WHERE $where ORDER BY qs.created_at DESC,qs.id DESC LIMIT 400";
$stmt=db()->prepare($sql); $stmt->execute($params); $rows=$stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-0">Quejas y sugerencias</h1><div class="text-muted">Mensajes enviados por clientes desde su nota digital.</div></div></div>
<form class="card card-body mb-3 no-print" method="get"><div class="row g-2"><div class="col-md-6"><input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Buscar folio, negocio, cliente o teléfono"></div><div class="col-md-4"><select class="form-select" name="estado"><option value="">Todos</option><option value="nueva" <?= $estado==="nueva"?"selected":"" ?>>Nueva</option><option value="en_revision" <?= $estado==="en_revision"?"selected":"" ?>>En revisión</option><option value="resuelta" <?= $estado==="resuelta"?"selected":"" ?>>Resuelta</option><option value="descartada" <?= $estado==="descartada"?"selected":"" ?>>Descartada</option></select></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrar</button></div></div></form>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Fecha</th><th>Nota</th><th>Cliente</th><th>Tipo</th><th>Mensaje</th><th>Estado / atención</th></tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr><td><?= date_mx($r["created_at"]) ?><br><span class="small text-muted"><?= h(date("H:i",strtotime($r["created_at"]))) ?></span></td><td><a href="<?= url("nota_ver.php?id=".(int)$r["nota_id"]) ?>"><?= h($r["folio"]) ?></a><br><span class="small text-muted"><?= h($r["empresa"]) ?></span></td><td><strong><?= h($r["negocio"] ?: $r["cliente_nombre"] ?: $r["nombre"]) ?></strong><br><span class="small text-muted"><?= h($r["nombre"]) ?> · <?= h($r["telefono"]) ?></span></td><td><?= h(vp_queja_tipo_label($r["tipo"])) ?></td><td><?= nl2br(h($r["mensaje"])) ?><?php if($r["foto_path"]): ?><br><a class="small" href="<?= h(url($r["foto_path"])) ?>" target="_blank">Ver foto</a><?php endif; ?><?php if($r["respuesta_interna"]): ?><div class="small text-muted mt-2">Interno: <?= nl2br(h($r["respuesta_interna"])) ?></div><?php endif; ?></td><td><form method="post" action="<?= url("queja_actualizar.php") ?>"><input type="hidden" name="id" value="<?= (int)$r["id"] ?>"><select class="form-select form-select-sm mb-1" name="estado"><option value="nueva" <?= $r["estado"]==="nueva"?"selected":"" ?>>Nueva</option><option value="en_revision" <?= $r["estado"]==="en_revision"?"selected":"" ?>>En revisión</option><option value="resuelta" <?= $r["estado"]==="resuelta"?"selected":"" ?>>Resuelta</option><option value="descartada" <?= $r["estado"]==="descartada"?"selected":"" ?>>Descartada</option></select><textarea class="form-control form-control-sm mb-1" name="respuesta_interna" rows="2" placeholder="Comentario interno"><?= h($r["respuesta_interna"]) ?></textarea><button class="btn btn-sm btn-primary w-100">Guardar</button><?php if($r["atendio"]): ?><div class="small text-muted mt-1">Último: <?= h($r["atendio"]) ?></div><?php endif; ?></form></td></tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay registros.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__."/includes/footer.php"; ?>
