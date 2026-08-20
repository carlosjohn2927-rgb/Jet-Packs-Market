<?php
/**
 * Vortex Precision - CLI installer (run from a terminal / SSH, NOT via HTTP).
 *
 *   php install/install.php
 *
 * 1. Creates the schema  (install/install.sql)
 * 2. Creates the initial SUPER_ADMIN account:
 *      - email:    VP_ADMIN_EMAIL    (default admin@vortexprecision.com)
 *      - password: VP_ADMIN_PASSWORD (if empty, a random temporary password
 *                  is generated, printed once below, and the account is
 *                  flagged to force a password change on first login)
 * 3. Loads demo content (install/seed.sql)
 * 4. Generates stable secret keys (app/application/config/.secrets.php)
 *    when VP_ENCRYPTION_KEY / VP_AUTH_SECRET are not set in the environment.
 *
 * Database credentials come from the environment (VP_DB_HOST, VP_DB_NAME,
 * VP_DB_USER, VP_DB_PASS, VP_DB_PORT) or a .env file next to this script.
 *
 * NOTE: this tooling lives OUTSIDE the web root (install/ is a sibling of
 * app/, not under it) so it can never be reached from a browser after
 * deployment. It refuses to run outside the CLI anyway.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This installer can only be run from the command line.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
$app  = $root . '/app';

/* ------------------------------------------------------------------ */
/* Minimal .env loader (same semantics as app/index.php)              */
/* ------------------------------------------------------------------ */
function vp_install_load_env($path)
{
    if (!is_file($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if ($k === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k)) continue;
        if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
            $v = substr($v, 1, -1);
        }
        if (getenv($k) === false) putenv($k . '=' . $v);
    }
}
vp_install_load_env($root . '/.env');
vp_install_load_env($app . '/.env');

function vp_install_env($key, $default = '')
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

// --users-only: schema/content were imported manually (phpMyAdmin etc.);
// only create the initial admin account + secrets.
$usersOnly = in_array('--users-only', $argv ?? [], true);

$dbHost = vp_install_env('VP_DB_HOST');
$dbName = vp_install_env('VP_DB_NAME');
$dbUser = vp_install_env('VP_DB_USER');
$dbPass = vp_install_env('VP_DB_PASS');
$dbPort = vp_install_env('VP_DB_PORT', '3306');

if ($dbHost === '' || $dbName === '' || $dbUser === '' || $dbPass === '') {
    fwrite(STDERR, "Missing database environment variables.\n"
        . "Set VP_DB_HOST, VP_DB_NAME, VP_DB_USER, VP_DB_PASS (and optionally VP_DB_PORT)\n"
        . "via a .env file in the repository root or app/.env - see .env.example\n");
    exit(1);
}

fwrite(STDOUT, "Connecting to {$dbUser}@{$dbHost}:{$dbPort}/{$dbName} ...\n");
$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int) $dbPort);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Database connection failed: {$mysqli->connect_error}\n");
    exit(1);
}
fwrite(STDOUT, "  OK - server version: " . $mysqli->server_info . "\n");

/* ------------------------------------------------------------------ */
/* 1. Schema (skipped with --users-only)                               */
/* ------------------------------------------------------------------ */
if (!$usersOnly) {
    $sqlFile = __DIR__ . '/install.sql';
    if (!is_file($sqlFile)) {
        fwrite(STDERR, "Missing {$sqlFile}\n");
        exit(1);
    }
    $sql = file_get_contents($sqlFile);
    if ($sql === false) { fwrite(STDERR, "Could not read {$sqlFile}\n"); exit(1); }

    fwrite(STDOUT, "Applying schema install.sql ...\n");
    // Strip full-line -- comments, then split statements on ; at line ends.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = preg_split('/;\s*\r?\n/', $sql);
    $applied = 0;
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '' || strpos($stmt, '--') === 0) continue;
        if (!$mysqli->query($stmt)) {
            fwrite(STDERR, "SQL error in install.sql: {$mysqli->error}\nStatement: "
                . substr(preg_replace('/\s+/', ' ', $stmt), 0, 300) . "\n");
            exit(1);
        }
        $applied++;
    }
    fwrite(STDOUT, "  OK - {$applied} statements applied.\n");
} else {
    fwrite(STDOUT, "--users-only: skipping schema import.\n");
}

/* ------------------------------------------------------------------ */
/* 1b. Upgrade migrations (database/migrations/*.sql)                   */
/*                                                                      */
/* Idempotent: CREATE TABLE IF NOT EXISTS / INSERT IGNORE. Duplicate    */
/* column + duplicate key errors are expected when a migration has      */
/* already been applied and are reported, not fatal.                    */
/* ------------------------------------------------------------------ */
if (!$usersOnly) {
    $migrationDir = dirname(__DIR__) . '/database/migrations';
    $migrations = is_dir($migrationDir) ? glob($migrationDir . '/*.sql') : [];
    sort($migrations);
    foreach ($migrations as $file) {
        fwrite(STDOUT, 'Applying migration ' . basename($file) . " ...\n");
        $sql = (string) file_get_contents($file);
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $applied = $skipped = 0;
        foreach (preg_split('/;\s*\r?\n/', $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') continue;
            if ($mysqli->query($stmt)) {
                $applied++;
                continue;
            }
            // 1060 duplicate column, 1061 duplicate key, 1050 table exists
            if (in_array($mysqli->errno, [1050, 1060, 1061, 1062], true)) {
                $skipped++;
                continue;
            }
            fwrite(STDERR, 'SQL error in ' . basename($file) . ": {$mysqli->error}\nStatement: "
                . substr(preg_replace('/\s+/', ' ', $stmt), 0, 300) . "\n");
            exit(1);
        }
        fwrite(STDOUT, "  OK - {$applied} applied, {$skipped} already present.\n");
    }
}

/* ------------------------------------------------------------------ */
/* 2. Initial SUPER_ADMIN account                                      */
/* ------------------------------------------------------------------ */
$adminEmail = strtolower(trim(vp_install_env('VP_ADMIN_EMAIL', 'admin@vortexprecision.com')));
$adminPass  = vp_install_env('VP_ADMIN_PASSWORD', '');
$firstName  = vp_install_env('VP_ADMIN_FIRSTNAME', 'Admin');
$lastName   = vp_install_env('VP_ADMIN_LASTNAME', 'User');

// Only generate a random password when NONE is supplied. A password provided
// via VP_ADMIN_PASSWORD is always honoured (even if short) so the operator can
// set a known credential — e.g. during recovery of an existing install.
// NOTE: prefer a long, unique password in production.
$generated = false;
if ($adminPass === '') {
    $adminPass = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    $generated = true;
}

$hash = password_hash($adminPass, PASSWORD_BCRYPT);
$mustChange = $generated ? 1 : 0;

$now = date('Y-m-d H:i:s');
$hex = bin2hex(random_bytes(16));
$hex[12] = '4'; // UUID v4 version nibble
$hex[16] = dechex((hexdec($hex[16]) & 0x3) | 0x8); // variant bits
$id = sprintf(
    '%s-%s-%s-%s-%s',
    substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12)
);

$check = $mysqli->query("SELECT id FROM users WHERE email = '" . $mysqli->real_escape_string($adminEmail) . "' LIMIT 1");
if ($check && $check->num_rows > 0) {
    $row = $check->fetch_assoc();
    $envPass = vp_install_env('VP_ADMIN_PASSWORD', '');
    if ($envPass !== '') {
        // A password is supplied in the environment: (re)set it so the
        // installer can recover/repair an already-installed site. The account
        // is reactivated and the forced password-change flag is cleared.
        $upd = $mysqli->prepare('UPDATE users SET password = ?, mustChangePassword = 0, isActive = 1 WHERE id = ?');
        $upd->bind_param('ss', password_hash($envPass, PASSWORD_BCRYPT), $row['id']);
        $upd->execute();
        $upd->close();
        fwrite(STDOUT, "Admin account {$adminEmail} already exists - password reset from VP_ADMIN_PASSWORD.\n");
    } else {
        fwrite(STDOUT, "Admin account {$adminEmail} already exists - leaving it untouched (set VP_ADMIN_PASSWORD to reset).\n");
    }
} else {
    $stmt = $mysqli->prepare(
        "INSERT INTO users (id, email, password, firstName, lastName, role, company, isActive, mustChangePassword, emailVerified, createdAt, updatedAt)
         VALUES (?, ?, ?, ?, ?, 'SUPER_ADMIN', 'Vortex Precision', 1, ?, 1, ?, ?)"
    );
    if (!$stmt) {
        fwrite(STDERR, "Failed to prepare admin INSERT: {$mysqli->error}\n");
        exit(1);
    }
    $stmt->bind_param('sssssiss', $id, $adminEmail, $hash, $firstName, $lastName, $mustChange, $now, $now);
    if (!$stmt->execute()) {
        fwrite(STDERR, "Failed to create admin account: {$stmt->error}\n");
        exit(1);
    }
    $stmt->close();
    fwrite(STDOUT, "  OK - created SUPER_ADMIN account {$adminEmail}.\n");
}

/* ------------------------------------------------------------------ */
/* 3. Demo content (seed.sql) - skipped with --users-only              */
/* ------------------------------------------------------------------ */
if (!$usersOnly) {
    $seedFile = __DIR__ . '/seed.sql';
    if (!is_file($seedFile)) {
        fwrite(STDERR, "Missing {$seedFile}\n");
        exit(1);
    }
    $sql = file_get_contents($seedFile);
    if ($sql === false) { fwrite(STDERR, "Could not read {$seedFile}\n"); exit(1); }
    fwrite(STDOUT, "Seeding demo content seed.sql ...\n");
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = preg_split('/;\s*\r?\n/', $sql);
    $applied = 0;
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '' || strpos($stmt, '--') === 0) continue;
        if (!$mysqli->query($stmt)) {
            fwrite(STDERR, "SQL error in seed.sql: {$mysqli->error}\nStatement: "
                . substr(preg_replace('/\s+/', ' ', $stmt), 0, 300) . "\n");
            exit(1);
        }
        $applied++;
    }
    fwrite(STDOUT, "  OK - {$applied} statements applied.\n");
} else {
    fwrite(STDOUT, "--users-only: skipping content seed.\n");
}

// Point the RFQ admin notification at the real admin email.
$mysqli->query(
    "UPDATE settings SET value = '" . $mysqli->real_escape_string($adminEmail) . "'
     WHERE `key` = 'rfq_admin_email'"
);

/* ------------------------------------------------------------------ */
/* 4. Stable secrets (.secrets.php)                                    */
/* ------------------------------------------------------------------ */
$secretsFile = $app . '/application/config/.secrets.php';
if (vp_install_env('VP_ENCRYPTION_KEY') !== '' && vp_install_env('VP_AUTH_SECRET') !== '') {
    fwrite(STDOUT, "Secrets: using VP_ENCRYPTION_KEY / VP_AUTH_SECRET from the environment.\n");
} elseif (!is_file($secretsFile)) {
    $secrets = [
        'encryption_key' => bin2hex(random_bytes(32)),
        'auth_secret'    => bin2hex(random_bytes(32)),
    ];
    $payload = "<?php defined('BASEPATH') OR exit('No direct script access allowed');\n"
        . "// Auto-generated secrets - do not commit to version control.\n"
        . "return " . var_export($secrets, true) . ";\n";
    if (@file_put_contents($secretsFile, $payload, LOCK_EX) !== false) {
        @chmod($secretsFile, 0600);
        fwrite(STDOUT, "  OK - generated {$secretsFile} (chmod 0600).\n");
    } else {
        fwrite(STDERR, "  WARNING: could not write {$secretsFile} - the app will fall back "
            . "to per-request random keys until VP_ENCRYPTION_KEY / VP_AUTH_SECRET are set.\n");
    }
} else {
    fwrite(STDOUT, "  OK - {$secretsFile} already present.\n");
}

/* ------------------------------------------------------------------ */
/* 5. Summary                                                          */
/* ------------------------------------------------------------------ */
fwrite(STDOUT, "\n" . str_repeat('=', 64) . "\nINSTALLATION COMPLETE\n" . str_repeat('=', 64) . "\n");
fwrite(STDOUT, "Database   : {$dbName} ({$mysqli->server_info})\n");
fwrite(STDOUT, "Admin URL  : /admin/login\n");
fwrite(STDOUT, "Admin user : {$adminEmail}\n");
if ($generated) {
    fwrite(STDOUT, "\n*** TEMPORARY ADMIN PASSWORD (randomly generated) ***\n\n    {$adminPass}\n\n"
        . "You MUST change it immediately after logging in (Users -> edit your\n"
        . "account). The account is flagged to force a password change.\n");
} else {
    fwrite(STDOUT, "Admin password: supplied via VP_ADMIN_PASSWORD (not printed).\n");
}
fwrite(STDOUT, "\nNext steps:\n"
    . "  1. Point your domain at the app/ directory.\n"
    . "  2. Configure production env vars (see .env.example).\n"
    . "  3. Verify HTTPS + /admin/login.\n"
    . "  4. Delete or protect this install/ directory (it is outside the web root).\n"
    . str_repeat('=', 64) . "\n");

$mysqli->close();
exit(0);
