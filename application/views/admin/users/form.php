<?php /** @var array|null $row */ $is_create = empty($row); ?>
<form method="post" action="<?= base_url('admin/users/save') ?>" class="space-y-4 max-w-2xl">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if (!$is_create): ?><input type="hidden" name="id" value="<?= $row['id'] ?>"><?php endif; ?>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>First name *</label><input class="vp-input" name="firstName" required value="<?= vp_safe_html($row['firstName'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Last name *</label><input class="vp-input" name="lastName" required value="<?= vp_safe_html($row['lastName'] ?? '') ?>"></div>
    </div>
    <div class="vp-form-row"><label>Email *</label><input class="vp-input" type="email" name="email" required value="<?= vp_safe_html($row['email'] ?? '') ?>"></div>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Phone</label><input class="vp-input" name="phone" value="<?= vp_safe_html($row['phone'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Company</label><input class="vp-input" name="company" value="<?= vp_safe_html($row['company'] ?? '') ?>"></div>
    </div>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Account type</label>
            <input class="vp-input bg-gray-50" type="text" value="Customer" disabled>
            <p class="vp-help">Staff roles are assigned by the Super Admin under
                <a class="text-brand-700 hover:underline" href="<?= base_url('admin/admins') ?>">Administrators</a>.</p>
        </div>
        <div class="vp-form-row">
            <label><?= $is_create ? 'Password *' : 'New password (leave blank to keep)' ?></label>
            <input class="vp-input" type="password" name="password" <?= $is_create ? 'required minlength="8"' : 'minlength="8"' ?>>
        </div>
    </div>
    <div class="vp-form-row">
        <label class="inline-flex items-center gap-2"><input type="hidden" name="isActive" value="0"><input type="checkbox" name="isActive" value="1" <?= (!$is_create || !empty($row['isActive'])) ? 'checked' : '' ?>> Active</label>
    </div>
    <div class="flex items-center gap-2">
        <button class="vp-btn vp-btn-primary" type="submit"><?= $is_create ? 'Create' : 'Save' ?></button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/users') ?>">Cancel</a>
    </div>
</form>
