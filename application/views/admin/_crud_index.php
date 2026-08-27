<?php
/** @var array $rows */
/** @var array $columns */
$base = $base_url ?? null;
// Resolve a stable controller base path (e.g. admin/categories). Passed in by
// Admin_Crud; the old view guessed it from get_class($this) which is the loader
// inside a view and produced broken relative links like "/edit/<id>".
$ctrlBase = isset($controller_url) ? $controller_url
    : (isset($redirect_url) ? rtrim($redirect_url, '/') : null);
if (!$ctrlBase) {
    $seg = trim(parse_url(current_url(), PHP_URL_PATH) ?: '', '/');
    $parts = explode('/', $seg);
    // strip a trailing action segment such as /edit /create
    $allowed = ['index','create','edit','save','delete'];
    if (count($parts) && in_array(end($parts), $allowed, true)) array_pop($parts);
    $ctrlBase = implode('/', $parts);
}
$ctrlBase = $ctrlBase ? rtrim($ctrlBase, '/') : '';
?>
<div class="flex items-center justify-between mb-4">
    <form method="get" class="flex items-center gap-2">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search ?? '') ?>" placeholder="Search…">
        <button class="vp-btn vp-btn-secondary" type="submit">Search</button>
    </form>
    <a class="vp-btn vp-btn-primary" href="<?= base_url($ctrlBase . '/create') ?>"><i class="ri-add-line"></i> New</a>
</div>

<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead>
            <tr>
                <?php foreach ($columns as $label => $col): ?>
                    <th><?= vp_safe_html($label) ?></th>
                <?php endforeach; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="<?= count($columns) + 1 ?>" class="text-center text-gray-500">No records.</td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr>
                <?php foreach ($columns as $label => $col): ?>
                    <td>
                        <?php
                        $v = $r[$col] ?? '';
                        if (in_array($col, ['isActive', 'active'], true)) {
                            echo (int)$v ? '<span class="vp-pill bg-green-100 text-green-800">Active</span>' : '<span class="vp-pill bg-gray-200 text-gray-700">Off</span>';
                        } elseif (in_array($col, ['featured'], true)) {
                            echo (int)$v ? '<span class="vp-pill bg-blue-100 text-blue-800">★</span>' : '';
                        } elseif (in_array($col, ['createdAt','updatedAt','postedAt','publishedAt'], true)) {
                            echo '<span class="text-xs text-gray-500">' . vp_human_date($v) . '</span>';
                        } elseif (strlen((string)$v) > 80) {
                            echo vp_safe_html(vp_truncate($v, 80));
                        } else {
                            echo vp_safe_html($v);
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
                <td class="text-right whitespace-nowrap">
                    <a class="text-brand-600 hover:underline text-xs" href="<?= base_url($ctrlBase . '/edit/' . $r['id']) ?>">Edit</a>
                    <form action="<?= base_url($ctrlBase . '/delete/' . $r['id']) ?>" method="post" class="inline" data-confirm="Delete this record?">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <button class="text-red-600 hover:underline text-xs ml-2" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4 flex justify-center"><?= vp_pagination_links($total_pages, $page, $base_url) ?></div>
