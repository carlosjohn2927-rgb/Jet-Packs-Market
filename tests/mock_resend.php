<?php
/**
 * Vortex Precision - mock Resend API endpoint (testing only).
 *
 * Boot:  MOCK_RESEND_LOG=/tmp/mock_resend.log php -S 127.0.0.1:8098 app/tests/mock_resend.php
 *
 * Point the app at it with:
 *   VP_RESEND_API_URL=http://127.0.0.1:8098/emails
 *   RESEND_API_KEY=re_test
 *
 * Each POST is appended as a JSON line to MOCK_RESEND_LOG so acceptance
 * tests can verify From/Reply-To/recipient/subject without sending real
 * email. Never deployed; blocked from web access by the root .htaccess.
 */

$log = getenv('MOCK_RESEND_LOG') ?: sys_get_temp_dir() . '/mock_resend.log';
$raw = file_get_contents('php://input');
$parsed = json_decode($raw, true);

file_put_contents(
    $log,
    json_encode([
        'time'      => date('c'),
        'uri'       => $_SERVER['REQUEST_URI'] ?? '',
        'auth'      => $_SERVER['HTTP_AUTHORIZATION'] ?? '',
        'payload'   => $parsed,
    ], JSON_UNESCAPED_SLASHES) . "\n",
    FILE_APPEND | LOCK_EX
);

header('Content-Type: application/json');
echo json_encode(['id' => 'mock_' . bin2hex(random_bytes(6))]);
