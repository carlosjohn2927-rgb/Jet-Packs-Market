<?php
/**
 * Unit tests for the RFQ / quote state machine primitives in
 * application/config/constants.php + the status label renderer in
 * application/helpers/app_helper.php.
 *
 * Assertions cover the contract:
 *   - 6 quote statuses (NEW, REVIEWING, QUOTED, APPROVED, REJECTED, COMPLETED)
 *   - forward-only allowed transitions (no backwards jumps, no skipping)
 *   - REJECTED and COMPLETED are terminal
 *   - vp_quote_status_label() returns one of the canonical UI labels
 *
 * No DB, no HTTP, no installer.
 */
require_once __DIR__ . '/_runner.php';

// Load constants + helpers in isolation (with minimal stand-ins for CI globals)
if (!defined('BASEPATH')) {
    // constants.php defines ROLE_* without BASEPATH protection at the file
    // level; require it directly.
    require_once dirname(__DIR__, 2) . '/application/config/constants.php';
} else {
    require_once APPPATH . 'config/constants.php';
}
require_once dirname(__DIR__, 2) . '/application/helpers/app_helper.php';

section('QUOTE status constants');
assert_eq('NEW',       QUOTE_NEW);
assert_eq('REVIEWING', QUOTE_REVIEWING);
assert_eq('QUOTED',    QUOTE_QUOTED);
assert_eq('APPROVED',  QUOTE_APPROVED);
assert_eq('REJECTED',  QUOTE_REJECTED);
assert_eq('COMPLETED', QUOTE_COMPLETED);

section('QUOTE_TRANSITIONS shape');
assert_true(is_array(QUOTE_TRANSITIONS), 'QUOTE_TRANSITIONS is an array');
assert_true(count(QUOTE_TRANSITIONS) === 6, 'every status has an entry');

// 1. Every from-state appears exactly once
assert_eq(6, count(QUOTE_TRANSITIONS), 'six from-states (same keys as the status constants)');
foreach (QUOTE_TRANSITIONS as $from => $allowed) {
    assert_true(is_array($allowed), "allowed transitions from $from is an array");
}

// 2. New ↔ terminal / forward-only contract
assert_eq([QUOTE_REVIEWING],       QUOTE_TRANSITIONS[QUOTE_NEW],       'NEW -> REVIEWING only');
assert_eq([QUOTE_QUOTED, QUOTE_REJECTED],     QUOTE_TRANSITIONS[QUOTE_REVIEWING], 'REVIEWING -> QUOTED, REJECTED');
assert_eq([QUOTE_APPROVED, QUOTE_REJECTED],   QUOTE_TRANSITIONS[QUOTE_QUOTED],    'QUOTED -> APPROVED, REJECTED');
assert_eq([QUOTE_COMPLETED],       QUOTE_TRANSITIONS[QUOTE_APPROVED],  'APPROVED -> COMPLETED only');
assert_eq([],                        QUOTE_TRANSITIONS[QUOTE_REJECTED],  'REJECTED is terminal');
assert_eq([],                        QUOTE_TRANSITIONS[QUOTE_COMPLETED], 'COMPLETED is terminal');

// 3. Forward-only: no from-state legally allows its OWN status or any earlier status
$allStatuses = ['NEW', 'REVIEWING', 'QUOTED', 'APPROVED', 'REJECTED', 'COMPLETED'];
foreach ($allStatuses as $from) {
    foreach ($allStatuses as $to) {
        if ($from === $to) {
            assert_eq(false, in_array($to, QUOTE_TRANSITIONS[$from], true), "$from cannot stay $from");
            continue;
        }
        $edge = in_array($to, QUOTE_TRANSITIONS[$from], true);
        if ($from === 'NEW')          $expected = $to === 'REVIEWING';
        elseif ($from === 'REVIEWING')$expected = $to === 'QUOTED' || $to === 'REJECTED';
        elseif ($from === 'QUOTED')   $expected = $to === 'APPROVED' || $to === 'REJECTED';
        elseif ($from === 'APPROVED') $expected = $to === 'COMPLETED';
        else                         $expected = false; // REJECTED, COMPLETED
        assert_eq($expected, $edge, "transition $from -> $to expected=" . var_export($expected, true));
    }
}

section('vp_quote_status_label()');
foreach ($allStatuses as $s) {
    $info = vp_quote_status_label($s);
    assert_true(is_array($info) && isset($info['label']) && isset($info['class']),
        "label for $s has label + class");
    assert_true(strlen($info['label']) > 0, "label '$s' has non-empty display text");
    assert_true(strpos($info['class'], 'bg-') === 0,  "label '$s' has bg-* tailwind classes");
}
$info = vp_quote_status_label('UNKNOWN');
assert_eq('UNKNOWN', $info['label'], 'unknown status returns the raw code');

summary();
exit($failures === 0 ? 0 : 1);
