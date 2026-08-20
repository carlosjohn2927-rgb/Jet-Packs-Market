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
                <a href="<?= base_url('products/' . $p['slug']) ?>" class="group bg-white rounded-2xl border overflow-hidden hover:shadow-lg transition flex flex-col">
                    <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                        <?= vp_product_image_tag($p, 'w-full h-full object-cover group-hover:scale-105 transition duration-300', null, 'lazy') ?>
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <div class="text-xs text-ink-800 font-mono font-semibold"><?= vp_safe_html($p['sku']) ?></div>
                        <h3 class="font-bold text-ink-900 mt-1"><?= vp_safe_html($p['name']) ?></h3>
                        <p class="text-sm text-ink-900 mt-2 flex-1 leading-relaxed"><?= vp_safe_html(vp_truncate($p['shortDescription'] ?? $p['description'], 90)) ?></p>
                        <div class="mt-3 text-brand-600 text-sm font-semibold">View details &rarr;</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
