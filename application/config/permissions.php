<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — permission catalogue.
 *
 * Single source of truth for every grantable permission in the admin area.
 * The `permissions` database table mirrors this list (synced automatically by
 * the Acl library) so permissions can be joined/reported on in SQL.
 *
 * Key format:  <resource>.<action>
 *   resource -> matches the `role_permissions`.`resource` column
 *   action   -> matches an entry in `role_permissions`.`actions`
 *
 * super_only permissions can never be granted to a normal ADMIN: they belong
 * to the SUPER_ADMIN alone (administrator management, system security).
 */
$config['permission_groups'] = [
    'Overview' => 'Dashboard and reports',
    'Sales'    => 'Quote requests and contact messages',
    'Catalog'  => 'Products and the product taxonomy',
    'Content'  => 'Editorial content published on the website',
    'Website'  => 'Homepage, pages, navigation, branding and media',
    'People'   => 'Customer accounts and administrator accounts',
    'System'   => 'Website settings, security and audit trail',
];

$config['permissions'] = [
    // key                     label                                     group        superOnly
    'dashboard.view'      => ['View dashboard',                          'Overview', false],
    'reports.view'        => ['View reports and analytics',              'Overview', false],

    'quotes.manage'       => ['Manage quote requests (RFQ)',             'Sales',    false],
    'contacts.manage'     => ['Manage contact messages',                 'Sales',    false],

    'products.manage'     => ['Manage products',                         'Catalog',  false],
    'categories.manage'   => ['Manage categories',                       'Catalog',  false],
    'industries.manage'   => ['Manage industries',                       'Catalog',  false],
    'downloads.manage'    => ['Manage downloads',                        'Catalog',  false],

    'blog.manage'         => ['Manage blog posts',                       'Content',  false],
    'news.manage'         => ['Manage news',                             'Content',  false],
    'faqs.manage'         => ['Manage FAQs',                             'Content',  false],
    'careers.manage'      => ['Manage careers and applications',         'Content',  false],
    'testimonials.manage' => ['Manage testimonials',                     'Content',  false],
    'partners.manage'     => ['Manage partners',                         'Content',  false],

    'homepage.manage'     => ['Manage page builder (entire website)',    'Website',  false],
    'pages.manage'        => ['Manage website pages',                    'Website',  false],
    'menus.manage'        => ['Manage navigation menus',                 'Website',  false],
    'appearance.manage'   => ['Manage logo, colours, header and footer', 'Website',  false],
    'media.manage'        => ['Manage the media library',                'Website',  false],
    'seo.manage'          => ['Manage SEO settings',                     'Website',  false],

    'customers.manage'    => ['Manage customer accounts',                'People',   false],
    'admins.manage'       => ['Manage administrators and permissions',   'People',   true],

    'settings.manage'     => ['Manage website settings',                 'System',   false],
    'audit.view'          => ['View the activity / audit log',           'System',   false],
    'system.manage'       => ['Manage system, email and security settings', 'System', true],
];

/**
 * Default permissions applied to each role. SUPER_ADMIN is intentionally
 * absent: it always has every permission, unconditionally, in code.
 */
$config['role_default_permissions'] = [
    'ADMIN' => [
        'dashboard.view', 'reports.view',
        'quotes.manage', 'contacts.manage',
        // Administrators are full website editors. These content permissions
        // are also protected in Acl::effective(), so an old per-user override
        // cannot accidentally leave an ADMIN unable to edit a public page.
        'products.manage', 'categories.manage', 'industries.manage', 'downloads.manage',
        'blog.manage', 'news.manage', 'faqs.manage', 'careers.manage',
        'testimonials.manage', 'partners.manage',
        'homepage.manage', 'pages.manage', 'menus.manage', 'appearance.manage',
        'media.manage', 'seo.manage', 'settings.manage',
    ],
    'SALES' => [
        'dashboard.view', 'quotes.manage', 'contacts.manage',
    ],
    'ENGINEER' => [
        'dashboard.view', 'products.manage', 'downloads.manage',
    ],
    'EDITOR' => [
        'dashboard.view', 'blog.manage', 'news.manage',
        'faqs.manage', 'media.manage', 'pages.manage',
    ],
];
