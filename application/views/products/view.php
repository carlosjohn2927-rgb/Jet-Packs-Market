<?php
/** @var array $product */
/** @var array $images */
/** @var array $specs */
/** @var array $downloads */
/** @var array $related */
/** @var array $category */
/** @var array $industries */
/** @var array $certifications */
?>
<section class="bg-white border-b">
    <div class="container mx-auto px-4 py-6">
        <nav class="text-xs text-ink-800">
            <a class="hover:text-brand-600" href="<?= base_url() ?>">Home</a>
            <span class="mx-1">/</span>
            <a class="hover:text-brand-600" href="<?= base_url('products') ?>">Products</a>
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
        // Show the primary uploaded image; otherwise fall back to the
        // category photo, then a keyword guess, then the generic default.
        // vp_product_image() centralises that chain; onerror keeps a
        // broken/relative URL from ever showing the empty icon placeholder.
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
        <div class="text-xs font-mono text-ink-800"><?= vp_safe_html($product['sku']) ?></div>
        <h1 class="text-3xl font-extrabold text-ink-900 mt-1"><?= vp_safe_html($product['name']) ?></h1>

        <div class="flex flex-wrap gap-2 mt-3">
            <span class="vp-pill <?= ($product['availability'] === 'IN_STOCK' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') ?>"><?= vp_safe_html(str_replace('_', ' ', $product['availability'])) ?></span>
            <?php foreach ($certifications as $c): ?>
                <span class="vp-pill bg-blue-50 text-blue-700"><?= vp_safe_html($c) ?></span>
            <?php endforeach; ?>
        </div>

        <div class="vp-prose mt-5">
            <?= $product['description'] ?>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="<?= base_url('rfq?product=' . urlencode($product['slug'])) ?>" class="vp-btn vp-btn-primary"><i class="ri-quote-text"></i> Request a quote</a>
            <a href="<?= base_url('contact') ?>" class="vp-btn vp-btn-secondary">Ask a question</a>
        </div>

        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 mt-8 text-sm">
            <?php if ($product['material']):    ?><div><dt class="text-ink-800">Material</dt><dd class="font-semibold"><?= vp_safe_html($product['material']) ?></dd></div><?php endif; ?>
            <?php if ($product['pressure']):    ?><div><dt class="text-ink-800">Pressure rating</dt><dd class="font-semibold"><?= vp_safe_html($product['pressure']) ?></dd></div><?php endif; ?>
            <?php if ($product['temperature']): ?><div><dt class="text-ink-800">Temperature</dt><dd class="font-semibold"><?= vp_safe_html($product['temperature']) ?></dd></div><?php endif; ?>
            <?php if ($product['voltage']):     ?><div><dt class="text-ink-800">Voltage</dt><dd class="font-semibold"><?= vp_safe_html($product['voltage']) ?></dd></div><?php endif; ?>
            <?php if ($product['dimensions']):  ?><div><dt class="text-ink-800">Dimensions</dt><dd class="font-semibold"><?= vp_safe_html($product['dimensions']) ?></dd></div><?php endif; ?>
            <?php if ($product['weight']):      ?><div><dt class="text-ink-800">Weight</dt><dd class="font-semibold"><?= vp_safe_html($product['weight']) ?></dd></div><?php endif; ?>
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
        <h2 class="text-2xl font-bold mb-4">Related products</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($related as $p): ?>
                <a href="<?= base_url('products/' . $p['slug']) ?>" class="bg-white border rounded-2xl overflow-hidden hover:shadow-lg transition flex flex-col">
                    <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                        <?= vp_product_image_tag($p) ?>
                    </div>
                    <div class="p-4 flex-1">
                        <h3 class="font-bold text-sm text-ink-900"><?= vp_safe_html($p['name']) ?></h3>
                        <div class="text-xs text-ink-800 font-mono font-semibold mt-1"><?= vp_safe_html($p['sku']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
