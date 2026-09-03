<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if (!can_cash()) { flash('danger','No tienes permiso para guardar cortes.'); redirect_to('index.php'); }
if (!table_exists('v2_cortes_caja')) { flash('danger','Falta instalar la actualización de corte diario.'); redirect_to('index.php'); }
if (!table_exists('v2_caja_movimientos')) { flash('danger','Falta instalar caja.'); redirect_to('index.php'); }

function corte_resumen_movimientos_guardar($fecha) {
    $res = array(
        'entradas_efectivo'=>0.0,'salidas_efectivo'=>0.0,'salidas_efectivo_operativas'=>0.0,'entrega_luis_sistema'=>0.0,
        'entradas_transferencia'=>0.0,'entradas_tarjeta'=>0.0,'entradas_otro'=>0.0,
        'salidas_transferencia'=>0.0,'salidas_tarjeta'=>0.0,'salidas_otro'=>0.0,
        'total_entradas'=>0.0,'total_salidas'=>0.0
    );
    $stmt = db()->prepare("SELECT tipo, concepto, forma_pago, COALESCE(SUM(monto),0) total FROM v2_caja_movimientos WHERE fecha_operacion=? GROUP BY tipo, concepto, forma_pago");
    $stmt->execute(array($fecha));
    foreach($stmt->fetchAll() as $r) {
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

function corte_empresa_default_id() {
    $stmt = db()->query("SELECT id, clave, nombre FROM v2_empresas WHERE activo=1 ORDER BY id ASC");
    $first = 0;
    foreach($stmt->fetchAll() as $e) {
        if ($first <= 0) $first = (int)$e['id'];
        if (stripos($e['nombre'], 'viprint') !== false || strtolower($e['clave'] ?? '') === 'viprint') return (int)$e['id'];
    }
    return $first;
}

$fecha = $_POST['fecha_corte'] ?? date('Y-m-d');
$accion = $_POST['accion'] ?? 'guardar';
$isClosing = $accion === 'cerrar';
$isAdmin = role_in(array('admin'));

$fondoInicial = (float)($_POST['fondo_inicial'] ?? 0);
$fondoBase = (float)($_POST['fondo_base'] ?? 800);
$efectivoContado = (float)($_POST['efectivo_contado'] ?? 0);
$entregaReal = (float)($_POST['entrega_luis_real'] ?? 0);
$observaciones = trim($_POST['observaciones'] ?? '');
$entregaNombre = trim($_POST['entrega_nombre'] ?? '');
$recibeNombre = trim($_POST['recibe_nombre'] ?? 'Luis');
$horaEntrega = trim($_POST['hora_entrega'] ?? date('H:i'));
if ($horaEntrega === '') $horaEntrega = date('H:i');
if (strlen($horaEntrega) === 5) $horaEntrega .= ':00';

try {
    if ($fondoBase < 0 || $fondoInicial < 0 || $efectivoContado < 0 || $entregaReal < 0) {
        throw new Exception('Los importes no pueden ser negativos.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM v2_cortes_caja WHERE fecha_corte=? FOR UPDATE');
    $stmt->execute(array($fecha));
    $prev = $stmt->fetch();
    if ($prev && (int)$prev['cerrado'] === 1 && !$isAdmin) {
        throw new Exception('Este corte ya está cerrado. Solo admin puede corregirlo.');
    }

    $mov = corte_resumen_movimientos_guardar($fecha);
    $cajaEsperada = $fondoInicial + $mov['entradas_efectivo'] - $mov['salidas_efectivo_operativas'];
    $diferencia = $efectivoContado - $cajaEsperada;
    $entregaSugerida = max(0, $efectivoContado - $fondoBase);
    $fondoFinal = $efectivoContado - $entregaReal;

    if ($isClosing && abs($diferencia) > 0.009 && !$isAdmin) {
        throw new Exception('Hay diferencia de caja. Solo un usuario admin puede cerrar un corte con diferencia. Guarda borrador y pide autorización.');
    }

    $cerrado = $isClosing ? 1 : (($prev && (int)$prev['cerrado'] === 1) ? 1 : 0);
    $cerradoPor = $isClosing ? (int)current_user()['id'] : ($prev['cerrado_por'] ?? null);
    $cerradoAt = $isClosing ? date('Y-m-d H:i:s') : ($prev['cerrado_at'] ?? null);

    $sql = "INSERT INTO v2_cortes_caja (
        fecha_corte,fondo_inicial,fondo_base,entradas_efectivo,salidas_efectivo,salidas_efectivo_operativas,entrega_luis_sistema,
        entradas_transferencia,entradas_tarjeta,entradas_otro,salidas_transferencia,salidas_tarjeta,salidas_otro,total_entradas,total_salidas,
        caja_esperada,efectivo_contado,diferencia_efectivo,entrega_luis_sugerida,entrega_luis_real,fondo_final,observaciones,entrega_nombre,recibe_nombre,hora_entrega,cerrado,realizado_por,cerrado_por,cerrado_at
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
        fondo_inicial=VALUES(fondo_inicial), fondo_base=VALUES(fondo_base), entradas_efectivo=VALUES(entradas_efectivo), salidas_efectivo=VALUES(salidas_efectivo), salidas_efectivo_operativas=VALUES(salidas_efectivo_operativas), entrega_luis_sistema=VALUES(entrega_luis_sistema),
        entradas_transferencia=VALUES(entradas_transferencia), entradas_tarjeta=VALUES(entradas_tarjeta), entradas_otro=VALUES(entradas_otro), salidas_transferencia=VALUES(salidas_transferencia), salidas_tarjeta=VALUES(salidas_tarjeta), salidas_otro=VALUES(salidas_otro), total_entradas=VALUES(total_entradas), total_salidas=VALUES(total_salidas),
        caja_esperada=VALUES(caja_esperada), efectivo_contado=VALUES(efectivo_contado), diferencia_efectivo=VALUES(diferencia_efectivo), entrega_luis_sugerida=VALUES(entrega_luis_sugerida), entrega_luis_real=VALUES(entrega_luis_real), fondo_final=VALUES(fondo_final), observaciones=VALUES(observaciones), entrega_nombre=VALUES(entrega_nombre), recibe_nombre=VALUES(recibe_nombre), hora_entrega=VALUES(hora_entrega), cerrado=VALUES(cerrado), realizado_por=VALUES(realizado_por), cerrado_por=VALUES(cerrado_por), cerrado_at=VALUES(cerrado_at)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        $fecha,$fondoInicial,$fondoBase,$mov['entradas_efectivo'],$mov['salidas_efectivo'],$mov['salidas_efectivo_operativas'],$mov['entrega_luis_sistema'],
        $mov['entradas_transferencia'],$mov['entradas_tarjeta'],$mov['entradas_otro'],$mov['salidas_transferencia'],$mov['salidas_tarjeta'],$mov['salidas_otro'],$mov['total_entradas'],$mov['total_salidas'],
        $cajaEsperada,$efectivoContado,$diferencia,$entregaSugerida,$entregaReal,$fondoFinal,$observaciones,$entregaNombre,$recibeNombre,$horaEntrega,$cerrado,(int)current_user()['id'],$cerradoPor,$cerradoAt
    ));

    $stmt = $pdo->prepare('SELECT * FROM v2_cortes_caja WHERE fecha_corte=?');
    $stmt->execute(array($fecha));
    $corte = $stmt->fetch();
    $corteId = (int)$corte['id'];

    if ($isClosing && $entregaReal > 0) {
        $empresaId = corte_empresa_default_id();
        if ($empresaId <= 0) throw new Exception('No hay empresa activa para registrar la entrega a Luis.');
        $descripcion = 'Entrega a Luis generada desde corte diario del ' . date_mx($fecha);
        $referencia = 'CORTE-' . $corteId;
        $movId = isset($corte['entrega_movimiento_id']) ? (int)$corte['entrega_movimiento_id'] : 0;
        if ($movId > 0) {
            $stmt = $pdo->prepare("UPDATE v2_caja_movimientos SET empresa_id=?, fecha_operacion=?, hora_operacion=?, tipo='salida', concepto='entrega_luis', forma_pago='efectivo', descripcion=?, monto=?, referencia=?, creado_por=? WHERE id=?");
            $stmt->execute(array($empresaId,$fecha,$horaEntrega,$descripcion,$entregaReal,$referencia,(int)current_user()['id'],$movId));
        } else {
            $stmt = $pdo->prepare("INSERT INTO v2_caja_movimientos (empresa_id,fecha_operacion,hora_operacion,tipo,concepto,forma_pago,descripcion,monto,referencia,creado_por) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute(array($empresaId,$fecha,$horaEntrega,'salida','entrega_luis','efectivo',$descripcion,$entregaReal,$referencia,(int)current_user()['id']));
            $movId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('UPDATE v2_cortes_caja SET entrega_movimiento_id=? WHERE id=?');
            $stmt->execute(array($movId,$corteId));
        }
    }

    $pdo->commit();
    flash('success', $isClosing ? 'Corte cerrado y entrega a Luis registrada.' : 'Corte guardado como borrador.');
    if ($isClosing) redirect_to('corte_ticket.php?id='.$corteId);
    redirect_to('corte_diario.php?fecha='.$fecha);
} catch (Exception $e) {
    if (db()->inTransaction()) db()->rollBack();
    flash('danger','Error al guardar corte: '.$e->getMessage());
    redirect_to('corte_diario.php?fecha='.$fecha);
}
