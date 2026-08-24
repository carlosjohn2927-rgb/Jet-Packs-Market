<?php
/**
 * Public site header — JetPacks Market.
 * Logo, navigation, parts search and contact CTA. Every element is managed
 * from the dashboard:
 *   logo      → Website → Logo & branding
 *   menu      → Website → Navigation (header)
 *   CTA/topbar→ Website → Header & footer
 */
$site  = vp_site();
$user  = $current_user ?? null;
$menu  = vp_menu('header');

// Built-in fallback so the site is still navigable if the menu table is empty.
if (empty($menu)) {
    foreach ([['Parts', 'products'], ['Aircraft', 'industries'], ['Services', 'services'],
              ['About', 'about'], ['Blog', 'blog'], ['FAQ', 'faq'],
              ['Downloads', 'downloads'], ['Contact', 'contact']] as $m) {
        $menu[] = ['label' => $m[0], 'href' => base_url($m[1]), 'target' => '_self', 'icon' => null];
    }
}
?>
<?php if ($site['topbar_enabled'] && $site['topbar_text']): ?>
    <div class="jpm-topbar" role="marquee" aria-live="off">
        <div class="jpm-topbar-track">
            <?php for ($__i = 0; $__i < 6; $__i++): ?>
            <span class="jpm-topbar-item">
                <i class="ri-flight-takeoff-line"></i>
                <?= vp_safe_html($site['topbar_text']) ?>
                <?php if ($site['phone']): ?>
                    <a href="tel:<?= vp_safe_html(preg_replace('/[^0-9+]/', '', $site['phone'])) ?>"><i class="ri-phone-line"></i> <?= vp_safe_html($site['phone']) ?></a>
                <?php endif; ?>
                <a href="<?= base_url('rfq') ?>"><i class="ri-quote-text"></i> Request a Quote</a>
            </span>
            <?php endfor; ?>
        </div>
    </div>
<?php endif; ?>

<header class="bg-white border-b sticky top-0 z-40 shadow-sm">
    <div class="container mx-auto px-4 py-3 flex items-center gap-6 jpm-header-inner">
        <a href="<?= base_url() ?>" class="flex items-center flex-shrink-0" aria-label="<?= vp_safe_html($site['name']) ?> home">
            <img src="<?= vp_safe_html(vp_logo_url('light')) ?>" alt="<?= vp_safe_html($site['logo_alt']) ?>"
                 class="w-auto object-contain max-w-[200px] md:max-w-[230px]"
                 style="height: <?= (int) $site['logo_height'] ?>px" decoding="async">
        </a>

        <nav class="hidden lg:flex items-center gap-5 text-sm font-semibold text-ink-900 ml-4 jpm-nav">
            <?php foreach ($menu as $item): ?>
                <a class="hover:text-brand-600 <?= vp_menu_is_active($item) ? 'text-brand-600' : '' ?>"
                   href="<?= vp_safe_html($item['href']) ?>" <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
                    <?= vp_safe_html($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="ml-auto flex items-center gap-3">
            <a href="<?= base_url('rfq') ?>"
               class="hidden sm:inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-ink-900 text-sm font-bold px-4 py-2 rounded-lg border border-amber-600">
                <i class="ri-quote-text"></i> Request a Quote
            </a>

            <?php if ($user): ?>
                <?php if (!empty($is_admin)): ?>
                    <a href="<?= base_url('admin') ?>" class="text-sm font-medium text-ink-900 hover:text-brand-600"><i class="ri-dashboard-line"></i> Dashboard</a>
                <?php else: ?>
                    <a href="<?= base_url('account') ?>" class="text-sm font-medium text-ink-900 hover:text-brand-600"><i class="ri-user-line"></i> My account</a>
                <?php endif; ?>
                <a href="<?= base_url('logout') ?>" class="text-sm text-ink-800 hover:text-red-600">Sign out</a>
            <?php else: ?>
                <a href="<?= base_url('login') ?>" class="text-sm font-semibold text-ink-900 hover:text-brand-600">Sign in</a>
            <?php endif; ?>

            <button class="lg:hidden p-2" id="vp-mobile-toggle" aria-label="Menu"><i class="ri-menu-line text-2xl"></i></button>
        </div>
    </div>

    <!-- Parts search strip -->
    <div class="border-t bg-gray-50">
        <div class="container mx-auto px-4 py-2.5">
            <form method="get" action="<?= base_url('products') ?>" class="jpm-search">
                <i class="ri-search-line text-xl text-brand-600"></i>
                <input type="search" name="q" placeholder="Search by part number, name or manufacturer — e.g. 2612201-2, brake, Honeywell…" aria-label="Search parts">
                <button type="submit">Search parts</button>
            </form>
        </div>
    </div>

    <div class="lg:hidden hidden border-t bg-white" id="vp-mobile-menu">
        <nav class="px-4 py-3 flex flex-col gap-2 text-sm font-semibold">
                <?php foreach ($menu as $item): ?>
                    <a href="<?= vp_safe_html($item['href']) ?>" <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>><?= vp_safe_html($item['label']) ?></a>
                <?php endforeach; ?>
                <?php if ($user && empty($is_admin)): ?>
                    <a href="<?= base_url('account') ?>"><i class="ri-user-line"></i> My account</a>
                <?php endif; ?>
                <a class="text-amber-600 font-bold" href="<?= base_url('rfq') ?>"><i class="ri-quote-text"></i> Request a Quote</a>
        </nav>
    </div>
</header>
