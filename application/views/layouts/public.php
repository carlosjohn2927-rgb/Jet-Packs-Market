<?php
/**
 * Vortex Precision - public layout.
 * Receives: $content, $page_title, $page_description, $site_name, $site_tagline,
 *           $contact, $social, $current_user, $is_admin, $flash, $csrf_token_name,
 *           $csrf_token, $vp_settings, $unread_notifications
 */
?><!doctype html>
<html lang="<?= vp_safe_html(vp_site('language', 'en')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?= vp_seo_head($page_title, $page_description, null, $csp_nonce ?? '') ?>

    <!-- Favicon + touch icon come from Dashboard → Website → Logo & branding -->
    <link rel="icon" href="<?= vp_safe_html(vp_favicon_url()) ?>">
    <link rel="apple-touch-icon" href="<?= vp_safe_html(vp_logo_url('light')) ?>">
    <link rel="manifest" href="<?= base_url('site.webmanifest') ?>">
    <meta name="theme-color" content="#0b1424">

    <!-- Tailwind via CDN (no build step, shared-hosting friendly) -->
    <script src="<?= JS_URL ?>tailwind-config.js?v=<?= VP_ASSET_VERSION ?>"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= CSS_URL ?>app.css?v=<?= VP_ASSET_VERSION ?>">
    <link rel="stylesheet" href="<?= CSS_URL ?>chat.css?v=<?= VP_ASSET_VERSION ?>">
    <?php if (!empty($inline_edit)): ?>
    <link rel="stylesheet" href="<?= CSS_URL ?>inline-editor.css?v=<?= VP_ASSET_VERSION ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">

</head>
<body class="font-sans bg-white text-ink-800 antialiased flex flex-col min-h-screen">

<?php $this->load->view('partials/header', get_defined_vars()); ?>

<?php if (!empty($admin_edit) || !empty($inline_edit_can)): ?>
<div class="bg-ink-900 text-white border-b border-white/10" role="region" aria-label="Administrator page tools">
    <div class="container mx-auto px-4 py-2 flex flex-wrap items-center gap-3 text-sm">
        <span class="inline-flex items-center gap-2 text-white/90">
            <i class="ri-shield-user-line text-amber-400"></i>
            You are viewing the live website as <?= vp_is_super_admin() ? 'Super Admin' : 'Admin' ?>.
        </span>
        <div class="ml-auto flex items-center gap-2">
            <?php if (!empty($inline_edit_can)): ?>
                <a href="<?= vp_safe_html($inline_edit_url) ?>"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-500 px-3 py-1.5 font-semibold text-white">
                    <i class="<?= $inline_edit ? 'ri-check-line' : 'ri-edit-line' ?>"></i> <?= $inline_edit ? 'Done editing' : 'Edit this page' ?>
                </a>
            <?php elseif (!empty($admin_edit)): ?>
                <a href="<?= vp_safe_html($admin_edit['url']) ?>"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 hover:bg-brand-500 px-3 py-1.5 font-semibold text-white">
                    <i class="ri-edit-line"></i> <?= vp_safe_html($admin_edit['label']) ?>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('admin') ?>"
               class="inline-flex items-center gap-2 rounded-lg border border-white/30 hover:bg-white/10 px-3 py-1.5 font-semibold text-white">
                <i class="ri-dashboard-line"></i> Dashboard
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($inline_edit)): ?>
<div class="bg-ink-900 text-white border-b border-white/10" role="region" aria-label="Inline page editor">
    <div class="container mx-auto px-4 py-2 flex flex-wrap items-center gap-3 text-sm">
        <span class="inline-flex items-center gap-2 text-white/90">
            <i class="ri-edit-box-line text-brand-300"></i>
            <strong>Editing page</strong> — click “Edit” on a section to rewrite its text or change its colours.
        </span>
        <div class="ml-auto flex flex-wrap items-center gap-2">
            <button type="button" data-vp-theme-panel class="vp-inline-tool">
                <i class="ri-palette-line"></i> Page colours
            </button>
            <?php if (!empty($inline_builder_url)): ?>
                <a href="<?= vp_safe_html($inline_builder_url) ?>" class="vp-inline-tool">
                    <i class="ri-stack-line"></i> Manage sections
                </a>
            <?php endif; ?>
            <a href="<?= vp_safe_html($inline_edit_url) ?>" class="vp-inline-tool vp-inline-tool-primary">
                <i class="ri-check-line"></i> Done
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($flash): ?>
<div class="container mx-auto px-4 mt-4">
    <div class="rounded-lg px-4 py-3 border <?= $flash['type']==='error'?'bg-red-50 border-red-200 text-red-800':($flash['type']==='success'?'bg-green-50 border-green-200 text-green-800':'bg-blue-50 border-blue-200 text-blue-800') ?>">
        <?= vp_safe_html($flash['message']) ?>
    </div>
</div>
<?php endif; ?>

<main class="flex-1">
    <?= $content ?>
</main>

<?php $this->load->view('partials/footer', get_defined_vars()); ?>

<?php if (!empty($chat['enabled'])): ?>
    <?php $this->load->view('partials/chat_widget', get_defined_vars()); ?>
<?php endif; ?>

<script src="<?= JS_URL ?>app.js?v=<?= VP_ASSET_VERSION ?>"></script>
<script src="<?= JS_URL ?>chat.js?v=<?= VP_ASSET_VERSION ?>"></script>
<?= vp_theme_style_tag() ?>

<?php if (!empty($inline_edit)): ?>
<?php $inline_cfg = json_encode([
    'base'        => base_url(),
    'csrfName'    => $csrf_token_name,
    'csrf'        => $csrf_token,
    'theme'       => vp_theme(),
    'sectionSave' => base_url('admin/inline_editor/section_save'),
    'settingSave' => base_url('admin/inline_editor/setting_save'),
    'themeSave'   => base_url('admin/inline_editor/theme_save'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>

<div id="vp-inline-panel" class="vp-inline-panel" hidden role="dialog" aria-label="Edit section">
    <div class="vp-inline-panel-head">
        <strong><i class="ri-edit-line"></i> Edit section</strong>
        <button type="button" class="vp-inline-close" data-vp-inline-close aria-label="Close"><i class="ri-close-line"></i></button>
    </div>
    <form id="vp-inline-form" class="vp-inline-panel-body">
        <input type="hidden" name="<?= vp_safe_html($csrf_token_name) ?>" value="<?= vp_safe_html($csrf_token) ?>">
        <input type="hidden" name="id" value="">
        <label class="vp-inline-field">
            <span>Title</span>
            <input type="text" name="title" class="vp-input">
        </label>
        <label class="vp-inline-field">
            <span>Subtitle</span>
            <input type="text" name="subtitle" class="vp-input">
        </label>
        <label class="vp-inline-field">
            <span>Body</span>
            <textarea name="body" rows="6" class="vp-input"></textarea>
        </label>
        <div class="vp-inline-grid">
            <label class="vp-inline-field">
                <span>Button text</span>
                <input type="text" name="buttonText" class="vp-input">
            </label>
            <label class="vp-inline-field">
                <span>Button URL</span>
                <input type="text" name="buttonUrl" class="vp-input" placeholder="rfq, /products or https://…">
            </label>
        </div>
        <div class="vp-inline-colors">
            <label class="vp-inline-field">
                <span>Text colour</span>
                <input type="color" name="text_color" value="#000000">
            </label>
            <label class="vp-inline-field">
                <span>Background colour</span>
                <input type="color" name="bg_color" value="#ffffff">
            </label>
            <label class="vp-inline-field">
                <span>Heading colour</span>
                <input type="color" name="heading_color" value="#000000">
            </label>
        </div>
        <div class="vp-inline-panel-foot">
            <button type="submit" class="vp-btn vp-btn-primary"><i class="ri-save-line"></i> Save changes</button>
            <button type="button" class="vp-btn vp-btn-secondary" data-vp-inline-close>Cancel</button>
        </div>
    </form>
</div>

<div id="vp-text-panel" class="vp-inline-panel" hidden role="dialog" aria-label="Edit text">
    <div class="vp-inline-panel-head">
        <strong><i class="ri-text"></i> Edit text</strong>
        <button type="button" class="vp-inline-close" data-vp-inline-close aria-label="Close"><i class="ri-close-line"></i></button>
    </div>
    <form id="vp-text-form" class="vp-inline-panel-body">
        <input type="hidden" name="<?= vp_safe_html($csrf_token_name) ?>" value="<?= vp_safe_html($csrf_token) ?>">
        <input type="hidden" name="key" value="">
        <label class="vp-inline-field">
            <span>Text</span>
            <textarea name="value" rows="5" class="vp-input"></textarea>
        </label>
        <div class="vp-inline-panel-foot">
            <button type="submit" class="vp-btn vp-btn-primary"><i class="ri-save-line"></i> Save text</button>
            <button type="button" class="vp-btn vp-btn-secondary" data-vp-inline-close>Cancel</button>
        </div>
    </form>
</div>

<div id="vp-theme-panel" class="vp-inline-panel" hidden role="dialog" aria-label="Page colours">
    <div class="vp-inline-panel-head">
        <strong><i class="ri-palette-line"></i> Page colours</strong>
        <button type="button" class="vp-inline-close" data-vp-inline-close aria-label="Close"><i class="ri-close-line"></i></button>
    </div>
    <form id="vp-theme-form" class="vp-inline-panel-body">
        <input type="hidden" name="<?= vp_safe_html($csrf_token_name) ?>" value="<?= vp_safe_html($csrf_token) ?>">
        <p class="vp-inline-hint">These two colours apply across the whole website.</p>
        <label class="vp-inline-field">
            <span>Page background</span>
            <input type="color" name="theme_bg" value="<?= vp_safe_html(vp_theme('bg')) ?>">
        </label>
        <label class="vp-inline-field">
            <span>Write-up (text) colour</span>
            <input type="color" name="theme_writeup" value="<?= vp_safe_html(vp_theme('writeup')) ?>">
        </label>
        <div class="vp-inline-panel-foot">
            <button type="submit" class="vp-btn vp-btn-primary"><i class="ri-save-line"></i> Save colours</button>
            <button type="button" class="vp-btn vp-btn-secondary" data-vp-inline-close>Cancel</button>
        </div>
    </form>
</div>

<script type="application/json" id="vp-inline-config"><?= $inline_cfg ?></script>
<script src="<?= JS_URL ?>inline-editor.js?v=<?= VP_ASSET_VERSION ?>"></script>
<?php endif; ?>
</body>
</html>
