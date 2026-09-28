<?php
if (!defined('APP_BOOTSTRAP_LOADED')) {
    define('APP_BOOTSTRAP_LOADED', true);
}
require_once __DIR__ . '/../config/app.php';

if (defined('APP_DEBUG') && APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

date_default_timezone_set('America/Mexico_City');

function app_error_page($title, $message, $detail = '') {
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Error del sistema</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body style="background:#F8F6F8">';
    echo '<div class="container py-5" style="max-width:900px"><div class="card shadow-sm"><div class="card-body p-4">';
    echo '<h1 class="h4 text-danger">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h1>';
    echo '<p>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</p>';
    if ($detail !== '') {
        echo '<pre class="bg-light border rounded p-3 small" style="white-space:pre-wrap">'.htmlspecialchars($detail, ENT_QUOTES, 'UTF-8').'</pre>';
    }
    echo '<p class="text-muted small mb-0">Este mensaje evita la pantalla en blanco. Revisa la conexión, que la base tenga las tablas V2 y que hayas ejecutado <code>database/update_caja_v2.sql</code>.</p>';
    echo '</div></div></div></body></html>';
    exit;
}

set_exception_handler(function($e) {
    app_error_page('Error interno del sistema', 'Ocurrió un error de PHP o base de datos.', $e->getMessage());
});

register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        app_error_page('Error fatal del sistema', 'El servidor detuvo la ejecución.', $e['message'].' en '.$e['file'].':'.$e['line']);
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
function asset_or_url($path) {
    $path = (string)$path;
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    return url($path);
}
function logo_src() {
    return asset_or_url(defined('LOGO_URL') ? LOGO_URL : 'assets/img/logo.png');
}
function redirect_to($path) {
    if (!headers_sent()) {
        header('Location: ' . url($path));
        exit;
    }
    echo '<script>location.href='.json_encode(url($path)).';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url='.h(url($path)).'"></noscript>';
    exit;
}
function flash($type, $message) {
    if (!isset($_SESSION['flash'])) $_SESSION['flash'] = array();
    $_SESSION['flash'][] = array('type' => $type, 'message' => $message);
}
function flashes() {
    $items = isset($_SESSION['flash']) ? $_SESSION['flash'] : array();
    unset($_SESSION['flash']);
    return $items;
}
function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
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
    return $u && ((isset($u['puede_ver_finanzas']) && (int)$u['puede_ver_finanzas'] === 1) || in_array($u['rol'], array('admin','direccion','administracion','asesor'), true));
}
function can_edit_prices() {
    $u = current_user();
    return $u && ((isset($u['puede_editar_precios']) && (int)$u['puede_editar_precios'] === 1) || in_array($u['rol'], array('admin','direccion','administracion','asesor'), true));
}
function can_delete_records() {
    $u = current_user();
    return $u && ((isset($u['puede_borrar']) && (int)$u['puede_borrar'] === 1) || in_array($u['rol'], array('admin','direccion'), true));
}
function can_register_payments() {
    return role_in(array('admin','direccion','administracion','operativo','disenador','externo','asesor'));
}
function can_cash() {
    return can_finance();
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
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute(array($table));
        return (int)$stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}
function column_exists($table, $column) {
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute(array($table, $column));
        return (int)$stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}
function empresas() {
    return db()->query('SELECT * FROM v2_empresas WHERE activo=1 ORDER BY nombre')->fetchAll();
}
function disenadores() {
    return db()->query('SELECT * FROM v2_usuarios WHERE activo=1 AND es_disenador=1 ORDER BY nombre')->fetchAll();
}
function usuarios_activos() {
    return db()->query('SELECT * FROM v2_usuarios WHERE activo=1 ORDER BY nombre')->fetchAll();
}
function recalcular_nota($nota_id) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) total,
        COALESCE(SUM(costo_estimado_material+costo_estimado_mano_obra+costo_estimado_maquila+costo_estimado_instalacion),0) costo_estimado,
        COALESCE(SUM(costo_real_material+costo_real_mano_obra+costo_real_maquila+costo_real_instalacion),0) costo_real
        FROM v2_nota_partidas WHERE nota_id=?");
    $stmt->execute(array($nota_id));
    $parts = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT 
        COALESCE(SUM(CASE WHEN concepto='devolucion' THEN 0 ELSE monto END),0) pagado,
        COALESCE(SUM(CASE WHEN concepto='devolucion' THEN monto ELSE 0 END),0) devolucion
        FROM v2_pagos WHERE nota_id=?");
    $stmt->execute(array($nota_id));
    $pays = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN afecta_ganancia=1 THEN GREATEST(costo_real,costo_estimado) ELSE 0 END),0) merma FROM v2_mermas WHERE nota_id=?");
    $stmt->execute(array($nota_id));
    $merma = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN aplica=1 THEN monto ELSE 0 END),0) comision FROM v2_comisiones WHERE nota_id=?");
    $stmt->execute(array($nota_id));
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
        $stmt->execute(array($nota_id));
        $fecha_liq = $stmt->fetchColumn() ?: date('Y-m-d');
    }
    $stmt = $pdo->prepare("UPDATE v2_notas SET total=?, pagado=?, saldo=?, devolucion_total=?, costo_estimado_total=?, costo_real_total=?, comision_total=?, merma_total=?, utilidad_estimada=?, utilidad_real=?, estado_pago=?, fecha_liquidacion=? WHERE id=?");
    $stmt->execute(array($total,$pagado,$saldo,$devolucion,$costo_est,$costo_real,$comision,$merma,$util_est,$util_real,$estado_pago,$fecha_liq,$nota_id));
}
function caja_totales_por_metodo($empresa_id, $fecha) {
    if (!table_exists('v2_caja_movimientos')) return array('efectivo'=>0.0,'transferencia'=>0.0,'tarjeta'=>0.0,'otro'=>0.0);
    $stmt = db()->prepare("SELECT forma_pago, COALESCE(SUM(CASE WHEN tipo='entrada' THEN monto ELSE -monto END),0) total FROM v2_caja_movimientos WHERE empresa_id=? AND fecha_operacion=? GROUP BY forma_pago");
    $stmt->execute(array($empresa_id, $fecha));
    $totales = array('efectivo'=>0.0,'transferencia'=>0.0,'tarjeta'=>0.0,'otro'=>0.0);
    foreach ($stmt->fetchAll() as $r) {
        $totales[$r['forma_pago']] = (float)$r['total'];
    }
    return $totales;
}
function caja_salidas_total($empresa_id, $fecha) {
    if (!table_exists('v2_caja_movimientos')) return 0.0;
    $stmt = db()->prepare("SELECT COALESCE(SUM(monto),0) FROM v2_caja_movimientos WHERE empresa_id=? AND fecha_operacion=? AND tipo='salida'");
    $stmt->execute(array($empresa_id, $fecha));
    return (float)$stmt->fetchColumn();
}
function estado_badge($value) {
    $map = array(
        'pendiente'=>'secondary','contactado'=>'info','cliente_no_contesta'=>'warning','sin_asignar'=>'warning','pendiente_contacto'=>'warning','en_diseno'=>'primary','en_aprobacion'=>'info','aprobado'=>'success','autorizada'=>'success','rechazada'=>'danger','para_imprimir'=>'primary','impresa'=>'info','sublimada'=>'info','en_costura'=>'primary','terminada'=>'success','programada'=>'primary','en_instalacion'=>'primary','instalada'=>'success','entregada'=>'success','cancelada'=>'danger','liquidada'=>'success','parcial'=>'warning','anticipo'=>'info','sin_pago'=>'secondary','problema'=>'danger','devolucion'=>'danger','lista'=>'info','no_aplica'=>'light','entrada'=>'success','salida'=>'danger'
    );
    $class = isset($map[$value]) ? $map[$value] : 'secondary';
    $labels = array(
        'cliente_no_contesta' => 'Cliente no contesta',
        'en_costura' => 'En costura',
        'terminada' => 'Terminada',
        'no_aplica' => 'No aplica',
        'para_imprimir' => 'Para imprimir',
        'sin_asignar' => 'Sin asignar',
        'pendiente_contacto' => 'Pendiente contacto',
        'en_diseno' => 'En diseño',
        'en_aprobacion' => 'En aprobación'
    );
    $label = isset($labels[$value]) ? $labels[$value] : str_replace('_',' ', $value);
    return '<span class="badge text-bg-'.$class.'">'.h($label).'</span>';
}
function logo_img($extra_class = '') {
    return '<img src="'.h(logo_src()).'" alt="ViPrint" class="'.h($extra_class).'" style="max-height:52px;width:auto">';
}
