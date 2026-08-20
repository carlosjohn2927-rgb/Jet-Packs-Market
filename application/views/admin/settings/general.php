<?php $this->load->view('admin/settings/_tabs', ['tabs' => $tabs, 'tab' => $tab]); ?>
<form method="post" action="<?= base_url('admin/settings/save') ?>" class="max-w-4xl space-y-6">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">

    <?= vp_admin_card_open('Website identity', 'Used across the website, emails and search results', 'ri-global-line') ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?= vp_text_field('site_name', vp_cms_setting('site_name', '', 'site_name'), 'Website name', ['required' => true]) ?>
            <?= vp_text_field('site_title', vp_cms_setting('site_title', ''), 'Website title (browser tab)') ?>
            <?= vp_text_field('site_tagline', vp_cms_setting('site_tagline', '', 'site_tagline'), 'Tagline') ?>
            <?= vp_text_field('site_url', vp_cms_setting('site_url', ''), 'Website URL', ['placeholder' => rtrim(base_url(), '/')]) ?>
            <?= vp_text_field('site_language', vp_cms_setting('site_language', 'en'), 'Default language code', ['help' => 'e.g. en, ru, kk']) ?>
        </div>
        <?= vp_textarea_field('site_description', vp_cms_setting('site_description', ''), 'Website description', 3,
            'Default meta description used where a page does not define its own.') ?>
    <?= vp_admin_card_close() ?>

    <div class="flex flex-wrap gap-3">
        <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save settings</button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/appearance') ?>"><i class="ri-image-2-line"></i> Logo &amp; branding</a>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/appearance/colors') ?>"><i class="ri-contrast-drop-2-line"></i> Colours</a>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/seo') ?>"><i class="ri-search-eye-line"></i> SEO settings</a>
    </div>
</form>
