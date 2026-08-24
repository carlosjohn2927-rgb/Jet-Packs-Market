<?php
/** @var array $payment */
/** @var array $quote */
/** @var string $state */
/** @var string $message */
$icons = [
    'paid' => ['ri-checkbox-circle-line', 'text-emerald-600', 'bg-emerald-50 border-emerald-200'],
    'pending' => ['ri-time-line', 'text-amber-600', 'bg-amber-50 border-amber-200'],
    'expired' => ['ri-timer-off-line', 'text-slate-600', 'bg-slate-50 border-slate-200'],
    'canceled' => ['ri-close-circle-line', 'text-slate-600', 'bg-slate-50 border-slate-200'],
    'failed' => ['ri-error-warning-line', 'text-red-600', 'bg-red-50 border-red-200'],
];
$icon = $icons[$state] ?? $icons['pending'];
?>
<section class="bg-slate-50 py-12 sm:py-16">
    <div class="container mx-auto max-w-xl px-4">
        <div class="rounded-2xl border bg-white px-6 py-9 text-center shadow-sm sm:px-10">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border <?= $icon[2] ?>">
                <i class="<?= $icon[0] ?> text-3xl <?= $icon[1] ?>" aria-hidden="true"></i>
            </div>
            <h1 class="mt-5 text-2xl font-extrabold text-ink-900">
                <?php if ($state === 'paid'): ?>Payment received
                <?php elseif ($state === 'pending'): ?>Payment confirmation in progress
                <?php else: ?>Payment status<?php endif; ?>
            </h1>
            <p class="mt-3 text-slate-600"><?= vp_safe_html($message) ?></p>

            <div class="mt-7 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left text-sm">
                <div class="flex justify-between gap-4"><span class="text-slate-500">Quote</span><strong class="font-mono text-ink-900"><?= vp_safe_html($quote['quoteNumber']) ?></strong></div>
                <div class="mt-2 flex justify-between gap-4"><span class="text-slate-500">Amount</span><strong class="text-ink-900"><?= vp_safe_html(vp_payment_format_minor($payment['amountMinor'], $payment['currency'])) ?></strong></div>
            </div>

            <?php if ($state === 'pending'): ?>
                <p class="mt-5 text-xs text-slate-500">If you have completed checkout, no action is needed. We will update the quote as soon as Stripe confirms the payment.</p>
            <?php elseif ($state !== 'paid'): ?>
                <a class="vp-btn vp-btn-secondary mt-6" href="mailto:<?= vp_safe_html(vp_site('email', $this->config->item('contact_email'))) ?>"><i class="ri-mail-line"></i> Contact sales</a>
            <?php endif; ?>
        </div>
    </div>
</section>
