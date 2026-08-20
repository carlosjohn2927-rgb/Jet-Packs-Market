<?php
/** Promotional banner. @var array $section */
$this->load->view('partials/sections/_helpers');
$img = vp_asset_url($section['image'] ?? '');
?>
<section class="container mx-auto px-4 py-10"<?= vp_section_style_attr($section) ?>>
    <div class="relative overflow-hidden rounded-2xl bg-ink-900 text-white">
        <?php if ($img): ?>
            <img src="<?= vp_safe_html($img) ?>" alt="" class="absolute inset-0 w-full h-full object-cover opacity-40" loading="lazy" decoding="async">
        <?php endif; ?>
        <div class="relative px-8 py-12 md:flex items-center gap-6">
            <div class="flex-1">
                <?php if (!empty($section['title'])): ?><h2 class="text-2xl md:text-3xl font-extrabold"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
                <?php if (!empty($section['subtitle'])): ?><p class="text-white/90 mt-2"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
                <?php if (!empty($section['body'])): ?><div class="mt-3 text-white/90"><?= $section['body'] ?></div><?php endif; ?>
            </div>
            <?php if (!empty($section['buttonText'])): ?>
                <a href="<?= vp_safe_html(vp_section_link($section['buttonUrl'])) ?>" class="mt-5 md:mt-0 inline-block bg-white text-ink-900 font-bold px-6 py-3 rounded-lg"><?= vp_safe_html($section['buttonText']) ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
