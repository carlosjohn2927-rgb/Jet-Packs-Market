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
                <div class="ml-auto flex gap-2">
                    <a class="vp-btn vp-btn-primary vp-btn-sm" href="<?= base_url('rfq?reorder=' . $quote['id']) ?>"><i class="ri-repeat-line"></i> Re-order</a>
                    <?php if ($invoice): ?>
                        <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('account/invoices/' . $invoice['id'] . '/download') ?>"><i class="ri-download-line"></i> Invoice</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="vp-card vp-card-pad">
                <h2 class="font-bold text-lg mb-3">Requested parts</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-ink-800">
                            <tr>
                                <th class="text-left px-4 py-2 font-semibold">Part</th>
                                <th class="text-center px-4 py-2 font-semibold">Qty</th>
                                <th class="text-left px-4 py-2 font-semibold">Specifications</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td class="px-4 py-2 font-semibold text-ink-900"><?= vp_safe_html($it['productName']) ?></td>
                                    <td class="px-4 py-2 text-center"><?= (int) $it['quantity'] ?></td>
                                    <td class="px-4 py-2 text-ink-800"><?= vp_safe_html($it['specifications'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="3" class="px-4 py-3 text-ink-800">No line items recorded.</td></tr>
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
