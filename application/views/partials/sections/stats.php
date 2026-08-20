<?php
/** Statistics strip. @var array $section */
$items = (array) vp_section_option($section, 'items', []);
if (empty($items)) return;
?>
<section class="bg-white border-b"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-10">
        <?php if (!empty($section['title'])): ?>
            <h2 class="text-2xl font-extrabold text-ink-900 text-center mb-6"><?= vp_safe_html($section['title']) ?></h2>
        <?php endif; ?>
        <div class="grid grid-cols-2 md:grid-cols-<?= max(2, min(6, count($items))) ?> gap-6 text-center">
            <?php foreach ($items as $s): ?>
                <div>
                    <div class="text-3xl md:text-4xl font-extrabold text-brand-600"><?= vp_safe_html($s['value'] ?? '') ?></div>
                    <div class="text-sm text-ink-800 mt-1"><?= vp_safe_html($s['label'] ?? '') ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
