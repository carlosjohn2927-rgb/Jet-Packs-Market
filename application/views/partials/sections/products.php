<?php
/** Featured products grid. @var array $section */
$this->load->view('partials/sections/_helpers');
$rows = $blocks['products'] ?? [];
if (empty($rows)) return;
?>
<section class="bg-gray-50"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-16">
        <div class="flex items-end justify-between mb-8">
            <div>
                <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
                <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mt-2"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php if (!empty($section['buttonText'])): ?>
                <a href="<?= vp_safe_html(vp_section_link($section['buttonUrl'] ?: 'products')) ?>" class="text-brand-600 font-semibold hidden sm:inline"><?= vp_safe_html($section['buttonText']) ?> &rarr;</a>
            <?php endif; ?>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($rows as $p): ?>
                <?php [$condLabel, $condClass] = vp_condition_badge($p['condition'] ?? 'NEW'); ?>
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
                        <div class="flex items-center justify-between mt-3">
                            <span class="jpm-qty">Qty: <b><?= (int) ($p['quantity'] ?? 1) ?></b></span>
                            <span class="jpm-price"><?= vp_part_price($p['price']) ?></span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100 flex gap-2">
                            <a href="<?= base_url('rfq?product=' . urlencode($p['slug'])) ?>" class="vp-btn vp-btn-quote flex-1 justify-center text-sm"><i class="ri-quote-text"></i> RFQ</a>
                            <a href="<?= base_url('contact?subject=' . urlencode('Question about ' . $p['name'] . ' (' . $p['sku'] . ')')) ?>" class="vp-btn vp-btn-ask flex-1 justify-center text-sm"><i class="ri-question-line"></i> Ask</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
