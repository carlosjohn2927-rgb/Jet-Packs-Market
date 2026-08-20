<?php
/**
 * Top-of-page photograph with a faint write-up card so the picture shows through.
 *
 * Vars:
 *   string $hero_image          Image URL (required)
 *   string $hero_alt            Alt text
 *   string $hero_eyebrow        Optional small label above the title
 *   string $hero_title_html     Already-rendered title markup (h1, etc.)
 *   string $hero_subtitle_html  Already-rendered subtitle markup
 *   string $hero_min            CSS min-height (default 380px)
 */
$hero_image          = $hero_image ?? '';
$hero_alt            = $hero_alt ?? '';
$hero_eyebrow        = $hero_eyebrow ?? '';
$hero_title_html     = $hero_title_html ?? '';
$hero_subtitle_html  = $hero_subtitle_html ?? '';
$hero_min            = $hero_min ?? '380px';
?>
<section class="relative bg-ink-900 overflow-hidden flex items-end" style="min-height: <?= vp_safe_html($hero_min) ?>">
    <?php if ($hero_image !== ''): ?>
        <img src="<?= vp_safe_html($hero_image) ?>" alt="<?= vp_safe_html($hero_alt) ?>"
             class="absolute inset-0 w-full h-full object-cover object-center" fetchpriority="high" decoding="async">
    <?php endif; ?>
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="container mx-auto px-4 py-14 relative">
        <div class="vp-writeup-band vp-writeup-overlay max-w-2xl rounded-2xl p-6 md:p-8">
            <?php if ($hero_eyebrow !== ''): ?>
                <span class="text-xs font-semibold tracking-widest uppercase text-white"><?= vp_safe_html($hero_eyebrow) ?></span>
            <?php endif; ?>
            <?= $hero_title_html ?>
            <?= $hero_subtitle_html ?>
        </div>
    </div>
</section>
