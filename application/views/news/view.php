<?php /** @var array $row */ ?>
<section class="vp-writeup-band bg-white border-b">
    <div class="container mx-auto px-4 py-12 max-w-3xl">
        <div class="text-xs text-brand-700 uppercase tracking-widest"><?= vp_human_date($row['publishedAt']) ?></div>
        <h1 class="text-3xl md:text-4xl font-extrabold mt-2"><?= vp_safe_html($row['title']) ?></h1>
    </div>
</section>
<div class="container mx-auto px-4 max-w-4xl pt-8">
    <img src="<?= vp_safe_html(vp_news_image($row)) ?>" alt="<?= vp_safe_html($row['title']) ?>" class="w-full aspect-[16/8] object-cover rounded-2xl shadow-sm" fetchpriority="high" decoding="async" onerror="this.onerror=null;this.src='<?= IMG_URL ?>hero-industrial.jpg'">
</div>
<section class="container mx-auto px-4 py-10 max-w-3xl vp-prose">
    <?= $row['content'] ?>
</section>
<section class="container mx-auto px-4 py-8 max-w-3xl">
    <a class="vp-btn vp-btn-secondary" href="<?= base_url('news') ?>">&larr; All news</a>
</section>
