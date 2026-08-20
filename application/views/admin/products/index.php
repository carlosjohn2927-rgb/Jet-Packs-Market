<?php
/** @var array $rows */
$categories_by_id = [];
foreach ($categories as $c) $categories_by_id[$c['id']] = $c;
?>
<div class="flex items-center justify-between mb-4">
    <form method="get" class="flex items-center gap-2 flex-1 max-w-xl">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search products…">
        <select class="vp-select w-auto" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['slug'] ?>" <?= $current_category === $c['slug'] ? 'selected' : '' ?>><?= vp_safe_html($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="vp-select w-auto" name="industry">
            <option value="">All industries</option>
            <?php foreach (($industries ?? []) as $i): ?>
                <option value="<?= $i['slug'] ?>" <?= ($current_industry ?? '') === $i['slug'] ? 'selected' : '' ?>><?= vp_safe_html($i['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="vp-btn vp-btn-secondary" type="submit">Filter</button>
    </form>
    <a href="<?= base_url('admin/products/create' . (!empty($current_category) || !empty($current_industry)
            ? '?' . http_build_query(array_filter(['category' => $current_category ?? '', 'industry' => $current_industry ?? '']))
            : '')) ?>"
       class="vp-btn vp-btn-primary"><i class="ri-add-line"></i> New product</a>
</div>

<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead>
            <tr>
                <th style="width:64px">Image</th>
                <th>SKU</th>
                <th>Name</th>
                <th>Category</th>
                <th>Availability</th>
                <th>Views</th>
                <th>Featured</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="9" class="text-center text-gray-500">No products yet.</td></tr>
        <?php else: foreach ($rows as $p): ?>
            <tr>
                <td>
                    <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 border">
                        <?= vp_product_image_tag($p, 'w-full h-full object-cover') ?>
                    </div>
                </td>
                <td class="font-mono text-xs"><?= vp_safe_html($p['sku']) ?></td>
                <td>
                    <a class="text-brand-600 hover:underline font-semibold" href="<?= base_url('admin/products/edit/' . $p['id']) ?>"><?= vp_safe_html($p['name']) ?></a>
                </td>
                <td class="text-xs text-gray-600"><?= $p['categoryId'] ? vp_safe_html(($categories_by_id[$p['categoryId']]['name'] ?? '—')) : '—' ?></td>
                <td><span class="vp-pill <?= ($p['availability']==='IN_STOCK' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') ?>"><?= vp_safe_html(str_replace('_',' ',$p['availability'])) ?></span></td>
                <td class="text-xs text-gray-500"><?= (int) $p['views'] ?></td>
                <td><?= !empty($p['featured']) ? '<span class="vp-pill bg-blue-100 text-blue-800">★ Featured</span>' : '' ?></td>
                <td><span class="vp-pill <?= !empty($p['isActive']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>"><?= !empty($p['isActive']) ? 'Active' : 'Draft' ?></span></td>
                <td class="text-right">
                    <a class="text-brand-600 hover:underline text-xs" href="<?= base_url('products/' . $p['slug']) ?>" target="_blank">View</a>
                    <a class="text-gray-600 hover:underline text-xs ml-2" href="<?= base_url('admin/products/edit/' . $p['id']) ?>">Edit</a>
                    <form action="<?= base_url('admin/products/delete/' . $p['id']) ?>" method="post" class="inline" data-confirm="Delete this product?">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <button class="text-red-600 hover:underline text-xs ml-2" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4 flex justify-center">
    <?= vp_pagination_links($total_pages, $page, $base_url) ?>
</div>
