<?php /** @var array $rows */ ?>
<div class="flex items-center justify-between mb-4">
    <form method="get" class="flex items-center gap-2">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search news…">
        <button class="vp-btn vp-btn-secondary" type="submit">Search</button>
    </form>
    <a class="vp-btn vp-btn-primary" href="<?= base_url('admin/news/create') ?>"><i class="ri-add-line"></i> New</a>
</div>
<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead><tr><th>Title</th><th>Category</th><th>Published</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $n): ?>
            <tr>
                <td><a class="text-brand-600 hover:underline font-semibold" href="<?= base_url('admin/news/edit/' . $n['id']) ?>"><?= vp_safe_html($n['title']) ?></a></td>
                <td class="text-xs text-gray-600"><?= vp_safe_html($n['category'] ?? '') ?></td>
                <td class="text-xs text-gray-500"><?= vp_human_date($n['publishedAt']) ?></td>
                <td><span class="vp-pill <?= !empty($n['isActive']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>"><?= !empty($n['isActive']) ? 'Active' : 'Draft' ?></span></td>
                <td class="text-right">
                    <a class="text-gray-600 hover:underline text-xs" href="<?= base_url('admin/news/edit/' . $n['id']) ?>">Edit</a>
                    <form action="<?= base_url('admin/news/delete/' . $n['id']) ?>" method="post" class="inline" data-confirm="Delete this news item?">
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
