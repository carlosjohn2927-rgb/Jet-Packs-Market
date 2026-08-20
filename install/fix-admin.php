<?php
/**
 * Halyk Petroleum — admin login repair tool (CLI only).
 *
 *   php install/fix-admin.php
 *   php install/fix-admin.php --email=admin@example.com --password='your password'
 *   php install/fix-admin.php --check     (diagnose only, change nothing)
 *
 * "I can't sign in at /admin/login" almost always comes down to one of six
 * things. This script checks and repairs every one of them in a single pass:
 *
 *   1. The account does not exist (schema imported by hand, installer never
 *      run) ................................. -> creates it as SUPER_ADMIN
 *   2. The stored password hash does not match the password you are typing
 *      ....................................... -> re-hashes with bcrypt
 *   3. users.isActive = 0. Vp_auth::attempt() rejects it, and Vp_auth::user()
 *      force-logs-out any session for an inactive user ... -> sets isActive=1
 *   4. users.mustChangePassword = 1. Login succeeds but Admin_Controller
 *      bounces every admin page to admin/users/edit/<id>, which looks like
 *      the login "did nothing" ................ -> clears the flag
 *   5. The role is not a staff role, so Auth::admin_login() logs you straight
 *      back out with "insufficient permissions" -> promotes to SUPER_ADMIN
 *   6. The file-based login lockout (5 failed attempts) is still active.
 *      Vp_auth::attempt() then returns null BEFORE checking the password, so
 *      even a correct password fails ..... -> deletes the ratelimit records
 *
 * It also verifies that `ci_sessions` exists — with the database session
 * driver a missing table means the session is silently dropped on every
 * request, so you log in successfully and land back on the login page.
 *
 * Credentials come from app/.env (VP_DB_*, VP_ADMIN_EMAIL, VP_ADMIN_PASSWORD)
 * or from the --email / --password flags.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This tool can only be run from the command line.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
$app  = $root . '/application';

/* ------------------------------------------------------------------ */
/* .env loader (same semantics as app/index.php)                       */
/* ------------------------------------------------------------------ */
function vp_fix_load_env($path)
{
    if (!is_file($path) || !is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        if (stripos($line, 'export ') === 0) $line = substr($line, 7);
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if ($k === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k)) continue;
        if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
            $v = substr($v, 1, -1);
        }
        if (getenv($k) === false) putenv($k . '=' . $v);
    }
}
vp_fix_load_env($root . '/.env');
vp_fix_load_env($root . '/app/.env');

function vp_fix_env($key, $default = '')
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

/* ------------------------------------------------------------------ */
/* Arguments                                                           */
/* ------------------------------------------------------------------ */
$argvv = $argv ?? [];
$checkOnly = in_array('--check', $argvv, true);
$optEmail = '';
$optPass  = '';
foreach ($argvv as $a) {
    if (strpos($a, '--email=') === 0)    $optEmail = substr($a, 8);
    if (strpos($a, '--password=') === 0) $optPass  = substr($a, 11);
}

$email = strtolower(trim($optEmail !== '' ? $optEmail : vp_fix_env('VP_ADMIN_EMAIL', 'admin@halykpetroleum-kz.com')));
$pass  = $optPass !== '' ? $optPass : vp_fix_env('VP_ADMIN_PASSWORD', '');
$first = vp_fix_env('VP_ADMIN_FIRSTNAME', 'Admin');
$last  = vp_fix_env('VP_ADMIN_LASTNAME', 'User');

if ($pass === '' && !$checkOnly) {
    fwrite(STDERR, "No password available. Set VP_ADMIN_PASSWORD in app/.env or pass --password=...\n");
    exit(1);
}

$ok   = function ($m) { fwrite(STDOUT, "  [ok]    $m\n"); };
$fix  = function ($m) { fwrite(STDOUT, "  [FIXED] $m\n"); };
$warn = function ($m) { fwrite(STDOUT, "  [warn]  $m\n"); };

fwrite(STDOUT, "Halyk Petroleum - admin login repair\n");
fwrite(STDOUT, str_repeat('-', 60) . "\n");
fwrite(STDOUT, "Target account : {$email}\n");
fwrite(STDOUT, "Mode           : " . ($checkOnly ? 'CHECK ONLY (no changes)' : 'REPAIR') . "\n\n");

/* ------------------------------------------------------------------ */
/* Connect                                                             */
/* ------------------------------------------------------------------ */
$dbHost = vp_fix_env('VP_DB_HOST', 'localhost');
$dbName = vp_fix_env('VP_DB_NAME');
$dbUser = vp_fix_env('VP_DB_USER');
$dbPass = vp_fix_env('VP_DB_PASS');
$dbPort = (int) vp_fix_env('VP_DB_PORT', '3306');

if ($dbName === '' || $dbUser === '') {
    fwrite(STDERR, "VP_DB_NAME / VP_DB_USER are not set. Check app/.env.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_OFF);
$db = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($db->connect_errno) {
    fwrite(STDERR, "1. Database: CONNECTION FAILED ({$db->connect_error})\n");
    fwrite(STDERR, "   Nothing else can work until this succeeds. Verify VP_DB_* in app/.env\n");
    fwrite(STDERR, "   and that the cPanel user is added to the database under\n");
    fwrite(STDERR, "   'MySQL Databases > Add User To Database' with ALL PRIVILEGES.\n");
    exit(1);
}
$db->set_charset('utf8mb4');
fwrite(STDOUT, "1. Database connection\n");
$ok("connected to {$dbName} on {$dbHost}:{$dbPort}");

/* ------------------------------------------------------------------ */
/* Sessions table (silent login failures come from here)               */
/* ------------------------------------------------------------------ */
fwrite(STDOUT, "\n2. Session storage (sess_driver = database)\n");
$hasSessions = false;
$res = $db->query("SHOW TABLES LIKE 'ci_sessions'");
if ($res && $res->num_rows > 0) {
    $hasSessions = true;
    $ok('ci_sessions table exists');
} else {
    $warn('ci_sessions table is MISSING - every session is discarded, so a');
    $warn('successful login instantly bounces you back to the login form.');
    if (!$checkOnly) {
        // Must match install/install.sql exactly (CI3 needs primary_key when
        // sess_match_ip is enabled).
        $create = "CREATE TABLE IF NOT EXISTS `ci_sessions` (
            `id`          VARCHAR(128) NOT NULL,
            `ip_address`  VARCHAR(45)  NOT NULL,
            `timestamp`   INT UNSIGNED NOT NULL DEFAULT 0,
            `data`        BLOB         NOT NULL,
            `primary_key` VARCHAR(64)  NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`,`ip_address`),
            KEY `ci_sessions_timestamp` (`timestamp`),
            KEY `ci_sessions_primary_key` (`primary_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        if ($db->query($create)) {
            $fix('created ci_sessions');
            $hasSessions = true;
        } else {
            $warn('could not create it: ' . $db->error);
        }
    }
}

/* ------------------------------------------------------------------ */
/* The account itself                                                  */
/* ------------------------------------------------------------------ */
fwrite(STDOUT, "\n3. Admin account\n");

$staffRoles = ['SUPER_ADMIN', 'ADMIN', 'SALES', 'ENGINEER', 'EDITOR'];
$esc = $db->real_escape_string($email);
$row = null;
$res = $db->query("SELECT id, email, password, role, isActive, mustChangePassword FROM users WHERE email = '{$esc}' LIMIT 1");
if ($res && $res->num_rows > 0) $row = $res->fetch_assoc();

if (!$row) {
    $warn("no user row for {$email}");
    if ($checkOnly) {
        fwrite(STDOUT, "  (run without --check to create it)\n");
    } else {
        $hex = bin2hex(random_bytes(16));
        $hex[12] = '4';
        $hex[16] = dechex((hexdec($hex[16]) & 0x3) | 0x8);
        $id = sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4),
            substr($hex, 16, 4), substr($hex, 20, 12));
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $now  = date('Y-m-d H:i:s');
        $stmt = $db->prepare(
            "INSERT INTO users (id, email, password, firstName, lastName, role, isActive, mustChangePassword, emailVerified, createdAt, updatedAt)
             VALUES (?, ?, ?, ?, ?, 'SUPER_ADMIN', 1, 0, 1, ?, ?)"
        );
        if (!$stmt) { fwrite(STDERR, "  prepare failed: {$db->error}\n"); exit(1); }
        $stmt->bind_param('sssssss', $id, $email, $hash, $first, $last, $now, $now);
        if (!$stmt->execute()) { fwrite(STDERR, "  insert failed: {$stmt->error}\n"); exit(1); }
        $stmt->close();
        $fix("created SUPER_ADMIN {$email}");
        $row = ['id' => $id, 'email' => $email, 'password' => $hash, 'role' => 'SUPER_ADMIN', 'isActive' => 1, 'mustChangePassword' => 0];
    }
} else {
    $ok("found user id={$row['id']}");

    // 3a. password
    $matches = $pass !== '' && password_verify($pass, (string) $row['password']);
    if ($matches) {
        $ok('stored bcrypt hash matches the configured password');
    } else {
        $warn('stored hash does NOT match the configured password');
        if (!$checkOnly) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('ss', $hash, $row['id']);
            $stmt->execute();
            $stmt->close();
            $fix('password re-hashed with bcrypt');
        }
    }

    // 3b. isActive
    if ((int) $row['isActive'] === 1) {
        $ok('isActive = 1');
    } else {
        $warn('isActive = 0 - Vp_auth rejects the login AND force-logs-out the session');
        if (!$checkOnly) {
            $db->query("UPDATE users SET isActive = 1 WHERE id = '" . $db->real_escape_string($row['id']) . "'");
            $fix('isActive set to 1');
        }
    }

    // 3c. mustChangePassword
    if ((int) $row['mustChangePassword'] === 0) {
        $ok('mustChangePassword = 0');
    } else {
        $warn('mustChangePassword = 1 - every admin page redirects to the profile form');
        if (!$checkOnly) {
            $db->query("UPDATE users SET mustChangePassword = 0 WHERE id = '" . $db->real_escape_string($row['id']) . "'");
            $fix('mustChangePassword cleared');
        }
    }

    // 3d. role
    if (in_array($row['role'], $staffRoles, true)) {
        $ok("role = {$row['role']} (staff)");
    } else {
        $warn("role = {$row['role']} is not a staff role - admin login logs you straight back out");
        if (!$checkOnly) {
            $db->query("UPDATE users SET role = 'SUPER_ADMIN' WHERE id = '" . $db->real_escape_string($row['id']) . "'");
            $fix('role promoted to SUPER_ADMIN');
        }
    }
}

/* ------------------------------------------------------------------ */
/* Login lockout files                                                 */
/* ------------------------------------------------------------------ */
fwrite(STDOUT, "\n4. Brute-force lockout (file based, 5 attempts / 15 min)\n");
$rlDir = $app . '/assets/logs/ratelimit';
if (!is_dir($rlDir)) {
    $ok('no ratelimit directory - nothing is locked out');
} else {
    $files = glob($rlDir . '/login_*') ?: [];
    if (!$files) {
        $ok('no active login lockouts');
    } else {
        $warn(count($files) . ' lockout record(s) present - a locked key makes even the');
        $warn('correct password fail, because attempt() returns before password_verify()');
        if (!$checkOnly) {
            $n = 0;
            foreach ($files as $f) { if (@unlink($f)) $n++; }
            $fix("deleted {$n} lockout record(s)");
        }
    }
    if (!is_writable($rlDir)) {
        $warn("{$rlDir} is not writable by PHP - chmod 755 it");
    }
}

/* ------------------------------------------------------------------ */
/* Writable runtime dirs (session/log failures show up as login loops) */
/* ------------------------------------------------------------------ */
fwrite(STDOUT, "\n5. Writable runtime directories\n");
foreach (['assets/logs', 'assets/logs/cache', 'assets/logs/ratelimit', 'assets/uploads'] as $rel) {
    $abs = $app . '/' . $rel;
    if (!is_dir($abs)) {
        if (!$checkOnly && @mkdir($abs, 0755, true)) { $fix("created {$rel}"); continue; }
        $warn("missing: {$rel}");
        continue;
    }
    is_writable($abs) ? $ok("{$rel} writable") : $warn("{$rel} NOT writable - chmod 755 (or 775) it");
}

/* ------------------------------------------------------------------ */
/* Base URL / cookie sanity                                            */
/* ------------------------------------------------------------------ */
fwrite(STDOUT, "\n6. URL + cookie configuration\n");
$baseUrl = vp_fix_env('VP_BASE_URL');
$cookieDomain = vp_fix_env('VP_COOKIE_DOMAIN');
if ($baseUrl === '') {
    $warn('VP_BASE_URL is empty - CI will guess from HTTP_HOST (usually fine)');
} else {
    $ok("VP_BASE_URL = {$baseUrl}");
    $host = parse_url(rtrim($baseUrl, '/'), PHP_URL_HOST);
    if ($cookieDomain !== '' && $host) {
        $cd = ltrim($cookieDomain, '.');
        if (substr($host, -strlen($cd)) !== $cd) {
            $warn("VP_COOKIE_DOMAIN ({$cookieDomain}) does not match the base URL host ({$host}).");
            $warn('The session cookie will be dropped by the browser -> endless login loop.');
        } else {
            $ok("VP_COOKIE_DOMAIN = {$cookieDomain} matches {$host}");
        }
    }
    if (stripos($baseUrl, 'https://') === 0) {
        $ok('HTTPS base URL - secure session cookies will be sent');
    } else {
        $warn('base URL is http:// while VP_FORCE_HTTPS may be 1; cookie_secure would');
        $warn('then mark cookies Secure and the browser would refuse them over http.');
    }
}

/* ------------------------------------------------------------------ */
/* Summary                                                             */
/* ------------------------------------------------------------------ */
fwrite(STDOUT, "\n" . str_repeat('-', 60) . "\n");
if ($checkOnly) {
    fwrite(STDOUT, "Check complete. Re-run without --check to apply the fixes.\n");
} else {
    fwrite(STDOUT, "Done. Sign in at:  " . rtrim($baseUrl ?: '', '/') . "/admin/login\n");
    fwrite(STDOUT, "  Email    : {$email}\n");
    fwrite(STDOUT, "  Password : {$pass}\n\n");
    if (strlen($pass) < 12) {
        fwrite(STDOUT, "NOTE: '{$pass}' is only " . strlen($pass) . " characters. It works, but the admin\n");
        fwrite(STDOUT, "      UI enforces a minimum of 8 characters when changing a password there,\n");
        fwrite(STDOUT, "      so a password shorter than 8 can only be set with this tool or the\n");
        fwrite(STDOUT, "      installer. Please switch to a long unique password once you are in.\n");
    }
}
$db->close();
