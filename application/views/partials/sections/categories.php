<?php
/** Product categories grid. @var array $section */
$rows = $blocks['categories'] ?? [];
if (empty($rows)) return;
?>
<section class="container mx-auto px-4 py-16"<?= vp_section_style_attr($section) ?>>
    <div class="text-center max-w-2xl mx-auto mb-10">
        <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
        <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mt-3"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($rows as $c): ?>
            <a href="<?= base_url('products?category=' . urlencode($c['slug'])) ?>" class="group bg-white border rounded-2xl overflow-hidden hover:shadow-lg hover:border-brand-300 transition">
                <div class="aspect-[16/9] bg-gray-100 overflow-hidden">
                    <img src="<?= vp_safe_html($c['image'] ? vp_asset_url($c['image']) : IMG_URL . 'products/' . $c['slug'] . '.jpg') ?>"
                         alt="<?= vp_safe_html($c['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                         loading="lazy" decoding="async" onerror="this.onerror=null;this.src='<?= IMG_URL ?>products/default.jpg'">
                </div>
                <div class="p-5">
                    <h3 class="font-bold text-lg text-ink-900"><?= vp_safe_html($c['name']) ?></h3>
                    <p class="text-sm text-ink-800 mt-2"><?= vp_safe_html(vp_truncate($c['description'] ?? '', 120)) ?></p>
                    <span class="text-brand-600 text-sm font-semibold mt-3 inline-block">Browse &rarr;</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
