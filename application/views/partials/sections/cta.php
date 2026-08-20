<?php
/** Call-to-action band (bottom of page — the white write-up bands are top-only). @var array $section */
$this->load->view('partials/sections/_helpers');
?>
<section class="bg-white border-y"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-14 text-center">
        <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
        <?php if (!empty($section['subtitle'])): ?><p class="mt-2 max-w-2xl mx-auto"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
        <?php if (!empty($section['body'])): ?><div class="mt-3 max-w-2xl mx-auto"><?= $section['body'] ?></div><?php endif; ?>
        <div class="mt-6 flex flex-wrap gap-3 justify-center">
            <?php if (!empty($section['buttonText'])): ?>
                <a href="<?= vp_safe_html(vp_section_link($section['buttonUrl'])) ?>" class="vp-cta-quote inline-block bg-brand-600 hover:bg-brand-700 text-black font-bold px-6 py-3 rounded-lg"><?= vp_safe_html($section['buttonText']) ?></a>
            <?php endif; ?>
            <?php if (!empty($section['buttonText2'])): ?>
                <a href="<?= vp_safe_html(vp_section_link($section['buttonUrl2'])) ?>" class="inline-block border border-black/20 font-bold px-6 py-3 rounded-lg"><?= vp_safe_html($section['buttonText2']) ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
