<?php /** @var array $services */ ?>
<?php $this->load->view('partials/photo_writeup_hero', [
    'hero_image'         => IMG_URL . 'services-parts-warehouse.jpg',
    'hero_alt'           => 'Aircraft parts warehouse with shelves of certified parts',
    'hero_eyebrow'       => 'Parts & support services',
    'hero_title_html'    => vp_inline_text('services_hero_title', 'Services', 'h1', 'text-4xl lg:text-5xl font-extrabold mt-3'),
    'hero_subtitle_html' => vp_inline_text('services_hero_subtitle', 'From a single part to a full fleet program, ' . ($site_name ?? 'JetPacks Market') . ' keeps your aircraft flying.', 'p', 'mt-3 text-lg'),
    'hero_min'           => '400px',
]); ?>
<section class="container mx-auto px-4 py-12">
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($services as $s): ?>
            <div class="vp-card vp-card-pad">
                <i class="<?= vp_safe_html($s['icon']) ?> text-3xl text-brand-600"></i>
                <h3 class="font-bold text-lg mt-3"><?= $s['title'] /* contains safe HTML */ ?></h3>
                <p class="text-sm text-ink-800 mt-2"><?= vp_safe_html($s['desc']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-12">
        <a class="vp-btn vp-btn-primary" href="<?= base_url('rfq') ?>">Request a quote</a>
    </div>
</section>
