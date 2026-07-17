<?php
class SimplePdf {
    private $pages = array();
    private $current = '';
    private $w;
    private $h;

    public function __construct($size = 'letter') {
        if ($size === 'a4') { $this->w = 595.28; $this->h = 841.89; }
        else { $this->w = 612; $this->h = 792; }
        $this->addPage();
    }

    public function width() { return $this->w; }
    public function height() { return $this->h; }

    public function addPage() {
        if ($this->current !== '') $this->pages[] = $this->current;
        $this->current = '';
    }

    private function y($topY) { return $this->h - $topY; }
    private function enc($text) {
        $text = str_replace(array("\r\n", "\r"), "\n", (string)$text);
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        $text = str_replace(array('\\','(',')'), array('\\\\','\\(','\\)'), $text);
        return $text;
    }

    public function text($x, $y, $text, $size = 10, $bold = false, $align = 'left', $width = 0) {
        $font = $bold ? 'F2' : 'F1';
        $txt = $this->enc($text);
        if ($align !== 'left' && $width > 0) {
            $approx = strlen($text) * $size * 0.48;
            if ($align === 'center') $x += max(0, ($width - $approx) / 2);
            if ($align === 'right') $x += max(0, $width - $approx);
        }
        $this->current .= "BT /{$font} {$size} Tf {$x} " . $this->y($y) . " Td ({$txt}) Tj ET\n";
    }

    public function line($x1, $y1, $x2, $y2, $width = 1, $r = 0, $g = 0, $b = 0) {
        $this->current .= sprintf("%.3f %.3f %.3f RG %.2f w %.2f %.2f m %.2f %.2f l S\n", $r, $g, $b, $width, $x1, $this->y($y1), $x2, $this->y($y2));
    }

    public function rect($x, $y, $w, $h, $stroke = true, $fill = false, $r = 1, $g = 1, $b = 1) {
        $op = $fill && $stroke ? 'B' : ($fill ? 'f' : 'S');
        $this->current .= sprintf("%.3f %.3f %.3f rg %.3f %.3f %.3f RG %.2f %.2f %.2f %.2f re {$op}\n", $r, $g, $b, $r, $g, $b, $x, $this->y($y + $h), $w, $h);
    }

    public function wrapLines($text, $maxChars) {
        $paragraphs = explode("\n", str_replace(array("\r\n", "\r"), "\n", (string)$text));
        $lines = array();
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') { $lines[] = ''; continue; }
            $words = preg_split('/\s+/', $p);
            $line = '';
            foreach ($words as $word) {
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if (mb_strlen($candidate, 'UTF-8') > $maxChars && $line !== '') {
                    $lines[] = $line;
                    $line = $word;
                } else {
                    $line = $candidate;
                }
            }
            if ($line !== '') $lines[] = $line;
        }
        return $lines;
    }

    public function output($filename = 'documento.pdf') {
        if ($this->current !== '') {
            $this->pages[] = $this->current;
            $this->current = '';
        }
        $objects = array();
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $kids = array();
        $pageObjNums = array();
        $contentObjNums = array();
        $base = 5;
        for ($i = 0; $i < count($this->pages); $i++) {
            $pageObj = $base + ($i * 2);
            $contentObj = $pageObj + 1;
            $pageObjNums[] = $pageObj;
            $contentObjNums[] = $contentObj;
            $kids[] = $pageObj . " 0 R";
        }
        $objects[] = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count " . count($this->pages) . " >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        for ($i = 0; $i < count($this->pages); $i++) {
            $pageObj = $pageObjNums[$i];
            $contentObj = $contentObjNums[$i];
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->w} {$this->h}] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentObj} 0 R >>";
            $content = $this->pages[$i];
            $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = array(0);
        for ($i = 0; $i < count($objects); $i++) {
            $offsets[] = strlen($pdf);
            $objNum = $i + 1;
            $pdf .= $objNum . " 0 obj\n" . $objects[$i] . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename) . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}
