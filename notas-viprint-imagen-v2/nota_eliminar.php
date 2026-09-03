<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!current_user() || current_user()['rol'] !== 'admin') { flash('danger','Solo admin puede eliminar notas.'); redirect_to('index.php'); }

$nota_id = (int)($_POST['nota_id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');
if ($nota_id <= 0 || $motivo === '') { flash('danger','Falta nota o motivo.'); redirect_to('admin_correcciones.php'); }

try {
    $pdo = db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM v2_notas WHERE id=? FOR UPDATE");
    $stmt->execute(array($nota_id));
    $nota = $stmt->fetch();
    if (!$nota) { throw new Exception('No se encontró la nota.'); }
    if ((int)($nota['eliminada'] ?? 0) === 1) { throw new Exception('Esta nota ya estaba eliminada.'); }

    $auditData = array('nota'=>$nota, 'pagos'=>array(), 'caja'=>array(), 'partidas'=>array());
    $stmt = $pdo->prepare("SELECT * FROM v2_pagos WHERE nota_id=?");
    $stmt->execute(array($nota_id));
    $auditData['pagos'] = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM v2_nota_partidas WHERE nota_id=?");
    $stmt->execute(array($nota_id));
    $auditData['partidas'] = $stmt->fetchAll();
    if (table_exists('v2_caja_movimientos')) {
        $stmt = $pdo->prepare("SELECT * FROM v2_caja_movimientos WHERE nota_id=?");
        $stmt->execute(array($nota_id));
        $auditData['caja'] = $stmt->fetchAll();
    }

    if (table_exists('v2_auditoria_admin')) {
        $stmt = $pdo->prepare("INSERT INTO v2_auditoria_admin (accion, entidad, entidad_id, nota_id, motivo, datos_antes, usuario_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute(array('eliminar_nota','v2_notas',$nota_id,$nota_id,$motivo,json_encode($auditData, JSON_UNESCAPED_UNICODE),current_user()['id']));
    }

    $obsAdd = "\n[NOTA ELIMINADA POR ADMIN " . date('Y-m-d H:i:s') . "] " . $motivo;

    if (column_exists('v2_pagos','anulado')) {
        $stmt = $pdo->prepare("UPDATE v2_pagos SET monto_original=COALESCE(monto_original,monto), monto=0, anulado=1, anulado_por=?, anulado_at=NOW(), anulacion_motivo=CONCAT('Nota eliminada: ', ?), observaciones=CONCAT(COALESCE(observaciones,''), ?) WHERE nota_id=? AND (anulado=0 OR anulado IS NULL)");
        $stmt->execute(array(current_user()['id'], $motivo, $obsAdd, $nota_id));
    } else {
        $stmt = $pdo->prepare("UPDATE v2_pagos SET monto=0, observaciones=CONCAT(COALESCE(observaciones,''), ?) WHERE nota_id=?");
        $stmt->execute(array($obsAdd, $nota_id));
    }

    if (table_exists('v2_caja_movimientos')) {
        if (column_exists('v2_caja_movimientos','anulado')) {
            $stmt = $pdo->prepare("UPDATE v2_caja_movimientos SET monto_original=COALESCE(monto_original,monto), monto=0, anulado=1, anulado_por=?, anulado_at=NOW(), anulacion_motivo=CONCAT('Nota eliminada: ', ?), descripcion=CONCAT(COALESCE(descripcion,''), ?) WHERE nota_id=? AND (anulado=0 OR anulado IS NULL)");
            $stmt->execute(array(current_user()['id'], $motivo, $obsAdd, $nota_id));
        } else {
            $stmt = $pdo->prepare("UPDATE v2_caja_movimientos SET monto=0, descripcion=CONCAT(COALESCE(descripcion,''), ?) WHERE nota_id=?");
            $stmt->execute(array($obsAdd, $nota_id));
        }
    }

    $stmt = $pdo->prepare("UPDATE v2_notas SET eliminada=1, eliminado_por=?, eliminado_at=NOW(), eliminacion_motivo=?, cancelacion_motivo=CONCAT(COALESCE(cancelacion_motivo,''), ?), estado_entrega='cancelada', estado_pago='cancelada', total=0, pagado=0, saldo=0, devolucion_total=0, utilidad_estimada=0, utilidad_real=0, actualizado_por=? WHERE id=?");
    $stmt->execute(array(current_user()['id'], $motivo, $obsAdd, current_user()['id'], $nota_id));

    $pdo->commit();
    flash('success','Nota eliminada correctamente. Pagos y caja relacionados quedaron anulados con historial.');
    redirect_to('admin_correcciones.php?q='.urlencode($nota['folio']));
} catch (Exception $e) {
    if (db()->inTransaction()) { db()->rollBack(); }
    flash('danger','No se pudo eliminar la nota: '.$e->getMessage());
    redirect_to('admin_correcciones.php?q='.$nota_id);
}
