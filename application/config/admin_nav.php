<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — admin dashboard navigation.
 *
 * The sidebar of both dashboards is generated from this structure and then
 * filtered by the signed-in account's effective permissions, so an Admin only
 * ever sees the sections the Super Admin granted. Hiding the link is cosmetic:
 * the same permission key is enforced server-side by Admin_Controller.
 */
$config['admin_nav'] = [
    [
        'group' => '',
        'items' => [
            ['label' => 'Dashboard', 'url' => 'admin', 'icon' => 'ri-dashboard-line', 'permission' => 'dashboard.view', 'match' => 'dashboard'],
        ],
    ],
    [
        'group' => 'Sales',
        'items' => [
            ['label' => 'Quotes',   'url' => 'admin/quotes',   'icon' => 'ri-file-list-3-line', 'permission' => 'quotes.manage',   'match' => 'quotes'],
            ['label' => 'Messages', 'url' => 'admin/contacts', 'icon' => 'ri-mail-line',        'permission' => 'contacts.manage', 'match' => 'contacts'],
            ['label' => 'Reports',  'url' => 'admin/reports',  'icon' => 'ri-line-chart-line',  'permission' => 'reports.view',    'match' => 'reports'],
        ],
    ],
    [
        'group' => 'Catalog',
        'items' => [
            ['label' => 'Products',   'url' => 'admin/products',   'icon' => 'ri-box-3-line',        'permission' => 'products.manage',   'match' => 'products'],
            ['label' => 'Categories', 'url' => 'admin/categories', 'icon' => 'ri-price-tag-3-line',  'permission' => 'categories.manage', 'match' => 'categories'],
            ['label' => 'Industries', 'url' => 'admin/industries', 'icon' => 'ri-building-2-line',   'permission' => 'industries.manage', 'match' => 'industries'],
            ['label' => 'Downloads',  'url' => 'admin/downloads',  'icon' => 'ri-download-2-line',   'permission' => 'downloads.manage',  'match' => 'downloads'],
        ],
    ],
    [
        'group' => 'Website',
        'items' => [
            ['label' => 'Page builder',   'url' => 'admin/homepage',   'icon' => 'ri-layout-masonry-line',  'permission' => 'homepage.manage',   'match' => 'homepage'],
            ['label' => 'Pages',          'url' => 'admin/pages',      'icon' => 'ri-pages-line',      'permission' => 'pages.manage',      'match' => 'pages'],
            ['label' => 'Navigation',     'url' => 'admin/menus',      'icon' => 'ri-menu-2-line',     'permission' => 'menus.manage',      'match' => 'menus'],
            ['label' => 'Header & Footer','url' => 'admin/appearance/header', 'icon' => 'ri-layout-top-line', 'permission' => 'appearance.manage', 'match' => 'appearance'],
            ['label' => 'Logo & Branding','url' => 'admin/appearance', 'icon' => 'ri-image-2-line',    'permission' => 'appearance.manage', 'match' => 'appearance'],
            ['label' => 'Colours',        'url' => 'admin/appearance/colors', 'icon' => 'ri-contrast-drop-2-line', 'permission' => 'appearance.manage', 'match' => 'appearance'],
            ['label' => 'Media Library',  'url' => 'admin/media',      'icon' => 'ri-image-line',      'permission' => 'media.manage',      'match' => 'media'],
            ['label' => 'SEO',            'url' => 'admin/seo',        'icon' => 'ri-search-eye-line', 'permission' => 'seo.manage',        'match' => 'seo'],
        ],
    ],
    [
        'group' => 'Content',
        'items' => [
            ['label' => 'Blog',         'url' => 'admin/blog',         'icon' => 'ri-article-line',    'permission' => 'blog.manage',         'match' => 'blog'],
            ['label' => 'News',         'url' => 'admin/news',         'icon' => 'ri-newspaper-line',  'permission' => 'news.manage',         'match' => 'news'],
            ['label' => 'FAQs',         'url' => 'admin/faqs',         'icon' => 'ri-question-line',   'permission' => 'faqs.manage',         'match' => 'faqs'],
            ['label' => 'Careers',      'url' => 'admin/careers',      'icon' => 'ri-briefcase-line',  'permission' => 'careers.manage',      'match' => 'careers'],
            ['label' => 'Testimonials', 'url' => 'admin/testimonials', 'icon' => 'ri-star-smile-line', 'permission' => 'testimonials.manage', 'match' => 'testimonials'],
            ['label' => 'Partners',     'url' => 'admin/partners',     'icon' => 'ri-shake-hands-line','permission' => 'partners.manage',     'match' => 'partners'],
        ],
    ],
    [
        'group' => 'People',
        'items' => [
            ['label' => 'Customers',      'url' => 'admin/users',  'icon' => 'ri-user-line',            'permission' => 'customers.manage', 'match' => 'users'],
            ['label' => 'Administrators', 'url' => 'admin/admins', 'icon' => 'ri-shield-user-line',     'permission' => 'admins.manage',    'match' => 'admins'],
        ],
    ],
    [
        'group' => 'System',
        'items' => [
            ['label' => 'Settings',      'url' => 'admin/settings',      'icon' => 'ri-settings-3-line',      'permission' => 'settings.manage', 'match' => 'settings'],
            // Personal inbox: available to every staff account, no permission needed.
            ['label' => 'Notifications', 'url' => 'admin/notifications', 'icon' => 'ri-notification-3-line',  'permission' => null, 'match' => 'notifications'],
            ['label' => 'Activity Log',  'url' => 'admin/audit',         'icon' => 'ri-shield-keyhole-line',  'permission' => 'audit.view',      'match' => 'audit'],
        ],
    ],
];
