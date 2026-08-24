<?php
/** @var array $row */
/** @var array|null $customer */
/** @var array|null $quote */

$status_badge = function ($status) {
    $map = [
        'REQUESTED'  => 'bg-amber-100 text-amber-800',
        'CONFIRMED'  => 'bg-sky-100 text-sky-800',
        'IN_TRANSIT' => 'bg-indigo-100 text-indigo-800',
        'DELIVERED'  => 'bg-emerald-100 text-emerald-800',
        'CANCELLED'  => 'bg-gray-200 text-gray-700',
    ];
    return '<span class="px-3 py-1 rounded-full text-sm font-semibold ' . ($map[$status] ?? 'bg-gray-100 text-gray-700') . '">' . $status . '</span>';
};
$statuses = ['REQUESTED' => 'Requested', 'CONFIRMED' => 'Confirmed', 'IN_TRANSIT' => 'In transit', 'DELIVERED' => 'Delivered', 'CANCELLED' => 'Cancelled'];
?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-ink-900"><?= vp_safe_html($row['reference']) ?></h1>
        <p class="text-sm text-ink-800"><?= $row['priority'] === 'AOG' ? 'AOG emergency dispatch' : 'Standard dispatch' ?></p>
    </div>
    <div class="flex gap-2">
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/aog/edit/' . $row['id']) ?>"><i class="ri-edit-line"></i> Edit</a>
        <a class="vp-btn vp-btn-danger" href="<?= base_url('admin/aog/delete/' . $row['id']) ?>" onclick="return confirm('Delete this dispatch?');"><i class="ri-delete-bin-line"></i> Delete</a>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
        <div class="flex items-center gap-3">
            <?= $status_badge($row['status']) ?>
            <?php if ($row['eta']): ?><span class="text-sm text-ink-800">ETA <?= vp_human_date($row['eta'], 'M j, Y g:i a') ?></span><?php endif; ?>
        </div>

        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="font-semibold text-ink-900">Part</dt><dd class="text-ink-800 whitespace-pre-line"><?= vp_safe_html($row['partDescription']) ?></dd></div>
            <div><dt class="font-semibold text-ink-900">Quantity</dt><dd class="text-ink-800"><?= (int) $row['quantity'] ?></dd></div>
            <div><dt class="font-semibold text-ink-900">Aircraft</dt><dd class="text-ink-800"><?= vp_safe_html($row['aircraft'] ?: '—') ?></dd></div>
            <div><dt class="font-semibold text-ink-900">Carrier</dt><dd class="text-ink-800"><?= vp_safe_html($row['carrier'] ?: '—') ?></dd></div>
            <div><dt class="font-semibold text-ink-900">Tracking</dt><dd class="text-ink-800 break-all"><?= vp_safe_html($row['trackingNumber'] ?: '—') ?></dd></div>
            <div><dt class="font-semibold text-ink-900">Pickup / origin</dt><dd class="text-ink-800"><?= vp_safe_html($row['pickupLocation'] ?: '—') ?></dd></div>
            <div><dt class="font-semibold text-ink-900">Delivered</dt><dd class="text-ink-800"><?= $row['deliveredAt'] ? vp_human_date($row['deliveredAt'], 'M j, Y g:i a') : '—' ?></dd></div>
            <div><dt class="font-semibold text-ink-900">Created</dt><dd class="text-ink-800"><?= vp_human_date($row['createdAt'], 'M j, Y g:i a') ?></dd></div>
        </dl>

        <?php if ($row['notes']): ?>
            <div><p class="font-semibold text-ink-900 mb-1">Notes</p><p class="text-ink-800 whitespace-pre-line"><?= vp_safe_html($row['notes']) ?></p></div>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="font-bold text-ink-900 mb-2">Update status</h2>
            <form method="post" action="<?= base_url('admin/aog/status/' . $row['id']) ?>">
                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                <select class="vp-select mb-3" name="status">
                    <?php foreach ($statuses as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $row['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="vp-btn vp-btn-primary w-full" type="submit">Update</button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-sm">
            <h2 class="font-bold text-ink-900 mb-2">Customer</h2>
            <?php if ($customer): ?>
                <p class="font-semibold text-ink-900"><?= vp_safe_html(trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? ''))) ?></p>
                <p class="text-ink-800"><?= vp_safe_html($customer['email']) ?></p>
                <?php if ($customer['company']): ?><p class="text-ink-800"><?= vp_safe_html($customer['company']) ?></p><?php endif; ?>
            <?php else: ?>
                <p class="text-ink-800">No customer linked.</p>
            <?php endif; ?>
            <?php if ($quote): ?>
                <p class="mt-3 font-semibold text-ink-900">Related quote</p>
                <p class="text-ink-800"><?= vp_safe_html($quote['quoteNumber']) ?> — <?= vp_safe_html($quote['companyName']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
