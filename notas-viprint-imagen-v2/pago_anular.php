<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!current_user() || current_user()['rol'] !== 'admin') { flash('danger','Solo admin puede anular pagos.'); redirect_to('index.php'); }

$pago_id = (int)($_POST['pago_id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');
if ($pago_id <= 0 || $motivo === '') { flash('danger','Falta pago o motivo.'); redirect_to('admin_correcciones.php'); }

try {
    $pdo = db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT p.*, n.folio, n.cliente_nombre, n.id nota_id_real FROM v2_pagos p JOIN v2_notas n ON n.id=p.nota_id WHERE p.id=? FOR UPDATE");
    $stmt->execute(array($pago_id));
    $pago = $stmt->fetch();
    if (!$pago) { throw new Exception('No se encontró el pago.'); }
    if ((int)($pago['anulado'] ?? 0) === 1) { throw new Exception('Este pago ya estaba anulado.'); }

    $auditData = array('pago'=>$pago, 'caja'=>array());
    if (table_exists('v2_caja_movimientos')) {
        $stmt = $pdo->prepare("SELECT * FROM v2_caja_movimientos WHERE pago_id=?");
        $stmt->execute(array($pago_id));
        $auditData['caja'] = $stmt->fetchAll();
    }

    if (table_exists('v2_auditoria_admin')) {
        $stmt = $pdo->prepare("INSERT INTO v2_auditoria_admin (accion, entidad, entidad_id, nota_id, motivo, datos_antes, usuario_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute(array('anular_pago','v2_pagos',$pago_id,$pago['nota_id'],$motivo,json_encode($auditData, JSON_UNESCAPED_UNICODE),current_user()['id']));
    }

    $obsAdd = "\n[ANULADO POR ADMIN " . date('Y-m-d H:i:s') . "] " . $motivo;
    $stmt = $pdo->prepare("UPDATE v2_pagos SET monto_original=COALESCE(monto_original,monto), monto=0, anulado=1, anulado_por=?, anulado_at=NOW(), anulacion_motivo=?, observaciones=CONCAT(COALESCE(observaciones,''), ?) WHERE id=?");
    $stmt->execute(array(current_user()['id'], $motivo, $obsAdd, $pago_id));

    if (table_exists('v2_caja_movimientos')) {
        $stmt = $pdo->prepare("UPDATE v2_caja_movimientos SET monto_original=COALESCE(monto_original,monto), monto=0, anulado=1, anulado_por=?, anulado_at=NOW(), anulacion_motivo=?, descripcion=CONCAT(COALESCE(descripcion,''), ?) WHERE pago_id=?");
        $stmt->execute(array(current_user()['id'], $motivo, $obsAdd, $pago_id));
    }

    recalcular_nota((int)$pago['nota_id']);
    $pdo->commit();
    flash('success','Pago anulado correctamente. El saldo y caja se revirtieron poniendo el monto en cero y dejando historial.');
    redirect_to('admin_correcciones.php?q='.urlencode($pago['folio']));
} catch (Exception $e) {
    if (db()->inTransaction()) { db()->rollBack(); }
    flash('danger','No se pudo anular el pago: '.$e->getMessage());
    redirect_to('admin_correcciones.php');
}
