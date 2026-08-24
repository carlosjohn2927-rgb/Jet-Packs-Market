<?php
/** @var array $recent_quotes */
/** @var array $invoices */
?>

<?php
$card = function ($label, $value, $icon, $href, $sub) {
    return '<a class="vp-card vp-card-pad flex items-start gap-4 hover:shadow-md transition" href="' . base_url($href) . '">
        <span class="text-2xl text-brand-600"><i class="' . $icon . '"></i></span>
        <span>
            <span class="block text-3xl font-extrabold text-ink-900">' . $value . '</span>
            <span class="block text-sm font-semibold text-ink-900">' . $label . '</span>
            <span class="block text-xs text-ink-800">' . $sub . '</span>
        </span>
    </a>';
};
?>

<section class="container mx-auto px-4 py-10">
    <h1 class="text-3xl font-extrabold text-ink-900">My account</h1>
    <p class="text-ink-800 mt-1">Your orders, invoices and AOG dispatches in one place.</p>

    <div class="grid lg:grid-cols-4 gap-6 mt-8">
        <?= $this->load->view('account/_nav', get_defined_vars(), TRUE) ?>

        <div class="lg:col-span-3 space-y-6">
            <div class="grid sm:grid-cols-3 gap-4">
                <?= $card('Orders & quotes', $quotes_total, 'ri-file-list-3-line', 'account/quotes', 'Request history') ?>
                <?= $card('Invoices', $invoices_count, 'ri-receipt-line', 'account/invoices', 'Paid card orders') ?>
                <?= $card('Active AOG', $active_aog, 'ri-truck-line', 'account/dispatches', 'In-flight dispatches') ?>
            </div>

            <div class="vp-card vp-card-pad">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-lg">Recent orders</h2>
                    <a class="text-sm font-semibold text-brand-600 hover:underline" href="<?= base_url('account/quotes') ?>">View all</a>
                </div>
                <?php if (empty($recent_quotes)): ?>
                    <p class="text-ink-800 text-sm">You have not placed any quotes yet.
                        <a class="text-brand-600 font-semibold" href="<?= base_url('rfq') ?>">Request a quote &rarr;</a></p>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($recent_quotes as $q): ?>
                            <a class="flex items-center justify-between py-3 hover:bg-gray-50 px-2 rounded" href="<?= base_url('account/quotes/' . $q['id']) ?>">
                                <span>
                                    <span class="font-semibold text-ink-900"><?= vp_safe_html($q['quoteNumber']) ?></span>
                                    <span class="block text-sm text-ink-800"><?= vp_safe_html($q['companyName']) ?></span>
                                </span>
                                <span class="text-sm">
                                    <?php $s = vp_quote_status_label($q['status']); ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $s['class'] ?>"><?= $s['label'] ?></span>
                                    <span class="text-ink-800 ml-2"><?= vp_human_date($q['createdAt']) ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($invoices)): ?>
            <div class="vp-card vp-card-pad">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-lg">Latest invoice</h2>
                    <a class="text-sm font-semibold text-brand-600 hover:underline" href="<?= base_url('account/invoices') ?>">All invoices</a>
                </div>
                <?php $inv = $invoices[0]; ?>
                <div class="flex items-center justify-between">
                    <span>
                        <span class="font-semibold text-ink-900"><?= vp_safe_html($inv['invoiceNumber']) ?></span>
                        <span class="block text-sm text-ink-800"><?= vp_money($inv['amount'], $inv['currency']) ?></span>
                    </span>
                    <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('account/invoices/' . $inv['id'] . '/download') ?>"><i class="ri-download-line"></i> Download</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
