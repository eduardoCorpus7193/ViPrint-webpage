<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

if (!table_exists('v2_caja_aperturas')) {
    echo json_encode(['ok' => false, 'message' => 'Falta ejecutar database/update_tickets_cajon_v2.sql']);
    exit;
}

$empresa_id = (int)($_POST['empresa_id'] ?? 0);
$nota_id = isset($_POST['nota_id']) && $_POST['nota_id'] !== '' ? (int)$_POST['nota_id'] : null;
$pago_id = isset($_POST['pago_id']) && $_POST['pago_id'] !== '' ? (int)$_POST['pago_id'] : null;
$tipo = $_POST['tipo'] ?? 'manual';
if (!in_array($tipo, ['pago','manual','prueba'], true)) { $tipo = 'manual'; }
$motivo = trim($_POST['motivo'] ?? '');
$impresora = trim($_POST['impresora'] ?? '58mm Series Printer');
$comando = trim($_POST['comando'] ?? '27,112,48,55,121');

try {
    if ($empresa_id <= 0) {
        throw new Exception('Empresa no válida.');
    }
    $stmt = db()->prepare("INSERT INTO v2_caja_aperturas (empresa_id, nota_id, pago_id, fecha_apertura, hora_apertura, tipo, motivo, impresora, comando, usuario_id, ip, user_agent) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $empresa_id,
        $nota_id,
        $pago_id,
        date('Y-m-d'),
        date('H:i:s'),
        $tipo,
        $motivo,
        $impresora,
        $comando,
        current_user()['id'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
    ]);
    echo json_encode(['ok' => true, 'id' => (int)db()->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
