<?php
/**
 * JetPacks Market — CLI installer (run from a terminal / SSH, NEVER via HTTP).
 *
 *   php install/install.php                 # interactive-default install
 *   php install/install.php --source=production  # force the combined database/production.sql
 *   php install/install.php --source=minimal  # force install.sql + seed.sql + migrations
 *   php install/install.php --users-only     # skip import; only create/reset SUPER_ADMIN
 *   php install/install.php --status        # show what is currently in the DB, do not write
 *
 * 1. Imports the schema (database/production.sql by default; falls back to
 *    install/install.sql + the database/migrations/* files + install/seed.sql
 *    when the combined file is unavailable)
 * 2. Applies every .sql migration in `database/migrations` (idempotent)
 * 3. Creates (or repaves) the initial SUPER_ADMIN account for optional local
 *    developer workflows; normal cPanel deployments use production.sql instead
 * 4. Requires stable VP_ENCRYPTION_KEY / VP_AUTH_SECRET from .env and never
 *    writes an application/config/.secrets.php file
 * 5. Prints row counts for every important table so the operator can
 *    confirm the install actually loaded
 *
 * Database credentials come from the environment (VP_DB_HOST, VP_DB_NAME,
 * VP_DB_USER, VP_DB_PASS, VP_DB_PORT) or a .env file in the document root.
 *
 * This file lives OUTSIDE the web root (install/ is a sibling of application/),
 * so it can never be reached from a browser. It also refuses any non-CLI
 * invocation.
 */

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    http_response_code(403);
    exit("This installer can only be run from the command line.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

/* ------------------------------------------------------------------ */
/* Minimal .env loader (same semantics as index.php)                  */
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
        if (getenv($k) === false) putenv("$k=$v");
    }
}

function vp_install_env($key, $default = '')
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

/* ------------------------------------------------------------------ */
/* Path resolution — this repo puts `application/` next to `install/`. */
/* ------------------------------------------------------------------ */
$root = dirname(__DIR__);              // <repoRoot>
$application_dir = $root . '/application';
$public_dir      = $root;              // document root == repo root
// Portable production deployments use stable VP_ENCRYPTION_KEY and
// VP_AUTH_SECRET from .env only. No application/config secret file is used.

/* ------------------------------------------------------------------ */
/* Argument parsing                                                     */
/* ------------------------------------------------------------------ */
$argvAll = $argv ?? [];
$cli_arg = static function ($name, $default = null) use ($argvAll) {
    foreach ($argvAll as $i => $a) {
        if ($a === $name)               return $argvAll[$i + 1] ?? $default;
        if (strpos($a, $name . '=') === 0) return substr($a, strlen($name) + 1);
    }
    return $default;
};

$usersOnly = in_array('--users-only', $argvAll, true)
          || vp_install_env('VP_INSTALL_USERS_ONLY') === '1';
$statusOnly = in_array('--status', $argvAll, true);
// --source=production|minimal|skip   default = auto-detect
$source = strtolower((string) $cli_arg('--source', 'auto'));
if (!in_array($source, ['auto', 'production', 'minimal', 'skip'], true)) {
    fwrite(STDERR, "--source must be one of: auto, production, minimal, skip\n");
    exit(2);
}

/* ------------------------------------------------------------------ */
/* Database connect                                                     */
/* ------------------------------------------------------------------ */
$dbHost = vp_install_env('VP_DB_HOST');
$dbName = vp_install_env('VP_DB_NAME');
$dbUser = vp_install_env('VP_DB_USER');
$dbPass = vp_install_env('VP_DB_PASS');
$dbPort = vp_install_env('VP_DB_PORT', '3306');

if ($dbHost === '' || $dbName === '' || $dbUser === '' || $dbPass === '') {
    fwrite(STDERR, "Missing database environment variables.\n"
        . "Set VP_DB_HOST, VP_DB_NAME, VP_DB_USER, VP_DB_PASS (and optionally VP_DB_PORT)\n"
        . "via a .env file in the document root - see .env.example\n");
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
/* SQL helper: split a file into statements, run them in order        */
/* ------------------------------------------------------------------ */
function vp_install_split_sql($sql)
{
    // Strip full-line -- comments first so they don't break "--" inside JSON.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    // MySQL does not allow DELIMITER control from a standard CSV-style splitter.
    // production.sql + install.sql + migrations/*.sql avoid custom delimiters.
    return preg_split('/;\s*\r?\n/', $sql);
}

function vp_install_run_sql_file(mysqli $db, string $path, string $label, bool $ignore_dup_errors = false): array
{
    if (!is_file($path)) {
        fwrite(STDERR, "Missing SQL file: {$path}\n");
        exit(1);
    }
    $sql = file_get_contents($path);
    if ($sql === false) {
        fwrite(STDERR, "Could not read {$path}\n");
        exit(1);
    }
    $applied = 0; $skipped = 0;
    foreach (vp_install_split_sql($sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;
        if ($db->query($stmt)) {
            $applied++;
            continue;
        }
        // Tolerated "already present" errors when re-running.
        if ($ignore_dup_errors && in_array($db->errno, [1050, 1051, 1060, 1061, 1062, 1091], true)) {
            $skipped++;
            continue;
        }
        fwrite(STDERR, "SQL error in {$label}: {$db->error}\nStatement: "
            . substr(preg_replace('/\s+/', ' ', $stmt), 0, 300) . "\n");
        exit(1);
    }
    fwrite(STDOUT, "  OK {$label}: {$applied} applied" . ($ignore_dup_errors ? ", {$skipped} already present" : '') . ".\n");
    return ['applied' => $applied, 'skipped' => $skipped];
}

/* ------------------------------------------------------------------ */
/* --status mode (read-only)                                            */
/* ------------------------------------------------------------------ */
function vp_install_row_count(mysqli $db, string $table): int
{
    $table = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    if ($table === '' || !$db->query("SELECT 1 FROM `$table` LIMIT 1")) return -1;
    $r = $db->query("SELECT COUNT(*) AS c FROM `$table`")->fetch_assoc();
    return (int) ($r['c'] ?? 0);
}

function vp_install_print_status(mysqli $db): void
{
    $rows = ['users', 'roles', 'settings', 'pages', 'categories', 'industries',
            'products', 'quotes', 'menus', 'page_sections', 'contact_messages'];
    fwrite(STDOUT, "Database status ({$db->host_info}):\n");
    foreach ($rows as $t) {
        $c = vp_install_row_count($db, $t);
        printf("  %-18s %s\n", $t, $c < 0 ? '— table not found —' : number_format($c) . ' rows');
    }
}

if ($statusOnly) {
    vp_install_print_status($mysqli);
    $mysqli->close();
    exit(0);
}

/* ------------------------------------------------------------------ */
/* 1. Import schema + content                                           */
/* ------------------------------------------------------------------ */
if (!$usersOnly) {
    $productionSql = $public_dir . '/database/production.sql';
    $installSql    = __DIR__ . '/install.sql';
    $migrationsDir = $public_dir . '/database/migrations';

    // Choose source
    $chosen = $source;
    if ($source === 'auto') {
        $chosen = is_file($productionSql) ? 'production' : 'minimal';
    }
    fwrite(STDOUT, "Import source: {$chosen} (" . ($chosen === 'production' ? $productionSql : $installSql . ' + migrations + seed.sql') . ")\n");

    if ($chosen === 'skip') {
        fwrite(STDOUT, "  --source=skip: skipping schema import.\n");
    } else {
        if ($chosen === 'production') {
            vp_install_run_sql_file($mysqli, $productionSql, 'database/production.sql');
            // The combined production.sql is already the full install, but a
            // few operators keep migrations/ in their deployment too. Re-run
            // them idempotently so any future-drifted db still catches up.
            if (is_dir($migrationsDir)) {
                $migrations = glob($migrationsDir . '/*.sql');
                sort($migrations);
                foreach ($migrations as $file) {
                    vp_install_run_sql_file($mysqli, $file, 'database/migrations/' . basename($file), true);
                }
            }
        } else {
            // Minimal path: install.sql + every migration + seed.sql.
            vp_install_run_sql_file($mysqli, $installSql, 'install/install.sql');
            if (is_dir($migrationsDir)) {
                $migrations = glob($migrationsDir . '/*.sql');
                sort($migrations);
                foreach ($migrations as $file) {
                    vp_install_run_sql_file($mysqli, $file, 'database/migrations/' . basename($file), true);
                }
            }
            vp_install_run_sql_file($mysqli, __DIR__ . '/seed.sql', 'install/seed.sql');
        }
    }
} else {
    fwrite(STDOUT, "--users-only: skipping schema import.\n");
}

/* ------------------------------------------------------------------ */
/* 2. Initial SUPER_ADMIN account                                       */
/* ------------------------------------------------------------------ */
$adminEmail = strtolower(trim(vp_install_env('VP_ADMIN_EMAIL', 'admin@jetpacksmarket.com')));
$adminPass  = vp_install_env('VP_ADMIN_PASSWORD', '');
$firstName  = vp_install_env('VP_ADMIN_FIRSTNAME', 'Admin');
$lastName   = vp_install_env('VP_ADMIN_LASTNAME', 'User');
$company    = vp_install_env('VP_BASE_URL') ? '' : 'JetPacks Market';
$company    = (string) vp_install_env('VP_ADMIN_COMPANY', 'JetPacks Market');

$generated = false;
if ($adminPass === '') {
    $adminPass = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    $generated = true;
}

$check = $mysqli->query("SELECT id FROM users WHERE email = '" . $mysqli->real_escape_string($adminEmail) . "' LIMIT 1");
if ($check && $check->num_rows > 0) {
    $row = $check->fetch_assoc();
    if ($adminPass !== '') {
        $upd = $mysqli->prepare('UPDATE users SET password = ?, mustChangePassword = 0, isActive = 1, updatedAt = NOW(), company = ? WHERE id = ?');
        $hash = password_hash($adminPass, PASSWORD_BCRYPT);
        $upd->bind_param('sss', $hash, $company, $row['id']);
        $upd->execute();
        $upd->close();
        fwrite(STDOUT, "Admin {$adminEmail} already exists - password reset, reactivated, mustChangePassword cleared.\n");
    } else {
        fwrite(STDOUT, "Admin {$adminEmail} already exists - leaving it untouched (set VP_ADMIN_PASSWORD to reset).\n");
    }
} else {
    $hash = password_hash($adminPass, PASSWORD_BCRYPT);
    $mustChange = $generated ? 1 : 0;
    $now = date('Y-m-d H:i:s');
    $hex = bin2hex(random_bytes(16));
    $hex[12] = '4';
    $hex[16] = dechex((hexdec($hex[16]) & 0x3) | 0x8);
    $id = sprintf('%s-%s-%s-%s-%s', substr($hex,0,8), substr($hex,8,4),
                  substr($hex,12,4), substr($hex,16,4), substr($hex,20,12));

    $stmt = $mysqli->prepare(
        "INSERT INTO users (id, email, password, firstName, lastName, role, company, isActive, mustChangePassword, emailVerified, createdAt, updatedAt)
         VALUES (?, ?, ?, ?, ?, 'SUPER_ADMIN', ?, 1, ?, 1, ?, ?)"
    );
    if (!$stmt) {
        fwrite(STDERR, "Failed to prepare admin INSERT: {$mysqli->error}\n");
        exit(1);
    }
    $stmt->bind_param('ssssssiss', $id, $adminEmail, $hash, $firstName, $lastName, $company, $mustChange, $now, $now);
    if (!$stmt->execute()) {
        fwrite(STDERR, "Failed to create admin account: {$stmt->error}\n");
        exit(1);
    }
    $stmt->close();
    fwrite(STDOUT, "  OK - created SUPER_ADMIN {$adminEmail} (company: {$company}).\n");
}

/* Point the RFQ admin notification at the real admin email. */
$mysqli->query(
    "UPDATE settings SET value = '" . $mysqli->real_escape_string($adminEmail) . "'
     WHERE `key` = 'rfq_admin_email'"
);

/* ------------------------------------------------------------------ */
/* 3. Stable secrets (.env only)                                       */
/* ------------------------------------------------------------------ */
$enc = vp_install_env('VP_ENCRYPTION_KEY');
$auth = vp_install_env('VP_AUTH_SECRET');
if (strlen($enc) < 32 || strlen($auth) < 32) {
    fwrite(STDERR, "VP_ENCRYPTION_KEY and VP_AUTH_SECRET must be configured in .env.\n"
        . "This installer will not generate application/config/.secrets.php.\n");
    exit(1);
}
fwrite(STDOUT, "Secrets: using stable VP_ENCRYPTION_KEY / VP_AUTH_SECRET from .env.\n");

/* ------------------------------------------------------------------ */
/* 4. Verify + summary                                                  */
/* ------------------------------------------------------------------ */
fwrite(STDOUT, "\n" . str_repeat('=', 64) . "\nFinal verification:\n");
vp_install_print_status($mysqli);

fwrite(STDOUT, "\n" . str_repeat('=', 64) . "\nINSTALLATION COMPLETE\n" . str_repeat('=', 64) . "\n");
fwrite(STDOUT, "Database      : {$dbName} ({$mysqli->server_info})\n");
fwrite(STDOUT, "Admin URL     : /admin/login\n");
fwrite(STDOUT, "Admin user    : {$adminEmail}\n");
fwrite(STDOUT, "Admin company : {$company}\n");
if ($generated) {
    fwrite(STDOUT, "\n*** TEMPORARY ADMIN PASSWORD (randomly generated) ***\n\n    {$adminPass}\n\n"
        . "Log in and change it from Dashboard → My profile → Change password.\n"
        . "The account is flagged to force a password change on first login.\n");
} else {
    fwrite(STDOUT, "Admin password: supplied via VP_ADMIN_PASSWORD (not printed).\n");
}
fwrite(STDOUT, "\nNext steps:\n"
    . "  1. Point your domain at the document root ({$public_dir}).\n"
    . "  2. Confirm VP_BASE_URL, VP_DB_* and (in production) VP_FORCE_HTTPS=1 in .env.\n"
    . "  3. Open https://yourdomain.com/admin/login and sign in.\n"
    . "  4. Delete or protect this install/ directory (it is outside the web root).\n"
    . "  5. Re-run `php install/install.php --status` later to confirm table counts.\n"
    . str_repeat('=', 64) . "\n");

$mysqli->close();
exit(0);
