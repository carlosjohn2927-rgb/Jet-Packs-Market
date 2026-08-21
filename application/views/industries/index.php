<?php /** @var array $industries */ ?>
<?php $this->load->view('partials/photo_writeup_hero', [
    'hero_image'         => IMG_URL . 'industries/oil-gas.jpg',
    'hero_alt'           => 'Oil and gas processing facility at sunset',
    'hero_title_html'    => vp_inline_text('industries_hero_title', 'Industries we serve', 'h1', 'text-4xl lg:text-5xl font-extrabold'),
    'hero_subtitle_html' => vp_inline_text('industries_hero_subtitle', "Engineered for the requirements of the world's most demanding sectors.", 'p', 'mt-3 max-w-2xl text-lg'),
]); ?>
<section class="container mx-auto px-4 py-12">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($industries as $i): ?>
            <a href="<?= base_url('industries/' . $i['slug']) ?>" class="group relative rounded-2xl overflow-hidden min-h-[330px] bg-ink-900 shadow-sm hover:shadow-xl transition">
                <img src="<?= vp_safe_html(vp_industry_image($i)) ?>" alt="<?= vp_safe_html($i['name']) ?> industry" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy" decoding="async">
                <div class="absolute inset-0 bg-gradient-to-t from-ink-900 via-ink-900/50 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                    <h3 class="font-bold text-xl text-white"><?= vp_safe_html($i['name']) ?></h3>
                    <p class="text-sm text-white mt-2"><?= vp_safe_html(vp_truncate($i['description'], 140)) ?></p>
                    <span class="text-brand-200 text-sm font-semibold mt-3 inline-block">Learn more &rarr;</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
