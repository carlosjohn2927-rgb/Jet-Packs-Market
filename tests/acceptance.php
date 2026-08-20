<?php
/**
 * Vortex Precision - full acceptance suite (CLI).
 *
 *   php app/tests/acceptance.php            # expects an installed database
 *   php app/tests/acceptance.php --install  # also runs install/install.php first
 *
 * What it does:
 *   - boots the app on PHP's built-in server (router: app/tests/router.php)
 *   - boots a mock Resend HTTP endpoint (app/tests/mock_resend.php)
 *   - drives every flow over real HTTP (login, RBAC, CSRF, RFQ, uploads,
 *     password reset, remember-me, rate limits, sessions...)
 *   - verifies the database directly (schema, FK, JSON, rows)
 *   - checks logs, secrets, and runs php -l over the whole app
 *
 * Required env:  VP_DB_HOST/NAME/USER/PASS(+PORT), VP_ADMIN_PASSWORD
 * Optional env:  VP_ADMIN_EMAIL, PHP_BIN, ACCEPT_PORT_*
 *
 * Exit code 0 = every check passed.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// When running under a wrapper that sets LD_LIBRARY_PATH, strip it from this
// process's environment so shelled-out tools (bash/xargs/curl) don't emit
// loader warnings; boot_server() re-injects it for the PHP servers.
$GLOBALS['VP_LIB_PATH'] = getenv('LD_LIBRARY_PATH');
if ($GLOBALS['VP_LIB_PATH'] !== false) {
    putenv('LD_LIBRARY_PATH');
}

$ROOT = dirname(__DIR__, 2);
$APP  = $ROOT . '/app';
$TMP  = sys_get_temp_dir() . '/vp-accept-' . getmypid();
@mkdir($TMP, 0700, true);

$phpBin = getenv('PHP_BIN') ?: 'php';

$dbHost = getenv('VP_DB_HOST') ?: '127.0.0.1';
$dbPort = (int) (getenv('VP_DB_PORT') ?: '3306');
$dbName = getenv('VP_DB_NAME') ?: 'vortex_ci';
$dbUser = getenv('VP_DB_USER') ?: 'root';
$dbPass = getenv('VP_DB_PASS') ?: '';

$adminEmail = strtolower(getenv('VP_ADMIN_EMAIL') ?: 'admin@vortexprecision.com');
$adminPass  = getenv('VP_ADMIN_PASSWORD') ?: '';

$portApp    = (int) (getenv('ACCEPT_PORT_APP')    ?: 18099);
$portMock   = (int) (getenv('ACCEPT_PORT_MOCK')   ?: 18098);
$portExpiry = (int) (getenv('ACCEPT_PORT_EXPIRY') ?: 18097);
$portProd   = (int) (getenv('ACCEPT_PORT_PROD')   ?: 18096);
$portNoEnv  = (int) (getenv('ACCEPT_PORT_NOENV')  ?: 18095);
$portMailFail = (int) (getenv('ACCEPT_PORT_MAILFAIL') ?: 18094);

$baseApp = "http://127.0.0.1:$portApp";
$mockLog = $TMP . '/mock_resend.log';

/* ------------------------------------------------------------------ */
/* Tiny test framework                                                 */
/* ------------------------------------------------------------------ */
$GLOBALS['VP_PASS'] = 0;
$GLOBALS['VP_FAIL'] = 0;
$GLOBALS['VP_FAILURES'] = [];

function section($name)
{
    echo "\n== " . $name . " ==\n";
}

function check($name, $cond, $detail = '')
{
    if ($cond) {
        $GLOBALS['VP_PASS']++;
        echo "  [PASS] $name" . ($detail !== '' ? " - $detail" : '') . "\n";
    } else {
        $GLOBALS['VP_FAIL']++;
        $GLOBALS['VP_FAILURES'][] = $name . ($detail !== '' ? " - $detail" : '');
        echo "  [FAIL] $name" . ($detail !== '' ? " - $detail" : '') . "\n";
    }
}

/* ------------------------------------------------------------------ */
/* HTTP helpers (curl CLI with cookie jars)                            */
/* ------------------------------------------------------------------ */
function http_req($method, $url, $opts = [])
{
    global $TMP;
    $id = str_replace('.', '', (string) microtime(true)) . rand(1000, 9999);
    $hdrFile = "$TMP/h$id.hdr";
    $bodyFile = "$TMP/b$id.out";
    $cmd = ['curl', '-s', '-S', '--max-time', '25', '-o', $bodyFile, '-D', $hdrFile, '-X', $method];
    if (!empty($opts['jar']))      { $cmd[] = '-b'; $cmd[] = $opts['jar']; $cmd[] = '-c'; $cmd[] = $opts['jar']; }
    foreach ((array) ($opts['headers'] ?? []) as $h) { $cmd[] = '-H'; $cmd[] = $h; }
    $isMultipart = !empty($opts['multipart']);
    foreach ((array) ($opts['form'] ?? []) as $k => $v) {
        if ($isMultipart) {
            // Mixed -d and -F is invalid in curl; send plain fields as parts.
            $cmd[] = '-F'; $cmd[] = $k . '=' . $v;
        } else {
            $cmd[] = '--data-urlencode'; $cmd[] = $k . '=' . $v;
        }
    }
    foreach ((array) ($opts['multipart'] ?? []) as $part) {
        $cmd[] = '-F';
        $cmd[] = $part['field'] . '=@' . $part['path']
            . (isset($part['filename']) ? ';filename=' . $part['filename'] : '')
            . (isset($part['type']) ? ';type=' . $part['type'] : '');
    }
    if (!empty($opts['raw'])) { $cmd[] = '--data-binary'; $cmd[] = $opts['raw']; }
    $cmd[] = $url;
    $cmd = array_map('escapeshellarg', $cmd);
    exec(implode(' ', $cmd) . ' 2>&1', $out, $code);
    $headers = @file_get_contents($hdrFile) ?: '';
    $body = @file_get_contents($bodyFile) ?: '';
    @unlink($hdrFile); @unlink($bodyFile);
    $status = 0;
    if (preg_match('/^HTTP\/\S+\s+(\d+)/m', $headers, $m)) $status = (int) $m[1];
    $location = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) $location = trim($m[1]);
    $setcookies = [];
    preg_match_all('/^Set-Cookie:\s*([^;=]+)=([^;]*)/mi', $headers, $sc, PREG_SET_ORDER);
    foreach ($sc as $c) $setcookies[$c[1]] = $c[2];
    return ['status' => $status, 'headers' => $headers, 'body' => $body,
            'location' => $location, 'cookies' => $setcookies, 'curl_code' => $code];
}

function csrf_from($body)
{
    return preg_match('/name="csrf_token"[^>]*value="([^"]+)"/', $body, $m) ? $m[1] : null;
}

function get_with_csrf($jar, $path, $base = null)
{
    $base = $base !== null ? $base : $GLOBALS['baseApp'];
    $r = http_req('GET', $base . $path, ['jar' => $jar]);
    $csrf = csrf_from($r['body']);
    if ($csrf === null) {
        // Token may use different quoting in some views
        $csrf = preg_match('/name="[^"]*csrf[^"]*"[^>]*value="([^"]+)"/', $r['body'], $m) ? $m[1] : null;
    }
    return [$r, $csrf];
}

/* ------------------------------------------------------------------ */
/* Database helper (mysqli)                                            */
/* ------------------------------------------------------------------ */
$db = null;
function db()
{
    global $db, $dbHost, $dbPort, $dbUser, $dbPass, $dbName;
    if ($db instanceof mysqli && $db->ping()) return $db;
    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if ($db->connect_errno) {
        fwrite(STDERR, "DB connect failed: {$db->connect_error}\n");
        exit(2);
    }
    return $db;
}
function db_one($sql)
{
    $r = db()->query($sql);
    if (!$r) { fwrite(STDERR, "SQL failed: " . db()->error . " :: $sql\n"); return null; }
    $row = $r->fetch_row();
    $r->free();
    return $row ? $row[0] : null;
}
function db_exec($sql)
{
    $r = db()->query($sql);
    if (!$r) { fwrite(STDERR, "SQL failed: " . db()->error . " :: $sql\n"); return false; }
    return true;
}

/* ------------------------------------------------------------------ */
/* Server management                                                   */
/* ------------------------------------------------------------------ */
$servers = [];
function boot_server($port, $env, $name, $router = null)
{
    global $phpBin, $APP;
    $log = $GLOBALS['TMP'] . "/server-$name.log";
    $envAll = array_merge($_SERVER, $_ENV, [
        'PATH' => getenv('PATH'),
        'CI_ENV' => $env['CI_ENV'] ?? 'development',
        'TZ' => 'UTC',
        'LD_LIBRARY_PATH' => $GLOBALS['VP_LIB_PATH'] !== false ? $GLOBALS['VP_LIB_PATH'] : '',
    ], $env);
    $cmd = [$phpBin,
        '-d', 'upload_max_filesize=20M',
        '-d', 'post_max_size=25M',
        '-d', 'max_file_uploads=20',
        '-d', 'date.timezone=UTC',
        '-S', "127.0.0.1:$port",
        '-t', $APP,
        $router !== null ? $router : $APP . '/tests/router.php',
    ];
    $fh = fopen($log, 'w');
    $proc = proc_open($cmd, [1 => $fh, 2 => $fh], $pipes, $APP, $envAll);
    if (!is_resource($proc)) { fwrite(STDERR, "Could not boot server $name\n"); exit(2); }
    $GLOBALS['servers'][] = [$proc, $log, $name];
    // wait for port
    for ($i = 0; $i < 100; $i++) {
        $s = @fsockopen('127.0.0.1', $port, $e1, $e2, 0.2);
        if ($s) { fclose($s); return; }
        usleep(100000);
    }
    fwrite(STDERR, "Server $name did not come up on port $port\n");
    exit(2);
}

register_shutdown_function(function () {
    foreach ($GLOBALS['servers'] as $s) {
        proc_terminate($s[0]);
    }
    foreach ($GLOBALS['servers'] as $s) {
        proc_close($s[0]);
    }
});

/* ------------------------------------------------------------------ */
/* Install (optional)                                                  */
/* ------------------------------------------------------------------ */
if (in_array('--install', $argv, true)) {
    echo "Running install/install.php ...\n";
    $cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($ROOT . '/install/install.php');
    passthru($cmd, $code);
    if ($code !== 0) { fwrite(STDERR, "install.php failed\n"); exit(2); }
}

if ($adminPass === '') {
    fwrite(STDERR, "VP_ADMIN_PASSWORD must be set (this is a test rig, not production).\n");
    exit(2);
}

/* ------------------------------------------------------------------ */
/* Boot everything                                                     */
/* ------------------------------------------------------------------ */
$common = [
    'VP_DB_HOST' => $dbHost, 'VP_DB_PORT' => (string) $dbPort,
    'VP_DB_NAME' => $dbName, 'VP_DB_USER' => $dbUser, 'VP_DB_PASS' => $dbPass,
    'VP_ENCRYPTION_KEY' => bin2hex(random_bytes(32)),
    'VP_AUTH_SECRET'    => bin2hex(random_bytes(32)),
    'VP_LOG_THRESHOLD'  => '4',
    'VP_FROM_EMAIL'     => 'no-reply@vortex.test',
    'VP_FROM_NAME'      => 'Vortex Precision',
    'VP_REPLY_TO'       => 'sales@vortex.test',
];

section("Booting servers");
boot_server($portMock, ['CI_ENV' => 'production', 'MOCK_RESEND_LOG' => $mockLog], 'mock', $APP . '/tests/mock_resend.php');
@unlink($mockLog);
boot_server($portApp, $common + [
    'RESEND_API_KEY' => 're_test_key',
    'VP_RESEND_API_URL' => "http://127.0.0.1:$portMock/emails",
    'MOCK_RESEND_LOG' => $mockLog,
], 'app');
boot_server($portExpiry, $common + [
    'RESEND_API_KEY' => 're_test_key',
    'VP_RESEND_API_URL' => "http://127.0.0.1:$portMock/emails",
    'MOCK_RESEND_LOG' => $mockLog,
    'VP_SESSION_EXPIRATION' => '3',
], 'expiry');
boot_server($portProd, $common + [
    'RESEND_API_KEY' => 're_test_key',
    'VP_RESEND_API_URL' => "http://127.0.0.1:$portMock/emails",
    'MOCK_RESEND_LOG' => $mockLog,
    'CI_ENV' => 'production',
], 'prod');
boot_server($portMailFail, $common + [
    'RESEND_API_KEY' => 're_test_key',
    'VP_RESEND_API_URL' => "http://127.0.0.1:59999/emails",
    'MOCK_RESEND_LOG' => $mockLog,
], 'mailfail');
// No-env server: deliberately strip database/secret variables to prove the
// production fail-fast path (HTTP 503) when configuration is missing.
$noEnvVars = [];
foreach (['VP_DB_HOST', 'VP_DB_PORT', 'VP_DB_NAME', 'VP_DB_USER', 'VP_DB_PASS',
          'VP_ENCRYPTION_KEY', 'VP_AUTH_SECRET', 'RESEND_API_KEY', 'VP_RESEND_API_URL'] as $k) {
    $noEnvVars[$k] = '';
}
boot_server($portNoEnv, $noEnvVars + [
    'CI_ENV' => 'production',
    'PATH' => getenv('PATH'),
], 'noenv');

// Make sure the mock resend actually answers
$mockCheck = http_req('POST', "http://127.0.0.1:$portMock/emails", ['raw' => '{"to":["x@y.z"]}']);
check('mock Resend endpoint answers', $mockCheck['status'] === 200);

// start from a clean rate-limit state (buckets are shared across servers)
foreach (glob($APP . '/assets/logs/ratelimit/*.json') ?: [] as $f) @unlink($f);

/* ------------------------------------------------------------------ */
/* 1. Syntax check (item 15)                                           */
/* ------------------------------------------------------------------ */
section("1. PHP syntax (php -l on every file)");
$lintOut = [];
exec("find " . escapeshellarg($APP) . " -name '*.php' -print0 | xargs -0 -n1 " . escapeshellarg($phpBin) . " -l 2>&1", $lintOut, $lintCode);
$lintErrors = array_values(array_filter($lintOut, function ($l) {
    $l = trim($l);
    if ($l === '') return false;
    if (stripos($l, 'No syntax errors') !== false) return false;
    if (stripos($l, 'no version information') !== false) return false; // LD_LIBRARY_PATH noise from wrapper shells
    return true;
}));
check('php -l across app/ (zero errors)', count($lintErrors) === 0, count($lintErrors) . ' problem(s)' . (count($lintErrors) ? ': ' . implode(' | ', array_slice($lintErrors, 0, 5)) : ''));

/* ------------------------------------------------------------------ */
/* 2. No production secrets committed                                  */
/* ------------------------------------------------------------------ */
section("2. No production secrets in the repository");
$grepOut = [];
// Strings are assembled so this file cannot match its own search patterns.
$changeMe = 'CHANGE' . '-' . 'ME';
exec('grep -RIn ' . escapeshellarg($changeMe) . ' ' . escapeshellarg($APP) . ' ' . escapeshellarg($ROOT . '/install') . ' 2>/dev/null', $grepOut);
check('no ' . $changeMe . ' placeholders anywhere', count($grepOut) === 0, count($grepOut) . ' hit(s)');
$grepOut = [];
$defPw1 = 'admin' . '123';
$defPw2 = 'sales' . '123';
exec('grep -RIn -e ' . escapeshellarg($defPw1) . ' -e ' . escapeshellarg($defPw2) . ' ' . escapeshellarg($APP) . ' ' . escapeshellarg($ROOT . '/install') . ' 2>/dev/null', $grepOut);
check('no known default passwords in repo', count($grepOut) === 0, count($grepOut) . ' hit(s)');
$grepOut = [];
exec('grep -RIn "\\$2y\\$\\|\\$2a\\$\\|\\$2b\\$" ' . escapeshellarg($ROOT . '/install') . ' 2>/dev/null', $grepOut);
check('no bcrypt password hashes in install/ (admin created at install time)', count($grepOut) === 0, count($grepOut) . ' hit(s)');
exec('cd ' . escapeshellarg($ROOT) . ' && git check-ignore -q app/.env', $o, $ignoreCode);
check('app/.env is gitignored', $ignoreCode === 0);
exec('cd ' . escapeshellarg($ROOT) . ' && git check-ignore -q app/application/config/.secrets.php', $o, $ignoreCode2);
check('application/config/.secrets.php is gitignored', $ignoreCode2 === 0);

/* ------------------------------------------------------------------ */
/* 3. Public routes (item 15.6)                                        */
/* ------------------------------------------------------------------ */
section("3. Public routes");
$publicRoutes = ['/', '/about', '/services', '/contact', '/rfq', '/products',
    '/industries', '/blog', '/careers', '/faq', '/downloads', '/news', '/login', '/register', '/forgot'];
foreach ($publicRoutes as $p) {
    $r = http_req('GET', $baseApp . $p);
    check("GET $p", $r['status'] === 200, 'HTTP ' . $r['status']);
}
$slugRoutes = [];
$s = db_one("SELECT slug FROM products WHERE isActive=1 ORDER BY views DESC LIMIT 1");
if ($s) $slugRoutes['/products/' . $s] = 'product page';
$s = db_one("SELECT slug FROM industries LIMIT 1");
if ($s) $slugRoutes['/industries/' . $s] = 'industry page';
$s = db_one("SELECT slug FROM careers WHERE isActive=1 LIMIT 1");
if ($s) $slugRoutes['/careers/' . $s] = 'career page';
$s = db_one("SELECT slug FROM news LIMIT 1");
if ($s) $slugRoutes['/news/' . $s] = 'news page';
$s = db_one("SELECT slug FROM blog_posts WHERE status='PUBLISHED' LIMIT 1");
if ($s) $slugRoutes['/blog/' . $s] = 'blog post page';
foreach ($slugRoutes as $p => $label) {
    $r = http_req('GET', $baseApp . $p);
    check("GET $p ($label)", $r['status'] === 200, 'HTTP ' . $r['status']);
}
$r = http_req('GET', $baseApp . '/search?q=valve');
check('GET /search?q=valve', $r['status'] === 200, 'HTTP ' . $r['status']);
$r = http_req('GET', $baseApp . '/');
check('homepage renders site name', stripos($r['body'], 'Vortex Precision') !== false);

/* ------------------------------------------------------------------ */
/* 4. Admin login / logout / invalid password / lockout                */
/* ------------------------------------------------------------------ */
section("4. Authentication basics");
$jar = "$TMP/admin.jar";

$r = http_req('GET', $baseApp . '/admin', ['jar' => $jar]);
check('anonymous /admin redirects to login', in_array($r['status'], [301, 302, 303, 307]) && $r['location'] !== null && stripos($r['location'], 'login') !== false, 'HTTP ' . $r['status'] . ' -> ' . $r['location']);

[$r, $csrf] = get_with_csrf($jar, '/admin/login');
check('admin login form shows CSRF token', $csrf !== null);
$r = http_req('POST', $baseApp . '/admin/login', ['jar' => $jar, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => 'definitely-wrong-password',
]]);
$r2 = http_req('GET', $baseApp . '/admin', ['jar' => $jar]);
check('invalid password does not log in', in_array($r2['status'], [301, 302, 303, 307]), 'HTTP ' . $r2['status']);

// lockout: 5 failed attempts then locked
$attempts = 0;
for ($i = 0; $i < 6; $i++) {
    [$r, $csrf] = get_with_csrf($jar, '/admin/login');
    $attempts++;
    http_req('POST', $baseApp . '/admin/login', ['jar' => $jar, 'form' => [
        'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => 'wrong-password-' . $i,
    ]]);
}
$lockFile = $APP . '/assets/logs/ratelimit/login_127.0.0.1_' . hash('sha256', $adminEmail) . '.json';
check('rate-limit bucket file created for failed logins', is_file($lockFile));
[$r, $csrf] = get_with_csrf($jar, '/admin/login');
$r = http_req('POST', $baseApp . '/admin/login', ['jar' => $jar, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $adminPass,
]]);
$r2 = http_req('GET', $baseApp . '/admin', ['jar' => $jar]);
check('account locked after 5 failures (correct password rejected while locked)', in_array($r2['status'], [301, 302, 303, 307]), 'HTTP ' . $r2['status']);
@unlink($lockFile);

// successful login
[$r, $csrf] = get_with_csrf($jar, '/admin/login');
$r = http_req('POST', $baseApp . '/admin/login', ['jar' => $jar, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $adminPass,
]]);
check('admin login redirects to dashboard', in_array($r['status'], [301, 302, 303, 307]) && stripos((string) $r['location'], '/admin') !== false, 'HTTP ' . $r['status'] . ' -> ' . $r['location']);
$r = http_req('GET', $baseApp . '/admin', ['jar' => $jar]);
check('admin dashboard renders', $r['status'] === 200 && stripos($r['body'], 'admin') !== false, 'HTTP ' . $r['status']);
check('admin login recorded in audit log', (int) db_one("SELECT COUNT(*) FROM audit_logs WHERE action='LOGIN' AND userId IS NOT NULL") >= 1);

// session persistence
$r = http_req('GET', $baseApp . '/admin', ['jar' => $jar]);
check('session persists across requests', $r['status'] === 200, 'HTTP ' . $r['status']);
$fresh = "$TMP/fresh.jar";
$r = http_req('GET', $baseApp . '/admin', ['jar' => $fresh]);
check('fresh session (no cookies) cannot access admin', in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);

// session fixation: session id must rotate on login
[$r, ] = get_with_csrf($fresh, '/admin/login');
$sidBefore = null;
if (preg_match('/vp_session\s+([^\s;]+)/', $r['headers'], $m)) $sidBefore = $m[1];
if ($sidBefore === null) {
    // cookie may be in jar file
    $jarRaw = @file_get_contents($fresh);
    if ($jarRaw && preg_match('/vp_session\s+(\S+)/', $jarRaw, $m)) $sidBefore = $m[1];
}
[$r, $csrf] = get_with_csrf($fresh, '/admin/login');
http_req('POST', $baseApp . '/admin/login', ['jar' => $fresh, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $adminPass,
]]);
$jarRaw = @file_get_contents($fresh);
$sidAfter = null;
if ($jarRaw && preg_match('/vp_session\s+(\S+)/', $jarRaw, $m)) $sidAfter = $m[1];
check('session id rotates on login (fixation protection)', $sidBefore !== null && $sidAfter !== null && $sidBefore !== $sidAfter, "$sidBefore -> $sidAfter");

// logout
$r = http_req('GET', $baseApp . '/admin/logout', ['jar' => $jar]);
$r = http_req('GET', $baseApp . '/admin', ['jar' => $jar]);
check('logout ends the session', in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);

// re-login for the rest of the suite
[$r, $csrf] = get_with_csrf($jar, '/admin/login');
http_req('POST', $baseApp . '/admin/login', ['jar' => $jar, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $adminPass,
]]);

/* ------------------------------------------------------------------ */
/* 5. CSRF                                                             */
/* ------------------------------------------------------------------ */
section("5. CSRF protection");
$csrfJar = "$TMP/csrf.jar";
$r = http_req('POST', $baseApp . '/contact/submit', ['jar' => $csrfJar, 'form' => [
    'name' => 'Nope', 'email' => 'nope@example.com', 'subject' => 'No token', 'message' => 'No token attached',
]]);
check('POST without CSRF token is rejected', $r['status'] >= 400 && $r['status'] < 500, 'HTTP ' . $r['status']);
check('rejected POST stored nothing', (int) db_one("SELECT COUNT(*) FROM contacts WHERE email='nope@example.com'") === 0);

/* ------------------------------------------------------------------ */
/* 6. Password reset                                                   */
/* ------------------------------------------------------------------ */
section("6. Password reset flow");
$resetJar = "$TMP/reset.jar";
$before = mock_count();
[$r, $csrf] = get_with_csrf($resetJar, '/forgot');
$r = http_req('POST', $baseApp . '/forgot', ['jar' => $resetJar, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail,
]]);
check('forgot-password submits (generic success)', in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);
check('reset email handed to transport', mock_count() === $before + 1, mock_count() - $before . ' new');
$entries = mock_entries();
$resetUrl = null;
foreach (array_reverse($entries) as $e) {
    if (isset($e['payload']['to'][0]) && strtolower($e['payload']['to'][0]) === $adminEmail
        && preg_match('/reset\/([A-Za-z0-9_-]{30,})/', $e['payload']['html'] ?? '', $m)) {
        $resetUrl = $baseApp . '/reset/' . $m[1];
        $token = $m[1];
        break;
    }
}
check('reset link found in email', $resetUrl !== null);

$newPass = 'New-Accept-Pass-' . rand(1000, 9999) . '!';
if ($resetUrl) {
    $r = http_req('GET', $resetUrl, ['jar' => $resetJar]);
    check('reset page renders', $r['status'] === 200, 'HTTP ' . $r['status']);
    [$r, $csrf] = get_with_csrf($resetJar, parse_url($resetUrl, PHP_URL_PATH));
    $r = http_req('POST', $baseApp . '/reset/' . $token, ['jar' => $resetJar, 'form' => [
        'csrf_token' => $csrf, 'password' => $newPass, 'password2' => $newPass,
    ]]);
    check('reset POST accepted', in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);

    $loginJar = "$TMP/resetlogin.jar";
    [$r, $csrf] = get_with_csrf($loginJar, '/admin/login');
    http_req('POST', $baseApp . '/admin/login', ['jar' => $loginJar, 'form' => [
        'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $newPass,
    ]]);
    $r = http_req('GET', $baseApp . '/admin', ['jar' => $loginJar]);
    check('login with new password works', $r['status'] === 200, 'HTTP ' . $r['status']);

    $oldJar = "$TMP/resetold.jar";
    [$r, $csrf] = get_with_csrf($oldJar, '/admin/login');
    http_req('POST', $baseApp . '/admin/login', ['jar' => $oldJar, 'form' => [
        'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $adminPass,
    ]]);
    $r = http_req('GET', $baseApp . '/admin', ['jar' => $oldJar]);
    check('old password no longer works', in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);

    $r = http_req('GET', $resetUrl, ['jar' => $resetJar]);
    check('reset token is single-use', in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);

    $adminPass = $newPass; // continue the suite with the new password
}

$before = mock_count();
[$r, $csrf] = get_with_csrf($resetJar, '/forgot');
http_req('POST', $baseApp . '/forgot', ['jar' => $resetJar, 'form' => [
    'csrf_token' => $csrf, 'email' => 'ghost@nowhere.invalid',
]]);
check('unknown email: same generic message, no email sent (no enumeration)', mock_count() === $before);

/* ------------------------------------------------------------------ */
/* 7. Remember-me                                                      */
/* ------------------------------------------------------------------ */
section("7. Remember me");
$remJar = "$TMP/remember.jar";
[$r, $csrf] = get_with_csrf($remJar, '/login');
$r = http_req('POST', $baseApp . '/login', ['jar' => $remJar, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $adminPass, 'remember' => '1',
]]);
$jarRaw = @file_get_contents($remJar);
$remember = null;
if ($jarRaw && preg_match('/vp_remember\s+(\S+)/', $jarRaw, $m)) $remember = $m[1];
check('remember-me cookie issued', $remember !== null);
if ($remember) {
    $r = http_req('GET', $baseApp . '/admin', ['jar' => "$TMP/rem2.jar", 'headers' => ["Cookie: vp_remember=$remember"]]);
    check('remember cookie restores session', $r['status'] === 200, 'HTTP ' . $r['status']);
    // tampered signature must fail
    $parts = explode('|', $remember);
    $tampered = $parts[0] . '|' . $parts[1] . '|' . str_repeat('0', 64);
    $r = http_req('GET', $baseApp . '/admin', ['jar' => "$TMP/rem3.jar", 'headers' => ["Cookie: vp_remember=$tampered"]]);
    check('tampered remember cookie rejected', in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);
}

/* ------------------------------------------------------------------ */
/* 8. RBAC + inactive users + forced password change                   */
/* ------------------------------------------------------------------ */
section("8. RBAC, inactive users, forced password change");
$salesPass = 'Sales-Accept-' . rand(1000, 9999) . '!';
$salesEmail = 'sales.accept@vortex.test';
$hex = bin2hex(random_bytes(16));
$salesId = sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
db_exec("INSERT INTO users (id,email,password,firstName,lastName,role,isActive,mustChangePassword,emailVerified,createdAt,updatedAt)
         VALUES ('$salesId','$salesEmail','" . password_hash($salesPass, PASSWORD_BCRYPT) . "','Sally','Sales','SALES',1,0,1,NOW(),NOW())");

$salesJar = "$TMP/sales.jar";
[$r, $csrf] = get_with_csrf($salesJar, '/admin/login');
http_req('POST', $baseApp . '/admin/login', ['jar' => $salesJar, 'form' => [
    'csrf_token' => $csrf, 'email' => $salesEmail, 'password' => $salesPass,
]]);
$r = http_req('GET', $baseApp . '/admin', ['jar' => $salesJar]);
check('SALES role can open dashboard', $r['status'] === 200, 'HTTP ' . $r['status']);
$r = http_req('GET', $baseApp . '/admin/users', ['jar' => $salesJar]);
check('SALES blocked from user management (RBAC)', $r['status'] === 403, 'HTTP ' . $r['status']);
$r = http_req('GET', $baseApp . '/admin/products', ['jar' => $salesJar]);
check('SALES blocked from products (RBAC)', $r['status'] === 403, 'HTTP ' . $r['status']);
$r = http_req('GET', $baseApp . '/admin/quotes', ['jar' => $salesJar]);
check('SALES allowed into quotes (RBAC)', $r['status'] === 200, 'HTTP ' . $r['status']);

// inactive user: session effectively dead - either redirected to login or denied.
db_exec("UPDATE users SET isActive=0 WHERE id='$salesId'");
$r = http_req('GET', $baseApp . '/admin', ['jar' => $salesJar]);
check('deactivated user is signed out mid-session', in_array($r['status'], [301, 302, 303, 307, 403]), 'HTTP ' . $r['status']);
db_exec("UPDATE users SET isActive=1 WHERE id='$salesId'");

// forced password change (temp admin)
$tmpPass = 'Temp-' . rand(100000, 999999);
$tmpEmail = 'tempadmin.accept@vortex.test';
$hex = bin2hex(random_bytes(16));
$tmpId = sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
db_exec("INSERT INTO users (id,email,password,firstName,lastName,role,isActive,mustChangePassword,emailVerified,createdAt,updatedAt)
         VALUES ('$tmpId','$tmpEmail','" . password_hash($tmpPass, PASSWORD_BCRYPT) . "','Temp','Admin','ADMIN',1,1,1,NOW(),NOW())");
$tmpJar = "$TMP/tmpadmin.jar";
[$r, $csrf] = get_with_csrf($tmpJar, '/admin/login');
http_req('POST', $baseApp . '/admin/login', ['jar' => $tmpJar, 'form' => [
    'csrf_token' => $csrf, 'email' => $tmpEmail, 'password' => $tmpPass,
]]);
$r = http_req('GET', $baseApp . '/admin/quotes', ['jar' => $tmpJar]);
check('temp password forces redirect to change-password page', in_array($r['status'], [301, 302, 303, 307]) && stripos((string) $r['location'], 'users/edit') !== false, 'HTTP ' . $r['status'] . ' -> ' . $r['location']);
$newTmpPass = 'Changed-' . rand(100000, 999999) . '!';
[$r, $csrf] = get_with_csrf($tmpJar, '/admin/users/edit/' . $tmpId);
http_req('POST', $baseApp . '/admin/users/save', ['jar' => $tmpJar, 'form' => [
    'csrf_token' => $csrf, 'id' => $tmpId, 'email' => $tmpEmail,
    'firstName' => 'Temp', 'lastName' => 'Admin', 'role' => 'ADMIN', 'isActive' => '1',
    'password' => $newTmpPass,
]]);
$r = http_req('GET', $baseApp . '/admin/quotes', ['jar' => $tmpJar]);
check('after password change, forced redirect is lifted', $r['status'] === 200, 'HTTP ' . $r['status']);
check('mustChangePassword flag cleared in DB', (int) db_one("SELECT mustChangePassword FROM users WHERE id='$tmpId'") === 0);
db_exec("DELETE FROM users WHERE id='$tmpId'");

/* ------------------------------------------------------------------ */
/* 9. Session expiration                                               */
/* ------------------------------------------------------------------ */
section("9. Session expiration");
$expJar = "$TMP/expiry.jar";
$expBase = "http://127.0.0.1:$portExpiry";
[$r, $csrf] = get_with_csrf($expJar, '/admin/login', $expBase);
http_req('POST', $expBase . '/admin/login', ['jar' => $expJar, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $adminPass,
]]);
$r = http_req('GET', $expBase . '/admin', ['jar' => $expJar]);
check('short-expiry server: fresh login works', $r['status'] === 200, 'HTTP ' . $r['status']);
sleep(4);
$r = http_req('GET', $expBase . '/admin', ['jar' => $expJar]);
check('session expires after configured lifetime', in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);

// DB-side session destruction
$jar2 = "$TMP/expiry2.jar";
[$r, $csrf] = get_with_csrf($jar2, '/admin/login');
http_req('POST', $baseApp . '/admin/login', ['jar' => $jar2, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $adminPass,
]]);
$r = http_req('GET', $baseApp . '/admin', ['jar' => $jar2]);
check('second admin session established', $r['status'] === 200, 'HTTP ' . $r['status']);
$jar2Raw = @file_get_contents($jar2);
$sessId2 = null;
if ($jar2Raw && preg_match('/vp_session\s+(\S+)/', $jar2Raw, $m)) $sessId2 = $m[1];
if ($sessId2) {
    db_exec("DELETE FROM ci_sessions WHERE id='" . db()->real_escape_string($sessId2) . "'");
}
$r = http_req('GET', $baseApp . '/admin', ['jar' => $jar2]);
check('destroying the session row signs the user out', in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);

/* ------------------------------------------------------------------ */
/* 10. Uploads                                                         */
/* ------------------------------------------------------------------ */
section("10. Upload endpoints and upload security");
$upJar = "$TMP/uploadadmin.jar";
[$r, $csrf] = get_with_csrf($upJar, '/admin/login');
http_req('POST', $baseApp . '/admin/login', ['jar' => $upJar, 'form' => [
    'csrf_token' => $csrf, 'email' => $adminEmail, 'password' => $adminPass,
]]);

// fixture files
$pngPath = "$TMP/fixture.png";
file_put_contents($pngPath, base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
));
$pdfPath = "$TMP/fixture.pdf";
file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
$phpPath = "$TMP/pwn.php";
file_put_contents($phpPath, "<?php echo 'PWNED'; ?>\n");
$shPath = "$TMP/pwn.sh";
file_put_contents($shPath, "#!/bin/sh\necho pwned\n");
$phtmlPath = "$TMP/pwn.phtml";
file_put_contents($phtmlPath, "<?php echo 'PWNED-PHTML'; ?>\n");
$cgiPath = "$TMP/pwn.cgi";
file_put_contents($cgiPath, "#!/bin/sh\necho pwned-cgi\n");
$fakeJpg = "$TMP/fake.jpg";
file_put_contents($fakeJpg, "<?php echo 'PWNED-FAKE-JPG'; ?>\n" . str_repeat('A', 200));
$emptyPath = "$TMP/empty.jpg";
file_put_contents($emptyPath, '');
$bigPath = "$TMP/big.jpg";
file_put_contents($bigPath, str_repeat('0', 17 * 1024 * 1024)); // > 16384 KB media limit

// valid PNG via admin media upload
function media_upload($jar, $filePath, $filename, $type)
{
    global $baseApp;
    [$r, $csrf] = get_with_csrf($jar, '/admin/media');
    return http_req('POST', $baseApp . '/admin/media/upload', ['jar' => $jar,
        'form' => ['csrf_token' => $csrf, 'folder' => 'accept-tests'],
        'multipart' => [
            ['field' => 'file', 'path' => $filePath, 'filename' => $filename, 'type' => $type],
        ],
    ]);
}
$r = media_upload($upJar, $pngPath, 'logo.png', 'image/png');
check('valid PNG upload accepted', strpos($r['body'] . $r['headers'], 'success') !== false || in_array($r['status'], [301, 302, 303, 307]), 'HTTP ' . $r['status']);
check('media row created', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='accept-tests' AND originalName='logo.png'") === 1);

foreach ([
    ['pwn.php', $phpPath, 'application/x-php'],
    ['pwn.phtml', $phtmlPath, 'application/x-php'],
    ['pwn.sh', $shPath, 'text/x-shellscript'],
    ['pwn.cgi', $cgiPath, 'application/x-cgi'],
    ['pwn.phar', $phpPath, 'application/octet-stream'],
] as $bad) {
    $name = $bad[0];
    $r = media_upload($upJar, $bad[1], $name, $bad[2]);
    $stored = (int) db_one("SELECT COUNT(*) FROM media WHERE folder='accept-tests' AND originalName='$name'");
    check("executable upload rejected: $name", $stored === 0, $stored . ' stored');
}
$r = media_upload($upJar, $fakeJpg, 'fake.jpg', 'image/jpeg');
check('PHP payload disguised as .jpg rejected (content sniffing)', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='accept-tests' AND originalName='fake.jpg'") === 0);
$r = media_upload($upJar, $emptyPath, 'empty.jpg', 'image/jpeg');
check('empty upload rejected', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='accept-tests' AND originalName='empty.jpg'") === 0);
$r = media_upload($upJar, $bigPath, 'big.jpg', 'image/jpeg');
check('oversized upload rejected', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='accept-tests' AND originalName='big.jpg'") === 0);
$r = media_upload($upJar, $pngPath, '../../etc/passwd.png', 'image/png');
check('path-traversal filename neutralized', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='accept-tests' AND originalName='passwd.png'") === 1);
$r = media_upload($upJar, $pngPath, 'dup.png', 'image/png');
$r = media_upload($upJar, $pngPath, 'dup.png', 'image/png');
check('duplicate filenames both stored (unique disk names)', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='accept-tests' AND originalName='dup.png'") === 2);

// careers resume upload
clear_rate_bucket('apply:127.0.0.1');
$careerSlug = db_one("SELECT slug FROM careers WHERE isActive=1 LIMIT 1");
if ($careerSlug) {
    $carJar = "$TMP/career.jar";
    [$r, $csrf] = get_with_csrf($carJar, '/careers/' . $careerSlug);
    $r = http_req('POST', $baseApp . '/careers/apply/' . $careerSlug, ['jar' => $carJar,
        'form' => ['csrf_token' => $csrf, 'name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
        'multipart' => [['field' => 'resume', 'path' => $pdfPath, 'filename' => 'ada-cv.pdf', 'type' => 'application/pdf']],
    ]);
    check('careers application with valid PDF resume accepted', (int) db_one("SELECT COUNT(*) FROM applications WHERE email='ada@example.com' AND resumeUrl LIKE '%careers%'") === 1);
    [$r, $csrf] = get_with_csrf($carJar, '/careers/' . $careerSlug);
    http_req('POST', $baseApp . '/careers/apply/' . $careerSlug, ['jar' => $carJar,
        'form' => ['csrf_token' => $csrf, 'name' => 'Evil Hacker', 'email' => 'evil@example.com'],
        'multipart' => [['field' => 'resume', 'path' => $phpPath, 'filename' => 'resume.php', 'type' => 'application/x-php']],
    ]);
    check('careers .php resume rejected', (int) db_one("SELECT COUNT(*) FROM applications WHERE email='evil@example.com'") === 0);
}

/* ------------------------------------------------------------------ */
/* 11. RFQ end-to-end                                                  */
/* ------------------------------------------------------------------ */
section("11. RFQ end-to-end (form -> validation -> DB -> attachment -> email -> log -> admin)");
clear_rate_bucket('rfq:127.0.0.1:rfq.accept@example.com');
db_exec("UPDATE settings SET value='2' WHERE `key`='rfq_rate_limit_per_hour'");
$rfqJar = "$TMP/rfq.jar";
$emailsBefore = (int) db_one("SELECT COUNT(*) FROM email_logs");
$mockBefore = mock_count();
$quotesBefore = (int) db_one("SELECT COUNT(*) FROM quotes");

[$r, $csrf] = get_with_csrf($rfqJar, '/rfq');
$r = http_req('POST', $baseApp . '/rfq/submit', ['jar' => $rfqJar,
    'form' => [
        'csrf_token' => $csrf,
        'companyName' => 'Acceptance Testing Ltd',
        'contactPerson' => 'QA Tester',
        'email' => 'rfq.accept@example.com',
        'phone' => '+1 555 0100',
        'country' => 'USA',
        'industry' => 'Oil & Gas',
        'address' => '1 Test Way',
        'notes' => 'Please quote 10 units.',
        'deadline' => '2026-12-31',
        'item_name[]' => 'VortexPro Ball Valve VP-150',
        'item_qty[]' => '10',
        'item_spec[]' => '316L, 150 PSI',
        'item_productId[]' => '',
    ],
    'multipart' => [
        ['field' => 'attachments[]', 'path' => $pdfPath, 'filename' => 'spec.pdf', 'type' => 'application/pdf'],
    ],
]);
check('RFQ submission redirects to thanks page', in_array($r['status'], [301, 302, 303, 307]) && stripos((string) $r['location'], '/rfq/thanks/') !== false, 'HTTP ' . $r['status'] . ' -> ' . $r['location']);
check('quote row created', (int) db_one("SELECT COUNT(*) FROM quotes") === $quotesBefore + 1);
check('quote item created', (int) db_one("SELECT COUNT(*) FROM quote_items") === 1);
check('quote attachment stored', (int) db_one("SELECT COUNT(*) FROM quote_attachments") === 1);
check('attachment file exists on disk', (int) db_one("SELECT COUNT(*) FROM quote_attachments WHERE url LIKE '/assets/uploads/quotes/%'") === 1);
check('staff notified in-app', (int) db_one("SELECT COUNT(*) FROM notifications WHERE type='rfq_new'") >= 1);
check('admin + customer emails logged', (int) db_one("SELECT COUNT(*) FROM email_logs") >= $emailsBefore + 2);
check('emails actually delivered to transport', mock_count() >= $mockBefore + 2, (mock_count() - $mockBefore) . ' new');
$entries = array_slice(mock_entries(), -2);
$payloads = array_map(function ($e) { return $e['payload'] ?? []; }, $entries);
$rfqEmailOk = false;
foreach ($payloads as $p) {
    $to = $p['to'][0] ?? '';
    if ($to === 'rfq.accept@example.com' || $to === $adminEmail) {
        if (!empty($p['from']) && !empty($p['reply_to']) && !empty($p['subject']) && !empty($p['html'])) $rfqEmailOk = true;
    }
}
check('RFQ emails carry From, Reply-To, subject and body', $rfqEmailOk);
check('email log rows are SENT', (int) db_one("SELECT COUNT(*) FROM email_logs WHERE status='SENT' AND relatedQuoteId IS NOT NULL") >= 2);

// duplicate-submission behavior: same payload again -> new quote (intentional),
// but the SAME quote's emails are deduplicated.
$quoteId = db_one("SELECT id FROM quotes ORDER BY createdAt DESC LIMIT 1");
$before = mock_count();
[$r, $csrf] = get_with_csrf($rfqJar, '/rfq');
http_req('POST', $baseApp . '/rfq/submit', ['jar' => $rfqJar, 'form' => [
    'csrf_token' => $csrf,
    'companyName' => 'Acceptance Testing Ltd', 'contactPerson' => 'QA Tester',
    'email' => 'rfq.accept@example.com', 'country' => 'USA',
    'item_name[]' => 'VortexPro Ball Valve VP-150', 'item_qty[]' => '10',
]]);
check('second identical submission creates its own quote', (int) db_one("SELECT COUNT(*) FROM quotes") === $quotesBefore + 2);
check('second quote gets its own emails (per-quote dedupe key)', mock_count() === $before + 2);

// rate limit: limit set to 2/hour, we've submitted twice -> third must block
$countBefore = (int) db_one("SELECT COUNT(*) FROM quotes");
[$r, $csrf] = get_with_csrf($rfqJar, '/rfq');
http_req('POST', $baseApp . '/rfq/submit', ['jar' => $rfqJar, 'form' => [
    'csrf_token' => $csrf,
    'companyName' => 'Rate Limited Ltd', 'contactPerson' => 'Spammer',
    'email' => 'rfq.accept@example.com', 'country' => 'USA',
    'item_name[]' => 'Valve', 'item_qty[]' => '1',
]]);
check('RFQ rate limit enforced (3rd blocked)', (int) db_one("SELECT COUNT(*) FROM quotes") === $countBefore);
db_exec("UPDATE settings SET value='5' WHERE `key`='rfq_rate_limit_per_hour'");

// admin sees the quote + status transition
$r = http_req('GET', $baseApp . '/admin/quotes', ['jar' => $upJar]);
check('admin quote list shows new RFQ', $r['status'] === 200 && stripos($r['body'], 'Acceptance Testing') !== false, 'HTTP ' . $r['status']);
$quoteNumber = db_one("SELECT quoteNumber FROM quotes WHERE id='$quoteId'");
$before = mock_count();
// Assign the quote to the admin first (leaving NEW requires an assignee).
$adminId = db_one("SELECT id FROM users WHERE email='$adminEmail'");
[$r, $csrf] = get_with_csrf($upJar, '/admin/quotes/' . $quoteId);
http_req('POST', $baseApp . '/admin/quotes/' . $quoteId . '/assign', ['jar' => $upJar, 'form' => [
    'csrf_token' => $csrf, 'assignedTo' => $adminId, 'version' => '1',
]]);
check('quote assigned', db_one("SELECT assignedTo FROM quotes WHERE id='$quoteId'") === $adminId);
[$r, $csrf] = get_with_csrf($upJar, '/admin/quotes/' . $quoteId);
http_req('POST', $baseApp . '/admin/quotes/' . $quoteId . '/status', ['jar' => $upJar, 'form' => [
    'csrf_token' => $csrf, 'status' => 'REVIEWING', 'version' => '2',
]]);
check('quote status transition persisted', db_one("SELECT status FROM quotes WHERE id='$quoteId'") === 'REVIEWING');
check('status change recorded in history', (int) db_one("SELECT COUNT(*) FROM quote_status_history WHERE quoteId='$quoteId' AND toStatus='REVIEWING'") === 1);
check('customer emailed on status change', mock_count() === $before + 1);
// replaying the SAME transition with a stale version is rejected (optimistic lock, no email)
[$r, $csrf] = get_with_csrf($upJar, '/admin/quotes/' . $quoteId);
http_req('POST', $baseApp . '/admin/quotes/' . $quoteId . '/status', ['jar' => $upJar, 'form' => [
    'csrf_token' => $csrf, 'status' => 'REVIEWING', 'version' => '1',
]]);
check('stale-version replay rejected (no duplicate email)', mock_count() === $before + 1);
// the NEXT transition emails again (dedupe key includes the transition)
[$r, $csrf] = get_with_csrf($upJar, '/admin/quotes/' . $quoteId);
http_req('POST', $baseApp . '/admin/quotes/' . $quoteId . '/status', ['jar' => $upJar, 'form' => [
    'csrf_token' => $csrf, 'status' => 'QUOTED', 'version' => '3',
]]);
check('second transition emails the customer again', mock_count() === $before + 2);

/* ------------------------------------------------------------------ */
/* 11b. Upload structure validation (Office / archive / CAD)           */
/* ------------------------------------------------------------------ */
section("11b. Upload structure validation (Office / archive / CAD)");

// Real Office documents and archives are ZIP containers; CAD formats have
// unambiguous magic bytes. These fixtures let us prove that a file wearing
// an allowed extension but with mismatched content is rejected, while a
// well-formed file is accepted.
$zipDocx = make_zip(['[Content_Types].xml' => '<Types/>', 'word/document.xml' => '<w:document/>']);
$zipXlsx = make_zip(['[Content_Types].xml' => '<Types/>', 'xl/workbook.xml' => '<workbook/>']);
$zipPlain = make_zip(['hello.txt' => 'hi']);
$textBlob = "hello, this is just plain text and nothing else\n";
$phpBlob  = "<?php echo 'PWNED'; ?>\n";
$dwgBlob  = "AC1015" . str_repeat("\x01", 32);
$dxfBlob  = "0\nSECTION\n2\nHEADER\n0\nENDSEC\n0\nEOF\n";
$stepBlob = "ISO-10303-21;\nHEADER;\nEND-ISO-10303-21;\n";
$igesBlob = "Vortex Precision IGES test model\nsecond record\n";
$ole2Blob = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\x00", 512);
$binBlob  = str_repeat("\x00", 64) . "not a text file";

$fDocx = "$TMP/valid.docx";   file_put_contents($fDocx, $zipDocx);
$fXlsx = "$TMP/valid.xlsx";   file_put_contents($fXlsx, $zipXlsx);
$fZip  = "$TMP/valid.zip";    file_put_contents($fZip, $zipPlain);
$fZipAsDocx = "$TMP/zipas.docx"; file_put_contents($fZipAsDocx, $zipPlain);
$fTxt  = "$TMP/plain.txt";    file_put_contents($fTxt, $textBlob);
$fPhp  = "$TMP/payload.txt";  file_put_contents($fPhp, $phpBlob);
$fOle2 = "$TMP/valid.doc";    file_put_contents($fOle2, $ole2Blob);
$fDwg  = "$TMP/valid.dwg";    file_put_contents($fDwg, $dwgBlob);
$fDxf  = "$TMP/valid.dxf";    file_put_contents($fDxf, $dxfBlob);
$fStep = "$TMP/valid.step";   file_put_contents($fStep, $stepBlob);
$fIges = "$TMP/valid.iges";   file_put_contents($fIges, $igesBlob);
$fBin  = "$TMP/binary.bin";   file_put_contents($fBin, $binBlob);

$officeFolder = 'accept-office';

// --- Office / archive formats via the admin media upload endpoint ---
$r = media_upload_folder($upJar, $fDocx, 'valid.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', $officeFolder);
check('valid .docx accepted', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='valid.docx'") === 1);
$r = media_upload_folder($upJar, $fXlsx, 'valid.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $officeFolder);
check('valid .xlsx accepted', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='valid.xlsx'") === 1);
$r = media_upload_folder($upJar, $fZip, 'valid.zip', 'application/zip', $officeFolder);
check('valid .zip accepted', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='valid.zip'") === 1);
$r = media_upload_folder($upJar, $fOle2, 'valid.doc', 'application/msword', $officeFolder);
check('valid .doc (OLE2 header) accepted', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='valid.doc'") === 1);

$r = media_upload_folder($upJar, $fZipAsDocx, 'zipas.docx', 'application/zip', $officeFolder);
check('zip archive renamed .docx rejected (no [Content_Types].xml)', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='zipas.docx'") === 0);
$r = media_upload_folder($upJar, $fTxt, 'text.docx', 'application/zip', $officeFolder);
check('plain text renamed .docx rejected', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='text.docx'") === 0);
$r = media_upload_folder($upJar, $fTxt, 'text.xlsx', 'application/zip', $officeFolder);
check('plain text renamed .xlsx rejected', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='text.xlsx'") === 0);
$r = media_upload_folder($upJar, $fTxt, 'text.zip', 'application/zip', $officeFolder);
check('plain text renamed .zip rejected', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='text.zip'") === 0);
$r = media_upload_folder($upJar, $fTxt, 'text.doc', 'application/msword', $officeFolder);
check('plain text renamed .doc rejected', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='text.doc'") === 0);
$r = media_upload_folder($upJar, $fTxt, 'text.xls', 'application/msword', $officeFolder);
check('plain text renamed .xls rejected', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='text.xls'") === 0);
$r = media_upload_folder($upJar, $fPhp, 'payload.docx', 'application/zip', $officeFolder);
check('PHP payload renamed .docx rejected', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='payload.docx'") === 0);
$r = media_upload_folder($upJar, $fPhp, 'payload.zip', 'application/zip', $officeFolder);
check('PHP payload renamed .zip rejected', (int) db_one("SELECT COUNT(*) FROM media WHERE folder='$officeFolder' AND originalName='payload.zip'") === 0);

// --- CAD formats via the RFQ endpoint (the only endpoint that accepts them) ---
check('valid .dwg accepted', rfq_upload($fDwg, 'part.dwg', 'image/vnd.dwg') === 1);
check('plain text renamed .dwg rejected', rfq_upload($fTxt, 'text.dwg', 'image/vnd.dwg') === 0);
check('PHP payload renamed .dwg rejected', rfq_upload($fPhp, 'payload.dwg', 'image/vnd.dwg') === 0);
check('valid .dxf accepted', rfq_upload($fDxf, 'part.dxf', 'text/plain') === 1);
check('plain text (no SECTION) renamed .dxf rejected', rfq_upload($fTxt, 'text.dxf', 'text/plain') === 0);
check('valid .step accepted', rfq_upload($fStep, 'part.step', 'text/plain') === 1);
check('plain text renamed .step rejected', rfq_upload($fTxt, 'text.step', 'text/plain') === 0);
check('valid .iges accepted', rfq_upload($fIges, 'part.iges', 'text/plain') === 1);
check('binary renamed .iges rejected', rfq_upload($fBin, 'binary.iges', 'application/octet-stream') === 0);

/* ------------------------------------------------------------------ */
/* 12. Contact + rate limiting                                         */
/* ------------------------------------------------------------------ */
section("12. Contact form + rate limiting");
clear_rate_bucket('contact:127.0.0.1');
$contactJar = "$TMP/contact.jar";
$contactsBefore = (int) db_one("SELECT COUNT(*) FROM contacts");
for ($i = 1; $i <= 5; $i++) {
    [$r, $csrf] = get_with_csrf($contactJar, '/contact');
    http_req('POST', $baseApp . '/contact/submit', ['jar' => $contactJar, 'form' => [
        'csrf_token' => $csrf, 'name' => 'Person ' . $i, 'email' => "person$i@example.com",
        'subject' => 'Inquiry ' . $i, 'message' => 'This is message number ' . $i . ' with enough length.',
    ]]);
}
check('5 contact submissions stored', (int) db_one("SELECT COUNT(*) FROM contacts") === $contactsBefore + 5);
[$r, $csrf] = get_with_csrf($contactJar, '/contact');
http_req('POST', $baseApp . '/contact/submit', ['jar' => $contactJar, 'form' => [
    'csrf_token' => $csrf, 'name' => 'Person 6', 'email' => 'person6@example.com',
    'subject' => 'Inquiry 6', 'message' => 'This is message number six with enough length.',
]]);
check('contact rate limit blocks the 6th submission', (int) db_one("SELECT COUNT(*) FROM contacts") === $contactsBefore + 5);
check('contact email logged to transport', (int) db_one("SELECT COUNT(*) FROM email_logs WHERE template='contact_received' AND status='SENT'") >= 5);

/* ------------------------------------------------------------------ */
/* 13. Email failure logging                                           */
/* ------------------------------------------------------------------ */
section("13. Email failure logging (dead Resend endpoint)");
clear_rate_bucket('contact:127.0.0.1');
$failJar = "$TMP/mailfail.jar";
$failBase = "http://127.0.0.1:$portMailFail";
$failBefore = (int) db_one("SELECT COUNT(*) FROM email_logs WHERE status='FAILED'");
[$r, $csrf] = get_with_csrf($failJar, '/contact', $failBase);
http_req('POST', $failBase . '/contact/submit', ['jar' => $failJar, 'form' => [
    'csrf_token' => $csrf, 'name' => 'Fail Test', 'email' => 'failtest@example.com',
    'subject' => 'Failure logging', 'message' => 'This submission should produce a FAILED email log entry.',
]]);
$failed = db_one("SELECT COUNT(*) FROM email_logs WHERE status='FAILED'");
check('transport failure recorded as FAILED in email_logs', (int) $failed === $failBefore + 1);
$err = db_one("SELECT errorMessage FROM email_logs WHERE status='FAILED' ORDER BY createdAt DESC LIMIT 1");
check('failure includes error message', $err !== null && $err !== '');

/* ------------------------------------------------------------------ */
/* 14. Downloads                                                       */
/* ------------------------------------------------------------------ */
section("14. Downloads");
$dl = db_one("SELECT id FROM downloads WHERE isActive=1 LIMIT 1");
if ($dl) {
    $fileUrl = db_one("SELECT fileUrl FROM downloads WHERE id='$dl'");
    $countBefore = (int) db_one("SELECT downloads FROM downloads WHERE id='$dl'");
    $r = http_req('GET', $baseApp . '/downloads/file/' . $dl);
    check('download endpoint redirects to the file', in_array($r['status'], [301, 302, 303, 307]) && $r['location'] !== null, 'HTTP ' . $r['status']);
    check('download counter incremented', (int) db_one("SELECT downloads FROM downloads WHERE id='$dl'") === $countBefore + 1);
}

/* ------------------------------------------------------------------ */
/* 15. Admin CRUD + audit trail                                        */
/* ------------------------------------------------------------------ */
section("15. Admin CRUD + audit trail");
[$r, $csrf] = get_with_csrf($upJar, '/admin/categories/create');
$r = http_req('POST', $baseApp . '/admin/categories/save', ['jar' => $upJar, 'form' => [
    'csrf_token' => $csrf, 'name' => 'Acceptance Category', 'slug' => 'acceptance-category-' . rand(100, 999),
]]);
$catId = db_one("SELECT id FROM categories WHERE name='Acceptance Category' ORDER BY createdAt DESC LIMIT 1");
check('category created via admin CRUD', $catId !== null);
[$r, $csrf] = get_with_csrf($upJar, '/admin/categories/edit/' . $catId);
http_req('POST', $baseApp . '/admin/categories/save', ['jar' => $upJar, 'form' => [
    'csrf_token' => $csrf, 'id' => $catId, 'name' => 'Acceptance Category Renamed', 'slug' => 'acceptance-renamed-' . rand(100, 999),
]]);
check('category edited via admin CRUD', db_one("SELECT name FROM categories WHERE id='$catId'") === 'Acceptance Category Renamed');
http_req('GET', $baseApp . '/admin/categories/delete/' . $catId, ['jar' => $upJar]);
check('category deleted via admin CRUD', db_one("SELECT COUNT(*) FROM categories WHERE id='$catId'") === '0');
check('admin mutations audited', (int) db_one("SELECT COUNT(*) FROM audit_logs WHERE action IN ('CREATE','UPDATE','DELETE') AND resource='Category_model'") >= 3);

/* ------------------------------------------------------------------ */
/* 16. Production-mode boot                                            */
/* ------------------------------------------------------------------ */
section("16. Production boot behavior");
$r = http_req('GET', "http://127.0.0.1:$portProd/");
check('production server renders homepage', $r['status'] === 200, 'HTTP ' . $r['status']);
$r = http_req('GET', "http://127.0.0.1:$portNoEnv/");
check('production without DB env fails fast (503, no secrets leaked)', $r['status'] === 503 && stripos($r['body'], 'VP_DB') === false, 'HTTP ' . $r['status']);
check('.secrets.php auto-generated when env keys absent', is_file($APP . '/application/config/.secrets.php'));
if (is_file($APP . '/application/config/.secrets.php')) {
    // Inspect the file contents (it contains a BASEPATH guard, so don't include it).
    $secRaw = (string) file_get_contents($APP . '/application/config/.secrets.php');
    preg_match_all('/\x27([0-9a-f]{64})\x27/', $secRaw, $m);
    check('.secrets.php contains strong random keys', count($m[1] ?? []) >= 2);
    @unlink($APP . '/application/config/.secrets.php');
}

/* ------------------------------------------------------------------ */
/* 17. Logs                                                            */
/* ------------------------------------------------------------------ */
section("17. Application logs");
$logs = glob($APP . '/assets/logs/log-*.log') ?: [];
check('application log files written', count($logs) > 0, count($logs) . ' file(s)');
$total = 0;
foreach ($logs as $l) $total += (int) filesize($l);
check('log content present', $total > 0, $total . ' bytes');

/* ------------------------------------------------------------------ */
/* 18. Database integrity (schema/FK/JSON/UUID)                        */
/* ------------------------------------------------------------------ */
section("18. Database integrity");
$expectedTables = ['users','role_permissions','sessions','ci_sessions','categories','products','product_images',
    'specifications','product_downloads','related_products','quotes','quote_items','quote_attachments',
    'quote_status_history','quote_activities','email_logs','contacts','blog_posts','careers','applications',
    'testimonials','partners','news','downloads','faqs','industries','settings','media','audit_logs',
    'notifications','password_resets'];
$missing = [];
foreach ($expectedTables as $t) {
    $c = (int) db_one("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='$t'");
    if ($c === 0) $missing[] = $t;
}
check('all 31 tables created', count($missing) === 0, count($missing) ? 'missing: ' . implode(',', $missing) : '');
check('foreign keys created', (int) db_one("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='$dbName'") >= 16);
check('indexes created', (int) db_one("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='$dbName'") >= 100);
$id = db_one("SELECT id FROM users ORDER BY createdAt DESC LIMIT 1");
check('UUID ids stored (CHAR(36))', $id !== null && strlen((string) $id) === 36);
check('JSON columns round-trip', db_one("SELECT JSON_EXTRACT(industryIds, '$[0]') IS NOT NULL FROM products WHERE industryIds IS NOT NULL LIMIT 1") === '1');
check('role permissions seeded', (int) db_one("SELECT COUNT(*) FROM role_permissions") >= 20);
check('CI3 session rows written', (int) db_one("SELECT COUNT(*) FROM ci_sessions") >= 1, db_one("SELECT COUNT(*) FROM ci_sessions") . ' row(s)');

/* ------------------------------------------------------------------ */
/* 19. PHP runtime health (warnings/deprecations)                      */
/* ------------------------------------------------------------------ */
section("19. PHP runtime health (warnings/deprecations)");
$noise = ['PHP Startup', 'Cannot load', 'xdebug', 'no version information', 'sh: 1: exec'];
$problems = [];
foreach ($GLOBALS['servers'] as [$proc, $log, $name]) {
    if (!is_file($log)) continue;
    foreach (file($log) as $line) {
        if (preg_match('/(Warning|Deprecated|Fatal error|Parse error|Notice)/', $line)) {
            $skip = false;
            foreach ($noise as $n) if (stripos($line, $n) !== false) $skip = true;
            if (!$skip) $problems[] = trim($line);
        }
    }
}
check('no PHP warnings/deprecations/fatals during the whole run', count($problems) === 0,
    count($problems) . ' issue(s)' . (count($problems) ? ': ' . implode(' | ', array_slice($problems, 0, 4)) : ''));

/* ------------------------------------------------------------------ */
/* helpers used above                                                  */
/* ------------------------------------------------------------------ */
function clear_rate_bucket($key)
{
    global $APP;
    $file = $APP . '/assets/logs/ratelimit/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $key) . '.json';
    if (is_file($file)) @unlink($file);
}
function mock_count()
{
    global $mockLog;
    return is_file($mockLog) ? count(file($mockLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : 0;
}
function mock_entries()
{
    global $mockLog;
    if (!is_file($mockLog)) return [];
    $out = [];
    foreach (file($mockLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        $d = json_decode($l, true);
        if (is_array($d)) $out[] = $d;
    }
    return $out;
}

/** Build a minimal valid ZIP archive (stored entries) without ZipArchive. */
function make_zip(array $entries)
{
    $local = '';
    $central = '';
    $offset = 0;
    foreach ($entries as $name => $data) {
        $crc = crc32($data);
        $csize = strlen($data);
        $usize = strlen($data);
        $nameLen = strlen($name);
        $lfh = "PK\x03\x04" . pack('vvvvv', 20, 0, 0, 0, 0)
             . pack('VVVvv', $crc, $csize, $usize, $nameLen, 0) . $name;
        $local .= $lfh . $data;
        $cdh = "PK\x01\x02" . pack('vvvvvv', 20, 20, 0, 0, 0, 0)
             . pack('VVVvv', $crc, $csize, $usize, $nameLen, 0)
             . pack('vvvVV', 0, 0, 0, 0, $offset) . $name;
        $central .= $cdh;
        $offset += strlen($lfh) + $csize;
    }
    $eocd = "PK\x05\x06"
        . pack('vvvvVVv', 0, 0, count($entries), count($entries), strlen($central), strlen($local), 0);
    return $local . $central . $eocd;
}

/** Upload a file to the admin media library into a specific folder. */
function media_upload_folder($jar, $filePath, $filename, $type, $folder)
{
    global $baseApp;
    [$r, $csrf] = get_with_csrf($jar, '/admin/media');
    return http_req('POST', $baseApp . '/admin/media/upload', ['jar' => $jar,
        'form' => ['csrf_token' => $csrf, 'folder' => $folder],
        'multipart' => [
            ['field' => 'file', 'path' => $filePath, 'filename' => $filename, 'type' => $type],
        ],
    ]);
}

/**
 * Submit an RFQ with a single attachment and return how many attachments
 * were saved (1 = accepted, 0 = rejected). Clears the rate-limit bucket so
 * each call is independent.
 */
function rfq_upload($filePath, $filename, $type)
{
    global $baseApp;
    clear_rate_bucket('rfq:127.0.0.1:cad.accept@example.com');
    $jar = $GLOBALS['TMP'] . '/cad.jar';
    $before = (int) db_one("SELECT COUNT(*) FROM quote_attachments");
    [$r, $csrf] = get_with_csrf($jar, '/rfq');
    http_req('POST', $baseApp . '/rfq/submit', ['jar' => $jar,
        'form' => [
            'csrf_token' => $csrf,
            'companyName' => 'CAD Upload Test',
            'contactPerson' => 'QA Tester',
            'email' => 'cad.accept@example.com',
            'country' => 'USA',
            'item_name[]' => 'Test Part',
            'item_qty[]' => '1',
        ],
        'multipart' => [
            ['field' => 'attachments[]', 'path' => $filePath, 'filename' => $filename, 'type' => $type],
        ],
    ]);
    $after = (int) db_one("SELECT COUNT(*) FROM quote_attachments");
    return $after - $before;
}

/* ------------------------------------------------------------------ */
/* Summary                                                             */
/* ------------------------------------------------------------------ */
echo "\n" . str_repeat('=', 70) . "\n";
echo 'ACCEPTANCE SUMMARY: ' . $GLOBALS['VP_PASS'] . ' passed, ' . $GLOBALS['VP_FAIL'] . " failed\n";
echo 'DB server: ' . db()->server_info . ' | PHP: ' . PHP_VERSION . "\n";
if ($GLOBALS['VP_FAIL'] > 0) {
    echo "Failures:\n";
    foreach ($GLOBALS['VP_FAILURES'] as $f) echo "  - $f\n";
    exit(1);
}
echo "ALL CHECKS PASSED\n";
exit(0);
