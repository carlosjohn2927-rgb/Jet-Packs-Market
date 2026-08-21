<?php
/** @var array|null $row */
/** @var array $roles */
/** @var array $groups   grouped permission catalogue */
/** @var array $granted  effective permission keys */
$granted = array_flip($granted ?? []);
$editing = !empty($row);
?>
<form method="post" action="<?= base_url('admin/admins/save') ?>" class="space-y-6 max-w-5xl">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= vp_safe_html($row['id']) ?>"><?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <?= vp_admin_card_open('Account', 'Sign-in details for this administrator', 'ri-user-3-line') ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <?= vp_text_field('firstName', $row['firstName'] ?? '', 'First name', ['required' => true]) ?>
                    <?= vp_text_field('lastName',  $row['lastName']  ?? '', 'Last name',  ['required' => true]) ?>
                    <?= vp_text_field('email', $row['email'] ?? '', 'Email address', ['type' => 'email', 'required' => true]) ?>
                    <?= vp_text_field('phone', $row['phone'] ?? '', 'Phone (optional)') ?>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <?= vp_select_field('role', $roles, $row['role'] ?? ROLE_ADMIN, 'Role',
                        'The Super Admin role cannot be assigned here — it belongs to the owner account only.') ?>
                    <?= vp_text_field('company', $row['company'] ?? '', 'Department / company (optional)') ?>
                </div>
                <div class="grid md:grid-cols-2 gap-4 pt-2">
                    <?= vp_toggle_field('isActive', !isset($row['isActive']) || !empty($row['isActive']), 'Account enabled',
                        'A disabled administrator cannot sign in and is signed out immediately.') ?>
                    <?= vp_toggle_field('mustChangePassword', !empty($row['mustChangePassword']), 'Force password change at next sign-in') ?>
                </div>
            <?= vp_admin_card_close() ?>

            <?= vp_admin_card_open($editing ? 'Change password' : 'Password', $editing ? 'Leave blank to keep the current password' : 'Minimum 10 characters', 'ri-lock-password-line') ?>
                <?= vp_text_field('password', '', $editing ? 'New password' : 'Password', [
                    'type' => 'password',
                    'help' => 'Stored as a bcrypt hash — never in plain text.',
                ]) ?>
            <?= vp_admin_card_close() ?>

            <?= vp_admin_card_open('Permissions', 'Exactly what this administrator can open and change', 'ri-shield-check-line') ?>
                <div class="flex flex-wrap gap-2 pb-2 border-b">
                    <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-perm-all><i class="ri-checkbox-multiple-line"></i> Select all</button>
                    <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-perm-none><i class="ri-checkbox-blank-line"></i> Clear all</button>
                    <span class="text-xs text-ink-800/60 self-center ml-2">Super Admin-only permissions are never listed here.</span>
                </div>
                <?php foreach ($groups as $group => $items): ?>
                    <div class="pt-3">
                        <div class="text-xs font-bold uppercase tracking-wide text-ink-800/70 mb-2"><?= vp_safe_html($group) ?></div>
                        <div class="vp-perm-grid">
                            <?php foreach ($items as $key => $def): if (!empty($def['super_only'])) continue; ?>
                                <label class="flex items-start gap-2 border rounded-lg px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                    <input class="mt-1" type="checkbox" name="permissions[]" value="<?= vp_safe_html($key) ?>"
                                           data-vp-perm <?= isset($granted[$key]) ? 'checked' : '' ?>>
                                    <span>
                                        <span class="block text-sm font-medium text-ink-900"><?= vp_safe_html($def['label']) ?></span>
                                        <code class="block text-[11px] text-ink-800/50 font-mono"><?= vp_safe_html($key) ?></code>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?= vp_admin_card_close() ?>
        </div>

        <div class="space-y-6">
            <?= vp_admin_card_open('Save', '', 'ri-save-3-line') ?>
                <button class="vp-btn vp-btn-primary w-full justify-center" type="submit">
                    <i class="ri-save-3-line"></i> <?= $editing ? 'Save changes' : 'Create administrator' ?>
                </button>
                <a class="vp-btn vp-btn-secondary w-full justify-center" href="<?= base_url('admin/admins') ?>">Cancel</a>
            <?= vp_admin_card_close() ?>

            <?php if ($editing): ?>
                <?= vp_admin_card_open('Security actions', '', 'ri-key-2-line') ?>
                    <p class="text-xs text-ink-800/70">Generate a temporary password the administrator must change at first sign-in.</p>
                <?= vp_admin_card_close() ?>

                <?= vp_admin_card_open('Recent activity', '', 'ri-history-line') ?>
                    <?php if (empty($activity)): ?>
                        <p class="text-sm text-ink-800/60">No activity recorded yet.</p>
                    <?php else: ?>
                        <ul class="space-y-2 text-xs">
                            <?php foreach ($activity as $a): ?>
                                <li class="flex gap-2">
                                    <span class="vp-pill bg-gray-100 text-gray-700"><?= vp_safe_html($a['action']) ?></span>
                                    <span class="text-ink-800/70"><?= vp_safe_html($a['resource']) ?></span>
                                    <span class="ml-auto text-ink-800/50"><?= vp_time_ago($a['createdAt']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <a class="text-xs text-brand-700 hover:underline" href="<?= base_url('admin/admins/activity/' . $row['id']) ?>">View full activity log →</a>
                <?= vp_admin_card_close() ?>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if ($editing): ?>
<form method="post" action="<?= base_url('admin/admins/reset_password/' . $row['id']) ?>" class="max-w-5xl mt-6"
      data-confirm="Reset this administrator's password?">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?= vp_admin_card_open('Reset password', 'Leave the field empty to generate a strong temporary password', 'ri-refresh-line') ?>
        <div class="grid md:grid-cols-3 gap-4 items-end">
            <?= vp_text_field('new_password', '', 'New password (optional)', ['type' => 'text', 'placeholder' => 'auto-generate']) ?>
            <?= vp_toggle_field('force_change', true, 'Force change at next sign-in') ?>
            <button class="vp-btn vp-btn-danger justify-center" type="submit"><i class="ri-key-2-line"></i> Reset password</button>
        </div>
    <?= vp_admin_card_close() ?>
</form>
<?php endif; ?>

<script nonce="<?= vp_safe_html($csp_nonce ?? '') ?>">
document.querySelectorAll('[data-vp-perm-all]').forEach(function (b) {
    b.addEventListener('click', function () { document.querySelectorAll('[data-vp-perm]').forEach(function (c) { c.checked = true; }); });
});
document.querySelectorAll('[data-vp-perm-none]').forEach(function (b) {
    b.addEventListener('click', function () { document.querySelectorAll('[data-vp-perm]').forEach(function (c) { c.checked = false; }); });
});
</script>
