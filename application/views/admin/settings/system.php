<?php $this->load->view('admin/settings/_tabs', ['tabs' => $tabs, 'tab' => $tab]); ?>
<div class="max-w-4xl space-y-6">
    <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-xl px-4 py-3 text-sm flex gap-2">
        <i class="ri-mail-settings-line text-lg"></i>
        <span><strong>Email and system settings.</strong> Admin and Super Admin accounts with settings access can configure outgoing SMTP here.</span>
    </div>

    <?= vp_admin_card_open('Outgoing email', 'Transport currently used for quotes, contact forms and password resets', 'ri-mail-send-line') ?>
        <table class="text-sm w-full">
            <tr><td class="py-1 font-semibold w-40">Transport</td><td><?= vp_safe_html($email['transport'] ?? 'unknown') ?></td></tr>
            <tr><td class="py-1 font-semibold">Status</td>
                <td>
                    <span class="vp-pill <?= !empty($email['ok']) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= !empty($email['ok']) ? 'Configured' : 'Needs attention' ?>
                    </span>
                </td></tr>
            <?php if (!empty($email['message'])): ?>
                <tr><td class="py-1 font-semibold align-top">Detail</td><td class="text-ink-800/70"><?= vp_safe_html($email['message']) ?></td></tr>
            <?php endif; ?>
        </table>
        <p class="text-xs text-ink-800/60">SMTP can now be managed below from the dashboard. Values in .env still work as fallbacks when dashboard fields are empty.</p>
        <form method="post" action="<?= base_url('admin/settings/test_email') ?>" class="mt-4 flex flex-col sm:flex-row gap-2">
            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
            <input class="vp-input" type="email" name="test_email" placeholder="Send test email to…" required>
            <button class="vp-btn vp-btn-secondary" type="submit"><i class="ri-send-plane-line"></i> Send test</button>
        </form>
    <?= vp_admin_card_close() ?>

    <form method="post" action="<?= base_url('admin/settings/save_system') ?>" class="space-y-6">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
        <?= vp_admin_card_open('Maintenance mode', 'Visitors see a maintenance page; signed-in staff still see the site', 'ri-tools-line') ?>
            <?= vp_toggle_field('maintenance_mode', $values['maintenance_mode'] === '1', 'Enable maintenance mode') ?>
            <?= vp_textarea_field('maintenance_message', $values['maintenance_message'], 'Message shown to visitors', 3) ?>
        <?= vp_admin_card_close() ?>

        <?= vp_admin_card_open('Email identity', 'Who outgoing messages come from — leave empty to use the .env defaults', 'ri-mail-line') ?>
            <div class="grid md:grid-cols-3 gap-4">
                <?= vp_text_field('mail_from_email', $values['mail_from_email'], 'From address', ['type' => 'email', 'placeholder' => 'no-reply@yourdomain.com']) ?>
                <?= vp_text_field('mail_from_name', $values['mail_from_name'], 'From name') ?>
                <?= vp_text_field('mail_reply_to', $values['mail_reply_to'], 'Reply-to address', ['type' => 'email']) ?>
            </div>
        <?= vp_admin_card_close() ?>

        <?= vp_admin_card_open('SMTP server', 'Use your cPanel/webmail mailbox. Set host + password to make SMTP the active transport.', 'ri-mail-settings-line') ?>
            <div class="grid md:grid-cols-2 gap-4">
                <?= vp_text_field('smtp_host', $values['smtp_host'], 'SMTP host', ['placeholder' => 'mail.yourdomain.com']) ?>
                <?= vp_text_field('smtp_port', $values['smtp_port'], 'SMTP port', ['type' => 'number', 'placeholder' => '465']) ?>
                <?= vp_text_field('smtp_user', $values['smtp_user'], 'SMTP username', ['placeholder' => 'full mailbox email address']) ?>
                <?= vp_select_field('smtp_crypto', ['ssl' => 'SSL (usually port 465)', 'tls' => 'TLS / STARTTLS (usually port 587)'], $values['smtp_crypto'], 'Encryption') ?>
            </div>
            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <?= vp_text_field('smtp_pass', '', 'SMTP password', ['type' => 'password', 'placeholder' => $values['smtp_has_password'] === '1' ? 'Password saved — leave blank to keep' : 'Mailbox password']) ?>
                <div class="flex items-end pb-2">
                    <label class="inline-flex items-center gap-2 text-sm text-ink-800/70">
                        <input type="checkbox" name="smtp_clear_password" value="1"> Clear saved dashboard SMTP password
                    </label>
                </div>
            </div>
            <div class="mt-3 rounded-lg bg-gray-50 border px-3 py-2 text-xs text-ink-800/70">
                Recommended cPanel values: host <strong>mail.yourdomain.com</strong>, username = full email address, port <strong>465 + SSL</strong> or <strong>587 + TLS</strong>.
                <?php if ($values['smtp_has_password'] === '1'): ?><span class="text-green-700 font-semibold"> A password is currently configured.</span><?php else: ?><span class="text-red-600 font-semibold"> Add a password to activate SMTP.</span><?php endif; ?>
            </div>
        <?= vp_admin_card_close() ?>

        <?= vp_admin_card_open('Chat assistant', 'The floating helper on the public website', 'ri-robot-line') ?>
            <?= vp_toggle_field('chat_enabled', $values['chat_enabled'] === '1', 'Show the chat assistant on the website') ?>
            <div class="grid md:grid-cols-3 gap-4">
                <?= vp_text_field('chat_title', $values['chat_title'], 'Window title', ['placeholder' => vp_site('name') . ' Assistant']) ?>
                <?= vp_text_field('chat_bot_name', $values['chat_bot_name'], 'Assistant name', ['placeholder' => 'Assistant']) ?>
                <?= vp_text_field('chat_rate_limit_per_hour', $values['chat_rate_limit_per_hour'], 'Messages allowed per hour / visitor', ['type' => 'number']) ?>
            </div>
            <?= vp_textarea_field('chat_welcome', $values['chat_welcome'], 'Welcome message', 2) ?>
            <?= vp_text_field('chat_quick_replies', $values['chat_quick_replies'], 'Quick reply buttons', ['help' => 'Comma separated, e.g. Products, Request a quote, Delivery times, Contact']) ?>
        <?= vp_admin_card_close() ?>

        <?= vp_admin_card_open('Features', '', 'ri-toggle-line') ?>
            <?= vp_toggle_field('rfq_enabled', $values['rfq_enabled'] === '1', 'Accept quote requests (RFQ form)') ?>
            <div class="grid md:grid-cols-2 gap-4">
                <?= vp_text_field('rfq_admin_email', $values['rfq_admin_email'], 'Send new quote alerts to', ['type' => 'email']) ?>
                <?= vp_text_field('rfq_rate_limit_per_hour', $values['rfq_rate_limit_per_hour'], 'Quote requests allowed per hour / IP', ['type' => 'number']) ?>
            </div>
        <?= vp_admin_card_close() ?>

        <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save settings</button>
    </form>
</div>
