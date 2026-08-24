<?php
/** Pure checks for warehouse/lot helper contracts (no DB or HTTP). */
require_once __DIR__ . '/_runner.php';
if (!defined('BASEPATH')) define('BASEPATH', __DIR__ . '/../../system/');
require_once __DIR__ . '/../../application/helpers/inventory_helper.php';

section('Lot-number normalization');
assert_same('LOT-2026/A.01', vp_inventory_lot_number(' lot 2026/a.01 '), 'spaces normalize to hyphen');
assert_same('SN-1234', vp_inventory_lot_number('SN #1234'), 'unsafe punctuation is removed');
assert_same('', vp_inventory_lot_number(' *** '), 'empty/unsafe lot is rejected to blank');

section('Expiry labels');
$today = strtotime('2026-08-23 00:00:00');
assert_eq(['No expiry', 'text-gray-500'], vp_inventory_expiry_label('', $today), 'no expiry stays neutral');
assert_eq(['Expired', 'text-red-700 font-semibold'], vp_inventory_expiry_label('2026-08-22', $today), 'past expiry is red');
assert_eq(['Expires in 7d', 'text-amber-700 font-semibold'], vp_inventory_expiry_label('2026-08-30', $today), 'near expiry warns');
assert_eq(['Oct 15, 2026', 'text-gray-600'], vp_inventory_expiry_label('2026-10-15', $today), 'distant expiry gets date');

section('Lot status labels');
foreach (['ACTIVE','QUARANTINE','EXPIRED','DEPLETED'] as $status) {
    $label = vp_inventory_lot_status_label($status);
    assert_true(is_array($label) && count($label) === 2 && $label[0] !== '' && strpos($label[1], 'bg-') === 0, "$status has label and pill classes");
}
assert_eq('MYSTERY', vp_inventory_lot_status_label('mystery')[0], 'unknown status remains inspectable');

summary();
exit($failures === 0 ? 0 : 1);
