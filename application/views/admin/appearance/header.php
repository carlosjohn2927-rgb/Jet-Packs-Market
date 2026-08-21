<?php /** @var array $site */ ?>
<div class="max-w-5xl space-y-6">
<?php $this->load->view('admin/appearance/_tabs'); ?>
<form method="post" action="<?= base_url('admin/appearance/save_header') ?>" class="space-y-6">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">

    <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-xl px-4 py-3 text-sm flex gap-2">
        <i class="ri-information-line text-lg"></i>
        <span>Everything on this page is rendered live by the public header and footer.
            Navigation links live under <a class="underline font-semibold" href="<?= base_url('admin/menus') ?>">Navigation</a>,
            the logo under <a class="underline font-semibold" href="<?= base_url('admin/appearance') ?>">Logo &amp; branding</a>.</span>
    </div>

    <?= vp_admin_card_open('Header', 'Top bar and the call-to-action button', 'ri-layout-top-line') ?>
        <?= vp_toggle_field('header_topbar_enabled', vp_site('topbar_enabled', false), 'Show announcement bar above the header') ?>
        <?= vp_text_field('header_topbar_text', vp_cms_setting('header_topbar_text', ''), 'Announcement text') ?>
        <hr class="my-2">
        <?= vp_toggle_field('header_cta_enabled', vp_site('header_cta_enabled', true), 'Show the call-to-action button') ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?= vp_text_field('header_cta_label', vp_cms_setting('header_cta_label', 'Request a Quote'), 'Button label') ?>
            <?= vp_text_field('header_cta_url', vp_cms_setting('header_cta_url', 'rfq'), 'Button link', ['help' => 'Internal path (rfq) or full URL']) ?>
        </div>
    <?= vp_admin_card_close() ?>

    <?= vp_admin_card_open('Contact information', 'Shown in the footer and on the contact page', 'ri-contacts-line') ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?= vp_text_field('contact_email', vp_cms_setting('contact_email', '', 'contact_email'), 'Contact email', ['type' => 'email']) ?>
            <?= vp_text_field('phone', vp_cms_setting('phone', '', 'phone'), 'Phone number') ?>
            <?= vp_text_field('address', vp_cms_setting('address', '', 'address'), 'Address') ?>
            <?= vp_text_field('contact_hours', vp_cms_setting('contact_hours', ''), 'Opening hours') ?>
        </div>
    <?= vp_admin_card_close() ?>

    <?= vp_admin_card_open('Social media links', 'Empty fields are hidden from the website', 'ri-share-line') ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach (['linkedin' => 'LinkedIn', 'twitter' => 'X / Twitter', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'instagram' => 'Instagram', 'telegram' => 'Telegram', 'whatsapp' => 'WhatsApp'] as $key => $label): ?>
                <?= vp_text_field('social_' . $key, vp_cms_setting('social_' . $key, $social[$key] ?? ''), $label, ['placeholder' => 'https://…']) ?>
            <?php endforeach; ?>
        </div>
    <?= vp_admin_card_close() ?>

    <?= vp_admin_card_open('Footer', '', 'ri-layout-bottom-line') ?>
        <?= vp_textarea_field('footer_about', vp_cms_setting('footer_about', ''), 'About text (first footer column)', 3) ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?= vp_text_field('footer_copyright', vp_cms_setting('footer_copyright', ''), 'Copyright line', ['help' => 'Leave empty to use “© YEAR Website name. All rights reserved.”']) ?>
            <?= vp_text_field('footer_note', vp_cms_setting('footer_note', ''), 'Small print / secondary note') ?>
        </div>
        <?= vp_toggle_field('footer_newsletter_enabled', (string) vp_cms_setting('footer_newsletter_enabled', '0') === '1', 'Show the newsletter sign-up block') ?>
    <?= vp_admin_card_close() ?>

    <div class="flex gap-3">
        <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save header &amp; footer</button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url() ?>" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> View website</a>
    </div>
</form>
</div>
