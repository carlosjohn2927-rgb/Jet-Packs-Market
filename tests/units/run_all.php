<?php
/**
 * Standalone test runner for tests/units/*.php
 *
 *   php tests/units/run_all.php           # run every suite
 *   php tests/units/run_all.php foo bar   # run only foo.php and bar.php
 *
 * Each suite runs in its own PHP process-equivalent — we include their
 * files in sequence, capturing their output + exit code, and produce a
 * pass / fail summary. The exit code of *this* script is non-zero
 * when any suite failed.
 */
require_once __DIR__ . '/_runner.php';

$dir = __DIR__;

$explicit = array_slice($argv, 1);
if (!empty($explicit)) {
    $tests = [];
    foreach ($explicit as $t) {
        $path = is_file($dir . '/' . $t) ? $dir . '/' . $t : $dir . '/' . basename($t, '.php') . '.php';
        if (!is_file($path)) {
            fwrite(STDERR, "Suite not found: {$t}\n");
            exit(2);
        }
        $tests[] = $path;
    }
} else {
    $tests = glob($dir . '/*.php') ?: [];
    // Children are loaded by parent tests via load->view; only top-level.
    // Our test files always include `_runner.php` and end in summary()/exit.
    // Treat everything in tests/units/ as a top-level suite except _runner.
    $tests = array_filter($tests, fn($f) => basename($f) !== '_runner.php'
                                            && basename($f) !== 'run_all.php');
}

if (empty($tests)) {
    fwrite(STDERR, "No suites found in tests/units/\n");
    exit(2);
}

$results = [];
foreach ($tests as $path) {
    $name = basename($path);
    fwrite(STDOUT, "\n===== {$name} =====\n");

    // Execute the suite in a child process so its $failures / $passes
    // globals + exit() don't pollute this orchestrator. We use PHP_BIN
    // (defaults to `php`) and timeout conservatively.
    $php = getenv('PHP_BIN') ?: 'php';
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($path) . ' 2>&1';
    $t0 = microtime(true);
    $buf = [];
    $rc  = 0;
    // exec() returns last line of output; use passthru-style capture instead.
    $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
    $proc = @proc_open($cmd, $descriptors, $pipes);
    if (!is_resource($proc)) {
        fwrite(STDERR, "Failed to launch {$php} for {$name}\n");
        $results[$name] = ['rc' => 1, 'time' => 0];
        continue;
    }
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    foreach ($pipes as $p) if (is_resource($p)) fclose($p);
    $rc = proc_close($proc);
    $dt = round((microtime(true) - $t0) * 1000, 1);

    echo $stdout;
    if ($stderr !== '' && $rc !== 0) echo $stderr;
    fwrite(STDOUT, "  ({$name} -> exited " . (int) $rc . ", {$dt}ms)\n");
    $results[$name] = ['rc' => $rc, 'time' => $dt];
}

fwrite(STDOUT, "\n" . str_repeat('=', 60) . "\n");
fwrite(STDOUT, "Suite summary:\n");
$failed = 0;
foreach ($results as $name => $r) {
    $tag = $r['rc'] === 0 ? 'PASS' : 'FAIL';
    if ($r['rc'] !== 0) $failed++;
    fwrite(STDOUT, sprintf("  %-4s  %-40s %ss\n", $tag, $name, $r['time']));
}
fwrite(STDOUT, str_repeat('=', 60) . "\n");
exit($failed === 0 ? 0 : 1);
