<?php
/**
 * CodeIgniter 3 - Vortex Precision
 * Front controller.
 */

// ---------------------------------------------------------------------
// Minimal .env loader (no composer required, cPanel-friendly).
// Reads KEY=VALUE lines from app/.env (document root) and <repo root>/.env.
// Real environment variables always win. This is what makes production
// configuration environment-based without hard-coding credentials.
// ---------------------------------------------------------------------
if (!function_exists('vp_load_env_file')) {
    function vp_load_env_file($path)
    {
        if (!is_file($path) || !is_readable($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            // Strip optional `export `
            if (stripos($line, 'export ') === 0) $line = substr($line, 7);
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ($k === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k)) continue;
            // Strip matching quotes
            if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                $v = substr($v, 1, -1);
            }
            // Never override a real environment variable
            if (getenv($k) === false && !isset($_ENV[$k]) && !isset($_SERVER[$k])) {
                // Guarded: some hardened hosts disable putenv(); $_ENV/$_SERVER
                // are still populated below so the config readers can find the
                // value (they fall back to $_ENV/$_SERVER when getenv fails).
                if (function_exists('putenv')) {
                    @putenv($k . '=' . $v);
                }
                $_ENV[$k] = $v;
                $_SERVER[$k] = $v;
            }
        }
    }
}
vp_load_env_file(__DIR__ . '/.env');
if (!is_file(__DIR__ . '/.env')) {
    // Give the server error log a hint when the .env file is missing, so
    // "The application is not configured" is not a dead end on cPanel.
    error_log('Vortex Precision: .env file not found at ' . __DIR__ . '/.env — ' . __FILE__);
}
if (dirname(__DIR__) . '/.env' !== __DIR__ . '/.env') {
    vp_load_env_file(dirname(__DIR__) . '/.env');
}

define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : (getenv('CI_ENV') !== false ? getenv('CI_ENV') : 'production'));

switch (ENVIRONMENT) {
	case 'development':
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		break;

	case 'testing':
	case 'staging':
		error_reporting(E_ALL);
		ini_set('display_errors', 0);
		break;

	case 'production':
		ini_set('display_errors', 0);
		error_reporting(0);
		break;

	default:
		exit('The application environment is not set correctly.');
}

$system_path = 'system';
$application_folder = 'application';
$view_folder = '';

// Resolve absolute paths relative to the front controller itself, so the
// app boots regardless of the server's working directory (Apache, php -S,
// CLI, cPanel subdirectory setups). __DIR__ is the document root (app/).
if (($_temp = realpath(__DIR__ . DIRECTORY_SEPARATOR . $system_path)) !== FALSE) {
	$system_path = $_temp.DIRECTORY_SEPARATOR;
} else {
	$system_path = rtrim(__DIR__ . DIRECTORY_SEPARATOR . $system_path, '/').DIRECTORY_SEPARATOR;
}

if (!is_dir($system_path)) {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your system folder path does not appear to be set correctly.';
	exit(3);
}

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('BASEPATH', str_replace('\\', '/', $system_path));
define('FCPATH', __DIR__.DIRECTORY_SEPARATOR);
define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));

if (($_temp = realpath(__DIR__ . DIRECTORY_SEPARATOR . $application_folder)) !== FALSE) {
	$application_folder = $_temp;
} else {
	$application_folder = strtr(
		rtrim(__DIR__ . DIRECTORY_SEPARATOR . $application_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
	);
}

define('APPPATH', $application_folder.DIRECTORY_SEPARATOR);

if (!isset($view_folder[0]) && is_dir(APPPATH.'views')) {
	$view_folder = APPPATH.'views';
} else {
	if (!is_dir($view_folder)) {
		header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
		echo 'Your view folder path does not appear to be set correctly.';
		exit(3);
	}

	if (($_temp = realpath($view_folder)) !== FALSE) {
		$view_folder = $_temp;
	} else {
		$view_folder = strtr(
			rtrim($view_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
		);
	}
}

define('VIEWPATH', $view_folder.DIRECTORY_SEPARATOR);

// Vortex Precision app constants
define('VP_BASE_URL', rtrim((function () {
    $env = getenv('VP_BASE_URL');
    if ($env !== false && $env !== '') return $env;
    $is_https = false;
    if (getenv('VP_FORCE_HTTPS') === '1') $is_https = true;
    elseif (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') $is_https = true;
    elseif (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') $is_https = true;
    elseif (isset($_SERVER['HTTP_CF_VISITOR']) && stripos((string) $_SERVER['HTTP_CF_VISITOR'], 'https') !== false) $is_https = true;
    return ($is_https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
})(), '/'));
define('VP_UPLOAD_PATH', FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('VP_UPLOAD_URL', '/assets/uploads/');

require_once BASEPATH.'core/CodeIgniter.php';
