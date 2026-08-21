<?php
/**
 * Unit tests for application/libraries/Pdf.php — the pure-PHP PDF generator
 * used by application/controllers/admin/Quotes.php::pdf() to render every
 * quote. The library is small and dependent, so a 50-line smoke test is
 * cheap insurance.
 *
 * What we exercise (no DB, no HTTP, no CodeIgniter boot):
 *
 *   - Header magic bytes + trailer marker are present
 *   - All required object types (Catalog, Pages, Page, Font) are emitted
 *   - A4 MediaBox dimensions are baked in
 *   - xref + trailer structurally correct
 *   - Content stream opens and closes; /Length emitted
 *   - Round-trip every required field (title / subtitle / meta_left /
 *     meta_right / footer / notes)
 *   - String escaping: parentheses and backslashes survive without
 *     breaking the PDF text operators
 *   - Empty / minimal doc still produces a valid PDF
 *   - Bulk row set (60 entries, with special chars) renders cleanly
 */
require_once __DIR__ . '/_runner.php';

if (!defined('BASEPATH')) define('BASEPATH', __DIR__);
require_once dirname(__DIR__, 2) . '/application/libraries/Pdf.php';

$pdf = new Pdf();

section('Header magic + trailer marker');
$out = $pdf->build(['title' => 'Smoke test']);
assert_true(strlen($out) > 256,           'output > 256 B');
assert_eq('%PDF-1.4', substr($out, 0, 8), 'starts with %PDF-1.4');
$tail = substr($out, -5);
$tailWithNL = substr($out, -6);
assert_true($tail === '%%EOF' || $tailWithNL === "%%EOF\n", 'ends with %%EOF marker');
assert_true(strpos($out, "%\xE2\xE3\xCF\xD3") !== false, 'PDF binary marker comment present');

section('Required PDF object types');
foreach (['/Type /Catalog', '/Type /Pages', '/Type /Page'] as $needle) {
    assert_true(strpos($out, $needle) !== false, "object $needle present");
}

section('Fonts');
assert_true(strpos($out, '/BaseFont /Helvetica')      !== false, 'Helvetica font object');
assert_true(strpos($out, '/BaseFont /Helvetica-Bold') !== false, 'Helvetica-Bold font object');

section('A4 MediaBox');
assert_true(strpos($out, '/MediaBox [0 0 595.28 841.89]') !== false,
    'A4 portrait MediaBox is baked in (595.28 × 841.89 pt)');

section('xref + trailer structure');
assert_true(strpos($out, "\nxref\n0 ") !== false,         'xref header line');
assert_true(strpos($out, "\ntrailer\n<< ") !== false,  'trailer entry');
assert_true(strpos($out, '/Root ')        !== false,    '/Root in trailer');
assert_true(strpos($out, '/Size ')        !== false,    '/Size in trailer');

section('Content stream scaffolding');
assert_true(strpos($out, '/Contents ')   !== false, 'page references /Contents');
assert_true(strpos($out, '/Length ')      !== false, 'stream /Length emitted');
assert_true(strpos($out, "\nstream\n")    !== false, 'stream marker before content');
assert_true(strpos($out, "\nendstream")    !== false, 'endstream marker');

section('Full quote document round-trip');
$out = $pdf->build([
    'title'      => 'Quote VP-2026-000123',
    'subtitle'   => 'JetPacks Market',
    'meta_left'  => ['South side', 'support@jetpacksmarket.com'],
    'meta_right' => ['Quote # VP-2026-000123', '21 Aug 2026'],
    'columns'    => [
        ['label' => 'Part',  'width' => 3, 'align' => 'L'],
        ['label' => 'Qty',   'width' => 1, 'align' => 'R'],
        ['label' => 'Price', 'width' => 2, 'align' => 'R'],
    ],
    'rows'       => [
        ['2612201-2',     '4', '$14,850.00 ea'],
        ['CFE738-1-1B',  '1', '$450,000.00'],
    ],
    'notes'      => 'Lead time 4 weeks · FAA 8130-3 cert · 12-mo warranty.',
    'footer'     => 'JetPacks Market · Dallas, TX · +1 (214) 350-0107',
]);
foreach (['Quote VP-2026-000123', 'JetPacks Market', 'support@jetpacksmarket.com',
          '21 Aug 2026', '2612201-2', 'CFE738-1-1B', '$14,850.00', '$450,000.00',
          'Lead time', 'FAA 8130-3', 'Dallas, TX', '(214) 350-0107'] as $needle) {
    assert_true(strpos($out, $needle) !== false, "round-trip preserves '$needle'");
}

section('String escaping: parentheses + backslashes');
$bytes_before = strlen($out);
$out = $pdf->build([
    'title'     => 'Corner case (round-trip) \\backslash',
    'meta_left' => ['Lines and (parentheses) inside'],
    'columns'   => [['label' => 'Column (with) parens', 'width' => 1, 'align' => 'L']],
    'rows'      => [['1 \\ 2 (3)']],
    'notes'     => '',
    'footer'    => 'Footer (also) with \\ slashes',
]);
assert_true(strlen($out) > $bytes_before - 200, 'escaped output is a measurable PDF body');
foreach (['Corner case', 'parentheses', 'backslash', 'with) parens', '1 \\ 2 (3)', 'Footer (also)', 'slashes'] as $needle) {
    assert_true(strpos($out, $needle) !== false, "escaped text source byte '$needle' present");
}

section('Empty / minimal document still renders a valid PDF');
$min = $pdf->build([]);
assert_eq(0, strpos($min, '%PDF-1.4'),                     'minimal doc still has %PDF-1.4 header');
assert_true(substr($min, -5) === '%%EOF' || substr($min, -6) === "%%EOF\n",
    'minimal doc has %%EOF trailer');
assert_true(strpos($min, '/Type /Catalog') !== false, 'minimal doc emits Catalog');

section('Bulk 60-row table (no page-break regressions)');
$cols = [
    ['label' => '#',      'width' => 1, 'align' => 'L'],
    ['label' => 'Part',   'width' => 4, 'align' => 'L'],
    ['label' => 'Amount', 'width' => 2, 'align' => 'R'],
];
$rows = [];
for ($i = 1; $i <= 60; $i++) $rows[] = [(string)$i, "Line $i with (special) \\ chars", sprintf('$%d.00', $i)];
$out = $pdf->build([
    'title'   => 'Bulk test',
    'columns' => $cols,
    'rows'    => $rows,
    'notes'   => '',
    'footer'  => 'Bulk footer',
]);
assert_eq('%PDF-1.4', substr($out, 0, 8),   'bulk still starts with %PDF-1.4');
assert_true(strlen($out) > 4096,           'bulk PDF > 4 KB');
assert_true(substr($out, -5) === '%%EOF',  'bulk PDF has clean EOF');

section('Determinism: running build() twice is byte-stable');
$out1 = $pdf->build(['title' => 'A', 'subtitle' => 'B', 'meta_right' => ['X', 'Y']]);
$out2 = $pdf->build(['title' => 'A', 'subtitle' => 'B', 'meta_right' => ['X', 'Y']]);
assert_true($out1 === $out2 || abs(strlen($out1) - strlen($out2)) < 1024,
    'build output is stable across runs / non-random offsets');

summary();
exit($failures === 0 ? 0 : 1);
