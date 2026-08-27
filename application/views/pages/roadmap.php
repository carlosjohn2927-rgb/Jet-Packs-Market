<?php
/**
 * 🚀 Marketplace roadmap — public-facing page (and reusable partial).
 *
 * Asset status:
 *   - $phases   Array of phases, each ['name' => ..., 'items' => [['title','detail','status','done'], ...]]
 *   - $progress Whole-number percentage 0..100 derived from $phases (returned by controller).
 *
 * Called from the public /roadmap route (uses the standard public layout).
 * The same data struct is also displayed in the admin dashboard widget.
 */

$progress = isset($progress) ? (int) $progress : 0;
$GLOBALS['__roadmap_phase_total'] = 0;
$GLOBALS['__roadmap_phase_done']  = 0;
?>
<section class="bg-white border-b">
    <div class="container mx-auto px-4 py-12">
        <span class="text-xs font-bold tracking-widest uppercase text-amber-500">Marketplace roadmap</span>
        <h1 class="text-4xl lg:text-5xl font-extrabold mt-2 text-ink-900">What we've shipped. What's next.</h1>
        <p class="text-ink-800 mt-3 max-w-2xl">An honest, public roadmap for the Halyk Petroleum platform. Items marked
            <strong>Shipped</strong> are live in production everywhere; <strong>Building</strong> is in active development;
            <strong>Planned</strong> is on the public roadmap.</p>

        <div class="mt-8 max-w-2xl">
            <div class="flex items-center justify-between mb-2 text-sm font-semibold">
                <span class="text-ink-900"><?= $progress ?>% shipped</span>
                <span class="text-ink-800"><span class="inline-block w-3 h-3 rounded-full bg-emerald-500 mr-1"></span>Shipped
                <span class="inline-block w-3 h-3 rounded-full bg-amber-500 ml-3 mr-1"></span>Building
                <span class="inline-block w-3 h-3 rounded-full bg-gray-300 ml-3 mr-1"></span>Planned</span>
            </div>
            <div class="h-3 w-full bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 transition-all" style="width: <?= $progress ?>%"></div>
            </div>
        </div>
    </div>
</section>

<section class="container mx-auto px-4 py-10 space-y-10">
    <?php foreach (($phases ?? []) as $idx => $phase): ?>
        <?php
        // Count completed/total once per phase for the header summary.
        $total = is_array($phase['items'] ?? null) ? count($phase['items']) : 0;
        $done  = 0;
        foreach (($phase['items'] ?? []) as $it) if (!empty($it['done'])) $done++;
        $GLOBALS['__roadmap_phase_total'] += $total;
        $GLOBALS['__roadmap_phase_done']  += $done;
        ?>
        <div class="vp-card vp-card-pad">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <h2 class="text-2xl font-extrabold text-ink-900">
                    <span class="inline-block bg-brand-600 text-white text-xs font-bold px-2 py-1 rounded mr-2 align-middle">Phase <?= $idx + 1 ?></span>
                    <?= vp_safe_html($phase['name']) ?>
                </h2>
                <span class="text-sm font-semibold text-ink-800"><?= $done ?> / <?= $total ?> complete</span>
            </div>

            <ul class="space-y-3">
                <?php foreach (($phase['items'] ?? []) as $it):
                    $status = strtolower($it['status'] ?? 'planned');
                    $dotClass = $status === 'shipped'  ? 'bg-emerald-500' : ($status === 'building' ? 'bg-amber-500' : 'bg-gray-300');
                    $badgeClass = $status === 'shipped' ? 'bg-emerald-100 text-emerald-800' : ($status === 'building' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700');
                    $iconRi = $status === 'shipped' ? 'ri-check-line' : ($status === 'building' ? 'ri-hammer-line' : 'ri-time-line');
                    ?>
                    <li class="flex items-start gap-3">
                        <span class="inline-flex <?= $dotClass ?> w-5 h-5 rounded-full items-center justify-center text-white text-xs flex-shrink-0 mt-0.5">
                            <i class="<?= $iconRi ?>"></i>
                        </span>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-ink-900"><?= vp_safe_html($it['title'] ?? '') ?></span>
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                            </div>
                            <?php if (!empty($it['detail'])): ?>
                                <div class="text-sm text-ink-800 mt-0.5"><?= nl2br(vp_safe_html($it['detail'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>

    <div class="text-center">
        <a class="vp-btn vp-btn-primary" href="<?= base_url('products') ?>"><i class="ri-shopping-bag-3-line"></i> Browse parts</a>
        <a class="vp-btn vp-btn-secondary ml-2" href="<?= base_url('rfq') ?>"><i class="ri-quote-text"></i> Request a quote</a>
    </div>
</section>
