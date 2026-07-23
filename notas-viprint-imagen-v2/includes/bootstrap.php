<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function db() {
    global $pdo;
    return $pdo;
}
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}
function redirect_to($path) {
    header('Location: ' . url($path));
    exit;
}
function flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}
function flashes() {
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}
function current_user() {
    return $_SESSION['user'] ?? null;
}
function is_logged_in() {
    return current_user() !== null;
}
function require_login() {
    if (!is_logged_in()) {
        redirect_to('login.php');
    }
}
function role_in($roles) {
    $u = current_user();
    return $u && in_array($u['rol'], (array)$roles, true);
}
function can_finance() {
    $u = current_user();
    return $u && ((int)$u['puede_ver_finanzas'] === 1 || in_array($u['rol'], ['admin','direccion','administracion','asesor'], true));
}
function can_edit_prices() {
    $u = current_user();
    return $u && ((int)$u['puede_editar_precios'] === 1 || in_array($u['rol'], ['admin','direccion','administracion','asesor'], true));
}
function can_delete_records() {
    $u = current_user();
    return $u && ((int)$u['puede_borrar'] === 1 || in_array($u['rol'], ['admin','direccion'], true));
}
function can_register_payments() {
    return role_in(['admin','direccion','administracion','operativo','disenador','externo','asesor']);
}
function money($n) {
    return '$' . number_format((float)$n, 2) . ' MXN';
}
function date_mx($date) {
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : h($date);
}
function table_exists($table) {
    $stmt = db()->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}
function select_options($name, $options, $selected, $attrs='') {
    echo '<select name="'.h($name).'" '.$attrs.'>';
    foreach ($options as $k => $v) {
        $sel = ((string)$selected === (string)$k) ? ' selected' : '';
        echo '<option value="'.h($k).'"'.$sel.'>'.h($v).'</option>';
    }
    echo '</select>';
}
function empresas() {
    return db()->query("SELECT * FROM v2_empresas WHERE activo=1 ORDER BY nombre")->fetchAll();
}
function disenadores() {
    return db()->query("SELECT * FROM v2_usuarios WHERE activo=1 AND es_disenador=1 ORDER BY nombre")->fetchAll();
}
function usuarios_activos() {
    return db()->query("SELECT * FROM v2_usuarios WHERE activo=1 ORDER BY nombre")->fetchAll();
}
function recalcular_nota($nota_id) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) total,
        COALESCE(SUM(costo_estimado_material+costo_estimado_mano_obra+costo_estimado_maquila+costo_estimado_instalacion),0) costo_estimado,
        COALESCE(SUM(costo_real_material+costo_real_mano_obra+costo_real_maquila+costo_real_instalacion),0) costo_real
        FROM v2_nota_partidas WHERE nota_id=?");
    $stmt->execute([$nota_id]);
    $parts = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT 
        COALESCE(SUM(CASE WHEN concepto='devolucion' THEN 0 ELSE monto END),0) pagado,
        COALESCE(SUM(CASE WHEN concepto='devolucion' THEN monto ELSE 0 END),0) devolucion
        FROM v2_pagos WHERE nota_id=?");
    $stmt->execute([$nota_id]);
    $pays = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN afecta_ganancia=1 THEN GREATEST(costo_real,costo_estimado) ELSE 0 END),0) merma FROM v2_mermas WHERE nota_id=?");
    $stmt->execute([$nota_id]);
    $merma = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN aplica=1 THEN monto ELSE 0 END),0) comision FROM v2_comisiones WHERE nota_id=?");
    $stmt->execute([$nota_id]);
    $comision = (float)$stmt->fetchColumn();

    $total = (float)$parts['total'];
    $pagado = (float)$pays['pagado'];
    $devolucion = (float)$pays['devolucion'];
    $saldo = max(0, $total - $pagado + $devolucion);
    $costo_est = (float)$parts['costo_estimado'];
    $costo_real = (float)$parts['costo_real'];
    $util_est = $total - $costo_est - $comision - $merma;
    $util_real = $total - $costo_real - $comision - $merma;
    $estado_pago = 'sin_pago';
    if ($devolucion > 0) $estado_pago = 'devolucion';
    if ($pagado > 0 && $saldo > 0) $estado_pago = ($pagado < $total ? 'parcial' : 'anticipo');
    if ($total > 0 && $saldo <= 0) $estado_pago = 'liquidada';

    $fecha_liq = null;
    if ($estado_pago === 'liquidada') {
        $stmt = $pdo->prepare("SELECT MAX(fecha_pago) FROM v2_pagos WHERE nota_id=? AND concepto <> 'devolucion'");
        $stmt->execute([$nota_id]);
        $fecha_liq = $stmt->fetchColumn() ?: date('Y-m-d');
    }
    $stmt = $pdo->prepare("UPDATE v2_notas SET total=?, pagado=?, saldo=?, devolucion_total=?, costo_estimado_total=?, costo_real_total=?, comision_total=?, merma_total=?, utilidad_estimada=?, utilidad_real=?, estado_pago=?, fecha_liquidacion=? WHERE id=?");
    $stmt->execute([$total,$pagado,$saldo,$devolucion,$costo_est,$costo_real,$comision,$merma,$util_est,$util_real,$estado_pago,$fecha_liq,$nota_id]);
}
function estado_badge($value) {
    $map = [
        'pendiente'=>'secondary','contactado'=>'info','sin_asignar'=>'warning','pendiente_contacto'=>'warning','en_diseno'=>'primary','en_aprobacion'=>'info','aprobado'=>'success','autorizada'=>'success','rechazada'=>'danger','para_imprimir'=>'primary','impresa'=>'info','sublimada'=>'info','programada'=>'primary','en_instalacion'=>'primary','instalada'=>'success','entregada'=>'success','cancelada'=>'danger','liquidada'=>'success','parcial'=>'warning','anticipo'=>'info','sin_pago'=>'secondary','problema'=>'danger','devolucion'=>'danger','lista'=>'info','no_aplica'=>'light'
    ];
    $class = $map[$value] ?? 'secondary';
    return '<span class="badge text-bg-'.$class.'">'.h(str_replace('_',' ', $value)).'</span>';
}
