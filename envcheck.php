<?php
/**
 * envcheck.php — one-time cPanel diagnostic for Vortex Precision / Halyk.
 *
 * HOW TO USE
 *   1. Upload this file to the SAME folder as index.php (your document root).
 *   2. Open https://yourdomain.com/envcheck.php in your browser.
 *   3. Read the report, then DELETE this file from the server.
 *
 * Values that are secrets are MASKED (only length + first 2 chars shown).
 * This file must never stay on a production server.
 */
header('Content-Type: text/plain; charset=utf-8');

function mask($v)
{
    if ($v === false || $v === null) return '(not set)';
    if ($v === '') return '(empty string)';
    return substr($v, 0, 2) . '*** (length ' . strlen($v) . ')';
}

echo "===== Vortex Precision envcheck =====\n\n";
echo 'PHP version     : ' . PHP_VERSION . "\n";
echo 'SAPI            : ' . php_sapi_name() . "\n";
echo '__DIR__         : ' . __DIR__ . "\n";
echo 'index.php here? : ' . (is_file(__DIR__ . '/index.php') ? 'YES' : 'NO') . "\n";
echo 'putenv enabled? : ' . (function_exists('putenv') ? 'yes' : 'NO (disabled)') . "\n";
$disabled = ini_get('disable_functions');
echo 'disable_functions: ' . ($disabled !== false && $disabled !== '' ? $disabled : '(none)') . "\n\n";

// ------------------------------------------------------------------
// Same loader as index.php
// ------------------------------------------------------------------
$parsed = [];
$envPath = __DIR__ . '/.env';
echo "===== .env file =====\n";
echo 'Path            : ' . $envPath . "\n";
echo 'Exists          : ' . (is_file($envPath) ? 'YES' : 'NO  <-- THIS IS THE PROBLEM if it says NO') . "\n";
if (is_file($envPath)) {
    echo 'Readable        : ' . (is_readable($envPath) ? 'YES' : 'NO  <-- permission problem') . "\n";
    echo 'Size            : ' . filesize($envPath) . " bytes\n";
    echo 'Permissions     : ' . substr(sprintf('%o', fileperms($envPath)), -4) . "\n";
    echo 'First line      : ' . str_replace("\n", '', (string) fgets(fopen($envPath, 'r'))) . "\n";

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        echo "ERROR: could not read .env with file()\n";
    } else {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            if (stripos($line, 'export ') === 0) $line = substr($line, 7);
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ($k === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k)) continue;
            if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                $v = substr($v, 1, -1);
            }
            $parsed[$k] = $v;
        }
        echo 'Keys parsed     : ' . (count($parsed) . ' — ' . implode(', ', array_keys($parsed))) . "\n";
    }
} else {
    echo "No .env found — the app cannot be configured. See checklist in chat.\n";
}
echo "\n";

// ------------------------------------------------------------------
// What each config reader can see, for the keys that matter
// ------------------------------------------------------------------
echo "===== Value visibility (getenv vs \$_ENV vs \$_SERVER) =====\n";
$keys = ['VP_DB_HOST', 'VP_DB_NAME', 'VP_DB_USER', 'VP_DB_PASS', 'VP_BASE_URL', 'VP_ENCRYPTION_KEY', 'VP_AUTH_SECRET', 'CI_ENV'];
foreach ($keys as $k) {
    $g = getenv($k);
    $e = isset($_ENV[$k]) ? $_ENV[$k] : null;
    $s = isset($_SERVER[$k]) ? $_SERVER[$k] : null;
    printf("%-18s getenv=%-22s \$_ENV=%-22s \$_SERVER=%s\n", $k, mask($g), mask($e), mask($s));
}
echo "\n";

// ------------------------------------------------------------------
// Replicate database.php's production check
// ------------------------------------------------------------------
echo "===== What database.php would report =====\n";
$check = [
    'VP_DB_HOST' => getenv('VP_DB_HOST') !== false && getenv('VP_DB_HOST') !== '',
    'VP_DB_NAME' => getenv('VP_DB_NAME') !== false && getenv('VP_DB_NAME') !== '',
    'VP_DB_USER' => getenv('VP_DB_USER') !== false && getenv('VP_DB_USER') !== '',
    'VP_DB_PASS' => getenv('VP_DB_PASS') !== false && getenv('VP_DB_PASS') !== '',
];
foreach ($check as $k => $ok) {
    echo ($ok ? 'OK    ' : 'MISSING ') . $k . "\n";
}
if (getenv('VP_DB_PASS') === 'change_me') {
    echo "NOTE: VP_DB_PASS is still the placeholder 'change_me'.\n";
}
$anyMissing = in_array(false, $check, true);
echo $anyMissing ? "\nRESULT: the app WILL show 'The application is not configured'.\n" : "\nRESULT: database env looks complete — the error must come from elsewhere (see chat).\n";

echo "\n===== done — DELETE envcheck.php from the server now =====\n";
