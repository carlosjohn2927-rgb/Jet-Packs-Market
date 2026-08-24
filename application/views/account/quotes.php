<?php /** @var array $quotes */ ?>

<section class="container mx-auto px-4 py-10">
    <h1 class="text-3xl font-extrabold text-ink-900">My orders &amp; quotes</h1>
    <p class="text-ink-800 mt-1">Every request you have submitted. Re-order a previous quote in one click.</p>

    <div class="grid lg:grid-cols-4 gap-6 mt-8">
        <?= $this->load->view('account/_nav', get_defined_vars(), TRUE) ?>

        <div class="lg:col-span-3">
            <div class="vp-card overflow-hidden">
                <?php if (empty($quotes)): ?>
                    <div class="p-8 text-center text-ink-800">
                        <p class="mb-3">You have not placed any quotes yet.</p>
                        <a class="vp-btn vp-btn-primary" href="<?= base_url('rfq') ?>"><i class="ri-quote-text"></i> Request a quote</a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-ink-800">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold">Quote #</th>
                                    <th class="text-left px-4 py-3 font-semibold">Company</th>
                                    <th class="text-left px-4 py-3 font-semibold">Date</th>
                                    <th class="text-left px-4 py-3 font-semibold">Status</th>
                                    <th class="text-right px-4 py-3 font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($quotes as $q): ?>
                                    <?php $s = vp_quote_status_label($q['status']); ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-semibold text-ink-900"><?= vp_safe_html($q['quoteNumber']) ?></td>
                                        <td class="px-4 py-3"><?= vp_safe_html($q['companyName']) ?></td>
                                        <td class="px-4 py-3 text-ink-800"><?= vp_human_date($q['createdAt']) ?></td>
                                        <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-semibold <?= $s['class'] ?>"><?= $s['label'] ?></span></td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a class="text-brand-600 font-semibold hover:underline" href="<?= base_url('account/quotes/' . $q['id']) ?>">View</a>
                                            <a class="text-brand-600 font-semibold hover:underline ml-3" href="<?= base_url('rfq?reorder=' . $q['id']) ?>"><i class="ri-repeat-line"></i> Re-order</a>
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
