<?php /** @var string $q */ /** @var array $products */ /** @var array $posts */ /** @var array $faqs */ ?>
<section class="vp-writeup-band bg-white border-b">
    <div class="container mx-auto px-4 py-12">
        <h1 class="text-4xl font-extrabold">Search</h1>
        <form method="get" action="<?= base_url('search') ?>" class="mt-3 flex max-w-xl">
            <input class="vp-input flex-1" name="q" value="<?= vp_safe_html($q) ?>" placeholder="Search products, articles, FAQs…">
            <button class="vp-btn vp-btn-primary" type="submit">Search</button>
        </form>
    </div>
</section>
<section class="container mx-auto px-4 py-10 max-w-4xl">
    <?php if ($q === ''): ?>
        <p class="text-ink-800 text-center py-8">Enter a search term to begin.</p>
    <?php else: ?>
        <?php $total = count($products) + count($posts) + count($faqs); ?>
        <p class="text-sm text-ink-800 mb-6"><?= $total ?> result<?= $total === 1 ? '' : 's' ?> for &ldquo;<?= vp_safe_html($q) ?>&rdquo;.</p>
        <?php if (!empty($products)): ?>
            <h2 class="font-bold text-lg mt-6 mb-3">Products (<?= count($products) ?>)</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                <?php foreach ($products as $p): ?>
                    <a href="<?= base_url('products/' . $p['slug']) ?>" class="vp-card block overflow-hidden hover:shadow">
                        <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                            <?= vp_product_image_tag($p) ?>
                        </div>
                        <div class="vp-card-pad">
                            <div class="text-xs text-ink-800 font-mono font-semibold"><?= vp_safe_html($p['sku']) ?></div>
                            <h3 class="font-semibold mt-1"><?= vp_safe_html($p['name']) ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($posts)): ?>
            <h2 class="font-bold text-lg mt-8 mb-3">Articles (<?= count($posts) ?>)</h2>
            <div class="space-y-2">
                <?php foreach ($posts as $p): ?>
                    <a href="<?= base_url('blog/' . $p['slug']) ?>" class="block vp-card vp-card-pad hover:shadow">
                        <h3 class="font-semibold"><?= vp_safe_html($p['title']) ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($faqs)): ?>
            <h2 class="font-bold text-lg mt-8 mb-3">FAQs (<?= count($faqs) ?>)</h2>
            <div class="space-y-2">
                <?php foreach ($faqs as $f): ?>
                    <details class="vp-card vp-card-pad">
                        <summary class="font-semibold cursor-pointer"><?= vp_safe_html($f['question']) ?></summary>
                        <p class="text-sm text-ink-900 mt-2"><?= nl2br(vp_safe_html($f['answer'])) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($total === 0): ?>
            <p class="text-center text-ink-800 py-12">No results.</p>
        <?php endif; ?>
    <?php endif; ?>
</section>
