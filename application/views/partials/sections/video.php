<?php
/** Video block — uploaded file or YouTube/Vimeo URL. @var array $section */
$this->load->view('partials/sections/_helpers');
$src = trim((string) vp_section_option($section, 'video', ''));
$embed = '';
if (preg_match('~(?:youtube\\.com/watch\\?v=|youtu\\.be/)([A-Za-z0-9_-]{6,})~', $src, $m)) {
    $embed = 'https://www.youtube.com/embed/' . $m[1];
} elseif (preg_match('~vimeo\\.com/(?:video/)?([0-9]+)~', $src, $m)) {
    $embed = 'https://player.vimeo.com/video/' . $m[1];
}
$file = $embed ? '' : vp_asset_url($src);
$poster = vp_asset_url($section['image'] ?? '');
?>
<section class="bg-white"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-12 max-w-4xl">
        <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900 mb-3"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
        <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mb-6"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
        <?php if ($embed): ?>
            <div class="aspect-video rounded-2xl overflow-hidden bg-black">
                <iframe src="<?= vp_safe_html($embed) ?>" class="w-full h-full" title="<?= vp_safe_html($section['title'] ?? 'Video') ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
            </div>
        <?php elseif ($file): ?>
            <video class="w-full rounded-2xl bg-black" controls<?= $poster ? ' poster="' . vp_safe_html($poster) . '"' : '' ?>>
                <source src="<?= vp_safe_html($file) ?>">
                Your browser does not support video playback.
            </video>
        <?php endif; ?>
        <?php if (!empty($section['body'])): ?><div class="vp-prose mt-6"><?= $section['body'] ?></div><?php endif; ?>
    </div>
</section>
