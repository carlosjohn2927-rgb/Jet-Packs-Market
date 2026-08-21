<?php
/** Partner logo strip. @var array $section */
$rows = $blocks['partners'] ?? [];
if (empty($rows)) return;
?>
<section class="bg-white border-t"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-12">
        <?php if (!empty($section['title'])): ?>
            <p class="text-center text-xs uppercase tracking-widest text-ink-800 mb-6"><?= vp_safe_html($section['title']) ?></p>
        <?php endif; ?>
        <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-6 opacity-80">
            <?php foreach ($rows as $p): ?>
                <a href="<?= vp_safe_html($p['website'] ?: '#') ?>" class="h-9 max-w-[150px]" target="_blank" rel="noopener" aria-label="<?= vp_safe_html($p['name']) ?>">
                    <img src="<?= vp_safe_html(vp_asset_url($p['logo'])) ?>" alt="<?= vp_safe_html($p['name']) ?>" class="h-full w-auto max-w-full object-contain grayscale" loading="lazy" decoding="async">
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
