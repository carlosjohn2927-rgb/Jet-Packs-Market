<?php
/** @var array $rows */
/** @var int   $total */
/** @var int   $total_pages */
/** @var int   $page */
/** @var array $categories */
/** @var array $industries */
/** @var string $current_category */
/** @var string $current_industry */
/** @var string $search */
/** @var string $base_url */
?>
<?php $this->load->view('partials/photo_writeup_hero', [
    'hero_image'         => IMG_URL . 'products/valves.jpg',
    'hero_alt'           => 'Industrial valves ready for process service',
    'hero_title_html'    => vp_inline_text('products_hero_title', 'Product catalog', 'h1', 'text-4xl lg:text-5xl font-extrabold'),
    'hero_subtitle_html' => vp_inline_text('products_hero_subtitle', 'Valves, pumps, heat exchangers, pressure vessels, filtration and instrumentation — engineered to the highest standards.', 'p', 'mt-3 text-lg'),
]); ?>

<section class="bg-white border-b">
    <div class="container mx-auto px-4 py-5">
        <form method="get" action="<?= base_url('products') ?>" class="grid md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="text-xs font-semibold text-ink-800 uppercase" for="q">Search</label>
                <input class="vp-input" type="search" id="q" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Valve, pump, heat exchanger…">
            </div>
            <div>
                <label class="text-xs font-semibold text-ink-800 uppercase" for="category">Category</label>
                <select class="vp-select" id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['slug'] ?>" <?= $current_category === $c['slug'] ? 'selected' : '' ?>><?= vp_safe_html($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-ink-800 uppercase" for="industry">Industry</label>
                <select class="vp-select" id="industry" name="industry">
                    <option value="">All industries</option>
                    <?php foreach ($industries as $i): ?>
                        <option value="<?= $i['slug'] ?>" <?= $current_industry === $i['slug'] ? 'selected' : '' ?>><?= vp_safe_html($i['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button class="vp-btn vp-btn-primary w-full justify-center" type="submit"><i class="ri-search-line"></i> Filter</button>
            </div>
        </form>
    </div>
</section>

<section class="container mx-auto px-4 py-10">
    <?php if (empty($rows)): ?>
        <div class="text-center text-ink-800 py-16">No products match your filters. <a class="text-brand-600 hover:underline" href="<?= base_url('products') ?>">Clear filters</a></div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($rows as $p): ?>
                <a href="<?= base_url('products/' . $p['slug']) ?>" class="group bg-white border rounded-2xl overflow-hidden hover:shadow-lg transition flex flex-col">
                    <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                        <?= vp_product_image_tag($p, 'w-full h-full object-cover group-hover:scale-105 transition duration-300') ?>
                    </div>
                    <div class="p-5 flex-1 flex flex-col">
                        <div class="text-xs text-ink-800 font-mono font-semibold"><?= vp_safe_html($p['sku']) ?></div>
                        <h3 class="font-bold text-ink-900 mt-1"><?= vp_safe_html($p['name']) ?></h3>
                        <p class="text-sm text-ink-900 mt-2 flex-1 leading-relaxed"><?= vp_safe_html(vp_truncate($p['shortDescription'] ?? $p['description'], 110)) ?></p>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="vp-pill <?= ($p['availability'] === 'IN_STOCK' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') ?>"><?= vp_safe_html(str_replace('_', ' ', $p['availability'])) ?></span>
                            <span class="text-brand-600 text-sm font-semibold">View details &rarr;</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 flex justify-center">
            <?= vp_pagination_links($total_pages, $page, $base_url) ?>
        </div>
    <?php endif; ?>
</section>
