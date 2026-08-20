<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - Minimal pure-PHP PDF generator.
 *
 * Produces a single-page A4 PDF from a simple text model. No external deps.
 * Designed for the RFQ quote document: header, customer, line items, notes,
 * footer. Supports basic styling (bold, larger sizes) and ruled lines.
 *
 * Not a full HTML renderer — if you need HTML->PDF with CSS, vendor mPDF or
 * DOMPDF later. This is good enough for printable quotes.
 */
class Pdf
{
    /** @var string Path to a temp file used during assembly. */
    private $tmp_path;

    public function __construct()
    {
        $this->tmp_path = sys_get_temp_dir() . '/vp_pdf_' . bin2hex(random_bytes(6)) . '.tmp';
    }

    /**
     * Build the PDF binary.
     *
     * @param array $doc {
     *   @var string $title
     *   @var string $subtitle
     *   @var string $meta_left   Lines printed top-left under the title (e.g. site info)
     *   @var string $meta_right  Lines printed top-right (e.g. quote #, date)
     *   @var array  $columns     [ ['label' => '...', 'width' => 0..1, 'align' => 'L|C|R'], ... ]
     *   @var array  $rows        [ [col1, col2, ...], ... ]
     *   @var string $notes       Optional notes block (plain text, multi-line)
     *   @var string $footer      Footer text
     * }
     * @return string Binary PDF content
     */
    public function build(array $doc): string
    {
        $title    = $doc['title']    ?? 'Document';
        $subtitle = $doc['subtitle'] ?? '';
        $metaL    = (array) ($doc['meta_left']  ?? []);
        $metaR    = (array) ($doc['meta_right'] ?? []);
        $columns  = (array) ($doc['columns']    ?? []);
        $rows     = (array) ($doc['rows']       ?? []);
        $notes    = (string) ($doc['notes']    ?? '');
        $footer   = (string) ($doc['footer']   ?? '');

        // A4 portrait: 595.28 x 841.89 pt
        $W = 595.28; $H = 841.89;
        $ml = 50; $mr = 50; $mt = 50; $mb = 50;
        $contentW = $W - $ml - $mr;

        $objects = [];
        $addObj = function (string $body) use (&$objects) {
            $id = count($objects) + 1;
            $objects[$id] = $body;
            return $id;
        };

        // Font and page-tree setup are deferred; build content stream directly.

        // --- Build content stream ---
        $stream = "q\n"; // save state
        $y = $H - $mt;  // current baseline
        $cursorX = $ml;
        $cursorY = $y;

        // Helper to advance cursor
        $advanceY = function (float $delta) use (&$cursorY) { $cursorY -= $delta; };

        // ---- Title ----
        $this->pdfText($stream, $title, $ml, $cursorY, 22, true, '0 0.3 0.7 rg');
        $advanceY(28);
        if ($subtitle !== '') {
            $this->pdfText($stream, $subtitle, $ml, $cursorY, 11, false, '0.4 0.4 0.4 rg');
            $advanceY(18);
        }

        // ---- Meta row (left + right) ----
        $metaTopY = $cursorY;
        foreach ($metaL as $i => $line) {
            $this->pdfText($stream, $line, $ml, $cursorY - ($i * 14), 9, false, '0.2 0.2 0.2 rg');
        }
        foreach ($metaR as $i => $line) {
            $this->pdfText($stream, $line, $W - $mr, $cursorY - ($i * 14), 9, $i === 0, '0.2 0.2 0.2 rg', 'R');
        }
        $advanceY(max(count($metaL), count($metaR)) * 14 + 8);

        // Divider line under header
        $stream .= sprintf("0.18 0.47 1.0 RG 1 w %.2f %.2f m %.2f %.2f l S\n", $ml, $cursorY, $W - $mr, $cursorY);
        $advanceY(16);

        // ---- Columns header row ----
        if (!empty($columns)) {
            $colWidths = [];
            $totalWeight = 0;
            foreach ($columns as $c) {
                $w = (float) ($c['width'] ?? 0);
                $colWidths[] = $w;
                $totalWeight += $w;
            }
            // Normalise
            $colWidths = array_map(fn($w) => $w / $totalWeight * $contentW, $colWidths);
            // Header
            $x = $ml;
            foreach ($columns as $i => $c) {
                $this->pdfText($stream, $c['label'] ?? '', $x + 2, $cursorY, 9, true, '1 1 1 rg', $c['align'] ?? 'L');
                $x += $colWidths[$i];
            }
            // Header background
            $stream .= sprintf("0.93 0.96 1.0 rg %.2f %.2f %.2f %.2f re f\n", $ml, $cursorY - 4, $contentW, 16);
            $x = $ml;
            foreach ($columns as $i => $c) {
                $this->pdfText($stream, $c['label'] ?? '', $x + 2, $cursorY, 9, true, '0 0 0 rg', $c['align'] ?? 'L');
                $x += $colWidths[$i];
            }
            $advanceY(16);
            // Header underline
            $stream .= sprintf("0.85 0.85 0.85 RG 0.5 w %.2f %.2f m %.2f %.2f l S\n", $ml, $cursorY, $W - $mr, $cursorY);
        }

        // ---- Rows ----
        $rowHeight = 16;
        $pageBreakAt = $mb + 60; // keep some margin for footer
        foreach ($rows as $r) {
            if ($cursorY - $rowHeight < $pageBreakAt) {
                $stream .= "Q\n"; // restore state
                $stream .= $this->newPage();
                $stream .= "q\n";
                $cursorY = $H - $mt;
                // Redraw header on new page
                $this->pdfText($stream, $title, $ml, $cursorY, 18, true, '0 0.3 0.7 rg');
                $advanceY(22);
                $stream .= sprintf("0.18 0.47 1.0 RG 1 w %.2f %.2f m %.2f %.2f l S\n", $ml, $cursorY, $W - $mr, $cursorY);
                $advanceY(14);
            }
            $x = $ml;
            $col = 0;
            foreach ($r as $cell) {
                $cellStr = (string) $cell;
                $align = $columns[$col]['align'] ?? 'L';
                $this->pdfText($stream, $cellStr, $x + 2, $cursorY, 9, false, '0.1 0.1 0.1 rg', $align);
                $x += $colWidths[$col];
                $col++;
            }
            // Row separator
            $stream .= sprintf("0.9 0.9 0.9 RG 0.3 w %.2f %.2f m %.2f %.2f l S\n", $ml, $cursorY - 4, $W - $mr, $cursorY - 4);
            $advanceY($rowHeight);
        }

        // ---- Notes ----
        if ($notes !== '') {
            $advanceY(10);
            $this->pdfText($stream, 'Notes', $ml, $cursorY, 11, true, '0 0 0 rg');
            $advanceY(14);
            $wrapped = wordwrap($notes, 90, "\n", true);
            foreach (explode("\n", $wrapped) as $line) {
                $this->pdfText($stream, $line, $ml, $cursorY, 9, false, '0.2 0.2 0.2 rg');
                $advanceY(13);
            }
        }

        // ---- Footer ----
        $footerY = $mb - 10;
        $stream .= sprintf("0.9 0.9 0.9 RG 0.5 w %.2f %.2f m %.2f %.2f l S\n", $ml, $footerY + 18, $W - $mr, $footerY + 18);
        $this->pdfText($stream, $footer, $ml, $footerY, 8, false, '0.5 0.5 0.5 rg');

        $stream .= "Q\n";

        $contentObjId = $addObj("<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream");
        $fontObjId   = $addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>");
        $fontBoldId  = $addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>");

        $pagesId = $addObj("<< /Type /Pages /Kids [ 2 0 R ] /Count 1 >>");
        $pageId  = $addObj(sprintf("<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> /Contents %d 0 R >>",
            $pagesId, $W, $H, $fontObjId, $fontBoldId, $contentObjId));

        // Override the page resource /Parent to point at the real pages id (and Kids at the real page)
        $objects[$pageId]  = sprintf("<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> /Contents %d 0 R >>",
            $pagesId, $W, $H, $fontObjId, $fontBoldId, $contentObjId);
        $objects[$pagesId] = "<< /Type /Pages /Kids [ " . $pageId . " 0 R ] /Count 1 >>";

        $catalogId = $addObj("<< /Type /Catalog /Pages " . $pagesId . " 0 R >>");

        // Assemble the PDF
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root " . $catalogId . " 0 R >>\nstartxref\n" . $xrefStart . "\n%%EOF\n";

        return $pdf;
    }

    /**
     * Emit a text run into the content stream.
     * $align: 'L' (left), 'C' (center), 'R' (right). Coordinates are for the start of the text.
     */
    private function pdfText(string &$stream, string $text, float $x, float $y, float $size, bool $bold, string $color, string $align = 'L'): void
    {
        $text = $this->pdfEscape($text);
        if ($text === '') return;
        $font = $bold ? '/F2' : '/F1';
        $stream .= "BT\n" . $color . "\n" . $font . " " . sprintf('%.2f', $size) . " Tf\n";
        if ($align !== 'L') {
            // Approximate text width using Helvetica metrics (1 char ≈ size * 0.5)
            $w = strlen($text) * $size * 0.5;
            if ($align === 'R') {
                $stream .= sprintf("1 0 0 1 %.2f %.2f Tm\n", $x - $w, $y);
            } else { // C
                $stream .= sprintf("1 0 0 1 %.2f %.2f Tm\n", $x - $w / 2, $y);
            }
        } else {
            $stream .= sprintf("1 0 0 1 %.2f %.2f Tm\n", $x, $y);
        }
        $stream .= "(" . $text . ") Tj\nET\n";
    }

    private function pdfEscape(string $s): string
    {
        // Escape PDF string literals
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    /**
     * Return stream content for starting a new page (we always have 1 page,
     * so this is a no-op placeholder for when we extend this to multi-page).
     */
    private function newPage(): string
    {
        return '';
    }
}
