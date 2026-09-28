<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if(!can_cash()){ flash('danger','No tienes permiso para caja.'); redirect_to('index.php'); }
if(!table_exists('v2_caja_movimientos')){ flash('danger','Falta instalar la actualización de caja.'); redirect_to('index.php'); }

$empresa_id = (int)($_POST['empresa_id'] ?? 0);
$fecha = $_POST['fecha_operacion'] ?? date('Y-m-d');
$tipo = $_POST['tipo'] ?? 'salida';
$concepto = $_POST['concepto'] ?? 'gasto';
$forma = $_POST['forma_pago'] ?? 'efectivo';
$monto = (float)($_POST['monto'] ?? 0);
$allowedTipos = array('entrada','salida');
$allowedConceptos = array('gasto','uber_envio','devolucion_cliente','entrega_luis','prestamo_cambio','compra_menor','ajuste_caja','otro','retiro','ajuste','pago_cliente');
$allowedFormas = array('efectivo','transferencia','tarjeta','otro');

if (!in_array($tipo, $allowedTipos, true)) $tipo = 'salida';
if (!in_array($concepto, $allowedConceptos, true)) $concepto = 'otro';
if (!in_array($forma, $allowedFormas, true)) $forma = 'efectivo';

try {
    if (table_exists('v2_cortes_caja')) {
        $stmt = db()->prepare('SELECT cerrado FROM v2_cortes_caja WHERE fecha_corte=?');
        $stmt->execute(array($fecha));
        if ((int)$stmt->fetchColumn() === 1) {
            flash('danger','No puedes registrar movimientos en esta fecha porque el corte diario ya está cerrado.');
            redirect_to('caja.php?fecha='.$fecha);
        }
    }

    if($empresa_id <= 0 || $monto <= 0){
        throw new Exception('Datos incompletos. Revisa empresa y monto.');
    }

    $stmt=db()->prepare("INSERT INTO v2_caja_movimientos (empresa_id,fecha_operacion,hora_operacion,tipo,concepto,forma_pago,forma_pago_otro,descripcion,monto,referencia,creado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute(array($empresa_id,$fecha,date('H:i:s'),$tipo,$concepto,$forma,trim($_POST['forma_pago_otro']??''),trim($_POST['descripcion']??''),$monto,trim($_POST['referencia']??''),current_user()['id']));
    flash('success','Movimiento de caja registrado.');
}catch(Exception $e){ flash('danger','Error al guardar movimiento: '.$e->getMessage()); }
redirect_to('caja.php?fecha='.$fecha);
