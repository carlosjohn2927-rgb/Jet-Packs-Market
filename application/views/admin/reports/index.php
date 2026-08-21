<?php
/** @var array $series */
/** @var array $totals */
$labels = array_keys($series);
$points = array_values($series);
$max = max(1, max($points));
?>
<div class="space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <form method="get" class="flex items-center gap-2">
            <label class="text-sm text-ink-800/70">Period</label>
            <select class="vp-select w-auto" name="days" onchange="this.form.submit()">
                <?php foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last 12 months'] as $d => $l): ?>
                    <option value="<?= $d ?>" <?= $days === $d ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a class="vp-btn vp-btn-secondary ml-auto" href="<?= base_url('admin/reports/export') ?>"><i class="ri-download-2-line"></i> Export quotes (CSV)</a>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ([
            ['Quotes in period', $totals['period'], 'ri-file-list-3-line', 'bg-blue-50 text-blue-700'],
            ['Quotes total', $totals['quotes'], 'ri-stack-line', 'bg-indigo-50 text-indigo-700'],
            ['Messages in period', $contacts, 'ri-mail-line', 'bg-emerald-50 text-emerald-700'],
            ['Customers', $totals['customers'], 'ri-user-line', 'bg-amber-50 text-amber-700'],
        ] as $c): ?>
            <div class="bg-white border rounded-2xl p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl <?= $c[3] ?> flex items-center justify-center"><i class="<?= $c[2] ?> text-xl"></i></div>
                <div>
                    <div class="text-2xl font-extrabold text-ink-900"><?= (int) $c[1] ?></div>
                    <div class="text-xs text-ink-800/60"><?= vp_safe_html($c[0]) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 bg-white border rounded-2xl p-5">
            <h2 class="font-bold text-ink-900 mb-4">Quote requests per day</h2>
            <div class="flex items-end gap-1 h-48">
                <?php foreach ($points as $i => $p): ?>
                    <div class="flex-1 group relative" title="<?= vp_safe_html($labels[$i]) ?>: <?= (int) $p ?>">
                        <div class="bg-brand-500 hover:bg-brand-600 rounded-t" style="height: <?= max(2, round($p / $max * 100)) ?>%"></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-between text-[11px] text-ink-800/50 mt-2">
                <span><?= vp_safe_html(reset($labels)) ?></span>
                <span><?= vp_safe_html(end($labels)) ?></span>
            </div>
        </section>

        <section class="bg-white border rounded-2xl p-5">
            <h2 class="font-bold text-ink-900 mb-4">Quote pipeline</h2>
            <ul class="space-y-2 text-sm">
                <?php foreach (['NEW', 'REVIEWING', 'QUOTED', 'APPROVED', 'REJECTED', 'COMPLETED'] as $st): ?>
                    <li class="flex items-center gap-2">
                        <span class="vp-pill bg-gray-100 text-gray-700"><?= $st ?></span>
                        <span class="ml-auto font-bold"><?= (int) ($by_status[$st] ?? 0) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <section class="bg-white border rounded-2xl p-5">
            <h2 class="font-bold text-ink-900 mb-4">Most viewed products</h2>
            <ul class="space-y-2 text-sm">
                <?php foreach ($top_products as $p): ?>
                    <li class="flex items-center gap-2">
                        <a class="truncate text-brand-700 hover:underline" href="<?= base_url('products/' . $p['slug']) ?>" target="_blank" rel="noopener"><?= vp_safe_html($p['name']) ?></a>
                        <span class="ml-auto text-xs text-ink-800/60"><?= (int) $p['views'] ?> views</span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($top_products)): ?><li class="text-ink-800/60">No products yet.</li><?php endif; ?>
            </ul>
        </section>

        <section class="bg-white border rounded-2xl p-5">
            <h2 class="font-bold text-ink-900 mb-4">Products per category</h2>
            <ul class="space-y-2 text-sm">
                <?php foreach ($categories as $c): ?>
                    <li class="flex items-center gap-2"><?= vp_safe_html($c['name']) ?><span class="ml-auto font-bold"><?= (int) $c['c'] ?></span></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="bg-white border rounded-2xl p-5">
            <h2 class="font-bold text-ink-900 mb-4">Website content</h2>
            <ul class="space-y-2 text-sm">
                <li class="flex"><span>CMS pages</span><span class="ml-auto font-bold"><?= (int) $content['pages'] ?> (<?= (int) $content['published'] ?> live)</span></li>
                <li class="flex"><span>Homepage sections</span><span class="ml-auto font-bold"><?= (int) $content['sections'] ?></span></li>
                <li class="flex"><span>Menu items</span><span class="ml-auto font-bold"><?= (int) $content['menu'] ?></span></li>
                <li class="flex"><span>Media files</span><span class="ml-auto font-bold"><?= (int) $content['media'] ?></span></li>
                <li class="flex"><span>Blog posts</span><span class="ml-auto font-bold"><?= (int) $content['blog'] ?></span></li>
                <li class="flex"><span>News items</span><span class="ml-auto font-bold"><?= (int) $content['news'] ?></span></li>
            </ul>
        </section>
    </div>

    <section class="bg-white border rounded-2xl p-5">
        <h2 class="font-bold text-ink-900 mb-4">Administrator activity in this period</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($admin_activity as $a): ?>
                <span class="vp-pill bg-gray-100 text-gray-700"><?= vp_safe_html($a['action']) ?> · <?= (int) $a['c'] ?></span>
            <?php endforeach; ?>
            <?php if (empty($admin_activity)): ?><span class="text-sm text-ink-800/60">No recorded activity.</span><?php endif; ?>
        </div>
    </section>
</div>
