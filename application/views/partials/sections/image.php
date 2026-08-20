<?php
/** Single image block. @var array $section */
$this->load->view('partials/sections/_helpers');
$img = vp_asset_url($section['image'] ?? '');
?>
<section class="bg-white"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-12">
        <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900 mb-4"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
        <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mb-6"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
        <?php if ($img): ?>
            <figure>
                <img src="<?= vp_safe_html($img) ?>" alt="<?= vp_safe_html($section['title'] ?? '') ?>" class="w-full rounded-2xl object-cover" loading="lazy" decoding="async">
                <?php if (!empty($section['body'])): ?><figcaption class="text-sm text-ink-800/70 mt-3"><?= $section['body'] ?></figcaption><?php endif; ?>
            </figure>
        <?php endif; ?>
    </div>
</section>
