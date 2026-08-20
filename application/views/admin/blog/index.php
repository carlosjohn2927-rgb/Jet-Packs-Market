<?php /** @var array $rows */ ?>
<div class="flex items-center justify-between mb-4">
    <form method="get" class="flex items-center gap-2">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search…">
        <button class="vp-btn vp-btn-secondary" type="submit">Search</button>
    </form>
    <a class="vp-btn vp-btn-primary" href="<?= base_url('admin/blog/create') ?>"><i class="ri-add-line"></i> New article</a>
</div>
<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th>Views</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><a class="text-brand-600 hover:underline font-semibold" href="<?= base_url('admin/blog/edit/' . $r['id']) ?>"><?= vp_safe_html($r['title']) ?></a></td>
                <td class="text-xs text-gray-600"><?= vp_safe_html($r['category'] ?? '') ?></td>
                <td><span class="vp-pill <?= $r['status']==='PUBLISHED' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>"><?= vp_safe_html($r['status']) ?></span></td>
                <td class="text-xs text-gray-500"><?= vp_human_date($r['publishedAt']) ?></td>
                <td class="text-xs text-gray-500"><?= (int) $r['views'] ?></td>
                <td class="text-right">
                    <a class="text-gray-600 hover:underline text-xs" href="<?= base_url('admin/blog/edit/' . $r['id']) ?>">Edit</a>
                    <form action="<?= base_url('admin/blog/delete/' . $r['id']) ?>" method="post" class="inline" data-confirm="Delete this article?">
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
