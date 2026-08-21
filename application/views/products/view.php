<?php
/** @var array $product */
/** @var array $images */
/** @var array $specs */
/** @var array $downloads */
/** @var array $related */
/** @var array $category */
/** @var array $industries */
/** @var array $certifications */
[$condLabel, $condClass] = vp_condition_badge($product['condition'] ?? 'NEW');
$qty = (int) ($product['quantity'] ?? 1);
?>
<section class="bg-white border-b">
    <div class="container mx-auto px-4 py-6">
        <nav class="text-xs text-ink-800">
            <a class="hover:text-brand-600" href="<?= base_url() ?>">Home</a>
            <span class="mx-1">/</span>
            <a class="hover:text-brand-600" href="<?= base_url('products') ?>">Parts</a>
            <?php if ($category): ?>
                <span class="mx-1">/</span>
                <a class="hover:text-brand-600" href="<?= base_url('products?category=' . urlencode($category['slug'])) ?>"><?= vp_safe_html($category['name']) ?></a>
            <?php endif; ?>
            <span class="mx-1">/</span>
            <span class="text-ink-900"><?= vp_safe_html($product['name']) ?></span>
        </nav>
    </div>
</section>

<section class="container mx-auto px-4 py-10 grid lg:grid-cols-2 gap-10">
    <div>
        <?php
        $mainImg = !empty($images) && !empty($images[0]['url'])
            ? $images[0]['url']
            : vp_product_image($product, $category['slug'] ?? null);
        ?>
        <div class="rounded-2xl aspect-square overflow-hidden bg-gray-100 border">
            <img id="vp-main-image" src="<?= vp_safe_html($mainImg) ?>" onerror="this.onerror=null;this.src='<?= IMG_URL ?>products/default.jpg'" alt="<?= vp_safe_html($product['name']) ?>" class="w-full h-full object-cover" decoding="async">
        </div>
        <?php if (count($images) > 1): ?>
            <div class="grid grid-cols-4 gap-2 mt-3">
                <?php foreach ($images as $img): ?>
                    <button type="button" class="aspect-square bg-gray-100 rounded-lg overflow-hidden border hover:border-brand-500 transition"
                            onclick="document.getElementById('vp-main-image').src=this.querySelector('img').src">
                        <img src="<?= vp_safe_html($img['url']) ?>" onerror="this.onerror=null;this.src='<?= IMG_URL ?>products/default.jpg'" alt="<?= vp_safe_html($img['alt'] ?? $product['name']) ?>" class="w-full h-full object-cover" loading="lazy" decoding="async">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-mono font-bold text-brand-700 bg-brand-50 border border-brand-100 px-2.5 py-1 rounded"><?= vp_safe_html($product['sku']) ?></span>
            <span class="jpm-cond <?= $condClass ?>"><?= vp_safe_html($condLabel) ?></span>
            <?php if (!empty($product['manufacturer'])): ?>
                <span class="jpm-chip"><i class="ri-building-line"></i> <?= vp_safe_html($product['manufacturer']) ?></span>
            <?php endif; ?>
        </div>
        <h1 class="text-3xl font-extrabold text-ink-900 mt-3"><?= vp_safe_html($product['name']) ?></h1>

        <?php if (!empty($product['aircraftType'])): ?>
            <div class="jpm-chip mt-3"><i class="ri-flight-takeoff-line"></i> Fits: <?= vp_safe_html($product['aircraftType']) ?></div>
        <?php endif; ?>

        <div class="vp-prose mt-5">
            <?= $product['description'] ?>
        </div>

        <div class="jpm-buybox mt-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-ink-800">Price</div>
                    <?= vp_part_price($product['price']) ?>
                    <?php if (!empty($product['price'])): ?><div class="text-xs text-ink-800 mt-1">Per unit · ex-works, certified</div><?php endif; ?>
                </div>
                <div class="text-right">
                    <div class="text-xs font-bold uppercase tracking-wide text-ink-800">Available</div>
                    <div class="font-extrabold text-ink-900 text-xl"><?= $qty ?> unit<?= $qty === 1 ? '' : 's' ?></div>
                    <div class="text-xs text-ink-800"><?= vp_safe_html(str_replace('_', ' ', $product['availability'])) ?></div>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="<?= base_url('rfq?product=' . urlencode($product['slug'])) ?>" class="vp-btn vp-btn-cta flex-1 min-w-[200px] justify-center text-base"><i class="ri-quote-text"></i> Request a Quote</a>
                <a href="<?= base_url('contact?subject=' . urlencode('Question about ' . $product['name'] . ' (' . $product['sku'] . ')')) ?>" class="vp-btn vp-btn-ask flex-1 min-w-[160px] justify-center text-base"><i class="ri-question-line"></i> Ask a Question</a>
            </div>
            <p class="text-xs text-ink-800 mt-3"><i class="ri-checkbox-circle-line text-emerald-600"></i> Ships with FAA 8130-3 / EASA Form 1 and full traceability · 12-month warranty · AOG dispatch 24/7</p>
        </div>

        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 mt-8 text-sm">
            <?php if (!empty($product['manufacturer'])): ?><div><dt class="text-ink-800">Manufacturer</dt><dd class="font-semibold"><?= vp_safe_html($product['manufacturer']) ?></dd></div><?php endif; ?>
            <?php if (!empty($product['aircraftType'])): ?>
                <div class="vp-form-row lg:col-span-2">
                    <dt class="text-ink-800">Aircraft compatibility</dt>
                    <dd class="font-semibold flex flex-wrap gap-x-3 gap-y-1">
                        <?php foreach (json_decode($product['aircraftType'], true) as $at_id): ?>
                            <?php if (isset($aircraft_names[$at_id])): ?>
                                <span class="jpm-chip"><?= vp_safe_html($aircraft_names[$at_id]) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </dd>
                </div>
            <?php endif; ?>
            <?php if ($product['condition']): ?><div><dt class="text-ink-800">Condition</dt><dd class="font-semibold"><?= vp_safe_html($product['condition']) ?></dd></div><?php endif; ?>
            <?php if ($qty > 0): ?><div><dt class="text-ink-800">Quantity available</dt><dd class="font-semibold"><?= $qty ?></dd></div><?php endif; ?>
            <?php if ($product['material']): ?><div><dt class="text-ink-800">Material</dt><dd class="font-semibold"><?= vp_safe_html($product['material']) ?></dd></div><?php endif; ?>
            <?php if ($product['weight']): ?><div><dt class="text-ink-800">Weight</dt><dd class="font-semibold"><?= vp_safe_html($product['weight']) ?></dd></div><?php endif; ?>
            <?php if ($product['dimensions']): ?><div><dt class="text-ink-800">Dimensions</dt><dd class="font-semibold"><?= vp_safe_html($product['dimensions']) ?></dd></div><?php endif; ?>
        </dl>
    </div>
</section>

<?php if (!empty($specs)): ?>
<section class="bg-gray-50">
    <div class="container mx-auto px-4 py-10">
        <h2 class="text-2xl font-bold mb-4">Full specifications</h2>
        <div class="bg-white rounded-2xl border overflow-hidden">
            <table class="w-full text-sm">
                <tbody>
                <?php foreach ($specs as $i => $s): ?>
                    <tr class="<?= $i % 2 ? 'bg-gray-50' : '' ?>">
                        <td class="px-5 py-3 font-semibold text-ink-900 w-1/3"><?= vp_safe_html($s['key']) ?></td>
                        <td class="px-5 py-3"><?= vp_safe_html($s['value']) ?><?= $s['unit'] ? ' <span class="text-ink-800">'.vp_safe_html($s['unit']).'</span>' : '' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($downloads)): ?>
<section class="container mx-auto px-4 py-10">
    <h2 class="text-2xl font-bold mb-4">Downloads</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($downloads as $d): ?>
            <a href="<?= vp_safe_html($d['url']) ?>" class="bg-white border rounded-xl p-4 flex items-center gap-3 hover:border-brand-300 hover:shadow">
                <i class="ri-file-download-line text-2xl text-brand-600"></i>
                <div class="flex-1">
                    <div class="font-semibold"><?= vp_safe_html($d['title']) ?></div>
                    <div class="text-xs text-ink-800"><?= vp_safe_html($d['type']) ?> &middot; <?= vp_safe_html($d['size'] ?? '') ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($related)): ?>
<section class="bg-gray-50">
    <div class="container mx-auto px-4 py-10">
        <h2 class="text-2xl font-bold mb-4">Related parts</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($related as $p): ?>
                <div class="jpm-part-card">
                    <a href="<?= base_url('products/' . $p['slug']) ?>" class="jpm-thumb block">
                        <?= vp_product_image_tag($p) ?>
                    </a>
                    <div class="p-4 flex-1">
                        <a href="<?= base_url('products/' . $p['slug']) ?>">
                            <h3 class="font-bold text-sm text-ink-900 leading-snug hover:text-brand-600"><?= vp_safe_html($p['name']) ?></h3>
                        </a>
                        <div class="text-xs text-ink-800 font-mono font-semibold mt-1"><?= vp_safe_html($p['sku']) ?></div>
                        <div class="mt-2 flex items-center justify-between">
                            <?php [$rl, $rc] = vp_condition_badge($p['condition'] ?? 'NEW'); ?>
                            <span class="jpm-cond <?= $rc ?>"><?= vp_safe_html($rl) ?></span>
                            <span class="jpm-price text-base"><?= vp_part_price($p['price']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
