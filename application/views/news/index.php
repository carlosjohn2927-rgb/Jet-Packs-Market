<?php /** @var array $rows */ ?>
<?php $this->load->view('partials/photo_writeup_hero', [
    'hero_image'         => IMG_URL . 'news/iso-quality.jpg',
    'hero_alt'           => 'Quality inspection of manufactured industrial equipment',
    'hero_title_html'    => '<h1 class="text-4xl lg:text-5xl font-extrabold">News</h1>',
    'hero_subtitle_html' => '<p class="mt-3 text-lg">Latest news and announcements.</p>',
]); ?>
<section class="container mx-auto px-4 py-12">
    <?php if (empty($rows)): ?>
        <p class="text-ink-800 text-center py-12">No news yet.</p>
    <?php else: ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($rows as $n): ?>
                <a href="<?= base_url('news/' . $n['slug']) ?>" class="bg-white border rounded-2xl overflow-hidden hover:shadow-lg transition flex flex-col">
                    <div class="aspect-[16/9] bg-gray-100 overflow-hidden"><img src="<?= vp_safe_html(vp_news_image($n)) ?>" alt="<?= vp_safe_html($n['title']) ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='<?= IMG_URL ?>hero-industrial.jpg'"></div>
                    <div class="p-5 flex-1 flex flex-col">
                        <div class="text-xs text-ink-800"><?= vp_human_date($n['publishedAt']) ?></div>
                        <h3 class="font-bold text-lg text-ink-900 mt-2"><?= vp_safe_html($n['title']) ?></h3>
                        <p class="text-sm text-ink-800 mt-2 flex-1"><?= vp_safe_html(vp_truncate($n['summary'] ?? $n['content'], 140)) ?></p>
                        <span class="text-brand-600 text-sm font-semibold mt-3">Read more &rarr;</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="mt-8 flex justify-center"><?= vp_pagination_links($total_pages, $page, $base_url) ?></div>
    <?php endif; ?>
</section>
