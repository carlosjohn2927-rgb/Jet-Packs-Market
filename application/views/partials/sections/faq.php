<?php
/** FAQ accordion. @var array $section */
$rows = $blocks['faqs'] ?? [];
if (empty($rows)) return;
?>
<section class="bg-gray-50"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-16 max-w-3xl">
        <div class="text-center mb-8">
            <?php if (!empty($section['title'])): ?><h2 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
            <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mt-3"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
        </div>
        <div class="space-y-3">
            <?php foreach ($rows as $f): ?>
                <details class="bg-white border rounded-xl p-4">
                    <summary class="font-semibold text-ink-900 cursor-pointer"><?= vp_safe_html($f['question']) ?></summary>
                    <p class="text-ink-800 mt-2 text-sm leading-relaxed"><?= vp_safe_html($f['answer']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
