<?php /** @var array $rows */ ?>
<div class="flex flex-wrap items-center gap-3 mb-5">
    <form method="get" class="flex items-center gap-2">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search pages…">
        <button class="vp-btn vp-btn-secondary" type="submit"><i class="ri-search-line"></i></button>
    </form>
    <a class="vp-btn vp-btn-primary ml-auto" href="<?= base_url('admin/pages/create') ?>"><i class="ri-add-line"></i> New page</a>
</div>

<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead><tr><th>Title</th><th>URL</th><th>Status</th><th>Visibility</th><th>Updated</th><th class="text-right">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <a class="font-semibold text-brand-700 hover:underline" href="<?= base_url('admin/pages/edit/' . $r['id']) ?>"><?= vp_safe_html($r['title']) ?></a>
                    <?php if (!empty($r['showInMenu'])): ?><span class="vp-pill bg-blue-100 text-blue-800 ml-1">in menu</span><?php endif; ?>
                </td>
                <td class="text-xs">
                    <a class="text-ink-800/70 hover:underline" href="<?= base_url($r['slug']) ?>" target="_blank" rel="noopener">/<?= vp_safe_html($r['slug']) ?> <i class="ri-external-link-line"></i></a>
                </td>
                <td><span class="vp-pill <?= $r['status'] === 'PUBLISHED' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>"><?= vp_safe_html($r['status']) ?></span></td>
                <td class="text-xs"><?= vp_safe_html($r['visibility']) ?></td>
                <td class="text-xs text-ink-800/60"><?= vp_time_ago($r['updatedAt'] ?? $r['createdAt']) ?></td>
                <td class="text-right whitespace-nowrap">
                    <a class="text-xs text-ink-800 hover:underline" href="<?= base_url('admin/pages/edit/' . $r['id']) ?>">Edit</a>
                    <a class="text-xs text-brand-700 hover:underline ml-2" href="<?= base_url('admin/homepage/index/' . rawurlencode('page:' . $r['slug'])) ?>">Builder</a>
                    <form action="<?= base_url('admin/pages/toggle/' . $r['id']) ?>" method="post" class="inline">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <button class="text-xs text-brand-700 hover:underline ml-2" type="submit"><?= $r['status'] === 'PUBLISHED' ? 'Unpublish' : 'Publish' ?></button>
                    </form>
                    <form action="<?= base_url('admin/pages/delete/' . $r['id']) ?>" method="post" class="inline" data-confirm="Delete this page?">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <button class="text-xs text-red-600 hover:underline ml-2" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-sm text-ink-800/60 py-8">No pages yet — create your first one.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-4 flex justify-center"><?= vp_pagination_links($total_pages, $page, $base_url) ?></div>
