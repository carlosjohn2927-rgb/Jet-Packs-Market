<?php
/** Industries grid. @var array $section */
$rows = $blocks['industries'] ?? [];
if (empty($rows)) return;
?>
<section class="container mx-auto px-4 py-16"<?= vp_section_style_attr($section) ?>>
    <div class="text-center max-w-2xl mx-auto mb-10">
        <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
        <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mt-3"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($rows as $i): ?>
            <a href="<?= base_url('industries/' . $i['slug']) ?>" class="group relative bg-white border rounded-2xl p-6 hover:shadow-lg hover:border-brand-300 transition overflow-hidden">
                <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-brand-50 group-hover:bg-brand-100 transition"></div>
                <div class="relative">
                    <i class="<?= vp_safe_html($i['icon'] ?: 'ri-government-line') ?> text-3xl text-brand-600"></i>
                    <h3 class="font-bold text-lg text-ink-900 mt-3"><?= vp_safe_html($i['name']) ?></h3>
                    <p class="text-sm text-ink-800 mt-2"><?= vp_safe_html(vp_truncate($i['description'], 110)) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
