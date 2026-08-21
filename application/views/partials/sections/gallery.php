<?php
/** Image gallery. @var array $section */
$images = (array) vp_section_option($section, 'gallery', []);
$images = array_values(array_filter(array_map('trim', $images)));
?>
<section class="bg-white"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-12">
        <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900 mb-3"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
        <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mb-6"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
        <?php if ($images): ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($images as $url): ?>
                    <img src="<?= vp_safe_html(vp_asset_url($url)) ?>" alt="" class="w-full h-56 object-cover rounded-xl" loading="lazy" decoding="async">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($section['body'])): ?><div class="vp-prose mt-6"><?= $section['body'] ?></div><?php endif; ?>
    </div>
</section>
