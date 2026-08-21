<?php
/** @var array $row */
/** @var array $groups */
/** @var array $granted */
/** @var array $role_defaults */
$granted  = array_flip($granted ?? []);
$defaults = array_flip($role_defaults ?? []);
$name = trim($row['firstName'] . ' ' . $row['lastName']);
?>
<div class="max-w-5xl space-y-6">
    <div class="bg-white border rounded-2xl p-5 flex flex-wrap items-center gap-4">
        <img class="w-12 h-12 rounded-full bg-gray-200" src="<?= vp_safe_html(vp_avatar_url($row, 96)) ?>" alt="">
        <div>
            <div class="font-bold text-lg text-ink-900"><?= vp_safe_html($name) ?></div>
            <div class="text-sm text-ink-800/70"><?= vp_safe_html($row['email']) ?> · <?= vp_role_label($row['role']) ?></div>
        </div>
        <div class="ml-auto flex gap-2">
            <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/admins/edit/' . $row['id']) ?>"><i class="ri-edit-line"></i> Edit account</a>
            <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/admins') ?>">Back</a>
        </div>
    </div>

    <form method="post" action="<?= base_url('admin/admins/permissions_save/' . $row['id']) ?>" class="space-y-5">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">

        <div class="bg-white border rounded-2xl">
            <div class="px-5 py-4 border-b flex flex-wrap items-center gap-2">
                <div>
                    <h2 class="font-bold text-ink-900">Dashboard permissions</h2>
                    <p class="text-xs text-ink-800/60">Ticked sections are the only ones this account can open — server-side, not just in the menu.</p>
                </div>
                <div class="ml-auto flex gap-2">
                    <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-perm-all>Select all</button>
                    <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-perm-none>Clear all</button>
                </div>
            </div>

            <div class="p-5 space-y-6">
                <?php foreach ($groups as $group => $items): ?>
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-bold uppercase tracking-wide text-ink-800/70"><?= vp_safe_html($group) ?></span>
                            <span class="text-[11px] text-ink-800/50"><?= vp_safe_html($descriptions[$group] ?? '') ?></span>
                        </div>
                        <div class="vp-perm-grid">
                            <?php foreach ($items as $key => $def): ?>
                                <?php if (!empty($def['super_only'])): ?>
                                    <div class="flex items-start gap-2 border border-dashed rounded-lg px-3 py-2 bg-gray-50 opacity-70">
                                        <i class="ri-lock-line mt-0.5 text-amber-600"></i>
                                        <span>
                                            <span class="block text-sm font-medium text-ink-900"><?= vp_safe_html($def['label']) ?></span>
                                            <span class="block text-[11px] text-amber-700">Super Admin only — cannot be granted</span>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <label class="flex items-start gap-2 border rounded-lg px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                        <input class="mt-1" type="checkbox" name="permissions[]" value="<?= vp_safe_html($key) ?>"
                                               data-vp-perm <?= isset($granted[$key]) ? 'checked' : '' ?>>
                                        <span>
                                            <span class="block text-sm font-medium text-ink-900"><?= vp_safe_html($def['label']) ?></span>
                                            <code class="block text-[11px] text-ink-800/50 font-mono"><?= vp_safe_html($key) ?></code>
                                            <?php if (isset($defaults[$key])): ?>
                                                <span class="text-[10px] text-ink-800/50">default for <?= vp_role_label($row['role']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="px-5 py-4 border-t flex gap-3">
                <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save permissions</button>
            </div>
        </div>
    </form>

    <form method="post" action="<?= base_url('admin/admins/permissions_reset/' . $row['id']) ?>" data-confirm="Reset to the role defaults?">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
        <button class="vp-btn vp-btn-secondary" type="submit"><i class="ri-restart-line"></i> Reset to role defaults</button>
    </form>
</div>

<script nonce="<?= vp_safe_html($csp_nonce ?? '') ?>">
document.querySelectorAll('[data-vp-perm-all]').forEach(function (b) {
    b.addEventListener('click', function () { document.querySelectorAll('[data-vp-perm]').forEach(function (c) { c.checked = true; }); });
});
document.querySelectorAll('[data-vp-perm-none]').forEach(function (b) {
    b.addEventListener('click', function () { document.querySelectorAll('[data-vp-perm]').forEach(function (c) { c.checked = false; }); });
});
</script>
