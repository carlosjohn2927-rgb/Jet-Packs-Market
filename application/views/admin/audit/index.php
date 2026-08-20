<?php /** @var array $rows */ /** @var array $users */ ?>
<form method="get" class="bg-white border rounded-2xl p-4 mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="vp-label">Administrator</label>
        <select class="vp-select w-auto" name="userId">
            <option value="">Everyone</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $user === $u['id'] ? 'selected' : '' ?>>
                    <?= vp_safe_html(trim($u['firstName'] . ' ' . $u['lastName']) ?: $u['email']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="vp-label">Action</label>
        <select class="vp-select w-auto" name="action">
            <option value="">All actions</option>
            <?php foreach ($actions as $a): ?>
                <option value="<?= vp_safe_html($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= vp_safe_html($a) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="vp-label">Resource</label>
        <select class="vp-select w-auto" name="resource">
            <option value="">All resources</option>
            <?php foreach ($resources as $r): ?>
                <option value="<?= vp_safe_html($r) ?>" <?= $resource === $r ? 'selected' : '' ?>><?= vp_safe_html($r) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="vp-label">Search</label>
        <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="details, id, IP…">
    </div>
    <button class="vp-btn vp-btn-secondary" type="submit"><i class="ri-filter-3-line"></i> Filter</button>
    <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/audit') ?>">Reset</a>
    <span class="ml-auto text-sm text-ink-800/60"><?= (int) $total ?> event(s)</span>
</form>

<div class="overflow-x-auto">
    <table class="vp-admin-table">
        <thead><tr><th>When</th><th>Administrator</th><th>Action</th><th>Resource</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td class="text-xs text-ink-800/60 whitespace-nowrap" title="<?= vp_safe_html($r['createdAt']) ?>"><?= vp_time_ago($r['createdAt']) ?></td>
                <td class="text-xs">
                    <?php if (!empty($r['userId'])): ?>
                        <span class="font-semibold"><?= vp_safe_html(trim(($r['firstName'] ?? '') . ' ' . ($r['lastName'] ?? '')) ?: $r['email']) ?></span>
                        <span class="block text-[11px] text-ink-800/50"><?= vp_role_label($r['role'] ?? '') ?></span>
                    <?php else: ?>
                        <span class="text-ink-800/40">system / anonymous</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="vp-pill <?= $r['action'] === 'ACCESS_DENIED' ? 'bg-red-100 text-red-800' : ($r['action'] === AUDIT_LOGIN ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700') ?>">
                        <?= vp_safe_html($r['action']) ?>
                    </span>
                </td>
                <td class="text-xs">
                    <?= vp_safe_html($r['resource']) ?>
                    <?php if (!empty($r['resourceId'])): ?><span class="text-ink-800/40 font-mono">#<?= vp_safe_html(substr($r['resourceId'], 0, 8)) ?></span><?php endif; ?>
                </td>
                <td class="text-xs text-ink-800/70 max-w-md truncate" title="<?= vp_safe_html($r['details'] ?? '') ?>"><?= vp_safe_html($r['details'] ?? '') ?></td>
                <td class="text-[11px] text-ink-800/50"><?= vp_safe_html($r['ipAddress'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-sm text-ink-800/60 py-8">No activity matches these filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-4 flex justify-center"><?= vp_pagination_links($total_pages, $page, $base_url) ?></div>
