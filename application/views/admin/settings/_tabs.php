<?php /** @var array $tabs */ /** @var string $tab */ ?>
<div class="bg-white border rounded-2xl p-3 mb-5 flex flex-wrap items-center gap-2">
    <?php foreach ($tabs as $key => $t): ?>
        <a class="vp-tab <?= $key === $tab ? 'vp-tab-active' : '' ?>" href="<?= base_url($t['url']) ?>">
            <i class="<?= vp_safe_html($t['icon']) ?>"></i> <?= vp_safe_html($t['label']) ?>
        </a>
    <?php endforeach; ?>
    <a class="vp-btn vp-btn-secondary ml-auto" href="<?= base_url() ?>" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> View website</a>
</div>
