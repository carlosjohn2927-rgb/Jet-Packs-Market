<?php
/**
 * Unit tests for the deterministic helpers in application/helpers/app_helper.php
 * that the rest of the application depends on:
 *
 *   - vp_money()           money formatter with currency symbol
 *   - vp_condition_badge() returns [label, css_classes] for NEW/OHC/USED/SERVICEABLE
 *   - vp_part_price()      RFQ fallback HTML for null/empty price
 *   - vp_slugify()         URL slug from any unicode text
 *   - vp_truncate()        UI truncate with ellipsis
 *   - vp_format_bytes()    IEC / SI byte formatter
 *
 * No DB, no HTTP, no installer.
 */
require_once __DIR__ . '/_runner.php';

// The helpers we test live in application/helpers/app_helper.php. They use
// vp_setting() / vp_saafe_html() internally; load the helper file in
// standalone mode (no BASEPATH / no CodeIgniter).
if (!defined('BASEPATH')) define('BASEPATH', __DIR__);
require_once dirname(__DIR__, 2) . '/application/helpers/app_helper.php';

section('vp_money()');
assert_eq('$0.00',                vp_money(0),     'zero formats as $0.00');
assert_eq('$0.01',                vp_money(0.01),  'fractional cents');
assert_eq('$1,234.56',            vp_money(1234.56),'thousands separator');
assert_eq('€500.00',              vp_money(500, 'EUR'), 'EUR symbol');
assert_eq('£12.50',               vp_money(12.5, 'GBP'),'GBP symbol');
assert_eq('—',                    vp_money(null), 'null returns dash');
assert_eq('—',                    vp_money(''),   'empty returns dash');
assert_eq('$9.99',                vp_money('9.99'),'numeric string ok');
assert_eq('$1.00',                vp_money('1'),   'whole int cents integer');
assert_eq('XYZ100.00',            vp_money(100, 'XYZ'),'unknown currency falls back to $');

section('vp_condition_badge()');
[$l, $c] = vp_condition_badge('NEW');          assert_eq('NEW', $l); assert_eq('bg-emerald-100 text-emerald-800', $c);
[$l, $c] = vp_condition_badge('new');          assert_eq('NEW', $l); assert_eq('bg-emerald-100 text-emerald-800', $c);
[$l, $c] = vp_condition_badge('');             assert_eq('NEW', $l); assert_eq('bg-emerald-100 text-emerald-800', $c);
[$l, $c] = vp_condition_badge('OHC');          assert_eq('OHC', $l); assert_eq('bg-amber-100 text-amber-800',   $c);
[$l, $c] = vp_condition_badge('OVERHAULED');   assert_eq('OHC', $l); assert_eq('bg-amber-100 text-amber-800',   $c, 'OVERHAULED alias');
[$l, $c] = vp_condition_badge('USED');         assert_eq('USED', $l);assert_eq('bg-sky-100 text-sky-800',       $c);
[$l, $c] = vp_condition_badge('SERVICEABLE');  assert_eq('SVCE', $l);assert_eq('bg-violet-100 text-violet-800', $c);
[$l, $c] = vp_condition_badge('REFURB');       assert_eq('REFURB', $l);assert_eq('bg-gray-100 text-gray-700',     $c, 'unknown value gets generic badge');

section('vp_part_price()');
assert_true(strpos(vp_part_price(null), 'Price on request') !== false, 'null -> ask for quote');
assert_true(strpos(vp_part_price(''),   'Price on request') !== false, 'empty -> ask for quote');
assert_true(strpos(vp_part_price(950),  '$950.00')           !== false, '950 renders formatted');

section('vp_slugify()');
assert_eq('hello-world',          vp_slugify('Hello World'),              'spaces -> dashes');
assert_eq('a-b',                   vp_slugify('a / b'),                    'multiple specials');
assert_eq('n-a',                   vp_slugify(''),                         'empty = n-a');
assert_eq('hello',                 vp_slugify('---hello---'),              'trims edges');
assert_eq('cafe',                  vp_slugify('cafe'),                      'ascii pass-through');
assert_eq('product',               vp_slugify('--'),                        'all-stripped = n-a');

section('vp_truncate()');
assert_eq('hi',                    vp_truncate('hi', 5),                    'short string unchanged');
// 'hello world' (11 chars) > 6 → cut to 6 -> 'hello ' -> last space at index 5 -> 'hello' -> + suffix
assert_eq('hello' . "\xe2\x80\xa6",  vp_truncate('hello world', 6),           'truncated with ellipsis suffix');

section('vp_format_bytes()');
// 1024 bytes -> 1.0 KB
$kb = vp_format_bytes(1024);
assert_true(is_string($kb) && strpos($kb, 'KB') !== false, '1 KB format includes KB unit');
assert_true(stripos($kb, '1') !== false,                     '1 KB level surfaces the 1 value');
assert_eq('0 B',                          vp_format_bytes(0),    'zero formats as 0 B');
assert_eq('500 B',                        vp_format_bytes(500),  'sub-1KB stays in bytes');

summary();
exit($failures === 0 ? 0 : 1);
