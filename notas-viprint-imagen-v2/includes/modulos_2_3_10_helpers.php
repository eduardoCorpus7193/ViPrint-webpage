<?php
// Helpers para actualización modular: corte diario mejorado, estado de pedidos y reportes generales.
// No modifica configuración. Se carga desde las pantallas nuevas y desde el menú.

if (!defined('APP_BOOTSTRAP_LOADED')) {
    require_once __DIR__ . '/bootstrap.php';
}

if (!function_exists('m2310_user_slug')) {
    function m2310_user_slug() {
        $u = current_user();
        if (!$u) return '';
        $raw = strtolower(trim(($u['usuario'] ?? '') . ' ' . ($u['nombre'] ?? '')));
        $raw = strtr($raw, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'));
        return preg_replace('/[^a-z0-9]+/', ' ', $raw);
    }
}

if (!function_exists('m2310_is_named_user')) {
    function m2310_is_named_user($names) {
        $slug = m2310_user_slug();
        foreach ((array)$names as $name) {
            $n = strtolower((string)$name);
            $n = strtr($n, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'));
            if ($n !== '' && strpos($slug, $n) !== false) return true;
        }
        return false;
    }
}

if (!function_exists('m2310_can_use_corte')) {
    function m2310_can_use_corte() {
        return role_in(array('admin','direccion','administracion','operativo','asesor')) || m2310_is_named_user(array('admin','mafer','danae','eduardo','luis'));
    }
}

if (!function_exists('m2310_can_close_corte')) {
    function m2310_can_close_corte() {
        return m2310_can_use_corte();
    }
}

if (!function_exists('m2310_can_close_corte_with_difference')) {
    function m2310_can_close_corte_with_difference() {
        return role_in(array('admin')) || m2310_is_named_user(array('admin'));
    }
}

if (!function_exists('m2310_can_reopen_corte')) {
    function m2310_can_reopen_corte() {
        return role_in(array('admin')) || m2310_is_named_user(array('admin'));
    }
}

if (!function_exists('m2310_can_view_reports')) {
    function m2310_can_view_reports() {
        return can_finance() || role_in(array('admin','direccion','administracion','asesor')) || m2310_is_named_user(array('admin','luis','mafer','eduardo'));
    }
}

if (!function_exists('m2310_notas_valid_sql')) {
    function m2310_notas_valid_sql($alias = 'n') {
        return column_exists('v2_notas', 'eliminada') ? " AND ($alias.eliminada = 0 OR $alias.eliminada IS NULL)" : '';
    }
}

if (!function_exists('m2310_pagos_valid_sql')) {
    function m2310_pagos_valid_sql($alias = 'p') {
        return column_exists('v2_pagos', 'anulado') ? " AND ($alias.anulado = 0 OR $alias.anulado IS NULL)" : '';
    }
}

if (!function_exists('m2310_caja_valid_sql')) {
    function m2310_caja_valid_sql($alias = 'cm') {
        return column_exists('v2_caja_movimientos', 'anulado') ? " AND ($alias.anulado = 0 OR $alias.anulado IS NULL)" : '';
    }
}

if (!function_exists('m2310_money_short')) {
    function m2310_money_short($n) {
        return '$' . number_format((float)$n, 2);
    }
}

if (!function_exists('m2310_estado_text')) {
    function m2310_estado_text($value) {
        $map = array(
            'pendiente' => 'Pendiente',
            'contactado' => 'Contactado',
            'cliente_no_contesta' => 'Cliente no contesta',
            'no_aplica' => 'No aplica',
            'sin_asignar' => 'Sin asignar',
            'pendiente_contacto' => 'Pendiente contacto',
            'en_diseno' => 'En diseño',
            'en_aprobacion' => 'En aprobación',
            'aprobado' => 'Aprobado',
            'autorizada' => 'Autorizada',
            'rechazada' => 'Rechazada',
            'para_imprimir' => 'Para imprimir',
            'impresa' => 'Impresa',
            'sublimada' => 'Sublimada',
            'en_costura' => 'En costura',
            'terminada' => 'Terminada',
            'problema' => 'Problema',
            'programada' => 'Programada',
            'en_instalacion' => 'En instalación',
            'instalada' => 'Instalada',
            'lista' => 'Lista',
            'entregada' => 'Entregada',
            'cancelada' => 'Cancelada',
            'sin_pago' => 'Sin pago',
            'anticipo' => 'Anticipo',
            'parcial' => 'Parcial',
            'liquidada' => 'Liquidada',
            'devolucion' => 'Devolución',
            'entrada' => 'Entrada',
            'salida' => 'Salida',
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'tarjeta' => 'Tarjeta',
            'otro' => 'Otro',
            'pago_cliente' => 'Pago cliente',
            'devolucion_cliente' => 'Devolución cliente',
            'gasto' => 'Gasto',
            'uber_envio' => 'Uber / envío',
            'entrega_luis' => 'Entrega a Luis',
            'prestamo_cambio' => 'Préstamo / cambio',
            'compra_menor' => 'Compra menor',
            'ajuste_caja' => 'Ajuste de caja',
            'retiro' => 'Retiro',
            'ajuste' => 'Ajuste'
        );
        return $map[$value] ?? ucwords(str_replace('_', ' ', (string)$value));
    }
}

if (!function_exists('m2310_badge')) {
    function m2310_badge($value) {
        $classMap = array(
            'contactado'=>'success','cliente_no_contesta'=>'warning','pendiente'=>'secondary','no_aplica'=>'light',
            'sin_asignar'=>'warning','pendiente_contacto'=>'warning','en_diseno'=>'primary','en_aprobacion'=>'info','aprobado'=>'success',
            'autorizada'=>'success','rechazada'=>'danger','para_imprimir'=>'primary','impresa'=>'info','sublimada'=>'info','en_costura'=>'primary','terminada'=>'success','problema'=>'danger',
            'programada'=>'primary','en_instalacion'=>'primary','instalada'=>'success','lista'=>'info','entregada'=>'success','cancelada'=>'danger',
            'sin_pago'=>'secondary','anticipo'=>'info','parcial'=>'warning','liquidada'=>'success','devolucion'=>'danger',
            'entrada'=>'success','salida'=>'danger','efectivo'=>'success','transferencia'=>'primary','tarjeta'=>'info','otro'=>'secondary'
        );
        $class = $classMap[$value] ?? 'secondary';
        return '<span class="badge text-bg-' . $class . '">' . h(m2310_estado_text($value)) . '</span>';
    }
}

if (!function_exists('m2310_progress_value')) {
    function m2310_progress_value($n) {
        $maps = array(
            'estado_contacto' => array('pendiente'=>0,'cliente_no_contesta'=>15,'contactado'=>100,'no_aplica'=>100),
            'estado_diseno' => array('sin_asignar'=>0,'pendiente_contacto'=>15,'en_diseno'=>45,'en_aprobacion'=>70,'aprobado'=>100,'no_aplica'=>100),
            'estado_aprobacion_impresion' => array('pendiente'=>20,'autorizada'=>100,'rechazada'=>10,'no_aplica'=>100),
            'estado_produccion' => array('pendiente'=>0,'para_imprimir'=>25,'impresa'=>50,'sublimada'=>70,'en_costura'=>85,'terminada'=>100,'problema'=>10,'no_aplica'=>100),
            'estado_instalacion' => array('no_aplica'=>100,'pendiente'=>20,'programada'=>60,'en_instalacion'=>80,'instalada'=>100),
            'estado_entrega' => array('pendiente'=>0,'lista'=>75,'entregada'=>100,'cancelada'=>0),
            'estado_pago' => array('sin_pago'=>0,'anticipo'=>35,'parcial'=>60,'liquidada'=>100,'devolucion'=>10,'cancelada'=>0)
        );
        $total = 0; $count = 0;
        foreach ($maps as $field => $map) {
            if (isset($n[$field])) {
                $value = $n[$field];
                $total += $map[$value] ?? 0;
                $count++;
            }
        }
        if ($count === 0) return 0;
        return max(0, min(100, (int)round($total / $count)));
    }
}

if (!function_exists('m2310_progress_class')) {
    function m2310_progress_class($value) {
        if ($value >= 85) return 'bg-success';
        if ($value >= 55) return 'bg-primary';
        if ($value >= 25) return 'bg-warning';
        return 'bg-secondary';
    }
}

if (!function_exists('m2310_note_alerts')) {
    function m2310_note_alerts($n) {
        $alerts = array();
        $today = date('Y-m-d');
        if (($n['estado_contacto'] ?? '') === 'cliente_no_contesta') $alerts[] = array('warning', 'Cliente no contesta');
        if (($n['estado_produccion'] ?? '') === 'problema') $alerts[] = array('danger', 'Problema en producción');
        if (($n['estado_diseno'] ?? '') === 'sin_asignar') $alerts[] = array('warning', 'Falta asignar diseño');
        if (!empty($n['fecha_promesa']) && $n['fecha_promesa'] < $today && !in_array(($n['estado_entrega'] ?? ''), array('entregada','cancelada'), true)) $alerts[] = array('danger', 'Atrasada');
        if ((float)($n['saldo'] ?? 0) > 0 && in_array(($n['estado_entrega'] ?? ''), array('lista','entregada'), true)) $alerts[] = array('warning', 'Tiene saldo pendiente');
        if (($n['estado_produccion'] ?? '') === 'en_costura') $alerts[] = array('primary', 'En costura');
        if (($n['estado_produccion'] ?? '') === 'terminada' && ($n['estado_entrega'] ?? '') !== 'entregada') $alerts[] = array('success', 'Producción terminada');
        return $alerts;
    }
}

if (!function_exists('m2310_alert_badges')) {
    function m2310_alert_badges($n) {
        $html = '';
        foreach (m2310_note_alerts($n) as $a) {
            $html .= '<span class="badge text-bg-' . h($a[0]) . ' me-1 mb-1">' . h($a[1]) . '</span>';
        }
        return $html ?: '<span class="text-muted small">Sin alerta</span>';
    }
}

if (!function_exists('m2310_corte_empresa_default_id')) {
    function m2310_corte_empresa_default_id() {
        $stmt = db()->query("SELECT id, clave, nombre FROM v2_empresas WHERE activo=1 ORDER BY id ASC");
        $first = 0;
        foreach ($stmt->fetchAll() as $e) {
            if ($first <= 0) $first = (int)$e['id'];
            if (strtolower($e['clave'] ?? '') === 'viprint' || stripos($e['nombre'] ?? '', 'viprint') !== false) return (int)$e['id'];
        }
        return $first;
    }
}

if (!function_exists('m2310_corte_resumen_movimientos')) {
    function m2310_corte_resumen_movimientos($fecha) {
        $res = array(
            'entradas_efectivo'=>0.0,'salidas_efectivo'=>0.0,'salidas_efectivo_operativas'=>0.0,'entrega_luis_sistema'=>0.0,
            'entradas_transferencia'=>0.0,'entradas_tarjeta'=>0.0,'entradas_otro'=>0.0,
            'salidas_transferencia'=>0.0,'salidas_tarjeta'=>0.0,'salidas_otro'=>0.0,
            'total_entradas'=>0.0,'total_salidas'=>0.0
        );
        if (!table_exists('v2_caja_movimientos')) return $res;
        $valid = m2310_caja_valid_sql('cm');
        $stmt = db()->prepare("SELECT cm.tipo, cm.concepto, cm.forma_pago, COALESCE(SUM(cm.monto),0) total FROM v2_caja_movimientos cm WHERE cm.fecha_operacion=? $valid GROUP BY cm.tipo, cm.concepto, cm.forma_pago");
        $stmt->execute(array($fecha));
        foreach ($stmt->fetchAll() as $r) {
            $monto = (float)$r['total'];
            $tipo = $r['tipo']; $forma = $r['forma_pago']; $concepto = $r['concepto'];
            if ($tipo === 'entrada') {
                $res['total_entradas'] += $monto;
                if ($forma === 'efectivo') $res['entradas_efectivo'] += $monto;
                elseif ($forma === 'transferencia') $res['entradas_transferencia'] += $monto;
                elseif ($forma === 'tarjeta') $res['entradas_tarjeta'] += $monto;
                else $res['entradas_otro'] += $monto;
            } else {
                $res['total_salidas'] += $monto;
                if ($forma === 'efectivo') {
                    $res['salidas_efectivo'] += $monto;
                    if ($concepto === 'entrega_luis') $res['entrega_luis_sistema'] += $monto;
                    else $res['salidas_efectivo_operativas'] += $monto;
                } elseif ($forma === 'transferencia') $res['salidas_transferencia'] += $monto;
                elseif ($forma === 'tarjeta') $res['salidas_tarjeta'] += $monto;
                else $res['salidas_otro'] += $monto;
            }
        }
        return $res;
    }
}

if (!function_exists('m2310_corte_detalle_movimientos')) {
    function m2310_corte_detalle_movimientos($fecha) {
        if (!table_exists('v2_caja_movimientos')) return array();
        $valid = m2310_caja_valid_sql('cm');
        $stmt = db()->prepare("SELECT cm.*, n.folio, n.cliente_nombre, e.nombre empresa, u.nombre usuario_nombre FROM v2_caja_movimientos cm LEFT JOIN v2_notas n ON n.id=cm.nota_id LEFT JOIN v2_empresas e ON e.id=cm.empresa_id LEFT JOIN v2_usuarios u ON u.id=cm.creado_por WHERE cm.fecha_operacion=? $valid ORDER BY cm.hora_operacion ASC, cm.id ASC");
        $stmt->execute(array($fecha));
        return $stmt->fetchAll();
    }
}

if (!function_exists('m2310_date_or_today')) {
    function m2310_date_or_today($value) {
        $value = trim((string)$value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
        return date('Y-m-d');
    }
}
