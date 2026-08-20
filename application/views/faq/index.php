<?php /** @var array $grouped */ ?>
<?php $this->load->view('partials/photo_writeup_hero', [
    'hero_image'         => IMG_URL . 'faq-engineer.jpg',
    'hero_alt'           => "Application engineer answering a customer's technical question",
    'hero_eyebrow'       => 'Expert answers',
    'hero_title_html'    => vp_inline_text('faq_hero_title', 'FAQ', 'h1', 'text-4xl lg:text-5xl font-extrabold mt-3'),
    'hero_subtitle_html' => vp_inline_text('faq_hero_subtitle', 'Common questions about lead times, engineering, quality and more.', 'p', 'mt-3 max-w-2xl text-lg'),
]); ?>
<section class="container mx-auto px-4 py-10 max-w-3xl">
    <?php foreach ($grouped as $cat => $rows): ?>
        <h2 class="text-xl font-bold mt-6 mb-3 text-ink-900"><?= vp_safe_html($cat) ?></h2>
        <div class="space-y-2">
        <?php foreach ($rows as $f): ?>
            <details class="vp-card vp-card-pad group">
                <summary class="cursor-pointer font-semibold flex items-center justify-between">
                    <?= vp_safe_html($f['question']) ?>
                    <i class="ri-arrow-down-s-line text-xl text-ink-800 group-open:rotate-180 transition"></i>
                </summary>
                <p class="mt-3 text-sm text-ink-900"><?= nl2br(vp_safe_html($f['answer'])) ?></p>
            </details>
        <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>
