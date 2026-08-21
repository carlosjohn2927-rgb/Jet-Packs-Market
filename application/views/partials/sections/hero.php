<?php
/** Hero banner section. @var array $section */
$this->load->view('partials/sections/_helpers');
$img     = vp_asset_url($section['image'] ?? '', IMG_URL . 'hero-jet.jpg');
$eyebrow = vp_section_option($section, 'eyebrow');
$badges  = (array) vp_section_option($section, 'badges', []);
$showSearch = (bool) vp_section_option($section, 'showSearch', false);
?>
<section class="relative overflow-hidden bg-ink-900 min-h-[520px] flex items-center"<?= vp_section_style_attr($section) ?>>
    <img src="<?= vp_safe_html($img) ?>" alt="<?= vp_safe_html($section['title'] ?? '') ?>" class="absolute inset-0 w-full h-full object-cover" fetchpriority="high" decoding="async">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="container mx-auto px-4 py-20 lg:py-28 relative">
        <div class="vp-writeup-band vp-writeup-overlay max-w-2xl rounded-2xl p-6 md:p-8">
            <?php if ($eyebrow): ?>
                <span class="inline-block text-xs font-semibold tracking-widest uppercase text-white bg-black/40 px-3 py-1 rounded-full"><?= vp_safe_html($eyebrow) ?></span>
            <?php endif; ?>
            <?php if (!empty($section['title'])): ?>
                <h1 class="text-4xl lg:text-6xl font-extrabold mt-4 leading-tight"><?= vp_safe_html($section['title']) ?></h1>
            <?php endif; ?>
            <?php if (!empty($section['subtitle'])): ?>
                <p class="text-lg mt-5 max-w-xl"><?= vp_safe_html($section['subtitle']) ?></p>
            <?php endif; ?>
            <?php if (!empty($section['body'])): ?>
                <div class="mt-4"><?= $section['body'] ?></div>
            <?php endif; ?>

            <div class="mt-8 flex flex-wrap gap-3">
                <?php if (!empty($section['buttonText'])): ?>
                    <a href="<?= vp_safe_html(vp_section_link($section['buttonUrl'])) ?>" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-lg"><?= vp_safe_html($section['buttonText']) ?></a>
                <?php endif; ?>
                <?php if (!empty($section['buttonText2'])): ?>
                    <a href="<?= vp_safe_html(vp_section_link($section['buttonUrl2'])) ?>" class="bg-white hover:bg-gray-50 text-black font-semibold px-6 py-3 rounded-lg border border-black/20"><?= vp_safe_html($section['buttonText2']) ?></a>
                <?php endif; ?>
            </div>

            <?php if ($badges): ?>
                <div class="mt-10 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <?php foreach ($badges as $b): ?>
                        <span><i class="ri-checkbox-circle-line text-amber-400 mr-1"></i> <?= vp_safe_html($b) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($showSearch): ?>
        <div class="jpm-hero-search">
            <form method="get" action="<?= base_url('products') ?>" class="jpm-search">
                <i class="ri-search-line text-xl text-brand-600"></i>
                <input type="search" name="q" placeholder="Part number, name or manufacturer — e.g. 2612201-2, brake, Honeywell…" aria-label="Search parts">
                <button type="submit">Search</button>
            </form>
            <div class="jpm-legend mt-4">
                <span><i class="ri-circle-fill text-emerald-500"></i> NEW</span>
                <span><i class="ri-circle-fill text-amber-500"></i> OHC</span>
                <span><i class="ri-circle-fill text-sky-500"></i> USED</span>
                <span><i class="ri-flashlight-fill text-amber-400"></i> 24/7 AOG support</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
