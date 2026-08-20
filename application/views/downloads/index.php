<?php /** @var array $grouped */ ?>
<?php $this->load->view('partials/photo_writeup_hero', [
    'hero_image'         => IMG_URL . 'downloads-library.jpg',
    'hero_alt'           => 'Industrial engineering guides and technical resources',
    'hero_eyebrow'       => 'Engineering resources',
    'hero_title_html'    => vp_inline_text('downloads_hero_title', 'Downloads', 'h1', 'text-4xl lg:text-5xl font-extrabold mt-3'),
    'hero_subtitle_html' => vp_inline_text('downloads_hero_subtitle', 'Brochures, selection guides, datasheets and engineering tools.', 'p', 'mt-3 max-w-2xl text-lg'),
    'hero_min'           => '360px',
]); ?>
<section class="container mx-auto px-4 py-10 max-w-4xl">
    <?php foreach ($grouped as $cat => $rows): ?>
        <h2 class="text-xl font-bold mt-6 mb-3"><?= vp_safe_html($cat) ?></h2>
        <div class="grid sm:grid-cols-2 gap-3">
        <?php foreach ($rows as $d): ?>
            <a href="<?= base_url('downloads/file/' . $d['id']) ?>" class="vp-card vp-card-pad flex items-center gap-3 hover:shadow hover:border-brand-300">
                <i class="ri-file-download-line text-3xl text-brand-600"></i>
                <div class="flex-1">
                    <div class="font-semibold"><?= vp_safe_html($d['title']) ?></div>
                    <div class="text-xs text-ink-800"><?= vp_safe_html($d['type']) ?> &middot; <?= vp_safe_html($d['fileSize'] ?? '') ?> &middot; <?= (int) $d['downloads'] ?> downloads</div>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>
