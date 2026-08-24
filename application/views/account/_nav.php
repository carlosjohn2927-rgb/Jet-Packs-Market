<?php
/** @var string $account_section */
$section = $account_section ?? '';
$me = $current_user ?? null;
$link = function ($key, $url, $label, $icon) use ($section) {
    $active = $section === $key;
    $cls = 'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold transition '
         . ($active ? 'bg-brand-50 text-brand-700' : 'text-ink-900 hover:bg-gray-100');
    return '<a class="' . $cls . '" href="' . base_url($url) . '"><i class="' . $icon . '"></i> ' . $label . '</a>';
};
?>
<aside class="lg:col-span-1">
    <div class="vp-card vp-card-pad space-y-1 lg:sticky lg:top-6">
        <?= $link('dashboard', 'account', 'Dashboard', 'ri-dashboard-line') ?>
        <?= $link('quotes', 'account/quotes', 'Orders &amp; quotes', 'ri-file-list-3-line') ?>
        <?= $link('invoices', 'account/invoices', 'Invoices', 'ri-receipt-line') ?>
        <?= $link('dispatches', 'account/dispatches', 'AOG dispatches', 'ri-truck-line') ?>
        <?= $link('profile', 'account/profile', 'Profile', 'ri-user-line') ?>
        <div class="pt-2 mt-2 border-t border-gray-200">
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-ink-800 hover:text-red-600" href="<?= base_url('logout') ?>"><i class="ri-logout-box-line"></i> Sign out</a>
        </div>
    </div>

    <?php if ($me): ?>
    <div class="mt-4 vp-card vp-card-pad text-sm text-ink-800">
        <p class="font-semibold text-ink-900"><?= vp_safe_html(trim(($me['firstName'] ?? '') . ' ' . ($me['lastName'] ?? ''))) ?></p>
        <p><?= vp_safe_html($me['email'] ?? '') ?></p>
        <?php if (!empty($me['company'])): ?><p class="text-ink-800"><?= vp_safe_html($me['company']) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
</aside>
