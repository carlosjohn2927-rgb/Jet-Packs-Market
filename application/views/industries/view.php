<?php /** @var array $industry */ /** @var array $capabilities */ /** @var array $products */ ?>
<section class="bg-white border-b">
    <div class="container mx-auto px-4 py-6">
        <nav class="text-xs text-ink-800">
            <a class="hover:text-brand-600" href="<?= base_url() ?>">Home</a>
            <span class="mx-1">/</span>
            <a class="hover:text-brand-600" href="<?= base_url('industries') ?>">Industries</a>
            <span class="mx-1">/</span>
            <span class="text-ink-900"><?= vp_safe_html($industry['name']) ?></span>
        </nav>
    </div>
</section>

<section class="relative bg-ink-900 overflow-hidden min-h-[380px] flex items-end">
    <img src="<?= vp_safe_html(vp_industry_image($industry)) ?>" alt="<?= vp_safe_html($industry['name']) ?> facility" class="absolute inset-0 w-full h-full object-cover" fetchpriority="high" decoding="async">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="container mx-auto px-4 py-14 relative">
        <div class="vp-writeup-band vp-writeup-overlay max-w-2xl rounded-2xl p-6 md:p-8">
            <h1 class="text-4xl lg:text-5xl font-extrabold"><?= vp_safe_html($industry['name']) ?></h1>
            <p class="mt-3 max-w-2xl text-lg"><?= vp_safe_html(vp_truncate($industry['description'], 200)) ?></p>
        </div>
    </div>
</section>

<section class="container mx-auto px-4 py-12 grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 vp-prose">
        <?= nl2br(vp_safe_html($industry['description'])) ?>
    </div>
    <aside>
        <?php if (!empty($capabilities)): ?>
        <div class="vp-card vp-card-pad">
            <h3 class="font-bold mb-3">Capabilities</h3>
            <ul class="text-sm space-y-1">
                <?php foreach ($capabilities as $c): ?>
                    <li class="flex items-center gap-2"><i class="ri-check-line text-brand-600"></i> <?= vp_safe_html($c) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <a href="<?= base_url('rfq') ?>" class="vp-btn vp-btn-primary w-full justify-center mt-4">Request a quote</a>
    </aside>
</section>

<?php if (!empty($products)): ?>
<section class="bg-gray-50">
    <div class="container mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold mb-6">Featured products for <?= vp_safe_html($industry['name']) ?></h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($products as $p): ?>
                <a href="<?= base_url('products/' . $p['slug']) ?>" class="bg-white border rounded-2xl overflow-hidden hover:shadow-lg transition flex flex-col">
                    <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                        <?= vp_product_image_tag($p) ?>
                    </div>
                    <div class="p-4">
                        <div class="text-xs text-ink-800 font-mono font-semibold"><?= vp_safe_html($p['sku']) ?></div>
                        <h3 class="font-bold text-sm text-ink-900 mt-1"><?= vp_safe_html($p['name']) ?></h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
