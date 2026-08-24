<?php /** @var array $invoices */ ?>

<section class="container mx-auto px-4 py-10">
    <h1 class="text-3xl font-extrabold text-ink-900">My invoices</h1>
    <p class="text-ink-800 mt-1">Download PDF invoices for every paid card order.</p>

    <div class="grid lg:grid-cols-4 gap-6 mt-8">
        <?= $this->load->view('account/_nav', get_defined_vars(), TRUE) ?>

        <div class="lg:col-span-3">
            <div class="vp-card overflow-hidden">
                <?php if (empty($invoices)): ?>
                    <div class="p-8 text-center text-ink-800">
                        <p>No invoices yet. Invoices are created automatically when a quote payment is completed.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-ink-800">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold">Invoice #</th>
                                    <th class="text-left px-4 py-3 font-semibold">Quote</th>
                                    <th class="text-left px-4 py-3 font-semibold">Issued</th>
                                    <th class="text-right px-4 py-3 font-semibold">Amount</th>
                                    <th class="text-right px-4 py-3 font-semibold">Download</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            <?php foreach ($invoices as $inv): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-ink-900"><?= vp_safe_html($inv['invoiceNumber']) ?></td>
                                    <td class="px-4 py-3 text-ink-800"><?= vp_safe_html($inv['quoteNumber'] ?? '—') ?></td>
                                    <td class="px-4 py-3 text-ink-800"><?= vp_human_date($inv['issuedAt']) ?></td>
                                    <td class="px-4 py-3 text-right font-semibold text-ink-900"><?= vp_money($inv['amount'], $inv['currency']) ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('account/invoices/' . $inv['id'] . '/download') ?>"><i class="ri-download-line"></i> PDF</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
