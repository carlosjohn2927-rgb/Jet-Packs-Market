<?php
/**
 * Halyk Petroleum — Super Admin / Admin dashboard acceptance suite.
 *
 * Drives a running instance over real HTTP (curl + cookie jars, CSRF tokens
 * read from the forms) and verifies the complete dashboard checklist:
 * authentication, permission enforcement (including direct-URL attempts),
 * administrator management, and that every content change made in the
 * dashboard actually appears on the public website.
 *
 * Usage:
 *   php tests/dashboard_acceptance.php [base-url]
 *
 * Environment:
 *   VP_TEST_URL              base URL (default http://127.0.0.1:8080)
 *   VP_TEST_SUPER_EMAIL/PASS super admin credentials
 *                            (default superadmin@halykpetroleum-kz.com / SuperAdmin123!)
 *
 * The suite creates a temporary administrator ("acceptance.admin@…") and
 * deletes it again at the end.
 */

$BASE  = rtrim($argv[1] ?? (getenv('VP_TEST_URL') ?: 'http://127.0.0.1:8080'), '/');
$SUPER = ['email' => getenv('VP_TEST_SUPER_EMAIL') ?: 'superadmin@halykpetroleum-kz.com',
          'pass'  => getenv('VP_TEST_SUPER_PASSWORD') ?: 'SuperAdmin123!'];

$TMP_ADMIN = [
    'email' => 'acceptance.admin@halyk-test.local',
    'pass'  => 'Acceptance-Pass-123',
];

$pass = 0; $fail = 0; $failures = [];

/* ------------------------------------------------------------------ */
/* Tiny HTTP client                                                     */
/* ------------------------------------------------------------------ */

function jar($name) { return sys_get_temp_dir() . '/vp_acc_' . $name . '.cookie'; }

function http($method, $url, array $opts = [])
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => !empty($opts['follow']),
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HEADER         => true,
    ]);
    if (!empty($opts['jar'])) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $opts['jar']);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $opts['jar']);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['body'] ?? '');
    }
    $raw    = curl_exec($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return ['code' => (int) $code, 'body' => (string) substr((string) $raw, $hsize), 'headers' => substr((string) $raw, 0, $hsize)];
}

function get($path, $jar = null, $follow = true)
{
    global $BASE;
    return http('GET', $BASE . '/' . ltrim($path, '/'), ['jar' => $jar, 'follow' => $follow]);
}

function csrf($path, $jar)
{
    $r = get($path, $jar);
    if (preg_match('/name="csrf_token" value="([^"]+)"/', $r['body'], $m)) return $m[1];
    return '';
}

function post($path, array $fields, $jar, $token_from = 'admin', $follow = true)
{
    global $BASE;
    $token = csrf($token_from, $jar);
    $fields['csrf_token'] = $token;
    return http('POST', $BASE . '/' . ltrim($path, '/'), [
        'jar' => $jar, 'body' => http_build_query($fields), 'follow' => $follow,
    ]);
}

function upload($path, $file_field, $file, array $fields, $jar, $token_from = 'admin')
{
    global $BASE;
    $fields['csrf_token'] = csrf($token_from, $jar);
    $fields[$file_field]  = new CURLFile($file);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $BASE . '/' . ltrim($path, '/'),
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => (int) $code, 'body' => (string) $body];
}

function login($email, $password, $jar)
{
    @unlink($jar);
    $token = csrf('admin/login', $jar);
    http('POST', $GLOBALS['BASE'] . '/admin/login', [
        'jar'  => $jar,
        'body' => http_build_query(['csrf_token' => $token, 'email' => $email, 'password' => $password]),
    ]);
    return get('admin', $jar);
}

/* ------------------------------------------------------------------ */
/* Assertions                                                           */
/* ------------------------------------------------------------------ */

function check($label, $condition, $detail = '')
{
    global $pass, $fail, $failures;
    if ($condition) {
        $pass++;
        echo "  \033[32m✓\033[0m {$label}\n";
    } else {
        $fail++;
        $failures[] = $label . ($detail ? " — {$detail}" : '');
        echo "  \033[31m✗\033[0m {$label}" . ($detail ? " — {$detail}" : '') . "\n";
    }
}

function section($title) { echo "\n\033[1m{$title}\033[0m\n"; }

/* ================================================================== */

echo "Halyk Petroleum — dashboard acceptance suite\nTarget: {$BASE}\n";

$superJar = jar('super');
$adminJar = jar('admin');

/* ---------- 1. Authentication ------------------------------------- */
section('1. Authentication');

$r = get('', null);
check('Public homepage responds 200', $r['code'] === 200, 'HTTP ' . $r['code']);

$r = get('contact', null);
check('Contact page includes a map', $r['code'] === 200 && strpos($r['body'], 'vp-contact-map') !== false && strpos($r['body'], 'Find us') !== false, 'HTTP ' . $r['code']);
check('Contact page top write-up is white', strpos($r['body'], 'vp-writeup-band') !== false);

$r = get('products', null);
check('Products page top write-up is white', $r['code'] === 200 && strpos($r['body'], 'vp-writeup-band') !== false, 'HTTP ' . $r['code']);

$r = login($SUPER['email'], $SUPER['pass'], $superJar);
check('Super Admin can log in', $r['code'] === 200 && strpos($r['body'], 'Super Admin') !== false, 'HTTP ' . $r['code']);

$r = get('admin/login', null);
check('Admin login page renders', $r['code'] === 200 && strpos($r['body'], 'csrf_token') !== false);

/* ---------- 2. Super Admin has full access ------------------------- */
section('2. Super Admin access');

$super_sections = [
    'admin', 'admin/admins', 'admin/homepage', 'admin/pages', 'admin/menus',
    'admin/appearance', 'admin/appearance/header', 'admin/appearance/colors', 'admin/media', 'admin/settings',
    'admin/settings/contact', 'admin/settings/social', 'admin/settings/system',
    'admin/settings/advanced', 'admin/reports', 'admin/audit', 'admin/users',
    'admin/products', 'admin/quotes', 'admin/seo', 'admin/profile', 'admin/notifications',
];
$all_ok = true; $bad = [];
foreach ($super_sections as $s) {
    $res = get($s, $superJar);
    if ($res['code'] !== 200) { $all_ok = false; $bad[] = $s . ' (' . $res['code'] . ')'; }
}
check('Super Admin can open every dashboard section', $all_ok, implode(', ', $bad));

$dash = get('admin', $superJar);
check('Super Admin dashboard logo links to the public homepage',
    (bool) preg_match('~<a href="' . preg_quote($BASE, '~') . '/?"[^>]*target="_blank"[^>]*>\s*<img~s', $dash['body'])
    || substr_count($dash['body'], 'target="_blank" rel="noopener"') >= 2);
check('Super Admin dashboard has a Homepage / View Website link',
    strpos($dash['body'], 'View Website') !== false && strpos($dash['body'], 'Homepage') !== false);

/* ---------- 3. Administrator management ---------------------------- */
section('3. Administrator management (Super Admin only)');

$r = post('admin/admins/save', [
    'email' => $TMP_ADMIN['email'], 'firstName' => 'Acceptance', 'lastName' => 'Admin',
    'role' => 'ADMIN', 'password' => $TMP_ADMIN['pass'], 'isActive' => '1',
    'permissions' => ['dashboard.view', 'products.manage', 'quotes.manage'],
], $superJar, 'admin/admins/create');
check('Super Admin can create an Admin account', $r['code'] === 200 && strpos($r['body'], 'Administrator') !== false, 'HTTP ' . $r['code']);

// Find the new admin id from the list page
$list = get('admin/admins', $superJar);
preg_match('~admin/admins/edit/([0-9a-f\-]{36})"[^>]*>\s*Acceptance~', $list['body'], $m);
if (!$m) preg_match('~admin/admins/permissions/([0-9a-f\-]{36})~', $list['body'], $m);
$adminId = $m[1] ?? null;
check('New Admin appears in the administrator list', $adminId !== null && strpos($list['body'], $TMP_ADMIN['email']) !== false);

$r = login($TMP_ADMIN['email'], $TMP_ADMIN['pass'], $adminJar);
check('Admin can log in', $r['code'] === 200 && strpos($r['body'], 'Dashboard') !== false, 'HTTP ' . $r['code']);

/* ---------- 4. Permission enforcement ------------------------------ */
section('4. Permission enforcement (server-side)');

$granted = ['admin', 'admin/products', 'admin/quotes', 'admin/profile', 'admin/notifications'];
$ok = true; $bad = [];
foreach ($granted as $s) {
    $res = get($s, $adminJar);
    if ($res['code'] !== 200) { $ok = false; $bad[] = $s . ' (' . $res['code'] . ')'; }
}
check('Admin can open the granted sections', $ok, implode(', ', $bad));

$denied = ['admin/admins', 'admin/settings', 'admin/settings/system', 'admin/appearance',
           'admin/appearance/colors',
           'admin/homepage', 'admin/pages', 'admin/menus', 'admin/media', 'admin/users',
           'admin/audit', 'admin/seo', 'admin/reports'];
$ok = true; $bad = [];
foreach ($denied as $s) {
    $res = get($s, $adminJar, false);
    if ($res['code'] !== 403) { $ok = false; $bad[] = $s . ' (' . $res['code'] . ')'; }
}
check('Admin cannot reach ungranted sections by typing the URL (403)', $ok, implode(', ', $bad));

$posts = [
    'admin/settings/save'            => ['site_name' => 'HACKED'],
    'admin/appearance/save_branding' => ['logo_light' => '/hacked.png'],
        'admin/appearance/save_colors'    => ['theme_bg' => '#000000', 'theme_writeup' => '#ffffff'],
    'admin/homepage/save'            => ['id' => 'x', 'type' => 'hero', 'title' => 'HACKED'],
    'admin/admins/save'              => ['email' => 'evil@x.com', 'firstName' => 'E', 'lastName' => 'V',
                                         'role' => 'SUPER_ADMIN', 'password' => 'Password12345'],
];
$ok = true; $bad = [];
foreach ($posts as $path => $body) {
    $res = post($path, $body, $adminJar, 'admin/products', false);
    if ($res['code'] !== 403) { $ok = false; $bad[] = $path . ' (' . $res['code'] . ')'; }
}
check('Admin POSTs to ungranted endpoints are rejected (403)', $ok, implode(', ', $bad));

$site_after = get('', null);
check('No unauthorised change reached the public website', strpos($site_after['body'], 'HACKED') === false);

/* ---------- 5. Super Admin protection ------------------------------ */
section('5. Super Admin protection');

$superRow = get('admin/admins', $superJar);
preg_match('~admin/admins/activity/([0-9a-f\-]{36})~', $superRow['body'], $sm);
$superId = $sm[1] ?? null;

if ($superId) {
    $res = get('admin/users/edit/' . $superId, $adminJar, false);
    check('Admin cannot open the Super Admin account via /admin/users', $res['code'] === 403, 'HTTP ' . $res['code']);

    $res = post('admin/users/save', [
        'id' => $superId, 'email' => 'taken-over@x.com', 'firstName' => 'X', 'lastName' => 'Y',
        'role' => 'SUPER_ADMIN', 'isActive' => '1',
    ], $adminJar, 'admin/products', false);
    check('Admin cannot modify or promote through /admin/users/save', $res['code'] === 403, 'HTTP ' . $res['code']);
} else {
    check('Super Admin id discoverable for protection tests', false);
}

$res = post('admin/admins/delete/' . ($superId ?: 'x'), [], $adminJar, 'admin/products', false);
check('Admin cannot delete the Super Admin', $res['code'] === 403, 'HTTP ' . $res['code']);

$r = login($SUPER['email'], $SUPER['pass'], $superJar);
check('Super Admin account still works after the attacks', $r['code'] === 200);

/* ---------- 6. Permission changes are live ------------------------- */
section('6. Granting and removing permissions');

if ($adminId) {
    post('admin/admins/permissions_save/' . $adminId, [
        'permissions' => ['dashboard.view', 'products.manage', 'quotes.manage', 'pages.manage'],
    ], $superJar, 'admin/admins/permissions/' . $adminId);
    $res = get('admin/pages', $adminJar, false);
    check('Newly granted permission unlocks the section immediately', $res['code'] === 200, 'HTTP ' . $res['code']);

    post('admin/admins/permissions_save/' . $adminId, [
        'permissions' => ['dashboard.view', 'products.manage', 'quotes.manage'],
    ], $superJar, 'admin/admins/permissions/' . $adminId);
    $res = get('admin/pages', $adminJar, false);
    check('Removed permission locks the section again', $res['code'] === 403, 'HTTP ' . $res['code']);

    // Disable / enable
    post('admin/admins/toggle/' . $adminId, [], $superJar, 'admin/admins');
    login($TMP_ADMIN['email'], $TMP_ADMIN['pass'], $adminJar);
    $res = get('admin', $adminJar, false);
    check('Disabled Admin can no longer sign in', $res['code'] !== 200, 'HTTP ' . $res['code']);
    post('admin/admins/toggle/' . $adminId, [], $superJar, 'admin/admins');
    $res = login($TMP_ADMIN['email'], $TMP_ADMIN['pass'], $adminJar);
    check('Re-enabled Admin can sign in again', $res['code'] === 200 && strpos($res['body'], 'Dashboard') !== false);
}

/* ---------- 7. Website content management -------------------------- */
section('7. Content management reaches the public website');

// Homepage hero
$home_admin = get('admin/homepage', $superJar);
preg_match('~admin/homepage/edit/([0-9a-f\-]{36})~', $home_admin['body'], $hm);
$sectionId = $hm[1] ?? null;
$marker = 'Acceptance hero ' . substr(md5((string) mt_rand()), 0, 6);
if ($sectionId) {
    post('admin/homepage/save', [
        'id' => $sectionId, 'type' => 'hero', 'pageKey' => 'home', 'name' => 'Hero banner',
        'title' => $marker, 'subtitle' => 'Set by the acceptance suite', 'isActive' => '1',
        'buttonText' => 'Request a Quote', 'buttonUrl' => 'rfq',
    ], $superJar, 'admin/homepage/edit/' . $sectionId);
    $pub = get('', null);
    check('Homepage hero edited in the dashboard appears on the public homepage', strpos($pub['body'], $marker) !== false);
} else {
    check('Homepage section available to edit', false);
}

// Settings → site name
$name_marker = 'Halyk Acceptance ' . substr(md5((string) mt_rand()), 0, 4);
post('admin/settings/save', [
    'site_name' => $name_marker, 'site_title' => $name_marker . ' | Industrial',
    'site_tagline' => 'Acceptance tagline', 'site_description' => 'Acceptance description',
    'site_url' => '', 'site_language' => 'en',
], $superJar, 'admin/settings');
$pub = get('', null);
check('Website name changed in Settings appears on the public site', strpos($pub['body'], $name_marker) !== false);

// Contact info → footer
$phone_marker = '+7 700 ' . mt_rand(100, 999) . ' 00 00';
post('admin/settings/save_contact', [
    'contact_email' => 'acceptance@halyk-test.local', 'support_email' => '', 'rfq_email' => '',
    'phone' => $phone_marker, 'address' => 'Acceptance Street 1', 'contact_hours' => 'Mon–Fri',
], $superJar, 'admin/settings/contact');
$pub = get('', null);
check('Footer contact information updates on the public website',
    strpos($pub['body'], 'acceptance@halyk-test.local') !== false && strpos($pub['body'], 'Acceptance Street 1') !== false);

// Navigation
$menu_marker = 'Acc' . mt_rand(100, 999);
post('admin/menus/save', [
    'menu' => 'header', 'label' => $menu_marker, 'type' => 'INTERNAL', 'url' => 'contact',
    'target' => '_self', 'isActive' => '1',
], $superJar, 'admin/menus');
$pub = get('', null);
check('New navigation item appears in the public header', strpos($pub['body'], $menu_marker) !== false);

// Pages
$slug = 'acceptance-page-' . mt_rand(1000, 9999);
post('admin/pages/save', [
    'title' => 'Acceptance Page', 'slug' => $slug, 'excerpt' => 'Created by the acceptance suite',
    'content' => '<p>Acceptance page body.</p>', 'status' => 'PUBLISHED', 'visibility' => 'PUBLIC',
    'template' => 'default', 'sortOrder' => '99',
], $superJar, 'admin/pages/create');
$pub = get($slug, null);
check('A page created in the dashboard is served on the public website',
    $pub['code'] === 200 && strpos($pub['body'], 'Acceptance page body.') !== false, 'HTTP ' . $pub['code']);

// Media + logo + favicon
$tmpPng = sys_get_temp_dir() . '/vp_acc_logo.png';
$im = imagecreatetruecolor(300, 90);
imagefill($im, 0, 0, imagecolorallocate($im, 12, 34, 56));
imagestring($im, 5, 20, 40, 'ACCEPTANCE', imagecolorallocate($im, 255, 255, 255));
imagepng($im, $tmpPng);

$r = upload('admin/appearance/upload', 'file', $tmpPng, ['target' => 'logo_light'], $superJar, 'admin/appearance');
preg_match('~/assets/uploads/branding/[a-z0-9]+\.png~', $r['body'], $lm);
$logoUrl = $lm[0] ?? null;
check('Logo upload stores the file and updates the setting', $logoUrl !== null, 'HTTP ' . $r['code']);
if ($logoUrl) {
    $pub = get('', null);
    check('Public website uses the uploaded logo', strpos($pub['body'], $logoUrl) !== false);
    $file = get(ltrim($logoUrl, '/'), null);
    check('Uploaded logo file is served', $file['code'] === 200, 'HTTP ' . $file['code']);
}

$r = upload('admin/appearance/upload', 'file', $tmpPng, ['target' => 'favicon'], $superJar, 'admin/appearance');
$pub = get('', null);
check('Favicon change is reflected in the public <head>',
    (bool) preg_match('~<link rel="icon" href="/assets/uploads/branding/~', $pub['body']));

$r = upload('admin/media/upload', 'file', $tmpPng, ['folder' => 'acceptance', 'alt' => 'Acceptance image'], $superJar, 'admin/media');
check('Media library upload works', $r['code'] === 200 && strpos($r['body'], 'acceptance') !== false);

$r = get('admin/media/browse', $superJar);
$json = json_decode($r['body'], true);
check('Media picker feed returns JSON items', is_array($json) && !empty($json['ok']) && !empty($json['items']));

// Header / footer
$footer_marker = 'Acceptance footer note ' . mt_rand(10, 99);
post('admin/appearance/save_header', [
    'header_cta_enabled' => '1', 'header_cta_label' => 'Request a Quote', 'header_cta_url' => 'rfq',
    'header_topbar_enabled' => '0', 'header_topbar_text' => '',
    'footer_about' => $footer_marker, 'footer_copyright' => '', 'footer_note' => '',
    'footer_newsletter_enabled' => '0',
    'contact_email' => 'acceptance@halyk-test.local', 'phone' => $phone_marker,
    'address' => 'Acceptance Street 1', 'contact_hours' => 'Mon–Fri',
    'social_linkedin' => 'https://linkedin.com/company/acceptance',
], $superJar, 'admin/appearance/header');
$pub = get('', null);
check('Footer text edited in the dashboard shows on the website', strpos($pub['body'], $footer_marker) !== false);
check('Social link edited in the dashboard shows in the footer', strpos($pub['body'], 'linkedin.com/company/acceptance') !== false);

// Section visibility
if ($sectionId) {
    post('admin/homepage/toggle/' . $sectionId, [], $superJar, 'admin/homepage');
    $pub = get('', null);
    check('Hiding a homepage section removes it from the public page', strpos($pub['body'], $marker) === false);
    post('admin/homepage/toggle/' . $sectionId, [], $superJar, 'admin/homepage');
    $pub = get('', null);
    check('Showing it again restores it', strpos($pub['body'], $marker) !== false);
}

/* ---------- 7b. Catalogue: adding products ------------------------- */
section('7b. Products can be added and edited from the dashboard');

$sku = 'ACC-' . mt_rand(1000, 9999);
$r = post('admin/products/save', [
    'name' => 'Acceptance Test Valve', 'sku' => $sku,
    'description' => 'Created by the acceptance suite to prove product creation works.',
    'shortDescription' => 'Acceptance valve', 'price' => '999',
    'availability' => 'IN_STOCK', 'isActive' => '1', 'featured' => '1',
], $superJar, 'admin/products/create');
check('Super Admin can create a product', $r['code'] === 200 && strpos($r['body'], 'Acceptance Test Valve') !== false, 'HTTP ' . $r['code']);

$pub = get('products/acceptance-test-valve', null);
check('The new product is live on the public website', $pub['code'] === 200 && strpos($pub['body'], 'Acceptance Test Valve') !== false, 'HTTP ' . $pub['code']);

$list = get('admin/products?q=' . urlencode($sku), $superJar);
preg_match('~admin/products/edit/([0-9a-f\-]{36})~', $list['body'], $pm);
$productId = $pm[1] ?? null;
if ($productId) {
    $r = post('admin/products/save', [
        'id' => $productId, 'name' => 'Acceptance Test Valve v2', 'sku' => $sku,
        'slug' => 'acceptance-test-valve',
        'description' => 'Updated by the acceptance suite.', 'shortDescription' => 'Updated',
        'price' => '1099', 'availability' => 'IN_STOCK', 'isActive' => '1',
    ], $superJar, 'admin/products/edit/' . $productId);
    $pub = get('products/acceptance-test-valve', null);
    check('Editing a product updates the public page', strpos($pub['body'], 'Acceptance Test Valve v2') !== false);

    $dupe = post('admin/products/save', [
        'name' => 'Duplicate', 'sku' => $sku, 'description' => 'Duplicate SKU attempt',
    ], $superJar, 'admin/products/create');
    check('Duplicate SKU is refused with a message, not a database error',
        strpos($dupe['body'], 'already uses that SKU') !== false || strpos($dupe['body'], 'New product') !== false);
}

// A normal Admin with products.manage must be able to add products too.
if ($adminId) {
    post('admin/admins/permissions_save/' . $adminId, [
        'permissions' => ['dashboard.view', 'products.manage', 'quotes.manage'],
    ], $superJar, 'admin/admins/permissions/' . $adminId);
    $sku2 = 'ACC-ADM-' . mt_rand(100, 999);
    $r = post('admin/products/save', [
        'name' => 'Admin Added Product', 'sku' => $sku2,
        'description' => 'Added by a normal administrator account.',
        'availability' => 'IN_STOCK', 'isActive' => '1',
    ], $adminJar, 'admin/products/create');
    check('An Admin with products.manage can add a product', $r['code'] === 200 && strpos($r['body'], 'Admin Added Product') !== false, 'HTTP ' . $r['code']);
}

if ($productId) {
    post('admin/products/delete/' . $productId, [], $superJar, 'admin/products');
}

/* ---------- 7c. Public chat assistant ------------------------------ */
section('7c. Chat assistant');

$home = get('', null);
preg_match('/data-csrf="([^"]*)"/', $home['body'], $cm);
$chatToken = $cm[1] ?? '';
$chatJar = jar('chat');
@unlink($chatJar);
get('', $chatJar);                       // establish the visitor session/cookies

$questions = ['hello', 'what products do you have', 'do you sell pumps', 'how much is a valve', 'contact details'];
$ok = true; $bad = [];
foreach ($questions as $i => $q) {
    $res = http('POST', $BASE . '/chat/message', [
        'jar'  => $chatJar,
        'body' => http_build_query(['csrf_token' => $chatToken, 'message' => $q]),
    ]);
    $data = json_decode($res['body'], true);
    if (!is_array($data) || empty($data['reply'])) {
        $ok = false;
        $bad[] = '#' . ($i + 1) . ' "' . $q . '" (HTTP ' . $res['code'] . ')';
    }
}
check('Every chat message gets a JSON reply — including the 2nd and later ones', $ok, implode(', ', $bad));

$res = http('POST', $BASE . '/chat/message', [
    'jar'  => $chatJar,
    'body' => http_build_query(['csrf_token' => 'totally-stale-token', 'message' => 'and one more question']),
]);
$data = json_decode($res['body'], true);
check('A stale CSRF token no longer breaks the conversation', is_array($data) && !empty($data['reply']), 'HTTP ' . $res['code']);

$res = http('POST', $BASE . '/chat/message', [
    'jar'  => $chatJar,
    'body' => http_build_query(['message' => 'hi']),
]);
check('Chat endpoint always answers with JSON (never an HTML error page)',
    json_decode($res['body'], true) !== null);

$res = get('chat/token', $chatJar);
$data = json_decode($res['body'], true);
check('Chat token endpoint lets the widget re-synchronise', is_array($data) && !empty($data['csrf_token']));

/* ---------- 8. Dashboard ↔ website navigation ---------------------- */
section('8. Dashboard ↔ public website');

$adminDash = get('admin', $adminJar);
check('Admin dashboard logo/homepage links point at the public site',
    substr_count($adminDash['body'], 'target="_blank" rel="noopener"') >= 2);
check('Admin dashboard shows the View Website button', strpos($adminDash['body'], 'View Website') !== false);

$pub = get('', $adminJar);
$still = get('admin', $adminJar);
check('Opening the public site keeps the dashboard session alive',
    $pub['code'] === 200 && $still['code'] === 200);

/* ---------- 9. Activity log ---------------------------------------- */
section('9. Activity / audit log');

$log = get('admin/audit', $superJar);
check('Activity log lists administrator actions',
    $log['code'] === 200 && (strpos($log['body'], 'UPDATE') !== false || strpos($log['body'], 'LOGIN') !== false));
check('Denied access attempts are recorded', strpos(get('admin/audit?action=ACCESS_DENIED', $superJar)['body'], 'ACCESS_DENIED') !== false);
if ($adminId) {
    $act = get('admin/admins/activity/' . $adminId, $superJar);
    check('Per-administrator activity view works', $act['code'] === 200);
}

/* ---------- 10. Cleanup -------------------------------------------- */
section('10. Cleanup');

if ($adminId) {
    $r = post('admin/admins/delete/' . $adminId, [], $superJar, 'admin/admins');
    check('Super Admin can delete an Admin account',
        $r['code'] === 200 && strpos($r['body'], $TMP_ADMIN['email']) === false);
}
@unlink($tmpPng);

/* ------------------------------------------------------------------ */

echo "\n" . str_repeat('─', 60) . "\n";
echo "Passed: {$pass}   Failed: {$fail}\n";
if ($failures) {
    echo "\nFailures:\n";
    foreach ($failures as $f) echo "  • {$f}\n";
}
exit($fail === 0 ? 0 : 1);
k('Per-administrator activity view works', $act['code'] === 200);
}

/* ---------- 10. Cleanup -------------------------------------------- */
section('10. Cleanup');

if ($adminId) {
    $r = post('admin/admins/delete/' . $adminId, [], $superJar, 'admin/admins');
    check('Super Admin can delete an Admin account',
        $r['code'] === 200 && strpos($r['body'], $TMP_ADMIN['email']) === false);
}
@unlink($tmpPng);

/* ------------------------------------------------------------------ */

echo "\n" . str_repeat('─', 60) . "\n";
echo "Passed: {$pass}   Failed: {$fail}\n";
if ($failures) {
    echo "\nFailures:\n";
    foreach ($failures as $f) echo "  • {$f}\n";
}
exit($fail === 0 ? 0 : 1);
