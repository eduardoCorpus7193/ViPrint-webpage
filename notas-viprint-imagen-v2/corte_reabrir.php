<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/modulos_2_3_10_helpers.php';
require_login();
if (!m2310_can_reopen_corte()) { flash('danger','Solo admin puede reabrir cortes.'); redirect_to('cortes_historial.php'); }
$corteId = (int)($_POST['corte_id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');
if ($corteId <= 0 || $motivo === '') { flash('danger','Falta corte o motivo.'); redirect_to('cortes_historial.php'); }
try {
    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT * FROM v2_cortes_caja WHERE id=? FOR UPDATE');
    $stmt->execute(array($corteId));
    $corte = $stmt->fetch();
    if (!$corte) throw new Exception('No se encontró el corte.');
    $obs = trim(($corte['observaciones'] ?? '') . "\n[REABIERTO POR ADMIN " . date('Y-m-d H:i:s') . "] " . $motivo);
    $stmt = $pdo->prepare('UPDATE v2_cortes_caja SET cerrado=0, cerrado_por=NULL, cerrado_at=NULL, observaciones=? WHERE id=?');
    $stmt->execute(array($obs,$corteId));
    $movId = (int)($corte['entrega_movimiento_id'] ?? 0);
    if ($movId > 0 && table_exists('v2_caja_movimientos')) {
        if (column_exists('v2_caja_movimientos','anulado')) {
            $stmt = $pdo->prepare("UPDATE v2_caja_movimientos SET monto_original=COALESCE(monto_original,monto), monto=0, anulado=1, anulado_por=?, anulado_at=NOW(), anulacion_motivo=?, descripcion=CONCAT(COALESCE(descripcion,''), ?) WHERE id=?");
            $stmt->execute(array(current_user()['id'],$motivo,"\n[REABIERTO CORTE] Movimiento de entrega a Luis anulado.",$movId));
        } else {
            $stmt = $pdo->prepare("UPDATE v2_caja_movimientos SET monto=0, descripcion=CONCAT(COALESCE(descripcion,''), ?) WHERE id=?");
            $stmt->execute(array("\n[REABIERTO CORTE] Movimiento de entrega a Luis puesto en cero. Motivo: ".$motivo,$movId));
        }
    }
    if (table_exists('v2_auditoria_admin')) {
        $stmt = $pdo->prepare("INSERT INTO v2_auditoria_admin (accion, entidad, entidad_id, nota_id, motivo, datos_antes, usuario_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute(array('reabrir_corte','v2_cortes_caja',$corteId,null,$motivo,json_encode($corte, JSON_UNESCAPED_UNICODE),current_user()['id']));
    }
    $pdo->commit();
    flash('success','Corte reabierto. Revisa los movimientos y vuelve a cerrarlo cuando quede correcto.');
    redirect_to('corte_diario.php?fecha='.$corte['fecha_corte']);
} catch (Exception $e) {
    if (db()->inTransaction()) db()->rollBack();
    flash('danger','No se pudo reabrir el corte: '.$e->getMessage());
    redirect_to('cortes_historial.php');
}
