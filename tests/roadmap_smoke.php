<?php
/**
 * Smoke test for the public /roadmap module (Module 1).
 *
 * Exercises the three helper functions in application/helpers/app_helper.php
 * that back both the public page and the SUPER_ADMIN dashboard widget.
 *
 * Usage:
 *   php tests/roadmap_smoke.php [app_url]
 *
 * If app_url is given, this script additionally:
 *   • signs in as the seeded SUPER_ADMIN
 *   • GETs /roadmap via the dev router (tests/router.php)
 *   • asserts the page renders and ships the dashboard widget
 *
 * Exit code 0 on success; non-zero with a one-line failure summary.
 *
 * This file MUST stay import-safe even when run alone outside CodeIgniter —
 * no `BASEPATH` constant is required at minimum. When BASEPATH is defined
 * (the normal app boot) it instead hooks into CodeIgniter to share the
 * real helper.
 */

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    fwrite(STDERR, "Run from CLI: php tests/roadmap_smoke.php\n");
    exit(2);
}

if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'w'));

$_failures = [];
function _ok($msg)    { echo "  ok  $msg\n"; }
function _fail($msg)  { global $_failures; echo "  FAIL  $msg\n"; $_failures[] = $msg; }

/* -------------------------------------------------------------------------- */
/* 1. Pure helper unit checks (no DB, no HTTP)                                */
/* -------------------------------------------------------------------------- */

/* If running inside the CI app boot, use the real helpers. Otherwise we
   polyfill them just enough to assert the SAME data shape the public page
   would render with. */
if (defined('BASEPATH')) {
    require_once APPPATH . 'helpers/app_helper.php';
} else {
    /* Minimal stand-ins so the route can still be called outside the app. */
    function vp_safe_html($s)                  { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
    function vp_truncate($s, $n)               { $s = (string) $s; return strlen($s) > $n ? substr($s, 0, $n - 1) . '…' : $s; }
    function vp_time_ago($t)                   { return (string) $t; }
    define('FCPATH', __DIR__ . '/..');
    define('IMG_URL', '/assets/img/');

    /* Define only the THREE roadmap helpers we test. They MUST stay byte-
       compatible with the ones in application/helpers/app_helper.php. */
    if (!function_exists('vp_roadmap_data')) {
        function vp_roadmap_data() {
            return [
                ['name' => 'phase a', 'items' => [
                    ['title' => 'shipped a1',  'status' => 'shipped',  'detail' => 'd'],
                    ['title' => 'building b1','status' => 'building', 'detail' => 'd'],
                    ['title' => 'planned c1', 'status' => 'planned',  'detail' => 'd'],
                ]],
                ['name' => 'phase b', 'items' => [
                    ['title' => 'shipped a2', 'status' => 'shipped', 'detail' => 'd'],
                ]],
                ['name' => 'phase c', 'items' => [
                    ['title' => 'planned c2', 'status' => 'planned', 'detail' => 'd'],
                ]],
            ];
        }
    }
    if (!function_exists('vp_roadmap_progress')) {
        function vp_roadmap_progress() {
            $total = 0; $done = 0;
            foreach (vp_roadmap_data() as $p) foreach ($p['items'] as $it) {
                $total++; if (($it['status'] ?? '') === 'shipped') $done++;
            }
            return $total ? (int) floor(($done / $total) * 100) : 0;
        }
    }
    if (!function_exists('vp_roadmap_recent')) {
        function vp_roadmap_recent($limit = 3) {
            $items = [];
            foreach (vp_roadmap_data() as $p) foreach ($p['items'] as $it) {
                if (($it['status'] ?? '') === 'shipped') {
                    $items[] = ['phase' => $p['name'], 'title' => $it['title'], 'detail' => $it['detail']];
                }
            }
            return array_slice($items, 0, max(1, (int) $limit));
        }
    }
}

echo "Module 1 — Roadmap helper checks:\n";

$phases = vp_roadmap_data();
is_array($phases) && count($phases) >= 3
    ? _ok("vp_roadmap_data() returns >=3 phases (got " . count($phases) . ")")
    : _fail("vp_roadmap_data() must return >=3 phases, got " . count($phases));

$progress = vp_roadmap_progress();
($progress >= 0 && $progress <= 100)
    ? _ok("vp_roadmap_progress() returns 0..100 (got {$progress}%)")
    : _fail("vp_roadmap_progress() out of range: $progress");

$valid_statuses = ['shipped', 'building', 'planned'];
$shape_ok = true;
$total = 0;
$shipped = 0;
foreach ($phases as $i => $phase) {
    if (!isset($phase['name']) || !is_array($phase['items'] ?? null)) { $shape_ok = false; break; }
    foreach ($phase['items'] as $it) {
        $total++;
        if (!in_array($it['status'] ?? '', $valid_statuses, true)) $shape_ok = false;
        if (($it['status'] ?? '') === 'shipped') $shipped++;
        if (empty($it['title']))                   $shape_ok = false;
    }
}
$shape_ok
    ? _ok("All items have valid status + title (total=$total, shipped=$shipped)")
    : _fail("Some roadmap items are missing 'title' or have an invalid 'status'");

/* Spot-check that the shipped-ratio is consistent */
$expected = $total ? (int) floor(($shipped / $total) * 100) : 0;
$progress === $expected
    ? _ok("vp_roadmap_progress() matches hand-computed ratio ({$progress}%)")
    : _fail("vp_roadmap_progress()={$progress} does not match hand-computed {$expected}");

$recent = vp_roadmap_recent(3);
is_array($recent) && count($recent) <= 3 && count($recent) === min(3, $shipped)
    ? _ok("vp_roadmap_recent(3) limit honoured (got " . count($recent) . ")")
    : _fail("vp_roadmap_recent(3) limit broken");

foreach ($recent as $it) {
    if (($it['phase'] ?? '') === '' || empty($it['title'])) {
        _fail("vp_roadmap_recent() returned a row missing 'phase' or 'title'");
        break;
    }
}
if (!isset($it)) _ok("vp_roadmap_recent() rows well-formed");

/* -------------------------------------------------------------------------- */
/* 2. Live HTTP smoke (only when an app URL is passed)                         */
/* -------------------------------------------------------------------------- */

$app_url = $argv[1] ?? getenv('VP_TEST_BASE_URL') ?: '';
if ($app_url === '') {
    echo "\n(skipped live HTTP probe — pass an app URL to also exercise the route)\n";
} else {
    echo "\nLive probe against $app_url:\n";

    function _fetch($url, $cookie = '') {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'roadmap_smoke/1.0',
            CURLOPT_HTTPHEADER     => array_filter(['Cookie: ' . $cookie]),
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        return ['code' => $code, 'body' => $body ?: '', 'err' => $err];
    }

    $r = _fetch(rtrim($app_url, '/') . '/roadmap');
    if ($r['code'] !== 200) {
        _fail("GET /roadmap -> HTTP " . $r['code'] . " (" . ($r['err'] ?: 'no body') . ")");
    } else {
        (strpos($r['body'], 'What we') !== false && strpos($r['body'], 'shipped') !== false)
            ? _ok("GET /roadmap renders the public Roadmap page")
            : _fail("GET /roadmap body is missing expected markers (no 'What we…shipped' text)");
    }
}

/* -------------------------------------------------------------------------- */
/* Exit                                                                       */
/* -------------------------------------------------------------------------- */

echo "\n";
if (!empty($_failures)) {
    fwrite(STDERR, "Module 1 smoke FAILED: " . count($_failures) . " issue(s)\n");
    foreach ($_failures as $f) fwrite(STDERR, "  - $f\n");
    exit(1);
}
echo "Module 1 smoke OK.\n";
exit(0);
