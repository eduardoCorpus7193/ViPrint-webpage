<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!is_operativo()) { http_response_code(403); echo 'No autorizado.'; exit; }
verify_csrf();
$nota_id = (int)($_POST['nota_id'] ?? 0);
$fecha_pago = $_POST['fecha_pago'] ?? date('Y-m-d');
$monto = max(0, (float)($_POST['monto'] ?? 0));
$forma_pago = $_POST['forma_pago'] ?? 'efectivo';
if (!in_array($forma_pago, array('efectivo','transferencia','tarjeta','otro'), true)) $forma_pago = 'efectivo';
$referencia = trim($_POST['referencia'] ?? '');
if ($monto <= 0) {
    $_SESSION['flash'] = array('type'=>'danger', 'message'=>'El monto debe ser mayor a cero.');
    redirect('nota_ver.php?id=' . $nota_id);
}
$stmt = $pdo->prepare('INSERT INTO abonos (nota_id, fecha_pago, monto, forma_pago, referencia, usuario_id) VALUES (?,?,?,?,?,?)');
$stmt->execute(array($nota_id, $fecha_pago, $monto, $forma_pago, $referencia, current_user()['id']));
recalculate_saldo($pdo, $nota_id);
$_SESSION['flash'] = array('type'=>'success', 'message'=>'Abono registrado correctamente.');
redirect('nota_ver.php?id=' . $nota_id);
