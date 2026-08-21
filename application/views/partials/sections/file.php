<?php
/** File download block. @var array $section */
$this->load->view('partials/sections/_helpers');
$url = vp_asset_url((string) vp_section_option($section, 'fileUrl', ''));
$label = vp_section_option($section, 'fileLabel', $section['buttonText'] ?? 'Download');
?>
<section class="bg-white"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-12 max-w-3xl">
        <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
        <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mt-3"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
        <?php if (!empty($section['body'])): ?><div class="vp-prose mt-4"><?= $section['body'] ?></div><?php endif; ?>
        <?php if ($url): ?>
            <a class="vp-btn vp-btn-primary mt-6 inline-flex" href="<?= vp_safe_html($url) ?>" download>
                <i class="ri-download-2-line"></i> <?= vp_safe_html($label ?: 'Download') ?>
            </a>
        <?php endif; ?>
    </div>
</section>
