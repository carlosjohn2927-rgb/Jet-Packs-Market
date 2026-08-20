<?php
/** Free-form content block. @var array $section */
$this->load->view('partials/sections/_helpers');
$img = vp_asset_url($section['image'] ?? '');
?>
<section class="bg-white"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-16">
        <div class="grid <?= $img ? 'lg:grid-cols-2' : '' ?> gap-10 items-center">
            <div>
                <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
                <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mt-3"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
                <?php if (!empty($section['body'])): ?><div class="vp-prose mt-4 leading-relaxed"><?= $section['body'] ?></div><?php endif; ?>
                <?php if (!empty($section['buttonText'])): ?>
                    <a href="<?= vp_safe_html(vp_section_link($section['buttonUrl'])) ?>" class="vp-btn vp-btn-primary mt-6 inline-flex"><?= vp_safe_html($section['buttonText']) ?></a>
                <?php endif; ?>
            </div>
            <?php if ($img): ?>
                <div><img src="<?= vp_safe_html($img) ?>" alt="<?= vp_safe_html($section['title'] ?? '') ?>" class="rounded-2xl w-full object-cover" loading="lazy" decoding="async"></div>
            <?php endif; ?>
        </div>
    </div>
</section>
