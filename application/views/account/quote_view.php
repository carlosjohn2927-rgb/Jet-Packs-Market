<?php
/** @var array $quote */
/** @var array $items */
/** @var array|null $invoice */
/** @var array $status */
?>

<section class="container mx-auto px-4 py-10">
    <a class="text-sm font-semibold text-brand-600 hover:underline" href="<?= base_url('account/quotes') ?>"><i class="ri-arrow-left-line"></i> Back to orders</a>
    <h1 class="text-3xl font-extrabold text-ink-900 mt-2">Quote <?= vp_safe_html($quote['quoteNumber']) ?></h1>

    <div class="grid lg:grid-cols-4 gap-6 mt-6">
        <?= $this->load->view('account/_nav', get_defined_vars(), TRUE) ?>

        <div class="lg:col-span-3 space-y-6">
            <div class="vp-card vp-card-pad flex flex-wrap items-center gap-4">
                <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $status['class'] ?>"><?= $status['label'] ?></span>
                <span class="text-ink-800 text-sm">Placed <?= vp_human_date($quote['createdAt'], 'M j, Y g:i a') ?></span>
                <span class="text-ink-800 text-sm font-mono"><?= vp_safe_html($quote['quoteNumber']) ?></span>
                <div class="ml-auto flex flex-wrap gap-2">
                    <?php if (!empty($pdf_url)): ?>
                        <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('account/quotes/' . $quote['id'] . '/download-pdf') ?>"><i class="ri-file-pdf-line"></i> Quote PDF</a>
                    <?php endif; ?>
                    <a class="vp-btn vp-btn-primary vp-btn-sm" href="<?= base_url('rfq?reorder=' . $quote['id']) ?>"><i class="ri-repeat-line"></i> Re-order</a>
                    <?php if ($invoice): ?>
                        <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('account/invoices/' . $invoice['id'] . '/download') ?>"><i class="ri-download-line"></i> Invoice</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($quote['status'] === QUOTE_QUOTED): ?>
            <div class="vp-card vp-card-pad">
                <h2 class="font-bold text-lg mb-1">Your quotation is ready</h2>
                <p class="text-sm text-ink-800 mb-4">Review the priced parts below. Approve to confirm the order, or let us know if anything needs changing.</p>
                <div class="flex flex-wrap gap-3">
                    <form method="post" action="<?= base_url('account/quotes/' . $quote['id'] . '/approve') ?>" data-confirm="Approve quotation <?= vp_safe_html($quote['quoteNumber']) ?> and confirm this order?">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <input type="hidden" name="version" value="<?= (int) $quote['version'] ?>">
                        <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-check-line"></i> Approve &amp; confirm order</button>
                    </form>
                    <form method="post" action="<?= base_url('account/quotes/' . $quote['id'] . '/reject') ?>">
                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                        <input type="hidden" name="version" value="<?= (int) $quote['version'] ?>">
                        <input class="vp-input mb-2" name="note" placeholder="Reason (optional) — e.g. needs revision">
                        <button class="vp-btn vp-btn-danger" type="submit"><i class="ri-close-line"></i> Decline</button>
                    </form>
                </div>
            </div>
            <?php elseif ($quote['status'] === QUOTE_APPROVED): ?>
            <div class="vp-card vp-card-pad bg-emerald-50 border-emerald-200">
                <p class="text-sm text-emerald-800 font-semibold"><i class="ri-checkbox-circle-line"></i> Quotation approved — thank you. Our sales desk will confirm availability and lead time shortly.</p>
            </div>
            <?php elseif ($quote['status'] === QUOTE_REJECTED): ?>
            <div class="vp-card vp-card-pad bg-red-50 border-red-200">
                <p class="text-sm text-red-800 font-semibold"><i class="ri-close-circle-line"></i> Quotation declined. A sales representative will follow up with alternative options.</p>
            </div>
            <?php endif; ?>

            <div class="vp-card vp-card-pad">
                <h2 class="font-bold text-lg mb-3">Requested parts</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-ink-800">
                            <tr>
                                <th class="text-left px-4 py-2 font-semibold">Part number</th>
                                <th class="text-left px-4 py-2 font-semibold">Part</th>
                                <th class="text-center px-4 py-2 font-semibold">Qty</th>
                                <th class="text-left px-4 py-2 font-semibold">Condition / spec</th>
                                <th class="text-right px-4 py-2 font-semibold">Unit price</th>
                                <th class="text-right px-4 py-2 font-semibold">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td class="px-4 py-2 font-mono text-xs whitespace-nowrap"><?= vp_safe_html($it['partNumber'] ?? '') ?></td>
                                    <td class="px-4 py-2 font-semibold text-ink-900"><?= vp_safe_html($it['productName']) ?></td>
                                    <td class="px-4 py-2 text-center"><?= (int) $it['quantity'] ?></td>
                                    <td class="px-4 py-2 text-ink-800"><?= vp_safe_html(trim(($it['condition'] ?? '') . ' ' . ($it['specifications'] ?? ''))) ?></td>
                                    <td class="px-4 py-2 text-right"><?= ($it['unitPrice'] !== null && $it['unitPrice'] !== '') ? vp_safe_html(vp_money($it['unitPrice'], $quote['currency'] ?? 'USD')) : '—' ?></td>
                                    <td class="px-4 py-2 text-right font-semibold"><?= ($it['total'] !== null && $it['total'] !== '') ? vp_safe_html(vp_money($it['total'], $quote['currency'] ?? 'USD')) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="6" class="px-4 py-3 text-ink-800">No line items recorded.</td></tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-2 text-right font-bold text-ink-900">Total (<?= vp_safe_html($quote['currency'] ?? 'USD') ?>)</td>
                                    <td class="px-4 py-2 text-right font-bold text-ink-900"><?= ($quote['totalAmount'] !== null && $quote['totalAmount'] !== '') ? vp_safe_html(vp_money($quote['totalAmount'], $quote['currency'] ?? 'USD')) : 'On quote' ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="vp-card vp-card-pad grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="font-semibold text-ink-900 mb-1">Bill to</p>
                    <p class="text-ink-800"><?= vp_safe_html($quote['companyName']) ?></p>
                    <p class="text-ink-800"><?= vp_safe_html($quote['contactPerson']) ?></p>
                    <p class="text-ink-800"><?= vp_safe_html($quote['email']) ?></p>
                    <?php if ($quote['phone']): ?><p class="text-ink-800"><?= vp_safe_html($quote['phone']) ?></p><?php endif; ?>
                    <?php if ($quote['country']): ?><p class="text-ink-800"><?= vp_safe_html($quote['country']) ?></p><?php endif; ?>
                </div>
                <div>
                    <p class="font-semibold text-ink-900 mb-1">Notes</p>
                    <p class="text-ink-800"><?= vp_safe_html($quote['notes'] ?: '—') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
