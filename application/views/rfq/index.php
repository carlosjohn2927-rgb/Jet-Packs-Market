<?php
/** @var array|null $prefill */
?>
<section class="vp-writeup-band jpm-navy border-b">
    <div class="container mx-auto px-4 py-12">
        <span class="text-xs font-bold tracking-widest uppercase text-amber-400">Aircraft parts marketplace</span>
        <h1 class="text-4xl font-extrabold">Request a quote</h1>
        <p class="mt-2 max-w-2xl">Send us the part numbers you need. We respond with a formal quote within 24 business hours — within 2 hours for AOG requests.</p>
    </div>
</section>

<section class="container mx-auto px-4 py-10">
    <form method="post" action="<?= base_url('rfq/submit') ?>" enctype="multipart/form-data" class="grid lg:grid-cols-3 gap-6">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">

        <div class="lg:col-span-2 space-y-6">
            <div class="vp-card vp-card-pad">
                <h2 class="font-bold text-lg mb-4">Company &amp; contact</h2>
                <div class="vp-grid-2">
                    <div class="vp-form-row"><label>Company *</label><input class="vp-input" name="companyName" required value="<?= vp_safe_html($this->input->post('companyName')) ?>"></div>
                    <div class="vp-form-row"><label>Contact person *</label><input class="vp-input" name="contactPerson" required value="<?= vp_safe_html($this->input->post('contactPerson')) ?>"></div>
                    <div class="vp-form-row"><label>Email *</label><input class="vp-input" type="email" name="email" required value="<?= vp_safe_html($this->input->post('email')) ?>"></div>
                    <div class="vp-form-row"><label>Phone</label><input class="vp-input" name="phone" value="<?= vp_safe_html($this->input->post('phone')) ?>"></div>
                    <div class="vp-form-row"><label>Country *</label><input class="vp-input" name="country" required value="<?= vp_safe_html($this->input->post('country')) ?>"></div>
                    <div class="vp-form-row"><label>Aircraft type</label>
                        <select class="vp-select" name="industry">
                            <option value="">—</option>
                            <?php foreach (['Gulfstream','Dassault Falcon','Cessna Citation','Bombardier Challenger','Hawker','Learjet','Boeing','Airbus','Embraer','Pilatus','Other'] as $i): ?>
                                <option <?= $this->input->post('industry') === $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="vp-form-row lg:col-span-2"><label>Address</label><input class="vp-input" name="address" value="<?= vp_safe_html($this->input->post('address')) ?>"></div>
                </div>
            </div>

            <div class="vp-card vp-card-pad">
                <h2 class="font-bold text-lg mb-4">Parts required</h2>
                <p class="text-sm text-ink-800 mb-3">Add one row per part — part number, quantity and any notes (condition preferred, certificate required).</p>
                <div id="vp-items" class="space-y-3">
                    <?php $firstItem = $prefill ? ['name' => $prefill['name'] . ' (' . $prefill['sku'] . ')', 'sku' => $prefill['sku'], 'productId' => $prefill['id']] : null; ?>
                    <div class="vp-item-row grid grid-cols-12 gap-2">
                        <input class="vp-input col-span-6" name="item_name[]" placeholder="Part name / part number" value="<?= vp_safe_html($firstItem['name'] ?? '') ?>" required>
                        <input class="vp-input col-span-2" name="item_qty[]"  type="number" min="1" value="1" required>
                        <input class="vp-input col-span-4" name="item_spec[]" placeholder="Condition / certs (NEW, OHC, 8130-3…)">
                        <?php if (!empty($firstItem['productId'])): ?><input type="hidden" name="item_productId[]" value="<?= $firstItem['productId'] ?>"><?php endif; ?>
                    </div>
                </div>
                <button type="button" id="vp-item-add" class="vp-btn vp-btn-secondary mt-3 vp-btn-sm">+ Add line item</button>
            </div>

            <div class="vp-card vp-card-pad">
                <h2 class="font-bold text-lg mb-4">Additional notes</h2>
                <div class="vp-form-row">
                    <textarea class="vp-textarea" name="notes" rows="5" placeholder="Anything else we should know (AOG status, delivery date, airworthiness certificates required, exchange core available)"><?= vp_safe_html($this->input->post('notes')) ?></textarea>
                </div>
                <div class="vp-form-row mt-3">
                    <label>Required delivery date (optional)</label>
                    <input class="vp-input" type="date" name="deadline" data-flatpickr value="<?= vp_safe_html($this->input->post('deadline')) ?>">
                </div>
            </div>

            <div class="vp-card vp-card-pad">
                <h2 class="font-bold text-lg mb-4">Attachments (optional)</h2>
                <div class="vp-form-row">
                    <label>Datasheets, photos, prior quotations</label>
                    <input class="vp-input" type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.png,.jpg,.jpeg,.dwg,.dxf,.step,.stp,.iges,.igs">
                    <p class="vp-help">You can attach multiple files. Max 8 MB each. Datasheets, photos and prior quotations are most helpful.</p>
                </div>
            </div>

            <div class="vp-card vp-card-pad bg-blue-50 border-blue-200">
                <p class="text-sm text-blue-900">By submitting, you agree to our processing of your information for the purpose of preparing this quote. Standard quotes return within 24 business hours; AOG requests within 2 hours.</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="vp-btn vp-btn-primary"><i class="ri-send-plane-line"></i> Submit RFQ</button>
                <a class="vp-btn vp-btn-secondary" href="<?= base_url() ?>">Cancel</a>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="vp-card overflow-hidden">
                <img src="<?= IMG_URL ?>rfq-engineering.jpg" alt="Engineer reviewing project specifications with a client" class="w-full aspect-[4/3] object-cover" loading="lazy" decoding="async">
                <div class="p-5">
                    <h3 class="font-bold">Sourced from our global supplier network</h3>
                    <p class="text-sm text-ink-800 mt-1">Can't find a part in the catalog? Share the part number and we will source it from 2,000+ vetted aviation suppliers.</p>
                </div>
            </div>
            <div class="vp-card vp-card-pad">
                <h3 class="font-bold">What happens next?</h3>
                <ol class="text-sm text-ink-800 list-decimal pl-5 space-y-2 mt-2">
                    <li>You receive an email confirmation with your quote reference number.</li>
                    <li>Our parts desk checks stock and certification, and may contact you with clarifying questions.</li>
                    <li>A formal quote is emailed to you within 24 business hours (2 hours for AOG).</li>
                </ol>
            </div>
            <div class="vp-card vp-card-pad">
                <h3 class="font-bold">Already submitted?</h3>
                <p class="text-sm text-ink-800 mt-1">We have all the information we need - no further action required.</p>
            </div>
        </aside>
    </form>
</section>

<?php /* The line-item add/remove behaviour lives in assets/js/app.js
         (inline scripts are blocked by the production CSP). */ ?>
