<?php
/**
 * Tiny shared test runner used by every file in tests/units.
 *
 * Provides:
 *   - $failures / $passes counter accumulator per file
 *   - assert_true / assert_eq / assert_same / assert_throws helpers
 *   - summary() that prints and returns the proper exit code
 *
 * Usage at the bottom of every test file:
 *
 *   summary();
 *   exit($failures === 0 ? 0 : 1);
 *
 * Designed for the project: CLI-only, zero dependencies, runnable on every
 * push via CI in well under a second — no DB, no HTTP server.
 */

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    fwrite(STDERR, "Run from CLI: php tests/units/{name}.php\n");
    exit(2);
}

if (!defined('VP_TEST_FILE')) define('VP_TEST_FILE', basename(__FILE__));

$failures = [];
$passes   = 0;

function assert_true($value, string $what = ''): void
{
    global $failures, $passes;
    if ($value) {
        $passes++;
        return;
    }
    $failures[] = ($what !== '' ? $what : 'expected true, got false');
}

function assert_false($value, string $what = ''): void
{
    global $failures, $passes;
    if (!$value) {
        $passes++;
        return;
    }
    $failures[] = ($what !== '' ? $what : 'expected false, got true');
}

function assert_eq($expected, $actual, string $what = ''): void
{
    global $failures, $passes;
    if ($expected == $actual) { // loose - intentional (numeric string vs int, etc)
        $passes++;
        return;
    }
    $label = $what !== '' ? $what : 'expected ==';
    $exp   = is_scalar($expected) || $expected === null ? var_export($expected, true) : json_encode($expected);
    $act   = is_scalar($actual)   || $actual === null   ? var_export($actual, true)   : json_encode($actual);
    $failures[] = "$label  expected <$exp>  actual <$act>";
}

function assert_same($expected, $actual, string $what = ''): void
{
    global $failures, $passes;
    if ($expected === $actual) { // strict
        $passes++;
        return;
    }
    $label = $what !== '' ? $what : 'expected ===';
    $exp   = is_scalar($expected) || $expected === null ? var_export($expected, true) : json_encode($expected);
    $act   = is_scalar($actual)   || $actual === null   ? var_export($actual, true)   : json_encode($actual);
    $failures[] = "$label  expected <$exp>  actual <$act>";
}

function assert_throws(callable $fn, ?string $expectedClass = null, string $what = 'expected exception'): void
{
    global $failures, $passes;
    $thrown = null;
    try {
        $fn();
    } catch (\Throwable $e) {
        $thrown = $e;
    }
    if ($thrown === null) {
        $failures[] = $what . ' (no exception thrown)';
        return;
    }
    if ($expectedClass !== null && !($thrown instanceof $expectedClass)) {
        $failures[] = $what . ' (got ' . get_class($thrown) . ', expected ' . $expectedClass . ')';
        return;
    }
    $passes++;
}

/**
 * Group header to make the test runner output legible.
 */
function section(string $title): void
{
    echo "\n--- $title ---\n";
}

function summary(): int
{
    global $failures, $passes;
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "File : " . VP_TEST_FILE . "\n";
    echo "Passed: {$passes}\n";
    echo "Failed: " . count($failures) . "\n";
    if (!empty($failures)) {
        foreach ($failures as $f) echo "  - $f\n";
        echo str_repeat('=', 60) . "\n";
        fwrite(STDERR, "FAIL\n");
        return 1;
    }
    echo str_repeat('=', 60) . "\n";
    return 0;
}
