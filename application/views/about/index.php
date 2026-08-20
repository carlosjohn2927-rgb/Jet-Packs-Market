<?php /** @var array $intro */ /** @var array $testimonials */ /** @var array $partners */ ?>
<?php $this->load->view('partials/photo_writeup_hero', [
    'hero_image'         => IMG_URL . 'about-facility.jpg',
    'hero_alt'           => 'Engineers reviewing a pressure vessel in our fabrication facility',
    'hero_eyebrow'       => 'About us',
    'hero_title_html'    => vp_inline_text('about_hero_title', 'Built on engineering. Trusted by operators.', 'h1', 'text-4xl lg:text-5xl font-extrabold mt-3'),
    'hero_subtitle_html' => vp_inline_text('about_hero_subtitle', 'For over 35 years, ' . ($site_name ?? 'Halyk Petroleum') . ' has engineered, manufactured and serviced industrial equipment for the most demanding applications on earth.', 'p', 'mt-3 max-w-2xl text-lg'),
    'hero_min'           => '420px',
]); ?>

<section class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-2 gap-10 items-center mb-10">
        <div class="vp-prose">
            <p class="text-xl font-semibold text-ink-900">Engineering confidence into every project.</p>
            <?= nl2br(vp_safe_html($intro)) ?>
        </div>
        <img src="<?= IMG_URL ?>about-facility.jpg" alt="Engineers reviewing a pressure vessel in our fabrication facility" class="w-full aspect-[4/3] object-cover rounded-2xl shadow-lg" loading="eager" decoding="async">
    </div>
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 grid sm:grid-cols-3 gap-4">
            <div class="vp-card vp-card-pad"><div class="text-3xl font-extrabold text-brand-600">35+</div><div class="text-sm text-ink-800 mt-1">Years of engineering experience</div></div>
            <div class="vp-card vp-card-pad"><div class="text-3xl font-extrabold text-brand-600">60+</div><div class="text-sm text-ink-800 mt-1">Countries supported worldwide</div></div>
            <div class="vp-card vp-card-pad"><div class="text-3xl font-extrabold text-brand-600">4,200+</div><div class="text-sm text-ink-800 mt-1">Projects successfully delivered</div></div>
        </div>
        <aside>
        <div class="vp-card vp-card-pad">
            <h3 class="font-bold mb-2">Capabilities</h3>
            <ul class="text-sm space-y-2 text-ink-900">
                <li><i class="ri-shield-check-line text-brand-600"></i> ASME U / U2 / S / NB</li>
                <li><i class="ri-rocket-2-line text-brand-600"></i> API 610 / 600 / 594</li>
                <li><i class="ri-medal-line text-brand-600"></i> ISO 9001:2015</li>
                <li><i class="ri-earth-line text-brand-600"></i> Global service network</li>
            </ul>
        </div>
        </aside>
    </div>
</section>

<?php if (!empty($testimonials)): ?>
<section class="bg-white">
    <div class="container mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold text-ink-900 mb-6">What our customers say</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach ($testimonials as $t): ?>
                <blockquote class="vp-review vp-card vp-card-pad">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="<?= vp_safe_html(vp_testimonial_image($t)) ?>" alt="<?= vp_safe_html($t['name']) ?>" class="w-16 h-16 rounded-full object-cover border border-gray-200" width="128" height="128" loading="lazy" decoding="async">
                        <div>
                            <div class="font-bold text-ink-900"><?= vp_safe_html($t['name']) ?></div>
                            <div class="text-sm text-ink-800"><?= vp_safe_html($t['title']) ?>, <?= vp_safe_html($t['company']) ?></div>
                        </div>
                    </div>
                    <div class="text-yellow-500 mb-2"><?= str_repeat('<i class="ri-star-fill"></i>', max(0, (int)($t['rating'] ?? 5))) ?></div>
                    <p class="text-ink-900 leading-relaxed">&ldquo;<?= vp_safe_html($t['content']) ?>&rdquo;</p>
                </blockquote>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($partners)): ?>
<section class="container mx-auto px-4 py-12 text-center">
    <p class="text-xs uppercase tracking-widest text-ink-800 mb-6">Trusted by world-class operators</p>
    <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-6 opacity-80">
        <?php foreach ($partners as $p): ?>
            <a href="<?= vp_safe_html($p['website'] ?? '#') ?>" class="h-9 max-w-[150px]" target="_blank" rel="noopener" aria-label="<?= vp_safe_html($p['name']) ?>">
                <img src="<?= vp_safe_html($p['logo']) ?>" alt="<?= vp_safe_html($p['name']) ?>" class="h-full w-auto max-w-full object-contain grayscale" loading="lazy" decoding="async">
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
