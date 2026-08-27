<?php
/**
 * Halyk Petroleum — admin layout.
 * Receives: same as public, plus $current_user, $is_admin, $is_super_admin,
 *           $admin_nav, $can, $unread_notifications.
 * Everything branded here (title, favicon, logo) comes from the dashboard.
 */
$site = vp_site();
?><!doctype html>
<html lang="<?= vp_safe_html($site['language'] ?: 'en') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= vp_safe_html($page_title ?: 'Dashboard') ?> | <?= vp_safe_html($site['name']) ?> <?= vp_safe_html(vp_dashboard_label()) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= vp_safe_html($csrf_token) ?>" data-name="<?= vp_safe_html($csrf_token_name) ?>">
    <script nonce="<?= vp_safe_html($csp_nonce ?? '') ?>">var VP_ADMIN_BASE = <?= json_encode(base_url()) ?>;</script>
    <link rel="icon" href="<?= vp_safe_html(vp_favicon_url()) ?>">
    <link rel="apple-touch-icon" href="<?= vp_safe_html(vp_logo_url('light')) ?>">
    <meta name="theme-color" content="<?= vp_safe_html(vp_theme('sidebar_bg')) ?>">

    <script src="<?= JS_URL ?>tailwind-config.js?v=<?= VP_ASSET_VERSION ?>"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="<?= CSS_URL ?>app.css?v=<?= VP_ASSET_VERSION ?>">
    <link rel="stylesheet" href="<?= CSS_URL ?>admin.css?v=<?= VP_ASSET_VERSION ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin font-sans bg-gray-50 text-ink-800 antialiased">

<div class="flex min-h-screen">
    <?php $this->load->view('partials/admin_sidebar', get_defined_vars()); ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php $this->load->view('partials/admin_nav', get_defined_vars()); ?>

        <?php if ($flash): ?>
        <div class="px-6 mt-4">
            <div class="rounded-lg px-4 py-3 border <?= $flash['type']==='error'?'bg-red-50 border-red-200 text-red-800':($flash['type']==='success'?'bg-green-50 border-green-200 text-green-800':($flash['type']==='warning'?'bg-amber-50 border-amber-200 text-amber-900':'bg-blue-50 border-blue-200 text-blue-800')) ?>">
                <?= vp_safe_html($flash['message']) ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (vp_maintenance_active()): ?>
        <div class="px-6 mt-4">
            <div class="rounded-lg px-4 py-3 border bg-amber-50 border-amber-300 text-amber-900 flex items-center gap-2 text-sm">
                <i class="ri-tools-line text-lg"></i>
                <span><strong>Maintenance mode is ON.</strong> Visitors see the maintenance page; administrators still see the live site.</span>
                <?php if (vp_can('settings.manage')): ?>
                    <a class="ml-auto font-semibold underline" href="<?= base_url('admin/settings/system') ?>">Turn off</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <main class="flex-1 p-4 md:p-6">
            <?= $content ?>
        </main>

        <footer class="px-6 py-4 text-sm text-ink-800 border-t bg-white flex flex-wrap items-center gap-2">
            <span><?= vp_safe_html($site['name']) ?> <?= vp_safe_html(vp_dashboard_label()) ?> &middot; <?= date('Y') ?></span>
            <a class="ml-auto inline-flex items-center gap-1 text-brand-700 font-semibold hover:underline" href="<?= base_url() ?>" target="_blank" rel="noopener">
                <i class="ri-home-4-line"></i> Homepage
            </a>
        </footer>
    </div>
</div>

<?php $this->load->view('admin/partials/media_picker'); ?>

<script src="<?= JS_URL ?>app.js?v=<?= VP_ASSET_VERSION ?>"></script>
<script src="<?= JS_URL ?>admin.js?v=<?= VP_ASSET_VERSION ?>"></script>
<?= vp_theme_style_tag() ?>
</body>
</html>
