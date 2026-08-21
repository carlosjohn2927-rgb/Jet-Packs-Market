<?php
$seg = (string) $this->uri->segment(3);
$tab = $seg === 'header' ? 'header' : ($seg === 'colors' ? 'colors' : 'branding');
$tabs = [
    'branding' => ['Logo & branding', 'admin/appearance',          'ri-image-2-line'],
    'header'   => ['Header & footer', 'admin/appearance/header',   'ri-layout-top-line'],
    'colors'   => ['Colours',         'admin/appearance/colors',   'ri-contrast-drop-2-line'],
];
?>
<nav class="flex flex-wrap gap-2 mb-6" aria-label="Appearance">
    <?php foreach ($tabs as $key => $def): ?>
        <a href="<?= base_url($def[1]) ?>"
           class="vp-tab <?= $tab === $key ? 'vp-tab-active' : '' ?>">
            <i class="<?= vp_safe_html($def[2]) ?>"></i> <?= vp_safe_html($def[0]) ?>
        </a>
    <?php endforeach; ?>
</nav>
