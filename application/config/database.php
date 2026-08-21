<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - MySQL/MariaDB database config (cPanel-friendly).
 *
 * Environment-based configuration. No production credentials are committed
 * to the repository. Supported sources (first match wins):
 *   1. Real environment variables (SetEnv in .htaccess, cPanel env, shell)
 *   2. app/.env file (loaded by index.php - see .env.example)
 *
 * Variables:
 *   VP_DB_HOST, VP_DB_NAME, VP_DB_USER, VP_DB_PASS, VP_DB_PORT (optional)
 *
 * The values below are inert DEVELOPMENT placeholders only - no real
 * credentials are ever committed to this repository. In production
 * (ENVIRONMENT=production) the application refuses to boot unless every
 * VP_DB_* variable is supplied by the environment or app/.env.
 */

if (!function_exists('vp_db_env')) {
    function vp_db_env($key, $default = '')
    {
        // Read from the real environment first, then fall back to the values
        // that index.php's .env loader put into $_ENV / $_SERVER. Some shared
        // hosts restrict putenv()/getenv() for PHP-FPM, which would otherwise
        // make the .env values invisible here.
        $name = 'VP_DB_' . strtoupper($key);
        foreach ([getenv($name), isset($_ENV[$name]) ? $_ENV[$name] : null, isset($_SERVER[$name]) ? $_SERVER[$name] : null] as $v) {
            if ($v !== false && $v !== null && $v !== '') {
                return $v;
            }
        }
        return $default;
    }
}

$db_host = vp_db_env('host', 'localhost');
$db_name = vp_db_env('name', 'vortex_precision');
$db_user = vp_db_env('user', 'vortex_user');
$db_pass = vp_db_env('pass', '');

/**
 * Driver. Production (cPanel) uses mysqli. `sqlite3` is supported for local
 * development / demos where no MySQL server is available: set
 *   VP_DB_DRIVER=sqlite3
 *   VP_DB_NAME=/absolute/path/to/site.sqlite
 * and run `php install/dev-sqlite.php` to build the schema.
 */
$db_driver  = strtolower((string) vp_db_env('driver', 'mysqli'));
$is_sqlite  = ($db_driver === 'sqlite3' || $db_driver === 'sqlite');

if (!$is_sqlite && defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    $missing = [];
    if ($db_host === 'localhost' && vp_db_env('host') === '') $missing[] = 'VP_DB_HOST';
    if (vp_db_env('name') === '') $missing[] = 'VP_DB_NAME';
    if (vp_db_env('user') === '') $missing[] = 'VP_DB_USER';
    if (vp_db_env('pass') === '') $missing[] = 'VP_DB_PASS';
    if ($db_pass === 'change_me') $missing[] = 'VP_DB_PASS';
    if (!empty($missing)) {
        $missing = array_unique($missing);
        $msg = 'Vortex Precision: production database configuration is missing. '
            . 'Set the following environment variables (via app/.env or SetEnv in .htaccess): '
            . implode(', ', $missing)
            . '. See .env.example and docs/INSTALLATION.md.';
        error_log($msg);
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        exit('The application is not configured (database environment variables missing): ' . implode(', ', $missing) . '.');
    }
}

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = [
    'dsn'      => '',
    'hostname' => $is_sqlite ? '' : $db_host . (vp_db_env('port') !== '' ? ':' . vp_db_env('port') : ''),
    'username' => $is_sqlite ? '' : $db_user,
    'password' => $is_sqlite ? '' : $db_pass,
    'database' => $db_name,
    'dbdriver' => $is_sqlite ? 'sqlite3' : 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,        // shared hosting: persistent conns cause issues
    'db_debug' => (ENVIRONMENT === 'development'),
    'cache_on' => FALSE,
    'cachedir' => APPPATH . '../assets/logs/cache/',
    'char_set' => $is_sqlite ? 'utf8' : 'utf8mb4',
    'dbcollat' => $is_sqlite ? 'utf8_general_ci' : 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    // TLS is OFF by default: typical cPanel MySQL runs on localhost without
    // SSL. Set VP_DB_SSL=1 (and optionally ssl_key/ssl_cert/ssl_ca) only if
    // your database server actually requires encrypted connections -
    // forcing MYSQLI_CLIENT_SSL against a plain server breaks the handshake
    // with "MySQL server has gone away".
    'encrypt'  => (vp_db_env('ssl') === '1') ? [
        'ssl_key'    => null,
        'ssl_cert'   => null,
        'ssl_ca'     => null,
        'ssl_verify' => true,
    ] : false,
    'compress'     => FALSE,
    'stricton'     => FALSE,
    'failover'     => [],
    'save_queries' => (ENVIRONMENT !== 'production'),
];
