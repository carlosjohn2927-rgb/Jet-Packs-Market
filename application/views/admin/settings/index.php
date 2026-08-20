<?php
/** Advanced key/value editor — every setting row in the database. */
$this->load->view('admin/settings/_tabs', ['tabs' => $tabs, 'tab' => $tab]);
?>
<div class="max-w-5xl space-y-6">
    <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-xl px-4 py-3 text-sm flex gap-2">
        <i class="ri-information-line text-lg"></i>
        <span>Raw access to every stored setting. Most values have a friendlier editor on the other tabs.</span>
    </div>

    <form method="post" action="<?= base_url('admin/settings/save_advanced') ?>" class="space-y-5">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
        <?php foreach ($grouped as $group => $rows): ?>
            <section class="bg-white border rounded-2xl">
                <header class="px-5 py-3 border-b bg-gray-50 rounded-t-2xl">
                    <h2 class="font-bold text-sm uppercase tracking-wide text-ink-800"><?= vp_safe_html($group) ?></h2>
                </header>
                <div class="divide-y">
                    <?php foreach ($rows as $r): ?>
                        <div class="px-5 py-3 grid md:grid-cols-12 gap-3 items-start">
                            <div class="md:col-span-3">
                                <code class="text-xs font-mono text-ink-900"><?= vp_safe_html($r['key']) ?></code>
                                <div class="text-[11px] text-ink-800/50"><?= vp_safe_html($r['type']) ?></div>
                            </div>
                            <div class="md:col-span-8">
                                <input type="hidden" name="key[]" value="<?= vp_safe_html($r['key']) ?>">
                                <input type="hidden" name="type[]" value="<?= vp_safe_html($r['type']) ?>">
                                <input type="hidden" name="group[]" value="<?= vp_safe_html($r['group']) ?>">
                                <?php if (in_array($r['type'], ['TEXT', 'JSON'], true) || strlen((string) $r['value']) > 80): ?>
                                    <textarea class="vp-input font-mono text-xs" name="value[]" rows="3"><?= vp_safe_html($r['value']) ?></textarea>
                                <?php else: ?>
                                    <input class="vp-input" type="text" name="value[]" value="<?= vp_safe_html($r['value']) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="md:col-span-1 text-right">
                                <?php if (!empty($is_super_admin)): ?>
                                    <button class="text-xs text-red-600 hover:underline" type="submit"
                                            formaction="<?= base_url('admin/settings/delete') ?>" name="key" value="<?= vp_safe_html($r['key']) ?>"
                                            onclick="return confirm('Delete the setting <?= vp_safe_html($r['key']) ?>?')">Delete</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save all values</button>
    </form>

    <form method="post" action="<?= base_url('admin/settings/add') ?>">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
        <?= vp_admin_card_open('Add a setting', '', 'ri-add-line') ?>
            <div class="grid md:grid-cols-4 gap-3">
                <?= vp_text_field('new_key', '', 'Key', ['placeholder' => 'my_setting_key']) ?>
                <?= vp_text_field('new_value', '', 'Value') ?>
                <?= vp_select_field('new_type', ['STRING' => 'String', 'TEXT' => 'Text', 'INT' => 'Number', 'BOOL' => 'Boolean', 'JSON' => 'JSON'], 'STRING', 'Type') ?>
                <?= vp_text_field('new_group', 'GENERAL', 'Group') ?>
            </div>
            <button class="vp-btn vp-btn-secondary" type="submit"><i class="ri-add-line"></i> Add setting</button>
        <?= vp_admin_card_close() ?>
    </form>
</div>
