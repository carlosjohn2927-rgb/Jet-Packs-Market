<?php /** @var array $rows */ ?>
<div class="flex items-center justify-between mb-4">
    <form method="get" class="flex items-center gap-2">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search…">
        <button class="vp-btn vp-btn-secondary" type="submit">Search</button>
    </form>
    <a class="vp-btn vp-btn-primary" href="<?= base_url('admin/careers/create') ?>"><i class="ri-add-line"></i> New job</a>
</div>
<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead><tr><th>Title</th><th>Department</th><th>Location</th><th>Type</th><th>Active</th><th>Posted</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><a class="text-brand-600 hover:underline font-semibold" href="<?= base_url('admin/careers/edit/' . $r['id']) ?>"><?= vp_safe_html($r['title']) ?></a></td>
                <td class="text-xs"><?= vp_safe_html($r['department']) ?></td>
                <td class="text-xs"><?= vp_safe_html($r['location']) ?></td>
                <td class="text-xs"><?= vp_safe_html($r['type']) ?></td>
                <td><span class="vp-pill <?= !empty($r['isActive']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>"><?= !empty($r['isActive']) ? 'Active' : 'Off' ?></span></td>
                <td class="text-xs text-gray-500"><?= vp_human_date($r['postedAt']) ?></td>
                <td class="text-right">
                    <a class="text-gray-600 hover:underline text-xs" href="<?= base_url('admin/careers/' . $r['id'] . '/applications') ?>">Applications</a>
                    <a class="text-gray-600 hover:underline text-xs ml-2" href="<?= base_url('admin/careers/edit/' . $r['id']) ?>">Edit</a>
                    <form action="<?= base_url('admin/careers/delete/' . $r['id']) ?>" method="post" class="inline" data-confirm="Delete this job posting?">
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
