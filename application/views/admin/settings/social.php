<?php $this->load->view('admin/settings/_tabs', ['tabs' => $tabs, 'tab' => $tab]); ?>
<form method="post" action="<?= base_url('admin/settings/save_social') ?>" class="max-w-4xl space-y-6">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?= vp_admin_card_open('Social media', 'Icons appear in the website footer. Empty fields are hidden.', 'ri-share-line') ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach (['linkedin' => 'LinkedIn', 'twitter' => 'X / Twitter', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'instagram' => 'Instagram', 'telegram' => 'Telegram', 'whatsapp' => 'WhatsApp'] as $key => $label): ?>
                <?= vp_text_field('social_' . $key, vp_cms_setting('social_' . $key, $social[$key] ?? ''), $label, ['placeholder' => 'https://…']) ?>
            <?php endforeach; ?>
        </div>
    <?= vp_admin_card_close() ?>
    <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save social links</button>
</form>
