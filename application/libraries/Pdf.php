<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — minimal pure-PHP PDF generator.
 *
 * Produces branded A4 PDF quotations without any external dependency
 * (no Composer, no mPDF — cPanel friendly). Supports:
 *   - branded header band with company name + tagline
 *   - meta blocks (quote number, dates, validity, customer)
 *   - a multi-column line-item table with wrapped text and page breaks
 *   - totals block, notes/terms and a branded footer on every page
 *
 * It is deliberately not an HTML renderer; the quote document model is
 * plain text and columnar.
 */
class Pdf
{
    /** @var float Page geometry (A4 portrait, points) */
    private $W = 595.28;
    private $H = 841.89;
    private $ml = 46;
    private $mr = 46;
    private $mb = 54;

    /** Brand colours (R G B in 0..1) */
    private $ink   = '0.03 0.12 0.25';   // deep navy
    private $blue  = '0.05 0.31 0.62';   // Halyk blue
    private $amber = '0.96 0.65 0.14';   // Halyk amber
    private $grey  = '0.45 0.45 0.45';
    private $light = '0.93 0.95 0.98';

    /** @var array<array{id:int,body:string}> */
    private $objects = [];

    /** @var array Content streams per page */
    private $pageStreams = [];
    private $stream = '';
    private $cursorY = 0;

    public function __construct()
    {
        // No-op; tmp files are not needed for the in-memory document.
    }

    /**
     * Build the PDF binary.
     *
     * @param array $doc {
     *   @var string $company      Company name (header)
     *   @var string $tagline      Tagline under the company name
     *   @var array  $company_info Lines of contact info (address, phone, email)
     *   @var string $title        Document title, e.g. "QUOTATION"
     *   @var array  $meta_left    Lines printed under the title (left block)
     *   @var array  $meta_right   Lines printed right (quote #, dates, validity)
     *   @var string $bill_to      Multi-line customer block
     *   @var string $ship_to      Optional multi-line delivery block
     *   @var array  $columns      [['label','width'=>float,'align'=>'L|C|R'], ...]
     *   @var array  $rows         [[...cells], ...]
     *   @var array  $totals       [['label'=>..., 'value'=>...,'bold'=>bool], ...]
     *   @var array  $notes_blocks [['heading'=>..., 'text'=>...], ...]
     *   @var string $footer       Footer line
     * }
     */
    public function build(array $doc): string
    {
        $contentW = $this->W - $this->ml - $this->mr;
        $this->pageStreams = [];
        $this->startPage();

        // ---------- Branded header band ----------
        $bandH = 64;
        $bandTop = $this->H - 30;
        $this->stream .= sprintf("%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n",
            0.03, 0.12, 0.25, 0, $bandTop - $bandH, $this->W, $bandH);
        // Amber accent stripe
        $this->stream .= sprintf("%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n",
            0.96, 0.65, 0.14, 0, $bandTop - $bandH - 4, $this->W, 4);
        // Wordmark ( Helvetica-Bold 20, white )
        $this->pdfText(strtoupper((string) ($doc['company'] ?? 'Halyk Petroleum')),
            $this->ml, $bandTop - 30, 20, true, '1 1 1');
        $this->pdfText((string) ($doc['tagline'] ?? 'Aircraft Parts & Components'),
            $this->ml, $bandTop - 46, 8.5, false, '0.85 0.88 0.95');
        // Company contact lines right-aligned inside the band
        $i = 0;
        foreach (array_slice((array) ($doc['company_info'] ?? []), 0, 3) as $line) {
            $this->pdfText((string) $line, $this->W - $this->mr, $bandTop - 28 - ($i * 12), 8, false, '0.9 0.93 0.98', 'R');
            $i++;
        }

        $this->cursorY = $bandTop - $bandH - 22;

        // ---------- Document title ----------
        $this->pdfText(strtoupper((string) ($doc['title'] ?? 'Quotation')),
            $this->ml, $this->cursorY, 17, true, $this->blue);
        $this->cursorY -= 20;

        // ---------- Meta blocks (left = customer/bill-to, right = quote data) ----------
        $metaL = (array) ($doc['meta_left'] ?? []);
        $metaR = (array) ($doc['meta_right'] ?? []);
        $startY = $this->cursorY;
        $lineH = 12.5;
        foreach ($metaL as $idx => $line) {
            $this->pdfText((string) $line, $this->ml, $startY - ($idx * $lineH), 8.7, $idx === 0, $this->ink);
        }
        foreach ($metaR as $idx => $line) {
            $this->pdfText((string) $line, $this->W - $this->mr, $startY - ($idx * $lineH), 8.7, false, $this->ink, 'R');
        }
        $this->cursorY = $startY - max(count($metaL), count($metaR)) * $lineH - 10;

        if (!empty($doc['bill_to'])) {
            $this->rect($this->ml, $this->cursorY - 4, $contentW, 0.6, $this->light);
            $this->cursorY -= 16;
            $this->pdfText('CUSTOMER', $this->ml, $this->cursorY, 8, true, $this->grey);
            $this->cursorY -= 12;
            foreach (explode("\n", (string) $doc['bill_to']) as $line) {
                $this->pdfText(trim($line), $this->ml, $this->cursorY, 9, false, $this->ink);
                $this->cursorY -= 12;
            }
            $this->cursorY -= 6;
        }

        // ---------- Line items table ----------
        $columns = (array) ($doc['columns'] ?? []);
        $rows    = (array) ($doc['rows'] ?? []);
        $weights = [];
        $totalW  = 0;
        foreach ($columns as $c) {
            $w = (float) ($c['width'] ?? 1);
            $weights[] = $w;
            $totalW += $w;
        }
        $colW = array_map(fn($w) => $w / $totalW * $contentW, $weights);
        $colX = [$this->ml];
        for ($i = 1; $i < count($colW); $i++) $colX[] = $colX[$i - 1] + $colW[$i - 1];

        // Header row
        $this->ensureSpace(34);
        $this->rect($this->ml, $this->cursorY - 12, $contentW, 18, $this->blue);
        foreach ($columns as $i => $c) {
            $align = $c['align'] ?? 'L';
            $x = $align === 'R' ? $colX[$i] + $colW[$i] - 4 : ($align === 'C' ? $colX[$i] + $colW[$i] / 2 : $colX[$i] + 6);
            $this->pdfText((string) ($c['label'] ?? ''), $x, $this->cursorY - 5, 8.5, true, '1 1 1', $align === 'L' ? 'L' : $align);
        }
        $this->cursorY -= 22;

        $rowNo = 0;
        foreach ($rows as $r) {
            $cells = [];
            $maxLines = 1;
            foreach ((array) $r as $colIdx => $cell) {
                $w = $colW[$colIdx] ?? $contentW;
                $lines = $this->wrapText((string) $cell, $w - 12, 8.2);
                $cells[] = $lines;
                $maxLines = max($maxLines, count($lines));
            }
            $rowH = $maxLines * 10.5 + 8;
            $this->ensureSpace($rowH + 4);

            // Zebra shading
            if ($rowNo % 2 === 1) {
                $this->rect($this->ml, $this->cursorY - $rowH + 6, $contentW, $rowH, '0.97 0.98 0.99');
            }
            foreach ($cells as $colIdx => $lines) {
                $align = $columns[$colIdx]['align'] ?? 'L';
                $y = $this->cursorY;
                foreach ($lines as $li => $line) {
                    $x = $align === 'R' ? $colX[$colIdx] + $colW[$colIdx] - 6
                        : ($align === 'C' ? $colX[$colIdx] + $colW[$colIdx] / 2 : $colX[$colIdx] + 6);
                    $this->pdfText($line, $x, $y - $li * 10.5, 8.2, false, $this->ink, $align);
                }
            }
            $this->cursorY -= $rowH;
            $rowNo++;
        }
        // Table bottom rule
        $this->rect($this->ml, $this->cursorY + 4, $contentW, 0.8, $this->blue);
        $this->cursorY -= 12;

        // ---------- Totals ----------
        foreach ((array) ($doc['totals'] ?? []) as $t) {
            $bold = !empty($t['bold']);
            if ($bold) {
                $this->rect($this->W - $this->mr - 190, $this->cursorY - 11, 190, 16, $this->light);
            }
            $this->pdfText((string) ($t['label'] ?? ''), $this->W - $this->mr - 184, $this->cursorY, 9, $bold, $this->ink);
            $this->pdfText((string) ($t['value'] ?? ''), $this->W - $this->mr - 8, $this->cursorY, 9, $bold, $bold ? $this->blue : $this->ink, 'R');
            $this->cursorY -= 15;
        }
        $this->cursorY -= 8;

        // ---------- Notes / terms ----------
        foreach ((array) ($doc['notes_blocks'] ?? []) as $nb) {
            $heading = (string) ($nb['heading'] ?? '');
            $text    = (string) ($nb['text'] ?? '');
            if ($heading === '' && $text === '') continue;
            $lines = $this->wrapText($text, $contentW - 8, 8.4);
            $needed = 16 + count($lines) * 11 + 8;
            $this->ensureSpace($needed + 10);
            if ($heading !== '') {
                $this->pdfText($heading, $this->ml, $this->cursorY, 9.5, true, $this->blue);
                $this->cursorY -= 13;
            }
            foreach ($lines as $line) {
                $this->pdfText($line, $this->ml, $this->cursorY, 8.4, false, '0.25 0.25 0.25');
                $this->cursorY -= 11;
            }
            $this->cursorY -= 6;
        }

        // ---------- Signature line ----------
        $this->ensureSpace(60);
        $this->cursorY -= 20;
        $this->pdfText('Prepared by Halyk Petroleum Sales', $this->ml, $this->cursorY, 8.5, true, $this->ink);
        $this->cursorY -= 14;
        $this->rect($this->ml + 200, $this->cursorY + 2, 150, 0.6, $this->grey);
        $this->pdfText('Authorised signature', $this->ml + 200, $this->cursorY - 10, 7.5, false, $this->grey);

        $footer = (string) ($doc['footer'] ?? '');
        $this->finishPages($footer);

        return $this->assemble();
    }

    /* ------------------------------------------------------------------ */

    private function startPage(): void
    {
        $this->stream = '';
        $this->cursorY = $this->H - 60;
    }

    private function finishPages(string $footer): void
    {
        $this->pageStreams[] = $this->stream;
        // Add a branded footer to every page.
        foreach ($this->pageStreams as $idx => &$s) {
            $fy = $this->mb - 24;
            $s .= sprintf("%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n",
                0.96, 0.65, 0.14, 0, $fy + 16, $this->W, 2.2);
            $s .= sprintf("%.3f %.3f %.3f RG 0.5 w %.2f %.2f m %.2f %.2f l S\n",
                0.8, 0.82, 0.86, $this->ml, $fy + 12, $this->W - $this->mr, $fy + 12);
            $this->pdfTextRaw($s, $footer !== '' ? $footer : 'Halyk Petroleum — Aircraft Parts & Components',
                $this->ml, $fy, 7.5, false, $this->grey);
            $this->pdfTextRaw($s, 'Halyk Petroleum · Quotation · Page ' . ($idx + 1) . ' of ' . count($this->pageStreams),
                $this->W - $this->mr, $fy, 7.5, false, $this->grey, 'R');
        }
        unset($s);
    }

    /** Ensure there is room for $h points; otherwise start a new page. */
    private function ensureSpace(float $h): void
    {
        if ($this->cursorY - $h < $this->mb + 20) {
            $this->pageStreams[] = $this->stream;
            $this->startPage();
            // Small continuation heading
            $this->pdfText('Quotation (continued)', $this->ml, $this->cursorY, 9, true, $this->grey);
            $this->cursorY -= 16;
        }
    }

    private function rect(float $x, float $y, float $w, float $h, string $color): void
    {
        $this->stream .= sprintf("%s rg %.2f %.2f %.2f %.2f re f\n", $color, $x, $y, $w, $h);
    }

    /**
     * Wrap text to a max width (points) at the given font size, using
     * Helvetica average character width. Returns an array of lines.
     */
    private function wrapText(string $text, float $maxWidth, float $fontSize): array
    {
        $out = [];
        foreach (preg_split('/\r?\n/', $text) as $paragraph) {
            $words = preg_split('/\s+/', trim($paragraph));
            $line = '';
            foreach ($words as $word) {
                $trial = $line === '' ? $word : $line . ' ' . $word;
                if ($this->textWidth($trial, $fontSize) > $maxWidth && $line !== '') {
                    $out[] = $line;
                    $line = $word;
                } else {
                    $line = $trial;
                }
            }
            if ($line !== '') $out[] = $line;
        }
        return $out ?: [''];
    }

    private function textWidth(string $s, float $fontSize): float
    {
        // Helvetica average char width ≈ 0.5 em; digits/narrow glyphs skew
        // slightly lower, so 0.48 is a safe conservative estimate.
        return strlen($s) * $fontSize * 0.48;
    }

    private function pdfText(string $text, float $x, float $y, float $size, bool $bold, string $color, string $align = 'L'): void
    {
        $this->pdfTextRaw($this->stream, $text, $x, $y, $size, $bold, $color, $align);
    }

    private function pdfTextRaw(string &$stream, string $text, float $x, float $y, float $size, bool $bold, string $color, string $align = 'L'): void
    {
        $text = $this->pdfEscape($text);
        if ($text === '') return;
        $font = $bold ? '/F2' : '/F1';
        $stream .= "BT\n" . $color . " rg\n" . $font . " " . sprintf('%.2f', $size) . " Tf\n";
        if ($align !== 'L') {
            $w = $this->textWidth($text, $size);
            $tx = $align === 'R' ? $x - $w : $x - $w / 2;
            $stream .= sprintf("1 0 0 1 %.2f %.2f Tm\n", $tx, $y);
        } else {
            $stream .= sprintf("1 0 0 1 %.2f %.2f Tm\n", $x, $y);
        }
        $stream .= "(" . $text . ") Tj\nET\n";
    }

    private function pdfEscape(string $s): string
    {
        // Transliterate common UTF-8 punctuation to Latin-1-ish equivalents so
        // the standard Helvetica fonts render them predictably.
        $map = [
            "\xe2\x80\x93" => '-', "\xe2\x80\x94" => '-', "\xe2\x80\x98" => "'", "\xe2\x80\x99" => "'",
            "\xe2\x80\x9c" => '"', "\xe2\x80\x9d" => '"', "\xe2\x80\xa6" => '...', "\xc2\xa0" => ' ',
            "\xc2\xb7" => '-',
        ];
        $s = strtr($s, $map);
        $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        // Strip any residual non-ASCII bytes.
        return preg_replace('/[^\x09\x0a\x0d\x20-\x7e]/', '?', $s);
    }

    private function assemble(): string
    {
        $fontObjId  = $this->addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>");
        $fontBoldId = $this->addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>");

        $pageIds = [];
        $pagesPlaceholder = count($this->objects) + 2; // pages object added later
        foreach ($this->pageStreams as $stream) {
            $contentId = $this->addObj("<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream");
            $pageId = $this->addObj(sprintf(
                "<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> /Contents %d 0 R >>",
                0, $this->W, $this->H, $fontObjId, $fontBoldId, $contentId
            ));
            $pageIds[] = $pageId;
        }
        $pagesId = $this->addObj("<< /Type /Pages /Kids [ " . implode(' 0 R ', $pageIds) . " 0 R ] /Count " . count($pageIds) . " >>");
        // Patch each page's Parent reference (placeholder 0 -> pagesId)
        foreach ($pageIds as $pid) {
            $this->objects[$pid]['body'] = str_replace('/Parent 0 0 R', "/Parent {$pagesId} 0 R", $this->objects[$pid]['body']);
        }
        $catalogId = $this->addObj("<< /Type /Catalog /Pages " . $pagesId . " 0 R >>");

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($this->objects as $id => $obj) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $obj['body'] . "\nendobj\n";
        }
        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 " . (count($this->objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($this->objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($this->objects) + 1) . " /Root " . $catalogId . " 0 R >>\nstartxref\n" . $xrefStart . "\n%%EOF\n";
        return $pdf;
    }

    private function addObj(string $body): int
    {
        $id = count($this->objects) + 1;
        $this->objects[$id] = ['id' => $id, 'body' => $body];
        return $id;
    }
}
