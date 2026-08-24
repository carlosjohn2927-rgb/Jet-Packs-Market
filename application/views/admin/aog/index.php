<?php
/** @var array $rows */
/** @var int $total */
/** @var int $total_pages */
/** @var int $page */
/** @var string $search */
/** @var string $base_url */

$status_badge = function ($status) {
    $map = [
        'REQUESTED'  => 'bg-amber-100 text-amber-800',
        'CONFIRMED'  => 'bg-sky-100 text-sky-800',
        'IN_TRANSIT' => 'bg-indigo-100 text-indigo-800',
        'DELIVERED'  => 'bg-emerald-100 text-emerald-800',
        'CANCELLED'  => 'bg-gray-200 text-gray-700',
    ];
    return '<span class="px-2 py-1 rounded-full text-xs font-semibold ' . ($map[$status] ?? 'bg-gray-100 text-gray-700') . '">' . $status . '</span>';
};
?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-ink-900">AOG dispatches</h1>
        <p class="text-sm text-ink-800">Emergency &amp; priority part dispatches customers can track.</p>
    </div>
    <a class="vp-btn vp-btn-primary" href="<?= base_url('admin/aog/create') ?>"><i class="ri-add-line"></i> New dispatch</a>
</div>

<form method="get" class="flex flex-wrap items-center gap-2 mb-4">
    <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search reference, aircraft, tracking, customer…">
    <button class="vp-btn vp-btn-secondary" type="submit">Search</button>
</form>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
    <table class="vp-admin-table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Customer</th>
                <th>Aircraft</th>
                <th>Priority</th>
                <th>Status</th>
                <th>ETA</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-ink-800 py-6">No dispatches found.</td></tr>
            <?php else: foreach ($rows as $d): ?>
                <tr>
                    <td class="font-semibold text-ink-900"><?= vp_safe_html($d['reference']) ?></td>
                    <td><?= vp_safe_html(trim(($d['firstName'] ?? '') . ' ' . ($d['lastName'] ?? '')) ?: ($d['customerCompany'] ?? ($d['customerEmail'] ?? '—'))) ?></td>
                    <td><?= vp_safe_html($d['aircraft'] ?: '—') ?></td>
                    <td><?= $d['priority'] === 'AOG' ? '<span class="text-red-700 font-semibold">AOG</span>' : 'Standard' ?></td>
                    <td><?= $status_badge($d['status']) ?></td>
                    <td class="text-ink-800"><?= $d['eta'] ? vp_human_date($d['eta'], 'M j, H:i') : '—' ?></td>
                    <td class="text-right whitespace-nowrap">
                        <a class="text-brand-600 font-semibold hover:underline" href="<?= base_url('admin/aog/view/' . $d['id']) ?>">View</a>
                        <a class="text-brand-600 font-semibold hover:underline ml-3" href="<?= base_url('admin/aog/edit/' . $d['id']) ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if ($total_pages > 1): ?>
<nav class="mt-4 flex gap-1">
    <?= vp_pagination_links($total_pages, $page, $base_url) ?>
</nav>
<?php endif; ?>
