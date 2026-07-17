<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/SimplePdf.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quote = fetch_quote($pdo, $id);
if (!$quote) { http_response_code(404); exit('Cotización no encontrada.'); }
$items = fetch_quote_items($pdo, $id);

$pdf = new SimplePdf('letter');
$red = array(169/255, 38/255, 36/255);
$x = 42; $y = 40; $pageW = $pdf->width();

function pdf_header($pdf, &$y, $quote, $red) {
    $x = 42; $pageW = $pdf->width();
    $pdf->text($x, $y, BUSINESS_NAME, 20, true);
    $pdf->text($x, $y + 23, 'RFC: ' . BUSINESS_RFC, 9);
    $pdf->text($x, $y + 38, BUSINESS_ADDRESS, 8);
    $pdf->text($x, $y + 52, 'Tel. ' . BUSINESS_PHONE . ' | ' . BUSINESS_EMAIL, 8);
    $pdf->rect($pageW - 190, $y, 148, 54, true, false, 0.75, 0.75, 0.75);
    $pdf->text($pageW - 190, $y + 15, 'FOLIO', 8, false, 'center', 148);
    $pdf->text($pageW - 190, $y + 34, $quote['folio'], 14, true, 'center', 148);
    $pdf->line($x, $y + 72, $pageW - 42, $y + 72, 4, $red[0], $red[1], $red[2]);
    $y += 98;
    $pdf->text($x, $y, 'COTIZACIÓN FORMAL', 18, true, 'center', $pageW - 84);
    $y += 28;
}

pdf_header($pdf, $y, $quote, $red);
$pdf->rect($x, $y, $pageW - 84, 82, true, false, .82, .82, .82);
$pdf->text($x + 12, $y + 18, 'Cliente:', 10, true); $pdf->text($x + 85, $y + 18, $quote['cliente_nombre'], 10);
$pdf->text($x + 12, $y + 36, 'Negocio:', 10, true); $pdf->text($x + 85, $y + 36, $quote['cliente_negocio'], 10);
$pdf->text($x + 12, $y + 54, 'Teléfono:', 10, true); $pdf->text($x + 85, $y + 54, $quote['cliente_telefono'], 10);
$pdf->text($x + 300, $y + 18, 'Fecha:', 10, true); $pdf->text($x + 360, $y + 18, date_mx($quote['fecha']), 10);
$pdf->text($x + 300, $y + 36, 'Vigencia:', 10, true); $pdf->text($x + 360, $y + 36, $quote['validez_dias'] . ' días', 10);
$pdf->text($x + 300, $y + 54, 'Estatus:', 10, true); $pdf->text($x + 360, $y + 54, quote_status_label($quote['estatus']), 10);
$y += 104;

// Table header
$pdf->rect($x, $y, 55, 24, true, true, .95, .89, .89);
$pdf->rect($x+55, $y, 297, 24, true, true, .95, .89, .89);
$pdf->rect($x+352, $y, 80, 24, true, true, .95, .89, .89);
$pdf->rect($x+432, $y, 96, 24, true, true, .95, .89, .89);
$pdf->text($x+8, $y+16, 'Cant.', 9, true);
$pdf->text($x+63, $y+16, 'Descripción', 9, true);
$pdf->text($x+360, $y+16, 'P. unitario', 9, true);
$pdf->text($x+440, $y+16, 'Importe', 9, true);
$y += 24;
foreach ($items as $it) {
    $lines = $pdf->wrapLines($it['descripcion'], 58);
    $rowH = max(28, count($lines) * 12 + 12);
    if ($y + $rowH > 650) {
        $pdf->addPage(); $y = 40; pdf_header($pdf, $y, $quote, $red);
    }
    $pdf->rect($x, $y, 55, $rowH, true, false, .8, .8, .8);
    $pdf->rect($x+55, $y, 297, $rowH, true, false, .8, .8, .8);
    $pdf->rect($x+352, $y, 80, $rowH, true, false, .8, .8, .8);
    $pdf->rect($x+432, $y, 96, $rowH, true, false, .8, .8, .8);
    $pdf->text($x+8, $y+18, number_format((float)$it['cantidad'], 2), 9);
    $lineY = $y + 17;
    foreach ($lines as $ln) { $pdf->text($x+63, $lineY, $ln, 8); $lineY += 12; }
    $pdf->text($x+360, $y+18, money($it['precio_unitario']), 9);
    $pdf->text($x+440, $y+18, money($it['importe']), 9, true);
    $y += $rowH;
}
$y += 8;
$pdf->text($x+330, $y+14, 'Subtotal:', 10, true); $pdf->text($x+425, $y+14, money($quote['subtotal']), 10, true);
$y += 18;
if ($quote['aplicar_iva']) {
    $pdf->text($x+330, $y+14, 'IVA ' . $quote['porcentaje_iva'] . '%:', 10, true); $pdf->text($x+425, $y+14, money($quote['iva']), 10, true);
    $y += 18;
}
$pdf->line($x+330, $y, $x+528, $y, 1, $red[0], $red[1], $red[2]);
$pdf->text($x+330, $y+18, 'TOTAL:', 13, true); $pdf->text($x+425, $y+18, money($quote['total']), 13, true);
$y += 42;

if ($quote['observaciones']) {
    $pdf->text($x, $y, 'Observaciones:', 10, true); $y += 14;
    foreach ($pdf->wrapLines($quote['observaciones'], 100) as $ln) { $pdf->text($x, $y, $ln, 8); $y += 11; }
    $y += 8;
}
$pdf->text($x, $y, 'Condiciones comerciales:', 10, true); $y += 14;
foreach ($pdf->wrapLines($quote['condiciones'], 106) as $ln) {
    if ($y > 710) { $pdf->addPage(); $y = 50; }
    $pdf->text($x, $y, $ln, 7.5); $y += 10;
}
$y = max($y + 30, 690);
if ($y > 730) { $pdf->addPage(); $y = 670; }
$pdf->line($x+25, $y, $x+235, $y, 1);
$pdf->line($x+310, $y, $x+520, $y, 1);
$pdf->text($x+25, $y+15, 'Firma / aceptación del cliente', 9, true, 'center', 210);
$pdf->text($x+310, $y+15, BUSINESS_OWNER, 9, true, 'center', 210);
$pdf->text($x+310, $y+29, 'ViPrint Publicidad', 8, false, 'center', 210);

$pdf->output($quote['folio'] . '_ViPrint.pdf');
