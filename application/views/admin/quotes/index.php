<?php
/** @var array $rows */
/** @var array $staff */
$status_options = ['' => 'All statuses', QUOTE_NEW => 'New', QUOTE_REVIEWING => 'Reviewing', QUOTE_QUOTED => 'Quoted', QUOTE_APPROVED => 'Approved', QUOTE_REJECTED => 'Rejected', QUOTE_COMPLETED => 'Completed'];
$staff_by_id = [];
foreach ($staff as $s) $staff_by_id[$s['id']] = $s;
?>
<div class="flex items-center justify-between mb-4">
    <form method="get" class="flex flex-wrap items-center gap-2 flex-1 max-w-3xl">
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search by quote #, company, contact…">
        <select class="vp-select w-auto" name="status">
            <?php foreach ($status_options as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($status === $k ? 'selected' : '') ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <select class="vp-select w-auto" name="assignedTo">
            <option value="">All assignees</option>
            <?php foreach ($staff as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($assignee === $s['id'] ? 'selected' : '') ?>><?= vp_safe_html(trim($s['firstName'] . ' ' . $s['lastName'])) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="vp-btn vp-btn-secondary" type="submit">Filter</button>
    </form>
    <div class="flex gap-2">
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/quotes/export/csv') ?>"><i class="ri-download-line"></i> CSV</a>
    </div>
</div>

<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead>
            <tr>
                <th>Quote #</th>
                <th>Company</th>
                <th>Contact</th>
                <th>Country</th>
                <th>Status</th>
                <th>Assigned</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-gray-500">No quotes match your filters.</td></tr>
        <?php else: foreach ($rows as $q):
            $st = vp_quote_status_label($q['status']);
            $assign = $q['assignedTo'] && isset($staff_by_id[$q['assignedTo']]) ? $staff_by_id[$q['assignedTo']] : null; ?>
            <tr>
                <td class="font-mono text-xs"><a class="text-brand-600 hover:underline font-semibold" href="<?= base_url('admin/quotes/' . $q['id']) ?>"><?= vp_safe_html($q['quoteNumber']) ?></a></td>
                <td><?= vp_safe_html($q['companyName']) ?></td>
                <td>
                    <div><?= vp_safe_html($q['contactPerson']) ?></div>
                    <div class="text-xs text-gray-500"><?= vp_safe_html($q['email']) ?></div>
                </td>
                <td class="text-xs text-gray-600"><?= vp_safe_html($q['country']) ?></td>
                <td><span class="vp-pill <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                <td class="text-xs"><?= $assign ? vp_safe_html(trim($assign['firstName'] . ' ' . $assign['lastName'])) : '<span class="text-gray-400">—</span>' ?></td>
                <td class="text-xs text-gray-500"><?= vp_time_ago($q['createdAt']) ?></td>
                <td class="text-right">
                    <a class="text-brand-600 hover:underline text-xs" href="<?= base_url('admin/quotes/' . $q['id']) ?>">View</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4 flex justify-center"><?= vp_pagination_links($total_pages, $page, $base_url) ?></div>
