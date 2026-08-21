<?php
/**
 * Halyk Petroleum — development SQLite installer.
 *
 * PRODUCTION USES MySQL/MariaDB (install/install.sql). This script exists so
 * the application can be run, demoed and tested on a machine that has no
 * MySQL server: it translates the canonical MySQL schema + seed files into
 * SQLite and writes them to a single file database.
 *
 * Usage:
 *   php install/dev-sqlite.php [path/to/site.sqlite]
 *
 * Then point the app at it:
 *   VP_DB_DRIVER=sqlite3
 *   VP_DB_NAME=/absolute/path/to/site.sqlite
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$root   = dirname(__DIR__);
$target = $argv[1] ?? ($root . '/database/dev.sqlite');

$files = [
    $root . '/install/install.sql',
    $root . '/database/migrations/001_cms_and_permissions.sql',
    $root . '/install/seed.sql',
    $root . '/database/migrations/002_cms_seed.sql',
];

echo "Halyk Petroleum — dev SQLite installer\n";
echo "Target: {$target}\n\n";

@mkdir(dirname($target), 0775, true);
if (is_file($target)) {
    unlink($target);
    echo "Removed existing database.\n";
}

$pdo = new PDO('sqlite:' . $target);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

/* --------------------------------------------------------------------- */

function uuid4()
{
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
    $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

/** Split a SQL script into statements, respecting quoted strings. */
function sql_statements($sql)
{
    $out = [];
    $buf = '';
    $len = strlen($sql);
    $q   = null;
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        if ($q !== null) {
            $buf .= $c;
            if ($c === '\\' && $i + 1 < $len) { $buf .= $sql[++$i]; continue; }
            if ($c === $q) $q = null;
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') { $q = $c; $buf .= $c; continue; }
        if ($c === '-' && substr($sql, $i, 2) === '--') {
            $nl = strpos($sql, "\n", $i);
            $i  = $nl === false ? $len : $nl;
            continue;
        }
        if ($c === ';') { $out[] = trim($buf); $buf = ''; continue; }
        $buf .= $c;
    }
    if (trim($buf) !== '') $out[] = trim($buf);
    return array_values(array_filter($out, function ($s) { return $s !== ''; }));
}

/** Replace MySQL function calls that SQLite has no equivalent for. */
function translate_functions($stmt)
{
    // UUID() -> literal uuid
    while (($p = stripos($stmt, 'UUID()')) !== false) {
        $stmt = substr($stmt, 0, $p) . "'" . uuid4() . "'" . substr($stmt, $p + 6);
    }
    // NOW() / CURRENT_TIMESTAMP() -> literal datetime
    $stmt = preg_replace('/\bNOW\(\)/i', "'" . date('Y-m-d H:i:s') . "'", $stmt);

    // JSON_ARRAY('a','b') -> '["a","b"]'
    while (($p = stripos($stmt, 'JSON_ARRAY(')) !== false) {
        $start = $p + strlen('JSON_ARRAY(');
        $depth = 1; $i = $start; $q = null; $len = strlen($stmt);
        for (; $i < $len && $depth > 0; $i++) {
            $c = $stmt[$i];
            if ($q !== null) {
                if ($c === '\\') { $i++; continue; }
                if ($c === $q) $q = null;
                continue;
            }
            if ($c === "'" || $c === '"') { $q = $c; continue; }
            if ($c === '(') $depth++;
            if ($c === ')') $depth--;
        }
        $inner = substr($stmt, $start, $i - $start - 1);
        $items = [];
        foreach (split_args($inner) as $arg) {
            $arg = trim($arg);
            if ($arg === '') continue;
            if ($arg[0] === "'" ) {
                $val = substr($arg, 1, -1);
                $val = str_replace(["\\'", "''", '\\"', '\\\\'], ["'", "'", '"', '\\'], $val);
                $items[] = $val;
            } else {
                $items[] = $arg;
            }
        }
        $json = json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $json = "'" . str_replace("'", "''", $json) . "'";
        $stmt = substr($stmt, 0, $p) . $json . substr($stmt, $i);
    }
    return $stmt;
}

function split_args($s)
{
    $out = []; $buf = ''; $q = null; $depth = 0; $len = strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $c = $s[$i];
        if ($q !== null) {
            $buf .= $c;
            if ($c === '\\' && $i + 1 < $len) { $buf .= $s[++$i]; continue; }
            if ($c === $q) $q = null;
            continue;
        }
        if ($c === "'" || $c === '"') { $q = $c; $buf .= $c; continue; }
        if ($c === '(') { $depth++; $buf .= $c; continue; }
        if ($c === ')') { $depth--; $buf .= $c; continue; }
        if ($c === ',' && $depth === 0) { $out[] = $buf; $buf = ''; continue; }
        $buf .= $c;
    }
    if (trim($buf) !== '') $out[] = $buf;
    return $out;
}

/** Convert a MySQL CREATE TABLE into SQLite CREATE TABLE + CREATE INDEX list. */
function translate_create_table($stmt)
{
    if (!preg_match('/CREATE TABLE(?: IF NOT EXISTS)?\s+`?([A-Za-z0-9_]+)`?\s*\((.*)\)\s*(ENGINE.*)?$/is', $stmt, $m)) {
        return [$stmt, []];
    }
    $table = $m[1];
    $body  = $m[2];

    $cols = [];
    $post = [];
    foreach (split_args($body) as $line) {
        $line = trim($line);
        if ($line === '') continue;

        if (preg_match('/^(UNIQUE\s+)?(?:KEY|INDEX)\s+`?([A-Za-z0-9_]+)`?\s*\((.+)\)$/i', $line, $km)) {
            $unique = trim($km[1]) !== '' ? 'UNIQUE ' : '';
            $post[] = "CREATE {$unique}INDEX IF NOT EXISTS `{$km[2]}` ON `{$table}` ({$km[3]})";
            continue;
        }
        if (preg_match('/^FULLTEXT\s+/i', $line)) continue;   // no SQLite equivalent
        if (preg_match('/^(PRIMARY KEY|CONSTRAINT|FOREIGN KEY|CHECK)/i', $line)) {
            $cols[] = $line;
            continue;
        }

        // Regular column definition
        $line = preg_replace('/\bENUM\s*\([^)]*\)/i', 'TEXT', $line);
        $line = preg_replace('/\b(LONGTEXT|MEDIUMTEXT|TINYTEXT|JSON|BLOB|LONGBLOB)\b/i', 'TEXT', $line);
        $line = preg_replace('/\bTINYINT\s*\(\s*1\s*\)/i', 'INTEGER', $line);
        $line = preg_replace('/\b(BIGINT|SMALLINT|MEDIUMINT|TINYINT|INT)\s*(\(\d+\))?\s*(UNSIGNED)?/i', 'INTEGER', $line);
        $line = preg_replace('/\bDOUBLE\b/i', 'REAL', $line);
        $line = preg_replace('/\bDEFAULT\s+CURRENT_TIMESTAMP\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', 'DEFAULT CURRENT_TIMESTAMP', $line);
        $line = preg_replace('/\bON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $line);
        $line = preg_replace('/\bDEFAULT\s+\(UUID\(\)\)/i', '', $line);
        $line = preg_replace('/\bCHARACTER SET\s+\S+/i', '', $line);
        $line = preg_replace('/\bCOLLATE\s+\S+/i', '', $line);
        $line = preg_replace('/\bAUTO_INCREMENT\b/i', '', $line);
        $cols[] = trim($line);
    }

    $create = "CREATE TABLE IF NOT EXISTS `{$table}` (\n  " . implode(",\n  ", $cols) . "\n)";
    return [$create, $post];
}

/* --------------------------------------------------------------------- */

$applied = 0;
$indexes = [];
$deferred = [];   // data statements, applied after the schema + indexes exist

foreach ($files as $file) {
    if (!is_file($file)) {
        echo "  (skipped missing {$file})\n";
        continue;
    }
    echo 'Applying ' . basename($file) . " ...\n";
    $sql = file_get_contents($file);

    foreach (sql_statements($sql) as $stmt) {
        if (preg_match('/^(SET|LOCK|UNLOCK|\/\*|DELIMITER)/i', $stmt)) continue;

        if (preg_match('/^CREATE TABLE/i', $stmt)) {
            [$create, $post] = translate_create_table($stmt);
            $pdo->exec($create);
            $indexes = array_merge($indexes, $post);
            $applied++;
            continue;
        }

        if (preg_match('/^(CREATE (UNIQUE )?INDEX)/i', $stmt)) {
            $indexes[] = $stmt;
            continue;
        }

        if (preg_match('/^(ALTER TABLE)/i', $stmt)) {
            // MySQL-only migration helpers: try, ignore failures (SQLite is
            // rebuilt from scratch by this script anyway).
            try { $pdo->exec($stmt); $applied++; } catch (PDOException $e) {}
            continue;
        }

        $deferred[] = $stmt;
    }
}

foreach ($indexes as $idx) {
    try { $pdo->exec($idx); } catch (PDOException $e) {}
}

{
    foreach ($deferred as $stmt) {
        $stmt = translate_functions($stmt);
        $stmt = preg_replace('/^INSERT IGNORE/i', 'INSERT OR IGNORE', $stmt);
        if (preg_match('/\bON DUPLICATE KEY UPDATE\b/i', $stmt)) {
            $stmt = preg_replace('/\s*ON DUPLICATE KEY UPDATE.*$/is', '', $stmt);
            $stmt = preg_replace('/^INSERT(\s+OR\s+\w+)?\s+INTO/i', 'INSERT OR REPLACE INTO', $stmt);
        }
        $stmt = str_replace('\\"', '"', $stmt);
        $stmt = str_replace("\\'", "''", $stmt);
        try {
            $pdo->exec($stmt);
            $applied++;
        } catch (PDOException $e) {
            echo '  ! ' . substr(preg_replace('/\s+/', ' ', $stmt), 0, 120) . "\n    " . $e->getMessage() . "\n";
        }
    }
}

echo "\nApplied {$applied} statements, " . count($indexes) . " indexes.\n";

/* ---------- Accounts ---------- */

$now = date('Y-m-d H:i:s');
$accounts = [
    [
        'email' => getenv('VP_ADMIN_EMAIL') ?: 'superadmin@halykpetroleum-kz.com',
        'pass'  => getenv('VP_ADMIN_PASSWORD') ?: 'SuperAdmin123!',
        'role'  => 'SUPER_ADMIN',
        'first' => 'Super', 'last' => 'Admin',
    ],
    [
        'email' => 'admin@halykpetroleum-kz.com',
        'pass'  => getenv('VP_DEMO_ADMIN_PASSWORD') ?: 'Admin123!',
        'role'  => 'ADMIN',
        'first' => 'Site', 'last' => 'Admin',
    ],
];

foreach ($accounts as $a) {
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$a['email']]);
    $existing = $st->fetchColumn();
    $hash = password_hash($a['pass'], PASSWORD_BCRYPT);
    if ($existing) {
        $pdo->prepare('UPDATE users SET password=?, role=?, isActive=1, mustChangePassword=0 WHERE id=?')
            ->execute([$hash, $a['role'], $existing]);
        echo "Updated {$a['role']}: {$a['email']}\n";
    } else {
        $pdo->prepare('INSERT INTO users (id,email,password,firstName,lastName,role,isActive,mustChangePassword,emailVerified,createdAt,updatedAt)
                       VALUES (?,?,?,?,?,?,1,0,1,?,?)')
            ->execute([uuid4(), $a['email'], $hash, $a['first'], $a['last'], $a['role'], $now, $now]);
        echo "Created {$a['role']}: {$a['email']}\n";
    }
}

$tables = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'")->fetchColumn();
echo "\nDone. {$tables} tables in {$target}\n";
