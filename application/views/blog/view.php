<?php
/** @var array $post */
/** @var array|null $author */
?>
<section class="vp-writeup-band bg-white border-b">
    <div class="container mx-auto px-4 py-12 max-w-3xl">
        <div class="text-xs text-brand-700 uppercase tracking-widest">
            <?= vp_human_date($post['publishedAt']) ?>
            <?php if (!empty($post['category'])): ?> &middot; <?= vp_safe_html($post['category']) ?><?php endif; ?>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold mt-2"><?= vp_safe_html($post['title']) ?></h1>
        <?php if (!empty($post['excerpt'])): ?>
            <p class="mt-3 text-lg"><?= vp_safe_html($post['excerpt']) ?></p>
        <?php endif; ?>
    </div>
</section>
<div class="container mx-auto px-4 max-w-4xl -mt-1 pt-8">
    <img src="<?= vp_safe_html(vp_blog_image($post)) ?>" alt="<?= vp_safe_html($post['title']) ?>" class="w-full aspect-[16/8] object-cover rounded-2xl shadow-sm" fetchpriority="high" decoding="async" onerror="this.onerror=null;this.src='<?= IMG_URL ?>products/default.jpg'">
</div>
<section class="container mx-auto px-4 py-10 max-w-3xl vp-prose">
    <?= $post['content'] ?>
</section>
<section class="container mx-auto px-4 py-8 max-w-3xl flex items-center justify-between gap-4">
    <a class="vp-btn vp-btn-secondary" href="<?= base_url('blog') ?>">&larr; All articles</a>
    <?php if ($author): ?>
        <div class="text-sm text-ink-800">By <?= vp_safe_html(trim($author['firstName'] . ' ' . $author['lastName'])) ?></div>
    <?php endif; ?>
</section>
