<?php
/** @var array $rows */
/** @var array $totals */
$byId = []; foreach ($totals as $total) $byId[$total['id']] = $total;
?>
<div class="flex flex-wrap justify-between items-center gap-3 mb-4">
    <p class="text-sm text-gray-600">Manage stock locations, AOG dispatch hubs and their inventory totals.</p>
    <a class="vp-btn vp-btn-primary" href="<?= base_url('admin/warehouses/create') ?>"><i class="ri-add-line"></i> New warehouse</a>
</div>
<div class="overflow-x-auto"><table class="vp-admin-table"><thead><tr><th>Warehouse</th><th>Location</th><th>AOG</th><th>Lots</th><th>Available</th><th>Expiring ≤30d</th><th>Status</th><th></th></tr></thead><tbody>
<?php if (empty($rows)): ?><tr><td colspan="8" class="text-center text-gray-500">No warehouses yet.</td></tr><?php else: foreach ($rows as $row): $t = $byId[$row['id']] ?? []; ?>
<tr><td><strong><?= vp_safe_html($row['name']) ?></strong><div class="text-xs font-mono text-gray-500"><?= vp_safe_html($row['code']) ?></div></td><td class="text-xs"><?= vp_safe_html(trim(($row['city'] ?? '') . (($row['country'] ?? '') ? ', ' . $row['country'] : ''))) ?: '—' ?></td><td><?= !empty($row['isAogHub']) ? '<span class="vp-pill bg-amber-100 text-amber-800">AOG hub</span>' : '—' ?></td><td><?= (int) ($t['lotCount'] ?? 0) ?></td><td><strong><?= (int) ($t['available'] ?? 0) ?></strong></td><td><?= (int) ($t['expiringCount'] ?? 0) ?></td><td><?= !empty($row['isActive']) ? '<span class="vp-pill bg-emerald-100 text-emerald-800">Active</span>' : '<span class="vp-pill bg-gray-200 text-gray-700">Inactive</span>' ?></td><td class="text-right flex justify-end gap-2"><a class="text-brand-600 hover:underline text-xs" href="<?= base_url('admin/warehouses/edit/' . $row['id']) ?>">Edit</a><form action="<?= base_url('admin/warehouses/delete/' . $row['id']) ?>" method="post" data-confirm="Delete this warehouse? Empty warehouses only."><input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>"><button class="text-red-600 hover:underline text-xs" type="submit">Delete</button></form></td></tr>
<?php endforeach; endif; ?></tbody></table></div>
<div class="mt-5"><a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/inventory') ?>"><i class="ri-stack-line"></i> Open inventory board</a></div>
