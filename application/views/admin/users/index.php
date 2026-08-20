<?php /** @var array $rows */ ?>
<div class="flex items-center justify-between mb-4">
    <form method="get" class="flex items-center gap-2">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search…">
        <button class="vp-btn vp-btn-secondary" type="submit">Filter</button>
    </form>
    <a class="vp-btn vp-btn-primary" href="<?= base_url('admin/users/create') ?>"><i class="ri-user-add-line"></i> New customer</a>
</div>
<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Company</th><th>Active</th><th>Last login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><a class="text-brand-600 hover:underline font-semibold" href="<?= base_url('admin/users/edit/' . $r['id']) ?>"><?= vp_safe_html(trim($r['firstName'] . ' ' . $r['lastName'])) ?></a></td>
                <td class="text-xs"><?= vp_safe_html($r['email']) ?></td>
                <td><span class="vp-pill bg-gray-100 text-gray-700"><?= vp_role_label($r['role']) ?></span></td>
                <td class="text-xs text-gray-500"><?= vp_safe_html($r['company'] ?? '') ?></td>
                <td><span class="vp-pill <?= !empty($r['isActive']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>"><?= !empty($r['isActive']) ? 'Active' : 'Off' ?></span></td>
                <td class="text-xs text-gray-500"><?= $r['lastLoginAt'] ? vp_time_ago($r['lastLoginAt']) : '—' ?></td>
                <td class="text-right">
                    <a class="text-gray-600 hover:underline text-xs" href="<?= base_url('admin/users/edit/' . $r['id']) ?>">Edit</a>
                    <?php if ($r['id'] !== $this->vp_auth->id()): ?>
                        <form action="<?= base_url('admin/users/delete/' . $r['id']) ?>" method="post" class="inline" data-confirm="Delete this customer account?">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <button class="text-red-600 hover:underline text-xs ml-2" type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mt-4 flex justify-center"><?= vp_pagination_links($total_pages, $page, $base_url) ?></div>
