<?php
/**
 * Roadmap widget rendered on the SUPER_ADMIN dashboard.
 * Single source of truth with the public /roadmap page (see helpers/app_helper.php).
 * Expects: $roadmap_progress (0..100 int), $roadmap_recent (array of {phase, title, detail}).
 */
?>
<div class="vp-card vp-card-pad mt-6">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <div>
            <h3 class="text-lg font-extrabold text-ink-900">
                <i class="ri-rocket-2-line text-amber-500"></i> Marketplace roadmap
            </h3>
            <p class="text-xs text-ink-800 mt-0.5">What ships next on Halyk Petroleum. Edits to the roadmap are mirrored on the public /roadmap page.</p>
        </div>
        <a href="<?= base_url('roadmap') ?>" target="_blank" rel="noopener" class="text-sm font-semibold text-brand-600 hover:underline">
            View public page &rarr;
        </a>
    </div>

    <div class="flex items-center gap-3 mb-4">
        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-emerald-500" style="width: <?= (int) $roadmap_progress ?>%"></div>
        </div>
        <span class="text-sm font-bold text-ink-900"><?= (int) $roadmap_progress ?>% shipped</span>
    </div>

    <ul class="space-y-2">
        <?php if (empty($roadmap_recent)): ?>
            <li class="text-sm text-ink-800">Nothing shipped yet — the marketplace is just getting started.</li>
        <?php else: foreach ($roadmap_recent as $it): ?>
            <li class="flex items-start gap-2 text-sm">
                <i class="ri-check-line text-emerald-600 mt-0.5"></i>
                <div>
                    <span class="font-semibold text-ink-900"><?= vp_safe_html($it['title']) ?></span>
                    <span class="text-xs uppercase tracking-wide text-ink-800 ml-1">· <?= vp_safe_html($it['phase']) ?></span>
                    <?php if (!empty($it['detail'])): ?>
                        <div class="text-xs text-ink-800 mt-0.5"><?= vp_safe_html(vp_truncate($it['detail'], 140)) ?></div>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; endif; ?>
    </ul>
</div>
