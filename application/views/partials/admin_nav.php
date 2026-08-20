<?php
/**
 * Admin dashboard top bar:
 *   [LOGO] · page title · View Website · Notifications · Profile
 * The logo and the "View Website" button both open the public homepage in a
 * new tab, so an administrator can switch between the dashboard and the live
 * site without ever losing the session.
 */
$user      = $current_user ?? null;
$notif     = (int) ($unread_notifications ?? 0);
$is_super  = !empty($is_super_admin);
$name      = trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''));
$recent    = $recent_notifications ?? [];
?>
<header class="bg-white border-b px-4 md:px-6 py-3 flex items-center gap-3 sticky top-0 z-30">
    <button class="md:hidden p-2" id="vp-admin-toggle" aria-label="Toggle menu"><i class="ri-menu-line text-2xl"></i></button>

    <a href="<?= base_url() ?>" target="_blank" rel="noopener"
       class="md:hidden flex-shrink-0" title="Open the public website">
        <img src="<?= vp_safe_html(vp_logo_url('light')) ?>" alt="<?= vp_safe_html(vp_site('logo_alt', vp_site('name'))) ?>" class="h-8 w-auto max-w-[140px] object-contain">
    </a>

    <div class="min-w-0">
        <h1 class="text-lg md:text-xl font-bold text-ink-900 truncate"><?= vp_safe_html($page_title ?: 'Dashboard') ?></h1>
        <?php if (!empty($page_subtitle)): ?>
            <p class="text-xs text-ink-800/70 truncate"><?= vp_safe_html($page_subtitle) ?></p>
        <?php endif; ?>
    </div>

    <!-- Browser-style navigation keeps long editing workflows in one tab. -->
    <nav class="hidden lg:flex items-center gap-1 ml-3" aria-label="Page history">
        <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-history="back" title="Go to the previous page">
            <i class="ri-arrow-left-line"></i> Previous
        </button>
        <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-history="forward" title="Go to the next page in your history">
            Forward <i class="ri-arrow-right-line"></i>
        </button>
        <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('admin') ?>" title="Back to the dashboard">
            <i class="ri-corner-up-left-line"></i> Back
        </a>
    </nav>

    <div class="ml-auto flex items-center gap-2 md:gap-3">
        <!-- Compact history controls on smaller screens. -->
        <button type="button" class="lg:hidden p-2 rounded hover:bg-gray-100" data-vp-history="back" aria-label="Previous page" title="Previous page">
            <i class="ri-arrow-left-line text-xl"></i>
        </button>
        <button type="button" class="lg:hidden p-2 rounded hover:bg-gray-100" data-vp-history="forward" aria-label="Forward page" title="Forward page">
            <i class="ri-arrow-right-line text-xl"></i>
        </button>

        <!-- View website -->
        <a href="<?= base_url() ?>" target="_blank" rel="noopener"
           class="hidden sm:inline-flex items-center gap-2 border border-brand-200 bg-brand-50 hover:bg-brand-100 text-brand-800 text-sm font-semibold px-3 py-2 rounded-lg"
           title="Open the public website in a new tab">
            <i class="ri-global-line"></i> View Website
            <i class="ri-external-link-line text-xs opacity-70"></i>
        </a>
        <a href="<?= base_url() ?>" target="_blank" rel="noopener" class="sm:hidden p-2 rounded hover:bg-gray-100" title="View website">
            <i class="ri-global-line text-xl"></i>
        </a>

        <!-- Notifications -->
        <div class="relative" data-vp-dropdown>
            <button type="button" class="relative p-2 rounded hover:bg-gray-100" data-vp-dropdown-toggle aria-label="Notifications">
                <i class="ri-notification-3-line text-xl"></i>
                <?php if ($notif > 0): ?>
                    <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full px-1.5"><?= $notif ?></span>
                <?php endif; ?>
            </button>
            <div class="hidden absolute right-0 mt-2 w-80 bg-white border rounded-xl shadow-xl overflow-hidden z-50" data-vp-dropdown-menu>
                <div class="px-4 py-3 border-b flex items-center justify-between">
                    <span class="font-semibold text-sm">Notifications</span>
                    <?php if ($notif > 0): ?><span class="text-xs text-red-600 font-semibold"><?= $notif ?> unread</span><?php endif; ?>
                </div>
                <div class="max-h-80 overflow-y-auto divide-y">
                    <?php if (empty($recent)): ?>
                        <p class="px-4 py-6 text-sm text-ink-800/70 text-center">Nothing new.</p>
                    <?php else: foreach ($recent as $n): ?>
                        <a href="<?= base_url('admin/notifications/read/' . $n['id']) ?>" class="block px-4 py-3 hover:bg-gray-50 <?= empty($n['read']) ? 'bg-brand-50/40' : '' ?>">
                            <div class="text-sm font-semibold text-ink-900"><?= vp_safe_html($n['title']) ?></div>
                            <div class="text-xs text-ink-800/80 mt-0.5"><?= vp_safe_html(vp_truncate($n['message'], 90)) ?></div>
                            <div class="text-[11px] text-ink-800/50 mt-1"><?= vp_time_ago($n['createdAt']) ?></div>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
                <a href="<?= base_url('admin/notifications') ?>" class="block px-4 py-2.5 text-center text-sm font-semibold text-brand-700 hover:bg-gray-50 border-t">View all</a>
            </div>
        </div>

        <!-- Profile -->
        <div class="relative" data-vp-dropdown>
            <button type="button" class="flex items-center gap-2 text-sm p-1 pr-2 rounded-lg hover:bg-gray-100" data-vp-dropdown-toggle>
                <img class="w-8 h-8 rounded-full bg-gray-200" src="<?= vp_safe_html(vp_avatar_url($user ?? ['email' => 'admin@example.com'], 64)) ?>" alt="">
                <span class="hidden md:block text-left leading-tight">
                    <span class="block font-semibold"><?= vp_safe_html($name ?: 'Administrator') ?></span>
                    <span class="block text-[11px] <?= $is_super ? 'text-amber-600 font-semibold' : 'text-ink-800/70' ?>">
                        <?= vp_safe_html(vp_role_label($user['role'] ?? '')) ?>
                    </span>
                </span>
                <i class="ri-arrow-down-s-line"></i>
            </button>
            <div class="hidden absolute right-0 mt-2 w-56 bg-white border rounded-xl shadow-xl overflow-hidden z-50" data-vp-dropdown-menu>
                <div class="px-4 py-3 border-b">
                    <div class="text-sm font-semibold"><?= vp_safe_html($name ?: 'Administrator') ?></div>
                    <div class="text-xs text-ink-800/70 truncate"><?= vp_safe_html($user['email'] ?? '') ?></div>
                </div>
                <a class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50" href="<?= base_url('admin/profile') ?>"><i class="ri-user-settings-line"></i> My profile</a>
                <a class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50" href="<?= base_url('admin/profile#password') ?>"><i class="ri-lock-password-line"></i> Change password</a>
                <a class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50" href="<?= base_url() ?>" target="_blank" rel="noopener"><i class="ri-global-line"></i> View website</a>
                <a class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 border-t" href="<?= base_url('admin/logout') ?>"><i class="ri-logout-box-r-line"></i> Sign out</a>
            </div>
        </div>
    </div>
</header>
