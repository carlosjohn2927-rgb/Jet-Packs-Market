<?php
/**
 * Admin dashboard sidebar.
 *
 * Contents come from application/config/admin_nav.php filtered by the
 * signed-in account's permissions (see Admin_Controller::_nav()), so an
 * Admin only sees what the Super Admin granted.
 *
 * The logo is a link to the public homepage (opens in a new tab so the
 * dashboard session is never lost).
 */
$nav        = $admin_nav ?? [];
$seg1       = $this->uri->segment(2) ?: 'dashboard';
$notif      = (int) ($unread_notifications ?? 0);
$is_super   = !empty($is_super_admin);
$logo       = vp_logo_url('dark');
?>
<aside class="vp-admin-sidebar bg-black text-white w-64 flex-shrink-0 hidden md:flex md:flex-col" id="vp-admin-sidebar">
    <div class="p-4 border-b border-white/10">
        <a href="<?= base_url() ?>" target="_blank" rel="noopener"
           class="block group" title="Open the public website in a new tab">
            <img src="<?= vp_safe_html($logo) ?>" alt="<?= vp_safe_html(vp_site('logo_alt', vp_site('name'))) ?>"
                 class="h-9 w-auto max-w-[200px] object-contain group-hover:opacity-80 transition" decoding="async">
        </a>
        <div class="text-[10px] uppercase tracking-widest text-white/70 mt-2 flex items-center gap-1">
            <?php if ($is_super): ?>
                <i class="ri-shield-star-line text-amber-400"></i> Super Admin
            <?php else: ?>
                <i class="ri-shield-user-line text-brand-300"></i> Administration
            <?php endif; ?>
        </div>
    </div>

    <div class="px-3 pt-3">
        <a href="<?= base_url() ?>" target="_blank" rel="noopener"
           class="flex items-center justify-center gap-2 w-full px-3 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold">
            <i class="ri-home-4-line"></i> Homepage
            <i class="ri-external-link-line text-xs opacity-80"></i>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto p-3 space-y-1 text-sm">
        <?php foreach ($nav as $group): ?>
            <?php if (!empty($group['group'])): ?>
                <div class="pt-3 pb-1 text-[10px] uppercase tracking-widest text-white/50"><?= vp_safe_html($group['group']) ?></div>
            <?php endif; ?>
            <?php foreach ($group['items'] as $item): ?>
                <?php $active = ($seg1 === ($item['match'] ?? '')); ?>
                <a class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg <?= $active ? 'bg-brand-600 text-white' : 'hover:bg-white/10' ?>"
                   href="<?= base_url($item['url']) ?>">
                    <span class="flex items-center gap-2"><i class="<?= vp_safe_html($item['icon']) ?>"></i> <?= vp_safe_html($item['label']) ?></span>
                    <?php if (($item['match'] ?? '') === 'notifications' && $notif > 0): ?>
                        <span class="bg-red-500 text-white text-[10px] font-bold rounded-full px-2"><?= $notif ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="p-3 border-t border-white/10 text-xs space-y-2">
        <a href="<?= base_url('admin/profile') ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10">
            <i class="ri-user-settings-line"></i> My profile
        </a>
        <a href="<?= base_url('admin/logout') ?>" class="block text-center py-2 rounded bg-white/10 hover:bg-red-600">Sign out</a>
    </div>
</aside>
