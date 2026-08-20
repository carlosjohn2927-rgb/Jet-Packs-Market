<?php
/** @var array $site */
$logo_variants = [
    'logo_light'  => ['Primary logo (header)', vp_logo_url('light')],
    'logo_dark'   => ['Logo for dark backgrounds', vp_logo_url('dark')],
    'logo_footer' => ['Footer logo', vp_logo_url('footer')],
    'favicon'     => ['Favicon (browser tab icon)', vp_favicon_url()],
];
?>
<div class="max-w-5xl space-y-6">
    <?php $this->load->view('admin/appearance/_tabs'); ?>

    <div class="bg-white border rounded-2xl p-5 flex flex-wrap items-center gap-4">
        <div class="px-4 py-3 rounded-xl bg-gray-50 border">
            <img src="<?= vp_safe_html(vp_logo_url('light')) ?>" alt="logo preview" class="h-10 w-auto max-w-[220px] object-contain">
        </div>
        <div class="px-4 py-3 rounded-xl bg-ink-900 border">
            <img src="<?= vp_safe_html(vp_logo_url('dark')) ?>" alt="dark logo preview" class="h-10 w-auto max-w-[220px] object-contain">
        </div>
        <div class="text-sm text-ink-800/70">
            <div class="font-semibold text-ink-900">Live preview</div>
            These are the logos the public website is using right now.
        </div>
        <a class="vp-btn vp-btn-secondary ml-auto" href="<?= base_url() ?>" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> View website</a>
    </div>

    <!-- Quick uploads -->
    <section class="bg-white border rounded-2xl">
        <header class="px-5 py-4 border-b flex items-center gap-3">
            <i class="ri-upload-cloud-2-line text-xl text-brand-600"></i>
            <div>
                <h2 class="font-bold text-ink-900">Upload logo / favicon</h2>
                <p class="text-xs text-ink-800/60">Uploads go straight into the media library and are applied immediately.</p>
            </div>
        </header>
        <div class="p-5 grid md:grid-cols-2 gap-5">
            <?php foreach ($logo_variants as $key => $def): ?>
                <div class="border rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-20 h-14 rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden">
                            <img src="<?= vp_safe_html($def[1]) ?>" alt="" class="max-h-full max-w-full object-contain">
                        </div>
                        <div class="min-w-0">
                            <div class="font-semibold text-sm text-ink-900"><?= vp_safe_html($def[0]) ?></div>
                            <div class="text-[11px] text-ink-800/50 truncate"><?= vp_safe_html($def[1]) ?></div>
                        </div>
                    </div>
                    <form method="post" action="<?= base_url('admin/appearance/upload') ?>" enctype="multipart/form-data" class="mt-3 flex flex-wrap gap-2 items-center">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <input type="hidden" name="target" value="<?= $key ?>">
                        <input class="text-xs" type="file" name="file" accept="<?= $key === 'favicon' ? '.png,.ico,.jpg,.gif,.webp' : 'image/*' ?>" required>
                        <button class="vp-btn vp-btn-primary vp-btn-sm" type="submit"><i class="ri-upload-2-line"></i> Upload</button>
                    </form>
                    <form method="post" action="<?= base_url('admin/appearance/remove') ?>" class="mt-2" data-confirm="Remove this image and fall back to the built-in default?">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <input type="hidden" name="target" value="<?= $key ?>">
                        <button class="text-xs text-red-600 hover:underline" type="submit">Remove</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Full form -->
    <form method="post" action="<?= base_url('admin/appearance/save_branding') ?>" class="space-y-6">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">

        <?= vp_admin_card_open('Website identity', 'Used in the browser tab, emails and SEO defaults', 'ri-global-line') ?>
            <div class="grid md:grid-cols-2 gap-4">
                <?= vp_text_field('site_name', vp_cms_setting('site_name', '', 'site_name'), 'Website name') ?>
                <?= vp_text_field('site_title', vp_cms_setting('site_title', ''), 'Website title (browser tab)') ?>
            </div>
            <?= vp_textarea_field('site_description', vp_cms_setting('site_description', ''), 'Website description', 3) ?>
        <?= vp_admin_card_close() ?>

        <?= vp_admin_card_open('Logo files', 'Or paste any URL / pick from the media library', 'ri-image-2-line') ?>
            <div class="grid md:grid-cols-2 gap-5">
                <?= vp_media_field('logo_light', vp_cms_setting('logo_light', ''), 'Primary logo (header)') ?>
                <?= vp_media_field('logo_dark', vp_cms_setting('logo_dark', ''), 'Logo for dark backgrounds') ?>
                <?= vp_media_field('logo_footer', vp_cms_setting('logo_footer', ''), 'Footer logo') ?>
                <?= vp_media_field('favicon', vp_cms_setting('favicon', ''), 'Favicon') ?>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <?= vp_text_field('logo_alt', vp_cms_setting('logo_alt', ''), 'Logo alt text (accessibility)') ?>
                <?= vp_text_field('logo_height', vp_cms_setting('logo_height', '44'), 'Header logo height (px)', ['type' => 'number']) ?>
            </div>
        <?= vp_admin_card_close() ?>

        <div class="flex gap-3">
            <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save branding</button>
            <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/media') ?>"><i class="ri-folder-image-line"></i> Open media library</a>
        </div>
    </form>
</div>
