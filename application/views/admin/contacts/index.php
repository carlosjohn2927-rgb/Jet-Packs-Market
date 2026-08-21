<?php /** @var array $rows */ ?>
<div class="flex items-center justify-between mb-4">
    <form method="get" class="flex items-center gap-2">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search…">
        <select class="vp-select w-auto" name="status">
            <?php foreach (['' => 'All', 'NEW' => 'New', 'READ' => 'Read', 'REPLIED' => 'Replied', 'ARCHIVED' => 'Archived'] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <button class="vp-btn vp-btn-secondary" type="submit">Filter</button>
    </form>
</div>
<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead><tr><th>Name</th><th>Subject</th><th>Department</th><th>Status</th><th>Received</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <a class="text-brand-600 hover:underline font-semibold" href="<?= base_url('admin/contacts/' . $r['id']) ?>"><?= vp_safe_html($r['name']) ?></a>
                    <div class="text-xs text-gray-500"><?= vp_safe_html($r['email']) ?></div>
                </td>
                <td class="text-sm"><?= vp_safe_html($r['subject']) ?></td>
                <td class="text-xs text-gray-500"><?= vp_safe_html($r['department'] ?? '') ?></td>
                <td><span class="vp-pill <?= $r['status']==='NEW' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700' ?>"><?= vp_safe_html($r['status']) ?></span></td>
                <td class="text-xs text-gray-500"><?= vp_time_ago($r['createdAt']) ?></td>
                <td class="text-right">
                    <a class="text-gray-600 hover:underline text-xs" href="<?= base_url('admin/contacts/' . $r['id']) ?>">Open</a>
                    <form action="<?= base_url('admin/contacts/' . $r['id'] . '/delete') ?>" method="post" class="inline" data-confirm="Delete this contact?">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <button class="text-red-600 hover:underline text-xs ml-2" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mt-4 flex justify-center"><?= vp_pagination_links($total_pages, $page, $base_url) ?></div>
