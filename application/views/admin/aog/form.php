<?php
/** @var array|null $row */
/** @var array $customers */
/** @var array $quotes */
/** @var string $form_url */
$is_create = empty($row);
$val = function ($k, $default = '') use ($row) { return vp_safe_html($row[$k] ?? $default); };
$dtval = function ($k) use ($row) {
    if (empty($row[$k])) return '';
    return date('Y-m-d\TH:i', strtotime($row[$k]));
};
$statuses = ['REQUESTED' => 'Requested', 'CONFIRMED' => 'Confirmed', 'IN_TRANSIT' => 'In transit', 'DELIVERED' => 'Delivered', 'CANCELLED' => 'Cancelled'];
?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold text-ink-900"><?= $is_create ? 'New AOG dispatch' : 'Edit ' . $val('reference') ?></h1>
        <p class="text-sm text-ink-800">Record an emergency / priority part dispatch for a customer.</p>
    </div>
    <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/aog') ?>">Cancel</a>
</div>

<form method="post" action="<?= $form_url ?>" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-3xl space-y-5">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if (!$is_create): ?><input type="hidden" name="id" value="<?= $row['id'] ?>"><?php endif; ?>

    <div class="grid sm:grid-cols-2 gap-4">
        <div class="vp-form-row">
            <label>Customer *</label>
            <select class="vp-select" name="userId" required>
                <option value="">— Select customer —</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($row['userId'] ?? '') === $c['id'] ? 'selected' : '' ?>>
                        <?= vp_safe_html(trim(($c['firstName'] ?? '') . ' ' . ($c['lastName'] ?? '')) ?: $c['email']) ?>
                        <?= $c['company'] ? ' (' . vp_safe_html($c['company']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="vp-form-row">
            <label>Related quote (optional)</label>
            <select class="vp-select" name="quoteId">
                <option value="">— None —</option>
                <?php foreach ($quotes as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= ($row['quoteId'] ?? '') === $q['id'] ? 'selected' : '' ?>><?= vp_safe_html($q['quoteNumber']) ?> — <?= vp_safe_html($q['companyName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="vp-form-row">
            <label>Aircraft</label>
            <input class="vp-input" name="aircraft" value="<?= $val('aircraft') ?>" placeholder="e.g. Gulfstream G650">
        </div>
        <div class="vp-form-row">
            <label>Quantity *</label>
            <input class="vp-input" type="number" min="1" name="quantity" value="<?= $val('quantity', '1') ?>" required>
        </div>
        <div class="vp-form-row">
            <label>Priority</label>
            <select class="vp-select" name="priority">
                <option value="AOG" <?= $val('priority', 'AOG') === 'AOG' ? 'selected' : '' ?>>AOG (emergency)</option>
                <option value="STANDARD" <?= $val('priority') === 'STANDARD' ? 'selected' : '' ?>>Standard</option>
            </select>
        </div>
        <div class="vp-form-row">
            <label>Status</label>
            <select class="vp-select" name="status">
                <?php foreach ($statuses as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $val('status', 'REQUESTED') === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="vp-form-row">
            <label>Carrier</label>
            <input class="vp-input" name="carrier" value="<?= $val('carrier') ?>" placeholder="e.g. UPS Aviation">
        </div>
        <div class="vp-form-row">
            <label>Tracking number</label>
            <input class="vp-input" name="trackingNumber" value="<?= $val('trackingNumber') ?>">
        </div>
        <div class="vp-form-row">
            <label>ETA</label>
            <input class="vp-input" type="datetime-local" name="eta" value="<?= $dtval('eta') ?>">
        </div>
        <div class="vp-form-row">
            <label>Delivered at</label>
            <input class="vp-input" type="datetime-local" name="deliveredAt" value="<?= $dtval('deliveredAt') ?>">
        </div>
        <div class="vp-form-row sm:col-span-2">
            <label>Pickup / origin</label>
            <input class="vp-input" name="pickupLocation" value="<?= $val('pickupLocation') ?>">
        </div>
        <div class="vp-form-row sm:col-span-2">
            <label>Part description *</label>
            <textarea class="vp-textarea" name="partDescription" rows="3" required><?= $val('partDescription') ?></textarea>
        </div>
        <div class="vp-form-row sm:col-span-2">
            <label>Notes</label>
            <textarea class="vp-textarea" name="notes" rows="3"><?= $val('notes') ?></textarea>
        </div>
    </div>

    <div class="flex items-center gap-2 pt-2">
        <button type="submit" class="vp-btn vp-btn-primary"><i class="ri-save-line"></i> <?= $is_create ? 'Create dispatch' : 'Save changes' ?></button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/aog') ?>">Cancel</a>
    </div>
</form>
