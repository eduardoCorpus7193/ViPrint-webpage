<?php
if (!defined('VIPRINT_ACTUALIZACION_20260924')) {
    define('VIPRINT_ACTUALIZACION_20260924', true);
}

define('VIPRINT_IVA_RATE', 0.16);
define('VIPRINT_IVA_DESC', 'IVA 16% factura');

function vp_current_role() {
    $u = function_exists('current_user') ? current_user() : null;
    return $u['rol'] ?? '';
}

function vp_is_admin() {
    return vp_current_role() === 'admin';
}

function vp_can_manage_factura() {
    if (!function_exists('current_user') || !current_user()) return false;
    // Danae=operativo, Mafer=administracion, Luis=direccion, Eduardo=asesor, admin=admin.
    return in_array(vp_current_role(), array('admin','direccion','administracion','operativo','asesor'), true);
}

function vp_can_disable_factura() {
    return vp_is_admin();
}

function vp_can_view_quejas() {
    return function_exists('is_logged_in') && is_logged_in();
}

function vp_estado_label($campo, $valor) {
    $map = array(
        'estado_contacto' => array(
            'pendiente' => 'Pendiente',
            'contactado' => 'Contactado',
            'cliente_no_contesta' => 'Cliente no contesta',
            'no_aplica' => 'No aplica'
        ),
        'estado_diseno' => array(
            'sin_asignar' => 'Sin asignar',
            'pendiente_contacto' => 'Pendiente contacto',
            'en_diseno' => 'En diseño',
            'en_aprobacion' => 'En aprobación',
            'aprobado' => 'Aprobado',
            'no_aplica' => 'No aplica'
        ),
        'estado_aprobacion_impresion' => array(
            'pendiente' => 'Pendiente',
            'autorizada' => 'Autorizada',
            'rechazada' => 'Rechazada',
            'no_aplica' => 'No aplica'
        ),
        'estado_produccion' => array(
            'pendiente' => 'Pendiente',
            'para_imprimir' => 'Para imprimir',
            'impresa' => 'Impresa',
            'sublimada' => 'Sublimada',
            'en_costura' => 'En costura',
            'terminada' => 'Terminada',
            'problema' => 'Problema',
            'no_aplica' => 'No aplica'
        ),
        'estado_instalacion' => array(
            'no_aplica' => 'No aplica',
            'pendiente' => 'Pendiente',
            'programada' => 'Programada',
            'en_instalacion' => 'En instalación',
            'instalada' => 'Instalada'
        ),
        'estado_entrega' => array(
            'pendiente' => 'Pendiente',
            'lista' => 'Lista',
            'entregada' => 'Entregada',
            'cancelada' => 'Cancelada'
        ),
        'estado_pago' => array(
            'sin_pago' => 'Sin pago',
            'anticipo' => 'Anticipo',
            'parcial' => 'Parcial',
            'liquidada' => 'Liquidada',
            'devolucion' => 'Devolución',
            'cancelada' => 'Cancelada'
        ),
        'comentario' => array('comentario' => 'Comentario')
    );
    if (isset($map[$campo][$valor])) return $map[$campo][$valor];
    return ucfirst(str_replace('_', ' ', (string)$valor));
}

function vp_campo_estado_label($campo) {
    $map = array(
        'estado_contacto' => 'Contacto',
        'estado_diseno' => 'Diseño',
        'estado_aprobacion_impresion' => 'Aprob. impresión',
        'estado_produccion' => 'Producción',
        'estado_instalacion' => 'Instalación',
        'estado_entrega' => 'Entrega',
        'estado_pago' => 'Pago',
        'comentario' => 'Comentario'
    );
    return $map[$campo] ?? ucfirst(str_replace('_', ' ', (string)$campo));
}

function vp_fecha_estado($nota_id, $campo) {
    if (!function_exists('table_exists') || !table_exists('v2_estado_historial')) return null;
    $stmt = db()->prepare("SELECT h.*, u.nombre usuario FROM v2_estado_historial h LEFT JOIN v2_usuarios u ON u.id = h.usuario_id WHERE h.nota_id = ? AND h.campo = ? ORDER BY h.created_at DESC, h.id DESC LIMIT 1");
    $stmt->execute(array((int)$nota_id, $campo));
    $row = $stmt->fetch();
    return $row ?: null;
}

function vp_fechas_estado_resumen($nota_id) {
    $campos = array('estado_contacto','estado_diseno','estado_aprobacion_impresion','estado_produccion','estado_instalacion','estado_entrega','comentario');
    $out = array();
    foreach ($campos as $c) $out[$c] = vp_fecha_estado($nota_id, $c);
    return $out;
}

function vp_is_auto_iva_line($descripcion) {
    $d = trim(strtolower((string)$descripcion));
    return in_array($d, array('iva 16% factura', 'iva 16 factura', 'iva factura', 'iva'), true);
}

function vp_calc_iva($subtotal) {
    return round(max(0, (float)$subtotal) * VIPRINT_IVA_RATE, 2);
}

function vp_update_note_iva_fields($nota_id, $subtotal, $iva) {
    $sets = array();
    $params = array();
    if (function_exists('column_exists') && column_exists('v2_notas', 'subtotal_sin_iva')) {
        $sets[] = 'subtotal_sin_iva = ?';
        $params[] = (float)$subtotal;
    }
    if (function_exists('column_exists') && column_exists('v2_notas', 'iva_monto')) {
        $sets[] = 'iva_monto = ?';
        $params[] = (float)$iva;
    }
    if (!$sets) return;
    $params[] = (int)$nota_id;
    db()->prepare('UPDATE v2_notas SET '.implode(', ', $sets).' WHERE id = ?')->execute($params);
}

function vp_apply_invoice_flag($nota_id, $requiere, $user_id = null) {
    $nota_id = (int)$nota_id;
    $requiere = (int)$requiere === 1 ? 1 : 0;
    if ($nota_id <= 0) return false;
    if (!vp_can_manage_factura()) throw new Exception('No tienes permiso para modificar la opción de factura.');
    $stmt = db()->prepare('SELECT requiere_factura FROM v2_notas WHERE id = ?');
    $stmt->execute(array($nota_id));
    $old = $stmt->fetch();
    if (!$old) throw new Exception('Nota no encontrada.');
    if ((int)$old['requiere_factura'] === 1 && $requiere === 0 && !vp_can_disable_factura()) {
        throw new Exception('Solo admin puede quitar Requiere factura.');
    }
    db()->prepare('UPDATE v2_notas SET requiere_factura = ?, actualizado_por = ? WHERE id = ?')->execute(array($requiere, $user_id ?: (current_user()['id'] ?? null), $nota_id));
    return true;
}

function vp_public_pedido_url($nota) {
    $base = function_exists('viprint_public_base_url') ? viprint_public_base_url() : 'https://viprint.com.mx/pedido/';
    $folio = $nota['folio'] ?? '';
    $codigo = $nota['public_code'] ?? '';
    return rtrim($base, '/') . '/?folio=' . rawurlencode($folio) . '&codigo=' . rawurlencode($codigo);
}

function vp_queja_public_url_base() {
    if (defined('QUEJA_PUBLIC_URL') && QUEJA_PUBLIC_URL) return rtrim(QUEJA_PUBLIC_URL, '/') . '/';
    return 'https://viprint.com.mx/queja/';
}

function vp_queja_public_url($nota) {
    return vp_queja_public_url_base() . '?folio=' . rawurlencode($nota['folio'] ?? '') . '&codigo=' . rawurlencode($nota['public_code'] ?? '');
}

function vp_queja_tipo_label($tipo) {
    $map = array('queja'=>'Queja','sugerencia'=>'Sugerencia','comentario'=>'Comentario','duda'=>'Duda');
    return $map[$tipo] ?? ucfirst(str_replace('_',' ', (string)$tipo));
}

function vp_queja_estado_label($estado) {
    $map = array('nueva'=>'Nueva','en_revision'=>'En revisión','resuelta'=>'Resuelta','descartada'=>'Descartada');
    return $map[$estado] ?? ucfirst(str_replace('_',' ', (string)$estado));
}

function vp_queja_upload_dir() {
    return __DIR__ . '/../uploads/quejas';
}

function vp_queja_ensure_upload_dir() {
    $dir = vp_queja_upload_dir();
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $ht = $dir . '/.htaccess';
    if (!file_exists($ht)) @file_put_contents($ht, "Options -Indexes\n");
    return is_dir($dir);
}

function vp_queja_save_photo($field, $prefix) {
    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) return '';
    if (!isset($_FILES[$field]['error']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) throw new Exception('No se pudo subir la foto.');
    if ((int)$_FILES[$field]['size'] > 6 * 1024 * 1024) throw new Exception('La foto es demasiado grande. Máximo 6 MB.');
    $ext = strtolower(pathinfo((string)$_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg','jpeg','png','webp'), true)) throw new Exception('Formato de foto no permitido. Usa JPG, PNG o WEBP.');
    vp_queja_ensure_upload_dir();
    $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix ?: 'queja');
    $filename = $safePrefix . '_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.' . $ext;
    $rel = 'uploads/quejas/' . $filename;
    $dest = __DIR__ . '/../' . $rel;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) throw new Exception('No se pudo guardar la foto.');
    return $rel;
}
?>
