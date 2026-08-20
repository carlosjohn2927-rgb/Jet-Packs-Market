<?php
/**
 * Public site header — logo, navigation, contact CTA.
 * Every element is managed from the dashboard:
 *   logo      → Website → Logo & branding
 *   menu      → Website → Navigation (header)
 *   CTA/topbar→ Website → Header & footer
 */
$site  = vp_site();
$user  = $current_user ?? null;
$menu  = vp_menu('header');

// Built-in fallback so the site is still navigable if the menu table is empty.
if (empty($menu)) {
    foreach ([['Products', 'products'], ['Industries', 'industries'], ['Services', 'services'],
              ['About', 'about'], ['Blog', 'blog'], ['Careers', 'careers'], ['FAQ', 'faq'],
              ['Downloads', 'downloads'], ['Contact', 'contact']] as $m) {
        $menu[] = ['label' => $m[0], 'href' => base_url($m[1]), 'target' => '_self', 'icon' => null];
    }
}
?>
<?php if ($site['topbar_enabled'] && $site['topbar_text']): ?>
    <div class="bg-ink-900 text-white text-sm">
        <div class="container mx-auto px-4 py-2 flex flex-wrap items-center gap-3">
            <span><?= vp_safe_html($site['topbar_text']) ?></span>
            <?php if ($site['phone']): ?>
                <a class="ml-auto hover:underline" href="tel:<?= vp_safe_html(preg_replace('/[^0-9+]/', '', $site['phone'])) ?>"><i class="ri-phone-line"></i> <?= vp_safe_html($site['phone']) ?></a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<header class="bg-white border-b sticky top-0 z-40">
    <div class="container mx-auto px-4 py-3 flex items-center gap-6">
        <a href="<?= base_url() ?>" class="flex items-center flex-shrink-0" aria-label="<?= vp_safe_html($site['name']) ?> home">
            <img src="<?= vp_safe_html(vp_logo_url('light')) ?>" alt="<?= vp_safe_html($site['logo_alt']) ?>"
                 class="w-auto object-contain max-w-[190px] md:max-w-[220px]"
                 style="height: <?= (int) $site['logo_height'] ?>px" decoding="async">
        </a>

        <nav class="hidden lg:flex items-center gap-5 text-sm font-medium text-ink-900 ml-4">
            <?php foreach ($menu as $item): ?>
                <a class="hover:text-brand-600 <?= vp_menu_is_active($item) ? 'text-brand-600' : '' ?>"
                   href="<?= vp_safe_html($item['href']) ?>" <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
                    <?php if (!empty($item['icon'])): ?><i class="<?= vp_safe_html($item['icon']) ?>"></i> <?php endif; ?>
                    <?= vp_safe_html($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="ml-auto flex items-center gap-3">
            <?php if ($site['header_cta_enabled'] && $site['header_cta_label']): ?>
                <a href="<?= vp_safe_html(preg_match('~^https?://~i', (string) $site['header_cta_url']) ? $site['header_cta_url'] : base_url(ltrim((string) $site['header_cta_url'], '/'))) ?>"
                   class="hidden sm:inline-flex items-center gap-1 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    <i class="ri-quote-text"></i> <?= vp_safe_html($site['header_cta_label']) ?>
                </a>
            <?php endif; ?>

            <?php if ($user): ?>
                <?php if (!empty($is_admin)): ?>
                    <a href="<?= base_url('admin') ?>" class="text-sm font-medium text-ink-900 hover:text-brand-600"><i class="ri-dashboard-line"></i> Dashboard</a>
                <?php endif; ?>
                <a href="<?= base_url('logout') ?>" class="text-sm text-ink-800 hover:text-red-600">Sign out</a>
            <?php else: ?>
                <a href="<?= base_url('login') ?>" class="text-sm font-medium text-ink-900 hover:text-brand-600">Sign in</a>
            <?php endif; ?>

            <button class="lg:hidden p-2" id="vp-mobile-toggle" aria-label="Menu"><i class="ri-menu-line text-2xl"></i></button>
        </div>
    </div>

    <div class="lg:hidden hidden border-t bg-white" id="vp-mobile-menu">
        <nav class="px-4 py-3 flex flex-col gap-2 text-sm font-medium">
            <?php foreach ($menu as $item): ?>
                <a href="<?= vp_safe_html($item['href']) ?>" <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>><?= vp_safe_html($item['label']) ?></a>
            <?php endforeach; ?>
            <?php if ($site['header_cta_enabled'] && $site['header_cta_label']): ?>
                <a class="text-brand-600 font-semibold" href="<?= base_url(ltrim((string) $site['header_cta_url'], '/')) ?>"><?= vp_safe_html($site['header_cta_label']) ?></a>
            <?php endif; ?>
        </nav>
    </div>
</header>
