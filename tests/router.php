<?php
/**
 * Vortex Precision - PHP built-in server router (development/testing only).
 *
 * Usage:  php -S 127.0.0.1:8099 -t app app/tests/router.php
 *
 * Static files (assets) are served by the built-in server; everything else
 * goes through CodeIgniter. Not used in production (Apache handles routing
 * via .htaccess) and blocked from web access by the root .htaccess.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$docroot = __DIR__ . '/..';
$file = $docroot . '/' . rawurldecode((string) $uri);

if ($uri !== '/' && $uri !== '/index.php' && is_file($file)) {
    return false; // let the built-in server serve this static file
}

// The built-in server points SCRIPT_NAME at the requested path when it looks
// like a file (e.g. /sitemap.xml, /robots.txt), which makes CodeIgniter parse
// an empty URI and fall back to the default controller. Apache always reports
// /index.php here, so normalise it for parity with production.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF']    = '/index.php';

require $docroot . '/index.php';
