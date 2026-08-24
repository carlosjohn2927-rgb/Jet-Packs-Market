<?php
/** @var array $dispatch */
/** @var array|null $quote */
?>

<?php
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
$priority_badge = $dispatch['priority'] === 'AOG'
    ? '<span class="px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">AOG</span>'
    : '<span class="px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-700">Standard</span>';
?>

<section class="container mx-auto px-4 py-10">
    <a class="text-sm font-semibold text-brand-600 hover:underline" href="<?= base_url('account/dispatches') ?>"><i class="ri-arrow-left-line"></i> Back to dispatches</a>
    <h1 class="text-3xl font-extrabold text-ink-900 mt-2">Dispatch <?= vp_safe_html($dispatch['reference']) ?></h1>

    <div class="grid lg:grid-cols-4 gap-6 mt-6">
        <?= $this->load->view('account/_nav', get_defined_vars(), TRUE) ?>

        <div class="lg:col-span-3 space-y-6">
            <div class="vp-card vp-card-pad flex flex-wrap items-center gap-3">
                <?= $priority_badge ?>
                <?= $status_badge($dispatch['status']) ?>
                <?php if ($dispatch['eta']): ?><span class="text-ink-800 text-sm">ETA <?= vp_human_date($dispatch['eta'], 'M j, Y g:i a') ?></span><?php endif; ?>
            </div>

            <div class="vp-card vp-card-pad grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="font-semibold text-ink-900 mb-1">Part</p>
                    <p class="text-ink-800"><?= vp_safe_html($dispatch['partDescription'] ?: '—') ?></p>
                    <p class="text-ink-800 mt-2">Quantity: <strong><?= (int) $dispatch['quantity'] ?></strong></p>
                </div>
                <div>
                    <p class="font-semibold text-ink-900 mb-1">Aircraft</p>
                    <p class="text-ink-800"><?= vp_safe_html($dispatch['aircraft'] ?: '—') ?></p>
                    <?php if ($quote): ?><p class="text-ink-800 mt-2">Related quote: <strong><?= vp_safe_html($quote['quoteNumber']) ?></strong></p><?php endif; ?>
                </div>
                <div>
                    <p class="font-semibold text-ink-900 mb-1">Carrier</p>
                    <p class="text-ink-800"><?= vp_safe_html($dispatch['carrier'] ?: '—') ?></p>
                </div>
                <div>
                    <p class="font-semibold text-ink-900 mb-1">Tracking number</p>
                    <p class="text-ink-800 break-all"><?= vp_safe_html($dispatch['trackingNumber'] ?: '—') ?></p>
                </div>
                <div>
                    <p class="font-semibold text-ink-900 mb-1">Pickup / origin</p>
                    <p class="text-ink-800"><?= vp_safe_html($dispatch['pickupLocation'] ?: '—') ?></p>
                </div>
                <div>
                    <p class="font-semibold text-ink-800 mb-1">Delivered</p>
                    <p class="text-ink-800"><?= $dispatch['deliveredAt'] ? vp_human_date($dispatch['deliveredAt'], 'M j, Y g:i a') : '—' ?></p>
                </div>
            </div>

            <?php if ($dispatch['notes']): ?>
            <div class="vp-card vp-card-pad">
                <h2 class="font-bold text-lg mb-2">Notes</h2>
                <p class="text-ink-800 whitespace-pre-line"><?= vp_safe_html($dispatch['notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
