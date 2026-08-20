<?php
/** @var array $theme */
/** @var array $defaults */
$theme    = $theme ?? vp_theme();
$defaults = $defaults ?? vp_theme_defaults();
?>
<div class="max-w-5xl space-y-6">
    <?php $this->load->view('admin/appearance/_tabs'); ?>

    <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-xl px-4 py-3 text-sm flex gap-2">
        <i class="ri-information-line text-lg"></i>
        <span>These colours apply to the <strong>entire public website</strong> and both Admin dashboards.
            The sidebar menu defaults to a <strong>black</strong> background with <strong>white</strong> write-up.
            Changes are live as soon as you save.</span>
    </div>

    <form method="post" action="<?= base_url('admin/appearance/save_colors') ?>" class="space-y-6" id="vp-theme-form">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">

        <?= vp_admin_card_open('Website colours', 'Background and write-up used across the public site and dashboard content', 'ri-contrast-drop-2-line') ?>
            <div class="grid md:grid-cols-2 gap-5">
                <?= vp_color_field('theme_bg', $theme['bg'], 'Background colour', 'Page canvas, light panels and cards.', '--vp-bg') ?>
                <?= vp_color_field('theme_writeup', $theme['writeup'], 'Write-up colour', 'Body copy, headings and labels on light surfaces.', '--vp-writeup') ?>
            </div>
            <div class="rounded-xl border p-4 mt-2" data-vp-preview-surface>
                <div class="text-xs uppercase tracking-widest mb-2" data-vp-preview-writeup>Live preview</div>
                <p class="text-sm leading-relaxed" data-vp-preview-writeup>
                    This is how write-up will read on the website. Headings, paragraphs and labels
                    use the write-up colour; the panel behind them uses the background colour.
                </p>
            </div>
        <?= vp_admin_card_close() ?>

        <?= vp_admin_card_open('Admin sidebar', 'Menu on the Admin and Super Admin dashboards', 'ri-layout-left-line') ?>
            <div class="grid md:grid-cols-2 gap-5">
                <?= vp_color_field('theme_sidebar_bg', $theme['sidebar_bg'], 'Sidebar background', 'Default is black (#000000).', '--vp-sidebar-bg') ?>
                <?= vp_color_field('theme_sidebar_writeup', $theme['sidebar_writeup'], 'Sidebar write-up', 'Menu labels and icons. Default is white (#ffffff).', '--vp-sidebar-writeup') ?>
            </div>
            <div class="mt-2 rounded-xl overflow-hidden border w-56" data-vp-preview-sidebar>
                <div class="px-4 py-3 text-[10px] uppercase tracking-widest opacity-80">Super Admin</div>
                <div class="px-3 pb-3 space-y-1 text-sm">
                    <div class="px-3 py-2 rounded-lg bg-brand-600 text-white">Dashboard</div>
                    <div class="px-3 py-2 rounded-lg">Quotes</div>
                    <div class="px-3 py-2 rounded-lg">Products</div>
                    <div class="px-3 py-2 rounded-lg">Colours</div>
                </div>
            </div>
        <?= vp_admin_card_close() ?>

        <div class="flex flex-wrap gap-3">
            <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save colours</button>
            <button class="vp-btn vp-btn-secondary" type="submit" name="reset" value="1"
                    data-confirm="Reset background, write-up and sidebar colours to the defaults?">
                <i class="ri-refresh-line"></i> Reset to defaults
            </button>
            <a class="vp-btn vp-btn-secondary" href="<?= base_url() ?>" target="_blank" rel="noopener">
                <i class="ri-external-link-line"></i> View website
            </a>
        </div>
        <p class="text-xs text-ink-800/60">
            Defaults: background <?= vp_safe_html($defaults['bg']) ?>,
            write-up <?= vp_safe_html($defaults['writeup']) ?>,
            sidebar <?= vp_safe_html($defaults['sidebar_bg']) ?> /
            <?= vp_safe_html($defaults['sidebar_writeup']) ?>.
        </p>
    </form>
</div>
