<?php
/** Testimonials. @var array $section */
$rows = $blocks['testimonials'] ?? [];
if (empty($rows)) return;
?>
<section class="bg-white"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-16">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
            <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mt-3"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
        </div>
        <div class="grid md:grid-cols-2 gap-5">
            <?php foreach ($rows as $t): ?>
                <blockquote class="vp-review bg-white border rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="<?= vp_safe_html(vp_testimonial_image($t)) ?>" alt="<?= vp_safe_html($t['name']) ?>" class="w-16 h-16 rounded-full object-cover border border-gray-200" loading="lazy" decoding="async">
                        <div>
                            <div class="font-bold text-ink-900"><?= vp_safe_html($t['name']) ?></div>
                            <div class="text-sm text-ink-800"><?= vp_safe_html($t['title']) ?><?= !empty($t['company']) ? ', ' . vp_safe_html($t['company']) : '' ?></div>
                        </div>
                    </div>
                    <div class="text-yellow-500 mb-2"><?= str_repeat('<i class="ri-star-fill"></i>', max(0, (int) ($t['rating'] ?? 5))) ?></div>
                    <p class="text-ink-900 leading-relaxed">&ldquo;<?= vp_safe_html($t['content']) ?>&rdquo;</p>
                </blockquote>
            <?php endforeach; ?>
        </div>
    </div>
</section>
