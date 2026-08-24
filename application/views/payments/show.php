<?php
/** @var array $payment */
/** @var array $quote */
/** @var array $stripe */
$status = vp_payment_status_label($payment['status']);
$payable = in_array($payment['status'], [PAYMENT_PENDING, PAYMENT_OPEN], true)
    && $quote['status'] === QUOTE_APPROVED
    && !empty($stripe['configured']);
?>
<section class="bg-slate-50 py-12 sm:py-16">
    <div class="container mx-auto max-w-2xl px-4">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="bg-ink-900 px-6 py-6 text-white sm:px-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-200">JetPacks Market</p>
                        <h1 class="mt-2 text-2xl font-extrabold">Secure card payment</h1>
                        <p class="mt-2 text-sm text-white/75">You will enter your card details on Stripe's secure checkout page.</p>
                    </div>
                    <i class="ri-secure-payment-line text-3xl text-brand-200" aria-hidden="true"></i>
                </div>
            </div>

            <div class="space-y-6 px-6 py-7 sm:px-8">
                <?php if (!empty($checkout_canceled) && $payable): ?>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Checkout was canceled. No charge was made; you can return to secure checkout whenever you are ready.
                    </div>
                <?php endif; ?>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quote</p>
                            <p class="mt-1 font-mono text-sm font-bold text-ink-900"><?= vp_safe_html($quote['quoteNumber']) ?></p>
                            <p class="mt-1 text-sm text-slate-600"><?= vp_safe_html($quote['companyName']) ?></p>
                        </div>
                        <span class="vp-pill <?= $status[1] ?>"><?= vp_safe_html($status[0]) ?></span>
                    </div>
                    <div class="mt-5 border-t border-slate-200 pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount due</p>
                        <p class="mt-1 text-3xl font-extrabold text-ink-900"><?= vp_safe_html(vp_payment_format_minor($payment['amountMinor'], $payment['currency'])) ?></p>
                        <p class="mt-1 text-xs text-slate-500">One-time card payment · <?= vp_safe_html($payment['currency']) ?></p>
                    </div>
                </div>

                <?php if ($payable): ?>
                    <form method="post" action="<?= base_url('pay/' . rawurlencode($payment['customerToken']) . '/checkout') ?>" class="space-y-4">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <button class="vp-btn vp-btn-primary w-full justify-center py-3 text-base" type="submit">
                            <i class="ri-lock-2-line"></i> Continue to secure card checkout
                        </button>
                        <p class="text-center text-xs text-slate-500">
                            Payments are processed by Stripe. JetPacks Market does not see or store your card number.
                            <?php if (!empty($payment['expiresAt'])): ?>
                                This link expires <?= vp_safe_html(vp_human_date($payment['expiresAt'], 'M j, Y g:i A')) ?>.
                            <?php endif; ?>
                        </p>
                    </form>
                <?php elseif ($payment['status'] === PAYMENT_PAID): ?>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900">
                        <strong>Payment received.</strong> Thank you — our team has been notified and will follow up about fulfillment.
                    </div>
                <?php elseif ($payment['status'] === PAYMENT_EXPIRED): ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                        This payment link has expired. Please contact our sales team for a new secure link.
                    </div>
                <?php elseif ($payment['status'] === PAYMENT_CANCELED): ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                        This payment request has been canceled. Please contact our sales team if you need assistance.
                    </div>
                <?php elseif (empty($stripe['configured'])): ?>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                        Secure card checkout is temporarily unavailable. Please contact our sales team to arrange payment.
                    </div>
                <?php else: ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                        This quote is not currently available for online card payment. Please contact our sales team.
                    </div>
                <?php endif; ?>

                <div class="border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
                    Questions about this quote? <a class="font-semibold text-brand-600 hover:underline" href="mailto:<?= vp_safe_html(vp_site('email', $this->config->item('contact_email'))) ?>">Contact our sales team</a>.
                </div>
            </div>
        </div>
    </div>
</section>
