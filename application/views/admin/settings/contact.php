<?php $this->load->view('admin/settings/_tabs', ['tabs' => $tabs, 'tab' => $tab]); ?>
<form method="post" action="<?= base_url('admin/settings/save_contact') ?>" class="max-w-4xl space-y-6">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?= vp_admin_card_open('Contact information', 'Shown in the website footer, contact page and outgoing email', 'ri-contacts-line') ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?= vp_text_field('contact_email', vp_cms_setting('contact_email', '', 'contact_email'), 'Main contact email', ['type' => 'email']) ?>
            <?= vp_text_field('support_email', vp_cms_setting('support_email', '', 'support_email'), 'Support email', ['type' => 'email']) ?>
            <?= vp_text_field('rfq_email', vp_cms_setting('rfq_email', ''), 'Quote request email', ['type' => 'email']) ?>
            <?= vp_text_field('phone', vp_cms_setting('phone', '', 'phone'), 'Phone number') ?>
            <?= vp_text_field('contact_hours', vp_cms_setting('contact_hours', ''), 'Opening hours') ?>
        </div>
        <?= vp_textarea_field('address', vp_cms_setting('address', '', 'address'), 'Address', 2) ?>
    <?= vp_admin_card_close() ?>
    <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save contact information</button>
</form>
