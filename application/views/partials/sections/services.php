<?php
/** Custom service / feature cards. @var array $section */
$this->load->view('partials/sections/_helpers');
$items = (array) vp_section_option($section, 'items', []);
?>
<section class="bg-white"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-16">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
            <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mt-3"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
        </div>
        <?php if ($items): ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($items as $it): ?>
                    <?php $href = vp_section_link($it['url'] ?? ''); ?>
                    <<?= $href ? 'a href="' . vp_safe_html($href) . '"' : 'div' ?> class="border rounded-2xl p-6 hover:shadow-lg transition block">
                        <i class="<?= vp_safe_html($it['icon'] ?? 'ri-checkbox-circle-line') ?> text-3xl text-brand-600"></i>
                        <h3 class="font-bold text-lg text-ink-900 mt-3"><?= vp_safe_html($it['label'] ?? '') ?></h3>
                        <p class="text-sm text-ink-800 mt-2"><?= vp_safe_html($it['text'] ?? '') ?></p>
                    </<?= $href ? 'a' : 'div' ?>>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($section['body'])): ?>
            <div class="vp-prose max-w-3xl mx-auto mt-8"><?= $section['body'] ?></div>
        <?php endif; ?>
    </div>
</section>
