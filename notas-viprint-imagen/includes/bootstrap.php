<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function base_path() {
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return ($dir === '/' || $dir === '.') ? '' : $dir;
}

function url($path = '') {
    return base_path() . '/' . ltrim($path, '/');
}

function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in() {
    return current_user() !== null;
}

function require_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function is_admin() {
    $u = current_user();
    return $u && $u['rol'] === 'admin';
}

function is_operativo() {
    $u = current_user();
    return $u && in_array($u['rol'], array('admin', 'operativo'), true);
}

function is_disenador() {
    $u = current_user();
    return $u && $u['rol'] === 'disenador';
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        echo 'Acceso no autorizado.';
        exit;
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf() {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!$token || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        echo 'Token de seguridad inválido. Regresa e intenta nuevamente.';
        exit;
    }
}

function money($amount) {
    return '$' . number_format((float)$amount, 2) . ' MXN';
}

function date_mx($date) {
    if (!$date) return '';
    $ts = strtotime($date);
    if (!$ts) return h($date);
    return date('d/m/Y', $ts);
}

function estado_options() {
    return array(
        'recibida' => 'Recibida',
        'pendiente_contacto' => 'Pendiente de contacto',
        'contactado' => 'Cliente contactado',
        'en_diseno' => 'En diseño',
        'en_aprobacion' => 'En aprobación del cliente',
        'aprobado_para_imprimir' => 'Aprobado para imprimir',
        'impresa' => 'Impresa',
        'sublimada' => 'Sublimada',
        'en_instalacion' => 'En instalación',
        'instalada' => 'Instalada',
        'entregada' => 'Entregada / cerrada',
        'cancelada' => 'Cancelada'
    );
}

function estado_label($estado) {
    $opts = estado_options();
    return isset($opts[$estado]) ? $opts[$estado] : $estado;
}

function estado_badge_class($estado) {
    $map = array(
        'recibida' => 'badge-soft-secondary',
        'pendiente_contacto' => 'badge-soft-warning',
        'contactado' => 'badge-soft-info',
        'en_diseno' => 'badge-soft-primary',
        'en_aprobacion' => 'badge-soft-warning',
        'aprobado_para_imprimir' => 'badge-soft-success',
        'impresa' => 'badge-soft-success',
        'sublimada' => 'badge-soft-success',
        'en_instalacion' => 'badge-soft-dark',
        'instalada' => 'badge-soft-success',
        'entregada' => 'badge-soft-success',
        'cancelada' => 'badge-soft-danger'
    );
    return isset($map[$estado]) ? $map[$estado] : 'badge-soft-secondary';
}

function empresa_label($empresa) {
    return $empresa === 'imagen' ? 'Imagen' : 'ViPrint';
}

function can_view_note($nota) {
    $u = current_user();
    if (!$u) return false;
    if (in_array($u['rol'], array('admin', 'operativo'), true)) return true;
    if ($u['rol'] === 'disenador' && (int)$nota['disenador_id'] === (int)$u['id']) return true;
    return false;
}

function can_edit_note($nota = null) {
    $u = current_user();
    if (!$u) return false;
    if (in_array($u['rol'], array('admin', 'operativo'), true)) return true;
    if ($nota && $u['rol'] === 'disenador' && (int)$nota['disenador_id'] === (int)$u['id']) return true;
    return false;
}

function get_designers($pdo) {
    $stmt = $pdo->query("SELECT id, nombre FROM usuarios WHERE activo = 1 AND rol = 'disenador' ORDER BY nombre");
    return $stmt->fetchAll();
}

function recalculate_saldo($pdo, $nota_id) {
    $stmt = $pdo->prepare('SELECT total, anticipo FROM notas WHERE id = ?');
    $stmt->execute(array($nota_id));
    $nota = $stmt->fetch();
    if (!$nota) return;
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(monto),0) AS abonos FROM abonos WHERE nota_id = ?');
    $stmt->execute(array($nota_id));
    $abonos = (float)$stmt->fetchColumn();
    $saldo = max(0, (float)$nota['total'] - (float)$nota['anticipo'] - $abonos);
    $upd = $pdo->prepare('UPDATE notas SET saldo = ? WHERE id = ?');
    $upd->execute(array($saldo, $nota_id));
}

function add_estado_historial($pdo, $nota_id, $anterior, $nuevo, $comentario = '') {
    $u = current_user();
    $usuario_id = $u ? $u['id'] : null;
    $stmt = $pdo->prepare('INSERT INTO estado_historial (nota_id, estado_anterior, estado_nuevo, comentario, usuario_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(array($nota_id, $anterior, $nuevo, $comentario, $usuario_id));
}
