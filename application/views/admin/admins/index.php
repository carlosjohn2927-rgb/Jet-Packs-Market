<?php
/** @var array $rows */
/** @var int   $total_perms */
?>
<div class="flex flex-wrap items-center gap-3 mb-5">
    <form method="get" class="flex items-center gap-2">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search administrators…">
        <button class="vp-btn vp-btn-secondary" type="submit"><i class="ri-search-line"></i> Search</button>
    </form>
    <a class="vp-btn vp-btn-primary ml-auto" href="<?= base_url('admin/admins/create') ?>"><i class="ri-user-add-line"></i> New administrator</a>
</div>

<div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-xl px-4 py-3 text-sm mb-5 flex gap-2">
    <i class="ri-shield-star-line text-lg"></i>
    <div>
        <strong>You are the Super Admin.</strong> You control every administrator account and exactly which
        dashboard sections each of them can open. Permissions are enforced on the server, so a disabled section
        cannot be reached by typing its URL either.
    </div>
</div>

<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead>
            <tr>
                <th>Administrator</th><th>Role</th><th>Status</th><th>Permissions</th><th>Last login</th><th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): $self = ($r['id'] === $this->vp_auth->id()); ?>
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <img class="w-9 h-9 rounded-full bg-gray-200" src="<?= vp_safe_html(vp_avatar_url($r, 72)) ?>" alt="">
                        <div>
                            <div class="font-semibold text-ink-900">
                                <?php if ($r['is_super']): ?>
                                    <?= vp_safe_html(trim($r['firstName'] . ' ' . $r['lastName'])) ?>
                                <?php else: ?>
                                    <a class="text-brand-700 hover:underline" href="<?= base_url('admin/admins/edit/' . $r['id']) ?>"><?= vp_safe_html(trim($r['firstName'] . ' ' . $r['lastName'])) ?></a>
                                <?php endif; ?>
                                <?php if ($self): ?><span class="text-[10px] text-ink-800/60">(you)</span><?php endif; ?>
                            </div>
                            <div class="text-xs text-ink-800/60"><?= vp_safe_html($r['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="vp-pill <?= $r['is_super'] ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' ?>">
                        <?= vp_role_label($r['role']) ?>
                    </span>
                </td>
                <td>
                    <span class="vp-pill <?= !empty($r['isActive']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>">
                        <?= !empty($r['isActive']) ? 'Enabled' : 'Disabled' ?>
                    </span>
                    <?php if (!empty($r['mustChangePassword'])): ?>
                        <span class="vp-pill bg-orange-100 text-orange-800 ml-1">Temp password</span>
                    <?php endif; ?>
                </td>
                <td class="text-xs">
                    <?php if ($r['is_super']): ?>
                        <span class="font-semibold text-amber-700">Full access (all <?= (int) $total_perms + 2 ?>)</span>
                    <?php else: ?>
                        <a class="text-brand-700 hover:underline" href="<?= base_url('admin/admins/permissions/' . $r['id']) ?>">
                            <?= (int) $r['permission_count'] ?> of <?= (int) $total_perms ?> permissions
                        </a>
                    <?php endif; ?>
                </td>
                <td class="text-xs text-ink-800/60"><?= $r['lastLoginAt'] ? vp_time_ago($r['lastLoginAt']) : 'never' ?></td>
                <td class="text-right whitespace-nowrap">
                    <a class="text-xs text-ink-800 hover:underline" href="<?= base_url('admin/admins/activity/' . $r['id']) ?>">Activity</a>
                    <?php if (!$r['is_super']): ?>
                        <a class="text-xs text-brand-700 hover:underline ml-2" href="<?= base_url('admin/admins/permissions/' . $r['id']) ?>">Permissions</a>
                        <a class="text-xs text-ink-800 hover:underline ml-2" href="<?= base_url('admin/admins/edit/' . $r['id']) ?>">Edit</a>
                        <form action="<?= base_url('admin/admins/toggle/' . $r['id']) ?>" method="post" class="inline"
                              data-confirm="<?= !empty($r['isActive']) ? 'Disable this administrator? They will not be able to sign in.' : 'Enable this administrator?' ?>">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <button class="text-xs <?= !empty($r['isActive']) ? 'text-orange-600' : 'text-green-700' ?> hover:underline ml-2" type="submit">
                                <?= !empty($r['isActive']) ? 'Disable' : 'Enable' ?>
                            </button>
                        </form>
                        <form action="<?= base_url('admin/admins/delete/' . $r['id']) ?>" method="post" class="inline"
                              data-confirm="Permanently delete this administrator account?">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <button class="text-xs text-red-600 hover:underline ml-2" type="submit">Delete</button>
                        </form>
                    <?php else: ?>
                        <span class="text-xs text-ink-800/40 ml-2" title="The Super Admin account is protected">
                            <i class="ri-lock-line"></i> protected
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-sm text-ink-800/60 py-8">No administrators found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
