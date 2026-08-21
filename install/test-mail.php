<?php
/**
 * Halyk Petroleum — cPanel SMTP diagnostic (CLI only).
 *
 *   php install/test-mail.php                       (connectivity + auth only)
 *   php install/test-mail.php --to=you@gmail.com    (also sends a real test email)
 *
 * The Mailer library picks a transport in this order:
 *
 *   1. SMTP   — only when BOTH smtp_host AND smtp_pass are non-empty
 *   2. Resend — only when RESEND_API_KEY is set
 *   3. mail() — the silent fallback
 *
 * So the single most common cause of "SMTP doesn't work" is an empty
 * VP_SMTP_PASS in app/.env: the code never even attempts SMTP, quietly drops
 * to mail(), and shared hosts routinely discard that mail as spam.
 *
 * This script reports which transport WOULD be used, opens a raw socket to the
 * SMTP host, reads the banner, runs EHLO, verifies that AUTH is offered, and
 * then performs a real AUTH LOGIN with your credentials. Every step is printed,
 * so a failure tells you exactly which knob to turn in cPanel.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This tool can only be run from the command line.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
$app  = $root . '/app';

function vp_mail_load_env($path)
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
vp_mail_load_env($root . '/.env');
vp_mail_load_env($app . '/.env');

function e($k, $d = '') { $v = getenv($k); return ($v === false || $v === '') ? $d : $v; }

$to = '';
foreach (($argv ?? []) as $a) {
    if (strpos($a, '--to=') === 0) $to = substr($a, 5);
}

$host   = e('VP_SMTP_HOST');
$port   = (int) e('VP_SMTP_PORT', '465');
$from   = e('VP_FROM_EMAIL', 'no-reply@halykpetroleum-kz.com');
$user   = e('VP_SMTP_USER', $from);
$pass   = e('VP_SMTP_PASS');
$crypto = e('VP_SMTP_CRYPTO', ($port === 587 || $port === 25) ? 'tls' : 'ssl');
$name   = e('VP_FROM_NAME', 'Halyk Petroleum');

$ok   = function ($m) { echo "  [ok]    $m\n"; };
$bad  = function ($m) { echo "  [FAIL]  $m\n"; };
$warn = function ($m) { echo "  [warn]  $m\n"; };

echo "Halyk Petroleum - SMTP diagnostic\n" . str_repeat('-', 60) . "\n";

/* ---------------------------------------------------------------- */
/* 1. Which transport will the Mailer actually use?                  */
/* ---------------------------------------------------------------- */
echo "1. Transport selection\n";
echo "  VP_SMTP_HOST   : " . ($host !== '' ? $host : '(empty)') . "\n";
echo "  VP_SMTP_PORT   : {$port}\n";
echo "  VP_SMTP_USER   : " . ($user !== '' ? $user : '(empty)') . "\n";
echo "  VP_SMTP_PASS   : " . ($pass !== '' ? str_repeat('*', min(12, strlen($pass))) . ' (' . strlen($pass) . " chars)" : '(EMPTY)') . "\n";
echo "  VP_SMTP_CRYPTO : {$crypto}\n";
echo "  VP_FROM_EMAIL  : {$from}\n\n";

if ($host !== '' && $pass !== '') {
    $ok('Mailer will use SMTP');
} elseif (e('RESEND_API_KEY') !== '') {
    $warn('SMTP is incomplete -> Mailer will use the Resend API instead');
} else {
    $bad('SMTP is incomplete -> Mailer silently falls back to PHP mail()');
    if ($host === '') $bad('VP_SMTP_HOST is empty');
    if ($pass === '') {
        $bad('VP_SMTP_PASS is empty. This is the usual cause.');
        echo "\n          Fix: cPanel > Email Accounts > (create/see) {$user}\n";
        echo "               > Manage > set a password, then put that exact\n";
        echo "               password in app/.env as VP_SMTP_PASS=...\n";
    }
    echo "\nStopping: cannot test SMTP without a host and a password.\n";
    exit(1);
}

/* ---------------------------------------------------------------- */
/* 2. DNS + TCP                                                      */
/* ---------------------------------------------------------------- */
echo "\n2. Network reachability\n";
$ip = gethostbyname($host);
if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
    $bad("DNS lookup for {$host} failed - check the hostname (try 'localhost')");
    exit(1);
}
$ok("{$host} resolves to {$ip}");

$transport = ($crypto === 'ssl') ? 'ssl://' : 'tcp://';
$ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$errno = 0; $errstr = '';
$sock = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
if (!$sock) {
    $bad("cannot open {$transport}{$host}:{$port} - {$errstr} (errno {$errno})");
    echo "\n          Fixes to try, in order:\n";
    echo "          - Port 465 blocked outbound? Use VP_SMTP_HOST=localhost with\n";
    echo "            VP_SMTP_PORT=25 and VP_SMTP_CRYPTO= (blank). On cPanel the\n";
    echo "            local MTA accepts unauthenticated mail from the server itself.\n";
    echo "          - Or try VP_SMTP_PORT=587 with VP_SMTP_CRYPTO=tls.\n";
    echo "          - Confirm the mail subdomain exists: mail.halykpetroleum-kz.com\n";
    exit(1);
}
stream_set_timeout($sock, 15);
$ok("TCP connect to {$host}:{$port} succeeded ({$transport})");

/* ---------------------------------------------------------------- */
/* 3. SMTP conversation                                              */
/* ---------------------------------------------------------------- */
function smtp_read($sock)
{
    $out = '';
    while (($line = fgets($sock, 1024)) !== false) {
        $out .= $line;
        // Last line of a multiline reply has a space in position 4.
        if (strlen($line) < 4 || $line[3] !== '-') break;
    }
    return $out;
}
function smtp_cmd($sock, $cmd, $echo = null)
{
    fwrite($sock, $cmd . "\r\n");
    $r = smtp_read($sock);
    if ($echo !== null) echo "  > {$echo}\n";
    echo '  < ' . trim(str_replace("\r\n", ' | ', $r)) . "\n";
    return $r;
}

echo "\n3. SMTP conversation\n";
$banner = smtp_read($sock);
echo '  < ' . trim($banner) . "\n";
if (strpos($banner, '220') !== 0) {
    $bad('server did not send a 220 greeting');
    fclose($sock);
    exit(1);
}

$ehloName = parse_url(e('VP_BASE_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
$reply = smtp_cmd($sock, 'EHLO ' . $ehloName, 'EHLO ' . $ehloName);

if ($crypto === 'tls') {
    $r = smtp_cmd($sock, 'STARTTLS', 'STARTTLS');
    if (strpos($r, '220') !== 0) {
        $bad('STARTTLS refused - try VP_SMTP_PORT=465 with VP_SMTP_CRYPTO=ssl');
        fclose($sock);
        exit(1);
    }
    if (!@stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        $bad('TLS handshake failed');
        fclose($sock);
        exit(1);
    }
    $ok('TLS negotiated');
    $reply = smtp_cmd($sock, 'EHLO ' . $ehloName, 'EHLO ' . $ehloName);
}

if (stripos($reply, 'AUTH') === false) {
    $warn('server did not advertise AUTH. If this is localhost:25 that is normal');
    $warn('and expected - the local MTA relays without authentication.');
    smtp_cmd($sock, 'QUIT', 'QUIT');
    fclose($sock);
    echo "\nConnectivity is fine. Skipping the AUTH test.\n";
    exit(0);
}
$ok('server advertises AUTH');

$r = smtp_cmd($sock, 'AUTH LOGIN', 'AUTH LOGIN');
if (strpos($r, '334') !== 0) {
    $bad('AUTH LOGIN refused');
    fclose($sock);
    exit(1);
}
$r = smtp_cmd($sock, base64_encode($user), '(username)');
$r = smtp_cmd($sock, base64_encode($pass), '(password)');

if (strpos($r, '235') === 0) {
    $ok("authenticated as {$user}");
} else {
    $bad('authentication REJECTED by the server');
    echo "\n          The host and port are right but the credentials are not.\n";
    echo "          - VP_SMTP_USER must be the FULL email address ({$user}).\n";
    echo "          - Reset that mailbox's password in cPanel > Email Accounts\n";
    echo "            and copy it verbatim into VP_SMTP_PASS (no quotes needed;\n";
    echo "            if it contains # the loader keeps it, but avoid leading/\n";
    echo "            trailing spaces).\n";
    fclose($sock);
    exit(1);
}

/* ---------------------------------------------------------------- */
/* 4. Optional real send                                             */
/* ---------------------------------------------------------------- */
if ($to === '') {
    smtp_cmd($sock, 'QUIT', 'QUIT');
    fclose($sock);
    echo "\n" . str_repeat('-', 60) . "\n";
    echo "SMTP is correctly configured. Re-run with --to=you@example.com to\n";
    echo "send a real test message.\n";
    exit(0);
}

echo "\n4. Sending a test message to {$to}\n";
$r = smtp_cmd($sock, 'MAIL FROM:<' . $from . '>', 'MAIL FROM');
if (strpos($r, '250') !== 0) {
    $bad("server rejected the sender {$from} - it must be a mailbox on this domain");
    fclose($sock);
    exit(1);
}
$r = smtp_cmd($sock, 'RCPT TO:<' . $to . '>', 'RCPT TO');
if (strpos($r, '25') !== 0) {
    $bad('server rejected the recipient (relaying denied?)');
    fclose($sock);
    exit(1);
}
$r = smtp_cmd($sock, 'DATA', 'DATA');
if (strpos($r, '354') !== 0) {
    $bad('server refused DATA');
    fclose($sock);
    exit(1);
}

$boundaryDate = date('r');
$body = "From: {$name} <{$from}>\r\n"
      . "To: <{$to}>\r\n"
      . "Subject: Halyk Petroleum SMTP test\r\n"
      . "Date: {$boundaryDate}\r\n"
      . "MIME-Version: 1.0\r\n"
      . "Content-Type: text/html; charset=utf-8\r\n"
      . "\r\n"
      . "<p>This is a test message from the Halyk Petroleum website.</p>\r\n"
      . "<p>If you are reading this, cPanel SMTP is working: quote requests and\r\n"
      . "contact-form notifications will be delivered.</p>\r\n"
      . "<p>Host: {$host}:{$port} ({$crypto})<br>Sent: {$boundaryDate}</p>\r\n"
      . "\r\n.";
fwrite($sock, $body . "\r\n");
$r = smtp_read($sock);
echo '  < ' . trim($r) . "\n";
smtp_cmd($sock, 'QUIT', 'QUIT');
fclose($sock);

if (strpos($r, '250') === 0) {
    $ok('message accepted for delivery');
    echo "\nCheck the inbox (and the spam folder) of {$to}.\n";
    echo "If it lands in spam, add SPF and DKIM records in cPanel >\n";
    echo "Email Deliverability for halykpetroleum-kz.com.\n";
} else {
    $bad('server did not accept the message');
    exit(1);
}
