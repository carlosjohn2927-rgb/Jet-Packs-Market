<?php
/**
 * Dashboard home — every panel is permission-aware, so an Admin sees exactly
 * the parts of the business they are responsible for.
 */
$is_super = !empty($is_super_admin);
$quick = [
    ['homepage.manage',  'Edit homepage',   'admin/homepage',          'ri-home-gear-line'],
    ['pages.manage',     'Manage pages',    'admin/pages',             'ri-pages-line'],
    ['menus.manage',     'Navigation',      'admin/menus',             'ri-menu-2-line'],
    ['appearance.manage','Logo & branding', 'admin/appearance',        'ri-image-2-line'],
    ['appearance.manage','Colours',         'admin/appearance/colors', 'ri-contrast-drop-2-line'],
    ['media.manage',     'Media library',   'admin/media',             'ri-image-line'],
    ['products.manage',  'Products',        'admin/products',          'ri-box-3-line'],
    ['quotes.manage',    'Quotes',          'admin/quotes',            'ri-file-list-3-line'],
    ['admins.manage',    'Administrators',  'admin/admins',            'ri-shield-user-line'],
    ['settings.manage',  'Website settings','admin/settings',          'ri-settings-3-line'],
    ['settings.manage',  'SMTP / email',    'admin/settings/system',   'ri-mail-settings-line'],
];
?>
<div class="space-y-6">

    <!-- Welcome / site card -->
    <section class="bg-white border rounded-2xl p-5 flex flex-wrap items-center gap-5">
        <div class="px-4 py-3 rounded-xl bg-gray-50 border">
            <img src="<?= vp_safe_html(vp_logo_url('light')) ?>" alt="<?= vp_safe_html(vp_site('logo_alt', vp_site('name'))) ?>" class="h-10 w-auto max-w-[200px] object-contain">
        </div>
        <div>
            <h2 class="font-bold text-lg text-ink-900">
                <?= vp_safe_html($site['name']) ?>
                <?php if ($is_super): ?><span class="vp-pill bg-amber-100 text-amber-800 ml-1">Super Admin</span><?php endif; ?>
            </h2>
            <p class="text-sm text-ink-800/70"><?= vp_safe_html($site['description'] ?: $site['tagline']) ?></p>
        </div>
        <div class="ml-auto flex flex-wrap gap-2">
            <a class="vp-btn vp-btn-primary" href="<?= base_url() ?>" target="_blank" rel="noopener"><i class="ri-home-4-line"></i> Homepage</a>
            <a class="vp-btn vp-btn-secondary" href="<?= base_url() ?>" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> View Website</a>
        </div>
    </section>

    <!-- Counters -->
    <?php if (!empty($counts)): ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php
            $cards = [
                'quotes_new'   => ['New quote requests', 'ri-file-list-3-line', 'bg-blue-50 text-blue-700',    'admin/quotes'],
                'quotes_total' => ['Quotes (all time)',  'ri-stack-line',       'bg-indigo-50 text-indigo-700','admin/quotes'],
                'contacts_new' => ['Unread messages',    'ri-mail-line',        'bg-emerald-50 text-emerald-700','admin/contacts'],
                'products'     => ['Active products',    'ri-box-3-line',       'bg-amber-50 text-amber-700',  'admin/products'],
                'customers'    => ['Customers',          'ri-user-line',        'bg-purple-50 text-purple-700','admin/users'],
                'pages'        => ['Published pages',    'ri-pages-line',       'bg-sky-50 text-sky-700',      'admin/pages'],
                'media'        => ['Media files',        'ri-image-line',       'bg-rose-50 text-rose-700',    'admin/media'],
            ];
            foreach ($cards as $key => $c):
                if (!isset($counts[$key])) continue; ?>
                <a href="<?= base_url($c[3]) ?>" class="bg-white border rounded-2xl p-5 flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-11 h-11 rounded-xl <?= $c[2] ?> flex items-center justify-center"><i class="<?= $c[1] ?> text-xl"></i></div>
                    <div>
                        <div class="text-2xl font-extrabold text-ink-900"><?= (int) $counts[$key] ?></div>
                        <div class="text-xs text-ink-800/60"><?= vp_safe_html($c[0]) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Quick actions -->
    <section class="bg-white border rounded-2xl p-5">
        <h2 class="font-bold text-ink-900 mb-4">Manage the website</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
            <?php $shown = 0; foreach ($quick as $q): if (!vp_can($q[0])) continue; $shown++; ?>
                <a href="<?= base_url($q[2]) ?>" class="border rounded-xl px-4 py-3 hover:border-brand-400 hover:bg-brand-50/40 transition flex items-center gap-2">
                    <i class="<?= $q[3] ?> text-brand-600 text-lg"></i>
                    <span class="text-sm font-semibold text-ink-900"><?= vp_safe_html($q[1]) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (!$shown): ?>
                <p class="text-sm text-ink-800/60">Your account has no website-management permissions yet. Ask the Super Admin to grant them.</p>
            <?php endif; ?>
        </div>
        <?php if (!empty($content)): ?>
            <p class="text-xs text-ink-800/60 mt-4">
                Homepage: <strong><?= (int) ($content['sections_active'] ?? 0) ?></strong> of
                <strong><?= (int) ($content['sections_total'] ?? 0) ?></strong> sections visible ·
                <a class="text-brand-700 hover:underline" href="<?= base_url('admin/homepage') ?>">edit sections</a>
            </p>
        <?php endif; ?>
    </section>

    <?php if (vp_can('settings.manage') && !empty($email_health)): ?>
        <section class="bg-white border rounded-2xl p-5 flex flex-col md:flex-row md:items-center gap-4">
            <div class="w-11 h-11 rounded-xl <?= !empty($email_health['ok']) ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?> flex items-center justify-center">
                <i class="ri-mail-settings-line text-xl"></i>
            </div>
            <div class="flex-1">
                <h2 class="font-bold text-ink-900">SMTP / outgoing email</h2>
                <p class="text-sm text-ink-800/70">
                    Transport: <strong><?= vp_safe_html($email_health['transport'] ?? 'unknown') ?></strong> ·
                    <?= !empty($email_health['ok']) ? 'configured' : 'needs attention' ?>
                    <?php if (!empty($email_health['message'])): ?> — <?= vp_safe_html(vp_truncate($email_health['message'], 160)) ?><?php endif; ?>
                </p>
            </div>
            <a class="vp-btn vp-btn-primary" href="<?= base_url('admin/settings/system') ?>"><i class="ri-settings-3-line"></i> Fix SMTP settings</a>
        </section>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Recent quotes -->
        <?php if (vp_can('quotes.manage')): ?>
            <section class="lg:col-span-2 bg-white border rounded-2xl">
                <header class="px-5 py-4 border-b flex items-center">
                    <h2 class="font-bold text-ink-900">Latest quote requests</h2>
                    <a class="ml-auto text-sm text-brand-700 hover:underline" href="<?= base_url('admin/quotes') ?>">All quotes →</a>
                </header>
                <div class="divide-y">
                    <?php foreach ($recent_quotes as $q): ?>
                        <a class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50" href="<?= base_url('admin/quotes/' . $q['id']) ?>">
                            <span class="vp-pill bg-gray-100 text-gray-700"><?= vp_safe_html($q['status']) ?></span>
                            <span class="text-sm font-semibold text-ink-900 truncate"><?= vp_safe_html($q['companyName']) ?></span>
                            <span class="text-xs text-ink-800/60 truncate hidden md:inline"><?= vp_safe_html($q['contactPerson']) ?></span>
                            <span class="ml-auto text-xs text-ink-800/50"><?= vp_time_ago($q['createdAt']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($recent_quotes)): ?>
                        <p class="px-5 py-8 text-center text-sm text-ink-800/60">No quote requests yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Super admin panel -->
        <?php if ($is_super): ?>
            <section class="bg-white border rounded-2xl">
                <header class="px-5 py-4 border-b flex items-center gap-2">
                    <i class="ri-shield-star-line text-amber-500"></i>
                    <h2 class="font-bold text-ink-900">Administrators</h2>
                    <a class="ml-auto text-sm text-brand-700 hover:underline" href="<?= base_url('admin/admins') ?>">Manage →</a>
                </header>
                <div class="divide-y">
                    <?php foreach ($admins as $a): ?>
                        <div class="px-5 py-3 flex items-center gap-3">
                            <img class="w-8 h-8 rounded-full bg-gray-200" src="<?= vp_safe_html(vp_avatar_url($a, 64)) ?>" alt="">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-ink-900 truncate"><?= vp_safe_html(trim($a['firstName'] . ' ' . $a['lastName'])) ?></div>
                                <div class="text-[11px] text-ink-800/60"><?= vp_role_label($a['role']) ?> · <?= $a['lastLoginAt'] ? vp_time_ago($a['lastLoginAt']) : 'never signed in' ?></div>
                            </div>
                            <span class="ml-auto vp-pill <?= !empty($a['isActive']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' ?>">
                                <?= !empty($a['isActive']) ? 'on' : 'off' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="px-5 py-3 border-t text-xs text-ink-800/70 space-y-1">
                    <?php if (!empty($email_health)): ?>
                        <div>Email transport:
                            <strong class="<?= !empty($email_health['ok']) ? 'text-green-700' : 'text-red-600' ?>">
                                <?= vp_safe_html($email_health['transport'] ?? 'unknown') ?>
                            </strong>
                        </div>
                    <?php endif; ?>
                    <div>Failed sign-ins (7 days): <strong><?= (int) ($failed_logins ?? 0) ?></strong></div>
                    <div>Maintenance mode: <strong><?= vp_maintenance_active() ? 'ON' : 'off' ?></strong></div>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <!-- Activity -->
    <?php if (vp_can('audit.view')): ?>
        <section class="bg-white border rounded-2xl">
            <header class="px-5 py-4 border-b flex items-center">
                <h2 class="font-bold text-ink-900">Recent administrator activity</h2>
                <a class="ml-auto text-sm text-brand-700 hover:underline" href="<?= base_url('admin/audit') ?>">Full activity log →</a>
            </header>
            <div class="divide-y">
                <?php foreach ($recent_activity as $a): ?>
                    <div class="px-5 py-2.5 flex items-center gap-3 text-sm">
                        <span class="vp-pill bg-gray-100 text-gray-700"><?= vp_safe_html($a['action']) ?></span>
                        <span class="text-ink-800/80"><?= vp_safe_html($a['resource']) ?></span>
                        <span class="text-xs text-ink-800/60 truncate hidden md:inline">
                            <?= vp_safe_html(trim(($a['firstName'] ?? '') . ' ' . ($a['lastName'] ?? '')) ?: ($a['email'] ?? 'system')) ?>
                        </span>
                        <span class="ml-auto text-xs text-ink-800/50"><?= vp_time_ago($a['createdAt']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($recent_activity)): ?>
                    <p class="px-5 py-8 text-center text-sm text-ink-800/60">No activity recorded yet.</p>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
