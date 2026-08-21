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
    'hero_image'         => IMG_URL . 'hero-hangar.jpg',
    'hero_alt'           => 'Aircraft parts in a hangar ready to ship',
    'hero_eyebrow'       => 'Aircraft parts marketplace',
    'hero_title_html'    => vp_inline_text('products_hero_title', 'Parts catalog', 'h1', 'text-4xl lg:text-5xl font-extrabold'),
    'hero_subtitle_html' => vp_inline_text('products_hero_subtitle', 'New, overhauled and used parts for business and commercial jets — every part certified, traceable and ready to ship.', 'p', 'mt-3 text-lg'),
]); ?>

<section class="jpm-filters">
    <div class="container mx-auto px-4 py-5">
        <form method="get" action="<?= base_url('products') ?>" class="grid md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="text-xs font-bold text-ink-800 uppercase tracking-wide" for="q">Search</label>
                <input class="vp-input" type="search" id="q" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Part number, name, manufacturer…">
            </div>
            <div>
                <label class="text-xs font-bold text-ink-800 uppercase tracking-wide" for="category">Category</label>
                <select class="vp-select" id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['slug'] ?>" <?= $current_category === $c['slug'] ? 'selected' : '' ?>><?= vp_safe_html($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-ink-800 uppercase tracking-wide" for="industry">Aircraft</label>
                <select class="vp-select" id="industry" name="industry">
                    <option value="">All aircraft</option>
                    <?php foreach ($industries as $i): ?>
                        <option value="<?= $i['slug'] ?>" <?= $current_industry === $i['slug'] ? 'selected' : '' ?>><?= vp_safe_html($i['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button class="vp-btn vp-btn-primary w-full justify-center" type="submit"><i class="ri-search-line"></i> Find parts</button>
            </div>
        </form>
    </div>
</section>

<section class="container mx-auto px-4 py-10">
    <?php if (!empty($total)): ?>
        <p class="text-sm text-ink-800 mb-5"><strong><?= number_format($total) ?></strong> part<?= $total === 1 ? '' : 's' ?> found <?= $search ? 'for “' . vp_safe_html($search) . '”' : '' ?></p>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="text-center text-ink-800 py-16">
            <i class="ri-search-eye-line text-5xl text-gray-300 block mb-3"></i>
            No parts match your filters.
            <a class="text-brand-600 hover:underline font-semibold" href="<?= base_url('products') ?>">Clear filters</a>
            — or <a class="text-brand-600 hover:underline font-semibold" href="<?= base_url('rfq') ?>">send us the part number</a> and we will source it.
        </div>
    <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            <?php foreach ($rows as $p): ?>
                <?php
                [$condLabel, $condClass] = vp_condition_badge($p['condition'] ?? 'NEW');
                $qty = (int) ($p['quantity'] ?? 1);
                ?>
                <div class="jpm-part-card">
                    <a href="<?= base_url('products/' . $p['slug']) ?>" class="jpm-thumb block" aria-label="<?= vp_safe_html($p['name']) ?>">
                        <?= vp_product_image_tag($p, 'w-full h-full object-cover', null, 'lazy') ?>
                    </a>
                    <div class="jpm-body">
                        <div class="flex items-center justify-between gap-2">
                            <span class="jpm-pn"><?= vp_safe_html($p['sku']) ?></span>
                            <span class="jpm-cond <?= $condClass ?>"><?= vp_safe_html($condLabel) ?></span>
                        </div>
                        <a href="<?= base_url('products/' . $p['slug']) ?>" class="block mt-1.5">
                            <h3 class="font-bold text-ink-900 leading-snug hover:text-brand-600"><?= vp_safe_html($p['name']) ?></h3>
                        </a>
                        <?php if (!empty($p['manufacturer'])): ?>
                            <div class="jpm-manufacturer mt-1"><?= vp_safe_html($p['manufacturer']) ?></div>
                        <?php endif; ?>
                        <div class="flex items-center justify-between mt-3">
                            <span class="jpm-qty">Qty: <b><?= $qty ?></b></span>
                            <span class="jpm-price"><?= vp_part_price($p['price']) ?></span>
                        </div>
                        <?php if (!empty($p['aircraftType'])): ?>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <?php foreach (json_decode($p['aircraftType'], true) as $at_id): ?>
                                    <?php if (isset($aircraft_names[$at_id])): ?>
                                        <span class="jpm-chip"><i class="ri-flight-takeoff-line"></i> <?= vp_safe_html($aircraft_names[$at_id]) ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-4 pt-3 border-t border-gray-100 flex gap-2">
                            <a href="<?= base_url('rfq?product=' . urlencode($p['slug'])) ?>" class="vp-btn vp-btn-quote flex-1 justify-center text-sm"><i class="ri-quote-text"></i> RFQ</a>
                            <a href="<?= base_url('contact?subject=' . urlencode('Question about ' . $p['name'] . ' (' . $p['sku'] . ')')) ?>" class="vp-btn vp-btn-ask flex-1 justify-center text-sm"><i class="ri-question-line"></i> Ask</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 flex justify-center">
            <?= vp_pagination_links($total_pages, $page, $base_url) ?>
        </div>
    <?php endif; ?>
</section>
