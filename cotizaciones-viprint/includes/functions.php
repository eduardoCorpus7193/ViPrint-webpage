<?php
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($amount) {
    return '$' . number_format((float)$amount, 2) . ' MXN';
}

function date_mx($date) {
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : $date;
}

function redirect_to($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h($_SESSION['csrf_token']) . '">';
}

function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            exit('Token de seguridad inválido. Recarga la página e intenta de nuevo.');
        }
    }
}

function flash_set($type, $message) {
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function flash_get() {
    if (empty($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function generate_quote_folio($pdo, $date) {
    $year = date('Y', strtotime($date ?: date('Y-m-d')));
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM cotizaciones WHERE YEAR(fecha) = ?");
    $stmt->execute(array($year));
    $row = $stmt->fetch();
    $next = ((int)$row['total']) + 1;
    return 'COT-' . $year . '-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
}

function get_promociones($pdo, $onlyActive = true) {
    $sql = "SELECT * FROM promociones";
    if ($onlyActive) $sql .= " WHERE activo = 1";
    $sql .= " ORDER BY nombre ASC";
    return $pdo->query($sql)->fetchAll();
}

function quote_status_label($status) {
    $labels = array(
        'borrador' => 'Borrador',
        'enviada' => 'Enviada',
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada',
        'cancelada' => 'Cancelada'
    );
    return isset($labels[$status]) ? $labels[$status] : $status;
}

function badge_class($status) {
    $classes = array(
        'borrador' => 'secondary',
        'enviada' => 'primary',
        'aprobada' => 'success',
        'rechazada' => 'danger',
        'cancelada' => 'dark'
    );
    return isset($classes[$status]) ? $classes[$status] : 'secondary';
}

function fetch_quote($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
    $stmt->execute(array($id));
    return $stmt->fetch();
}

function fetch_quote_items($pdo, $quoteId) {
    $stmt = $pdo->prepare("SELECT * FROM cotizacion_items WHERE cotizacion_id = ? ORDER BY orden ASC, id ASC");
    $stmt->execute(array($quoteId));
    return $stmt->fetchAll();
}

function default_terms() {
    return "Cotización válida por 7 días naturales.\nSe requiere anticipo del 50% para iniciar producción, salvo acuerdo distinto.\nEl tiempo de entrega corre a partir de la aprobación del diseño y confirmación del anticipo.\nCambios posteriores a la aprobación pueden modificar precio y tiempo de entrega.\nLos precios no incluyen IVA salvo que se indique expresamente en la cotización.";
}
