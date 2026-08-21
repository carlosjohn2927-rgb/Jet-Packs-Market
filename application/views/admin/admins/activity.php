<?php
/** @var array $row */
/** @var array $rows */
?>
<div class="flex flex-wrap items-center gap-3 mb-5">
    <div>
        <h2 class="font-bold text-lg text-ink-900"><?= vp_safe_html(trim($row['firstName'] . ' ' . $row['lastName'])) ?></h2>
        <p class="text-sm text-ink-800/70"><?= vp_safe_html($row['email']) ?> · <?= vp_role_label($row['role']) ?> · <?= (int) $total ?> recorded events</p>
    </div>
    <a class="vp-btn vp-btn-secondary ml-auto" href="<?= base_url('admin/admins') ?>">Back to administrators</a>
</div>

<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead><tr><th>When</th><th>Action</th><th>Resource</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $a): ?>
            <tr>
                <td class="text-xs whitespace-nowrap" title="<?= vp_safe_html($a['createdAt']) ?>"><?= vp_time_ago($a['createdAt']) ?></td>
                <td><span class="vp-pill bg-gray-100 text-gray-700"><?= vp_safe_html($a['action']) ?></span></td>
                <td class="text-xs"><?= vp_safe_html($a['resource']) ?><?= $a['resourceId'] ? ' <span class="text-ink-800/40">#' . vp_safe_html(substr($a['resourceId'], 0, 8)) . '</span>' : '' ?></td>
                <td class="text-xs text-ink-800/70 max-w-md truncate"><?= vp_safe_html(vp_truncate((string) $a['details'], 140)) ?></td>
                <td class="text-xs text-ink-800/50"><?= vp_safe_html($a['ipAddress'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="text-center text-sm text-ink-800/60 py-8">No activity recorded for this account.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-4 flex justify-center"><?= vp_pagination_links($total_pages, $page, $base_url) ?></div>
