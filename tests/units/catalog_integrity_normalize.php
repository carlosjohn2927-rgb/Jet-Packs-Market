<?php
/**
 * Unit tests for Catalog_integrity::normalize_name() — the case- and
 * whitespace-insensitive key used by uk_categories_name_norm /
 * uk_products_name_norm and the admin duplicate guards.
 *
 * No DB, no HTTP, no installer.
 */
require_once __DIR__ . '/_runner.php';

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once dirname(__DIR__, 2) . '/application/libraries/Catalog_integrity.php';

section('normalize_name collapses whitespace and casefolds');
assert_eq('wheels & brakes', Catalog_integrity::normalize_name('Wheels & Brakes'), 'plain');
assert_eq('wheels & brakes', Catalog_integrity::normalize_name(' wheels  &  Brakes '), 'spaces + case');
assert_eq('wheels & brakes', Catalog_integrity::normalize_name("WHEELS\t&\nBRAKES"), 'tabs/newlines');
assert_eq('main wheel', Catalog_integrity::normalize_name('Main   Wheel'), 'multi space');
assert_eq('', Catalog_integrity::normalize_name(''), 'empty');
assert_eq('', Catalog_integrity::normalize_name('   '), 'whitespace only');
assert_eq('', Catalog_integrity::normalize_name(null), 'null');

section('normalize_name treats lookalikes as equal');
$a = Catalog_integrity::normalize_name('Landing Gear');
$b = Catalog_integrity::normalize_name('landing  gear');
$c = Catalog_integrity::normalize_name('LANDING GEAR');
assert_true($a === $b && $b === $c, 'Landing Gear variants share one key');

section('normalize_name keeps meaningful punctuation');
assert_eq('gtcp36-150 apu', Catalog_integrity::normalize_name('GTCP36-150 APU'), 'hyphen kept');
assert_eq('main wheel & brake assembly (steel)', Catalog_integrity::normalize_name('Main Wheel & Brake Assembly (Steel)'), 'parens kept');

summary();
exit($failures === 0 ? 0 : 1);
