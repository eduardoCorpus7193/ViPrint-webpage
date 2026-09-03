<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';
$comentario = trim($_POST['comentario'] ?? '');
if (!array_key_exists($estado, estado_options())) {
    $_SESSION['flash'] = array('type'=>'danger', 'message'=>'Estado inválido.');
    redirect('notas.php');
}
$stmt = $pdo->prepare('SELECT * FROM notas WHERE id = ?');
$stmt->execute(array($id));
$nota = $stmt->fetch();
if (!$nota || !can_edit_note($nota)) { http_response_code(403); echo 'No autorizado.'; exit; }
$anterior = $nota['estado'];
$stmt = $pdo->prepare('UPDATE notas SET estado = ?, actualizado_por = ? WHERE id = ?');
$stmt->execute(array($estado, current_user()['id'], $id));
if ($anterior !== $estado) add_estado_historial($pdo, $id, $anterior, $estado, $comentario);
$_SESSION['flash'] = array('type'=>'success', 'message'=>'Estado actualizado.');
redirect('nota_ver.php?id=' . $id);
