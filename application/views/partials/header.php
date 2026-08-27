<?php
/**
 * Public site header — Halyk Petroleum.
 *
 * Element order (desktop and mobile alike):
 *   Logo → Search button → Parts → Industries → Blog → FAQ → … → Request a Quote
 *
 * The Search button opens the site search panel (a real, working form that
 * searches parts/products, blog articles and FAQs). It is a native <button>,
 * keyboard accessible, with visible focus states and aria-expanded state.
 *
 * Every text label is dashboard-managed (Website → Navigation / Header & footer).
 */
$site  = vp_site();
$user  = $current_user ?? null;
$menu  = vp_menu('header');

// Built-in fallback so the site is still navigable if the menu table is empty.
// Order matters: Parts first, then Industries, Blog, FAQ, then the rest.
if (empty($menu)) {
    foreach ([['Parts', 'products'], ['Industries', 'industries'], ['Blog', 'blog'],
              ['FAQ', 'faq'], ['Services', 'services'], ['About', 'about'],
              ['Downloads', 'downloads'], ['Contact', 'contact']] as $m) {
        $menu[] = ['label' => $m[0], 'href' => base_url($m[1]), 'target' => '_self', 'icon' => null];
    }
}

$search_q = trim((string) $this->input->get('q', true));
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

<header class="bg-white border-b sticky top-0 z-40 shadow-sm jpm-site-header">
    <div class="container mx-auto px-4 py-3 flex items-center gap-3 md:gap-5 jpm-header-inner">
        <a href="<?= base_url() ?>" class="flex items-center flex-shrink-0 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2" aria-label="<?= vp_safe_html($site['name']) ?> — home">
            <img src="<?= vp_safe_html(vp_logo_url('light')) ?>" alt="<?= vp_safe_html($site['logo_alt']) ?>"
                 class="w-auto object-contain max-w-[170px] md:max-w-[220px]"
                 style="height: <?= (int) $site['logo_height'] ?>px" decoding="async">
        </a>

        <?php /* Search button — immediately after the logo, before Parts. */ ?>
        <button type="button" id="jpm-search-toggle"
                class="jpm-search-toggle inline-flex items-center justify-center gap-2 p-2 rounded-lg text-ink-900 hover:text-brand-600 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2"
                aria-haspopup="true" aria-expanded="false" aria-controls="jpm-search-panel"
                aria-label="Search parts and content">
            <i class="ri-search-line text-2xl" aria-hidden="true"></i>
            <span class="hidden md:inline text-sm font-semibold">Search</span>
        </button>

        <nav class="hidden lg:flex items-center gap-5 text-sm font-semibold text-ink-900 jpm-nav" aria-label="Main navigation">
            <?php foreach ($menu as $item): ?>
                <a class="hover:text-brand-600 rounded focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-1 <?= vp_menu_is_active($item) ? 'text-brand-600' : '' ?>"
                   href="<?= vp_safe_html($item['href']) ?>" <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
                    <?= vp_safe_html($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="ml-auto flex items-center gap-3">
            <a href="<?= base_url('rfq') ?>"
               class="hidden sm:inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-ink-900 text-sm font-bold px-4 py-2 rounded-lg border border-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2">
                <i class="ri-quote-text"></i> Request a Quote
            </a>

            <?php if ($user): ?>
                <?php if (!empty($is_admin)): ?>
                    <a href="<?= base_url('admin') ?>" class="hidden sm:inline text-sm font-medium text-ink-900 hover:text-brand-600"><i class="ri-dashboard-line"></i> Dashboard</a>
                <?php else: ?>
                    <a href="<?= base_url('account') ?>" class="hidden sm:inline text-sm font-medium text-ink-900 hover:text-brand-600"><i class="ri-user-line"></i> My account</a>
                <?php endif; ?>
                <a href="<?= base_url('logout') ?>" class="hidden sm:inline text-sm text-ink-800 hover:text-red-600">Sign out</a>
            <?php else: ?>
                <a href="<?= base_url('login') ?>" class="hidden sm:inline text-sm font-semibold text-ink-900 hover:text-brand-600">Sign in</a>
            <?php endif; ?>

            <button type="button" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-600" id="vp-mobile-toggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="vp-mobile-menu">
                <i class="ri-menu-line text-2xl" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <?php /* Search panel — revealed by the Search button. Real form, posts to /search. */ ?>
    <div id="jpm-search-panel" class="hidden border-t bg-gray-50 jpm-search-panel" role="search" aria-label="Site search">
        <div class="container mx-auto px-4 py-4">
            <form method="get" action="<?= base_url('search') ?>" class="jpm-search-form flex flex-col sm:flex-row gap-3">
                <label class="flex-1 flex items-center gap-3 bg-white border-2 border-brand-600 rounded-xl px-4 py-3 shadow-sm">
                    <i class="ri-search-line text-xl text-brand-600" aria-hidden="true"></i>
                    <span class="sr-only">Search parts, part numbers, manufacturers, articles and answers</span>
                    <input type="search" name="q" id="jpm-search-input"
                           value="<?= vp_safe_html($search_q) ?>"
                           placeholder="Search part number, name or manufacturer — e.g. 2612201-2, brake, Honeywell…"
                           aria-label="Search parts and content"
                           class="w-full bg-transparent outline-none text-base text-ink-900 placeholder:text-ink-800/60"
                           autocomplete="off">
                </label>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-500 text-white font-bold px-6 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2">
                    <i class="ri-search-line" aria-hidden="true"></i> Search
                </button>
            </form>
            <p class="mt-2 text-xs text-ink-800">
                Searches our aircraft parts catalog, blog articles and support answers.
                Need a quote? <a class="text-brand-600 font-semibold hover:underline" href="<?= base_url('rfq') ?>">Submit an RFQ</a>.
            </p>
        </div>
    </div>

    <div class="lg:hidden hidden border-t bg-white" id="vp-mobile-menu">
        <nav class="px-4 py-3 flex flex-col gap-2 text-sm font-semibold" aria-label="Mobile navigation">
                <a href="<?= base_url('search') ?>" class="inline-flex items-center gap-2 py-1.5 text-brand-700">
                    <i class="ri-search-line text-lg"></i> Search parts
                </a>
                <?php foreach ($menu as $item): ?>
                    <a class="py-1.5" href="<?= vp_safe_html($item['href']) ?>" <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>><?= vp_safe_html($item['label']) ?></a>
                <?php endforeach; ?>
                <?php if ($user && empty($is_admin)): ?>
                    <a href="<?= base_url('account') ?>" class="py-1.5"><i class="ri-user-line"></i> My account</a>
                <?php endif; ?>
                <a class="text-amber-700 font-bold py-1.5" href="<?= base_url('rfq') ?>"><i class="ri-quote-text"></i> Request a Quote</a>
        </nav>
    </div>
</header>
