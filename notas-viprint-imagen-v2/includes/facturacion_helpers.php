<?php
if (file_exists(__DIR__ . '/actualizacion_20260924_helpers.php')) { require_once __DIR__ . '/actualizacion_20260924_helpers.php'; }
if (!defined('FACTURACION_HELPERS_LOADED')) {
    define('FACTURACION_HELPERS_LOADED', true);
}

function factura_can_manage() {
    if (!function_exists('current_user')) return false;
    $u = current_user();
    if (!$u) return false;
    return in_array($u['rol'], array('admin','direccion','administracion','operativo','asesor'), true);
}

function factura_can_admin() {
    if (!function_exists('current_user')) return false;
    $u = current_user();
    if (!$u) return false;
    return in_array($u['rol'], array('admin','direccion'), true);
}

function factura_public_url_base() {
    if (defined('FACTURA_PUBLIC_URL') && FACTURA_PUBLIC_URL) {
        return rtrim(FACTURA_PUBLIC_URL, '/') . '/';
    }
    return 'https://viprint.com.mx/factura/';
}

function factura_order_public_url_base() {
    if (function_exists('viprint_public_base_url')) {
        return viprint_public_base_url();
    }
    if (defined('PUBLIC_ORDER_URL') && PUBLIC_ORDER_URL) {
        return rtrim(PUBLIC_ORDER_URL, '/') . '/';
    }
    return 'https://viprint.com.mx/pedido/';
}

function factura_status_label($estado) {
    $map = array(
        'pendiente' => 'Pendiente',
        'en_revision' => 'En revisión',
        'datos_incorrectos' => 'Datos incorrectos',
        'facturada' => 'Facturada',
        'cancelada' => 'Cancelada'
    );
    return isset($map[$estado]) ? $map[$estado] : ucfirst(str_replace('_',' ', (string)$estado));
}

function factura_status_class($estado) {
    $map = array(
        'pendiente' => 'warning',
        'en_revision' => 'info',
        'datos_incorrectos' => 'danger',
        'facturada' => 'success',
        'cancelada' => 'secondary'
    );
    return isset($map[$estado]) ? $map[$estado] : 'secondary';
}

function factura_mark_note($nota_id, $requiere, $user_id = null) {
    $nota_id = (int)$nota_id;
    $requiere = (int)$requiere === 1 ? 1 : 0;
    if ($nota_id <= 0) return false;

    if (function_exists('vp_apply_invoice_flag')) {
        vp_apply_invoice_flag($nota_id, $requiere, $user_id);
    } else {
        $sets = array('requiere_factura = ?');
        $params = array($requiere);
        if (function_exists('column_exists') && column_exists('v2_notas','actualizado_por')) {
            $sets[] = 'actualizado_por = ?';
            $params[] = $user_id ?: (current_user()['id'] ?? null);
        }
        $params[] = $nota_id;
        $sql = 'UPDATE v2_notas SET ' . implode(', ', $sets) . ' WHERE id = ?';
        db()->prepare($sql)->execute($params);
    }

    if ($requiere === 1) {
        if (function_exists('column_exists') && column_exists('v2_notas','factura_requiere_fecha')) {
            db()->prepare('UPDATE v2_notas SET factura_requiere_fecha = COALESCE(factura_requiere_fecha, NOW()) WHERE id = ?')->execute(array($nota_id));
        }
        if (function_exists('column_exists') && column_exists('v2_notas','factura_limite_datos')) {
            db()->prepare('UPDATE v2_notas SET factura_limite_datos = COALESCE(factura_limite_datos, DATE_ADD(NOW(), INTERVAL 72 HOUR)) WHERE id = ?')->execute(array($nota_id));
        }
        if (function_exists('column_exists') && column_exists('v2_notas','factura_requiere_por')) {
            db()->prepare('UPDATE v2_notas SET factura_requiere_por = COALESCE(factura_requiere_por, ?) WHERE id = ?')->execute(array($user_id ?: (current_user()['id'] ?? null), $nota_id));
        }
    }

    factura_recalcular_iva_partida($nota_id);
    return true;
}

function factura_recalcular_iva_partida($nota_id) {
    $nota_id = (int)$nota_id;
    if ($nota_id <= 0) return;
    $stmt = db()->prepare('SELECT * FROM v2_notas WHERE id = ?');
    $stmt->execute(array($nota_id));
    $nota = $stmt->fetch();
    if (!$nota) return;

    db()->prepare("DELETE FROM v2_nota_partidas WHERE nota_id = ? AND LOWER(TRIM(descripcion)) IN ('iva 16% factura','iva 16 factura','iva factura','iva')")->execute(array($nota_id));
    $stmt = db()->prepare('SELECT COALESCE(SUM(total),0) FROM v2_nota_partidas WHERE nota_id = ?');
    $stmt->execute(array($nota_id));
    $subtotal = (float)$stmt->fetchColumn();
    $iva = 0.0;
    if ((int)$nota['requiere_factura'] === 1 && $subtotal > 0) {
        $iva = function_exists('vp_calc_iva') ? vp_calc_iva($subtotal) : round($subtotal * 0.16, 2);
        db()->prepare("INSERT INTO v2_nota_partidas (nota_id,empresa_id,catalogo_id,tipo,descripcion,cantidad,precio_unitario,precio_especial,total,costo_estimado_material,costo_estimado_mano_obra,costo_estimado_maquila,costo_estimado_instalacion,costo_real_material,costo_real_mano_obra,costo_real_maquila,costo_real_instalacion) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute(array($nota_id,(int)$nota['empresa_id'],null,'otro',defined('VIPRINT_IVA_DESC') ? VIPRINT_IVA_DESC : 'IVA 16% factura',1,$iva,0,$iva,0,0,0,0,0,0,0,0));
    }
    if (function_exists('recalcular_nota')) recalcular_nota($nota_id);
    if (function_exists('vp_update_note_iva_fields')) vp_update_note_iva_fields($nota_id, $subtotal, $iva);
}

function factura_upload_base_dir() {
    return __DIR__ . '/../uploads/facturas';
}

function factura_ensure_upload_dirs() {
    $base = factura_upload_base_dir();
    $dirs = array($base, $base . '/constancias', $base . '/emitidas');
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
    $ht = $base . '/.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Options -Indexes\n<FilesMatch \".*\">\nRequire all denied\n</FilesMatch>\n");
    }
    return is_dir($base . '/constancias') && is_dir($base . '/emitidas');
}

function factura_safe_upload($field, $subdir, $allowed_ext, $prefix) {
    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) return '';
    if (!isset($_FILES[$field]['error']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se pudo subir el archivo: ' . $field);
    }
    $max = 8 * 1024 * 1024;
    if ((int)$_FILES[$field]['size'] > $max) {
        throw new Exception('El archivo es demasiado grande. Máximo 8 MB.');
    }
    $original = (string)$_FILES[$field]['name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        throw new Exception('Formato no permitido para ' . $field . '.');
    }
    factura_ensure_upload_dirs();
    $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix);
    $filename = $safePrefix . '_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.' . $ext;
    $rel = 'uploads/facturas/' . trim($subdir, '/') . '/' . $filename;
    $dest = __DIR__ . '/../' . $rel;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        throw new Exception('No se pudo guardar el archivo en el servidor.');
    }
    return $rel;
}

function factura_stream_file($rel_path) {
    $rel_path = (string)$rel_path;
    if ($rel_path === '' || strpos($rel_path, '..') !== false || strpos($rel_path, '\\') !== false) {
        http_response_code(404);
        exit('Archivo no válido.');
    }
    $full = realpath(__DIR__ . '/../' . $rel_path);
    $base = realpath(__DIR__ . '/../uploads/facturas');
    if (!$full || !$base || strpos($full, $base) !== 0 || !is_file($full)) {
        http_response_code(404);
        exit('Archivo no encontrado.');
    }
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $types = array('pdf'=>'application/pdf','xml'=>'application/xml','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp');
    $type = isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
    header('Content-Type: ' . $type);
    header('Content-Length: ' . filesize($full));
    header('Content-Disposition: inline; filename="' . basename($full) . '"');
    readfile($full);
    exit;
}

function factura_limite_text($nota) {
    if (empty($nota['factura_limite_datos'])) return '72 horas después de solicitar factura';
    return date_mx($nota['factura_limite_datos']);
}

function factura_is_expired($nota) {
    if (empty($nota['factura_limite_datos'])) return false;
    return strtotime($nota['factura_limite_datos']) < time();
}
