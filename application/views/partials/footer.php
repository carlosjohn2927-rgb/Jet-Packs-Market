<?php
/**
 * Public site footer — logo, footer menus, contact block, social links and
 * copyright. All content comes from the dashboard (Website → Header & footer,
 * Website → Navigation, Settings → Contact/Social).
 */
$site   = vp_site();
$social = vp_social_links();

$columns = [
    'Solutions' => vp_menu('footer_solutions'),
    'Company'   => vp_menu('footer_company'),
];
$legal = vp_menu('footer_legal');

// Fallbacks so the footer is never empty on a fresh install.
if (empty($columns['Solutions'])) {
    foreach ([['Products', 'products'], ['Industries', 'industries'], ['Services', 'services'], ['Request a Quote', 'rfq']] as $m) {
        $columns['Solutions'][] = ['label' => $m[0], 'href' => base_url($m[1]), 'target' => '_self'];
    }
}
if (empty($columns['Company'])) {
    foreach ([['About', 'about'], ['Blog', 'blog'], ['Careers', 'careers'], ['Contact', 'contact']] as $m) {
        $columns['Company'][] = ['label' => $m[0], 'href' => base_url($m[1]), 'target' => '_self'];
    }
}
$copyright = $site['copyright'] ?: ('© ' . date('Y') . ' ' . $site['name'] . '. All rights reserved.');
?>
<footer class="bg-black text-white mt-16">
    <div class="container mx-auto px-4 py-12 grid md:grid-cols-4 gap-8">
        <div>
            <a href="<?= base_url() ?>" class="inline-block mb-4" aria-label="<?= vp_safe_html($site['name']) ?> home">
                <img src="<?= vp_safe_html(vp_logo_url('footer')) ?>" alt="<?= vp_safe_html($site['logo_alt']) ?>"
                     class="h-11 w-auto max-w-[240px] object-contain" loading="lazy" decoding="async">
            </a>
            <p class="text-sm text-white"><?= vp_safe_html($site['footer_about'] ?: $site['tagline']) ?></p>
        </div>

        <?php foreach ($columns as $title => $items): ?>
            <div>
                <h4 class="text-white font-semibold mb-3"><?= vp_safe_html($title) ?></h4>
                <ul class="space-y-2 text-sm">
                    <?php foreach ($items as $it): ?>
                        <li>
                            <a class="hover:text-white" href="<?= vp_safe_html($it['href']) ?>"
                               <?= ($it['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>><?= vp_safe_html($it['label']) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>

        <div>
            <h4 class="text-white font-semibold mb-3">Contact</h4>
            <ul class="space-y-2 text-sm">
                <?php if ($site['address']): ?><li><i class="ri-map-pin-line"></i> <?= vp_safe_html($site['address']) ?></li><?php endif; ?>
                <?php if ($site['phone']): ?><li><i class="ri-phone-line"></i> <a class="hover:text-white" href="tel:<?= vp_safe_html(preg_replace('/[^0-9+]/', '', $site['phone'])) ?>"><?= vp_safe_html($site['phone']) ?></a></li><?php endif; ?>
                <?php if ($site['email']): ?><li><i class="ri-mail-line"></i> <a class="hover:text-white" href="mailto:<?= vp_safe_html($site['email']) ?>"><?= vp_safe_html($site['email']) ?></a></li><?php endif; ?>
                <?php if ($site['hours']): ?><li><i class="ri-time-line"></i> <?= vp_safe_html($site['hours']) ?></li><?php endif; ?>
            </ul>
            <?php if ($social): ?>
                <div class="mt-5">
                    <h5 class="sr-only">Social media</h5>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($social as $network => $url): ?>
                            <a class="w-9 h-9 rounded-full border border-white/25 bg-white/10 hover:bg-white hover:text-black transition inline-flex items-center justify-center text-xl"
                               href="<?= vp_safe_html($url) ?>" rel="noopener" target="_blank" aria-label="<?= vp_safe_html(ucfirst($network)) ?>">
                                <i class="<?= vp_safe_html(vp_social_icon($network)) ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="border-t border-white/20">
        <div class="container mx-auto px-4 py-4 text-xs text-white flex flex-col md:flex-row justify-between gap-2">
            <div><?= vp_safe_html($copyright) ?></div>
            <div class="flex flex-wrap gap-4">
                <?php foreach ($legal as $it): ?>
                    <a class="hover:underline" href="<?= vp_safe_html($it['href']) ?>"><?= vp_safe_html($it['label']) ?></a>
                <?php endforeach; ?>
                <?php if ($site['footer_note']): ?><span class="text-white/70"><?= vp_safe_html($site['footer_note']) ?></span><?php endif; ?>
            </div>
        </div>
    </div>
</footer>
