<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - routes.
 * See docs/API_MAP.md for the mapping to old NestJS endpoints.
 */
$route['default_controller'] = 'home';
$route['404_override'] = 'errors/not_found';
$route['translate_uri_dashes'] = FALSE;

/* ---------- Public ---------- */
$route['about']                = 'about';
$route['services']             = 'services';
$route['contact']              = 'contact';
$route['contact/submit']       = 'contact/submit';

$route['rfq']                  = 'rfq';
$route['rfq/thanks/(:any)']    = 'rfq/thanks/$1';

$route['products']             = 'products';
$route['products/(:any)']      = 'products/view/$1';

$route['industries']           = 'industries';
$route['industries/(:any)']    = 'industries/view/$1';

$route['blog']                 = 'blog';
$route['blog/(:any)']          = 'blog/view/$1';

$route['careers']              = 'careers';
$route['careers/(:any)']       = 'careers/view/$1';
$route['careers/apply/(:any)'] = 'careers/apply/$1';

$route['faq']                  = 'faq';
$route['downloads']            = 'downloads';
$route['downloads/file/(:any)']= 'downloads/file/$1';
$route['news']                 = 'news';
$route['news/(:any)']          = 'news/view/$1';
$route['search']               = 'search';

/* ---------- SEO ---------- */
$route['robots.txt']           = 'seo/robots';
$route['sitemap.xml']          = 'seo/sitemap';

/* ---------- AI chat ---------- */
$route['chat/message']         = 'chat/message';

$route['login']                = 'auth/login';
$route['register']             = 'auth/register';
$route['logout']               = 'auth/logout';
$route['forgot']               = 'auth/forgot';
$route['reset/(:any)']         = 'auth/reset/$1';

/* ---------- Admin ---------- */
$route['admin']                = 'admin/dashboard';
$route['admin/login']          = 'auth/admin_login';
$route['admin/logout']         = 'auth/admin_logout';
$route['admin/dashboard']      = 'admin/dashboard';

$route['admin/products']                 = 'admin/products';
$route['admin/products/create']          = 'admin/products/create';
$route['admin/products/edit/(:any)']      = 'admin/products/edit/$1';
$route['admin/products/delete/(:any)']   = 'admin/products/delete/$1';
$route['admin/products/(:any)/images/(:any)/delete'] = 'admin/products/image_delete/$1/$2';
$route['admin/products/(:any)/images/(:any)/primary'] = 'admin/products/image_primary/$1/$2';

$route['admin/categories']                = 'admin/categories';
$route['admin/categories/create']         = 'admin/categories/create';
$route['admin/categories/edit/(:any)']    = 'admin/categories/edit/$1';
$route['admin/categories/delete/(:any)']  = 'admin/categories/delete/$1';

$route['admin/quotes']                    = 'admin/quotes';
$route['admin/quotes/(:any)']             = 'admin/quotes/view/$1';
$route['admin/quotes/(:any)/status']      = 'admin/quotes/status/$1';
$route['admin/quotes/(:any)/assign']      = 'admin/quotes/assign/$1';
$route['admin/quotes/(:any)/note']        = 'admin/quotes/note/$1';
$route['admin/quotes/(:any)/pdf']         = 'admin/quotes/pdf/$1';
$route['admin/quotes/(:any)/delete']      = 'admin/quotes/delete/$1';
$route['admin/quotes/(:any)/attachments/(:any)/delete'] = 'admin/quotes/attachment_delete/$1/$2';
$route['admin/quotes/export/csv']         = 'admin/quotes/export_csv';

$route['admin/users']                     = 'admin/users';
$route['admin/users/create']              = 'admin/users/create';
$route['admin/users/edit/(:any)']         = 'admin/users/edit/$1';
$route['admin/users/delete/(:any)']       = 'admin/users/delete/$1';

$route['admin/contacts']                  = 'admin/contacts';
$route['admin/contacts/(:any)']           = 'admin/contacts/view/$1';
$route['admin/contacts/(:any)/delete']    = 'admin/contacts/delete/$1';

$route['admin/blog']                      = 'admin/blog';
$route['admin/blog/create']               = 'admin/blog/create';
$route['admin/blog/edit/(:any)']          = 'admin/blog/edit/$1';
$route['admin/blog/delete/(:any)']        = 'admin/blog/delete/$1';

$route['admin/careers']                   = 'admin/careers';
$route['admin/careers/create']            = 'admin/careers/create';
$route['admin/careers/edit/(:any)']       = 'admin/careers/edit/$1';
$route['admin/careers/delete/(:any)']     = 'admin/careers/delete/$1';
$route['admin/careers/(:any)/applications'] = 'admin/careers/applications/$1';

$route['admin/faqs']                      = 'admin/faqs';
$route['admin/faqs/create']               = 'admin/faqs/create';
$route['admin/faqs/edit/(:any)']          = 'admin/faqs/edit/$1';
$route['admin/faqs/delete/(:any)']        = 'admin/faqs/delete/$1';

$route['admin/downloads']                 = 'admin/downloads';
$route['admin/downloads/create']          = 'admin/downloads/create';
$route['admin/downloads/edit/(:any)']     = 'admin/downloads/edit/$1';
$route['admin/downloads/delete/(:any)']   = 'admin/downloads/delete/$1';

$route['admin/industries']                = 'admin/industries';
$route['admin/industries/create']         = 'admin/industries/create';
$route['admin/industries/edit/(:any)']    = 'admin/industries/edit/$1';
$route['admin/industries/delete/(:any)']  = 'admin/industries/delete/$1';

$route['admin/news']                      = 'admin/news';
$route['admin/news/create']               = 'admin/news/create';
$route['admin/news/edit/(:any)']          = 'admin/news/edit/$1';
$route['admin/news/delete/(:any)']        = 'admin/news/delete/$1';

$route['admin/testimonials']              = 'admin/testimonials';
$route['admin/testimonials/create']       = 'admin/testimonials/create';
$route['admin/testimonials/edit/(:any)']  = 'admin/testimonials/edit/$1';
$route['admin/testimonials/delete/(:any)']= 'admin/testimonials/delete/$1';

$route['admin/partners']                  = 'admin/partners';
$route['admin/partners/create']           = 'admin/partners/create';
$route['admin/partners/edit/(:any)']      = 'admin/partners/edit/$1';
$route['admin/partners/delete/(:any)']    = 'admin/partners/delete/$1';

$route['admin/media']                     = 'admin/media';
$route['admin/media/upload']              = 'admin/media/upload';
$route['admin/media/delete/(:any)']       = 'admin/media/delete/$1';

$route['admin/settings']                  = 'admin/settings';
$route['admin/settings/save']             = 'admin/settings/save';
$route['admin/seo']                       = 'admin/seo';
$route['admin/seo/save']                  = 'admin/seo/save';
$route['admin/audit']                     = 'admin/audit';
$route['admin/notifications']             = 'admin/notifications';
$route['admin/notifications/read/(:any)'] = 'admin/notifications/read/$1';

/* ---------- Admin: Super Admin — administrator management ---------- */
$route['admin/admins']                            = 'admin/admins/index';
$route['admin/admins/create']                     = 'admin/admins/create';
$route['admin/admins/save']                       = 'admin/admins/save';
$route['admin/admins/edit/(:any)']                = 'admin/admins/edit/$1';
$route['admin/admins/permissions/(:any)']         = 'admin/admins/permissions/$1';
$route['admin/admins/permissions_save/(:any)']    = 'admin/admins/permissions_save/$1';
$route['admin/admins/permissions_reset/(:any)']   = 'admin/admins/permissions_reset/$1';
$route['admin/admins/toggle/(:any)']              = 'admin/admins/toggle/$1';
$route['admin/admins/reset_password/(:any)']      = 'admin/admins/reset_password/$1';
$route['admin/admins/delete/(:any)']              = 'admin/admins/delete/$1';
$route['admin/admins/activity/(:any)']            = 'admin/admins/activity/$1';

/* ---------- Admin: website content management ---------- */
$route['admin/homepage']                          = 'admin/homepage/index';
$route['admin/homepage/index/(:any)']             = 'admin/homepage/index/$1';
$route['admin/homepage/create/(:any)']            = 'admin/homepage/create/$1';
$route['admin/homepage/edit/(:any)']              = 'admin/homepage/edit/$1';
$route['admin/homepage/save']                     = 'admin/homepage/save';
$route['admin/homepage/toggle/(:any)']            = 'admin/homepage/toggle/$1';
$route['admin/homepage/move/(:any)/(:any)']       = 'admin/homepage/move/$1/$2';
$route['admin/homepage/reorder']                  = 'admin/homepage/reorder';
$route['admin/homepage/delete/(:any)']            = 'admin/homepage/delete/$1';
$route['admin/homepage/duplicate/(:any)']         = 'admin/homepage/duplicate/$1';

$route['admin/inline_editor/section_save']        = 'admin/inline_editor/section_save';
$route['admin/inline_editor/setting_save']        = 'admin/inline_editor/setting_save';
$route['admin/inline_editor/theme_save']          = 'admin/inline_editor/theme_save';

$route['admin/pages']                             = 'admin/pages/index';
$route['admin/pages/create']                      = 'admin/pages/create';
$route['admin/pages/save']                        = 'admin/pages/save';
$route['admin/pages/edit/(:any)']                 = 'admin/pages/edit/$1';
$route['admin/pages/toggle/(:any)']               = 'admin/pages/toggle/$1';
$route['admin/pages/delete/(:any)']               = 'admin/pages/delete/$1';

$route['admin/menus']                             = 'admin/menus/index';
$route['admin/menus/index/(:any)']                = 'admin/menus/index/$1';
$route['admin/menus/save']                        = 'admin/menus/save';
$route['admin/menus/toggle/(:any)']               = 'admin/menus/toggle/$1';
$route['admin/menus/move/(:any)/(:any)']          = 'admin/menus/move/$1/$2';
$route['admin/menus/delete/(:any)']               = 'admin/menus/delete/$1';

$route['admin/appearance']                        = 'admin/appearance/index';
$route['admin/appearance/save_branding']          = 'admin/appearance/save_branding';
$route['admin/appearance/upload']                 = 'admin/appearance/upload';
$route['admin/appearance/remove']                 = 'admin/appearance/remove';
$route['admin/appearance/header']                 = 'admin/appearance/header';
$route['admin/appearance/save_header']            = 'admin/appearance/save_header';
$route['admin/appearance/colors']                 = 'admin/appearance/colors';
$route['admin/appearance/save_colors']            = 'admin/appearance/save_colors';

$route['admin/media/browse']                      = 'admin/media/browse';
$route['admin/media/replace/(:any)']              = 'admin/media/replace/$1';
$route['admin/media/update/(:any)']               = 'admin/media/update/$1';

$route['admin/settings/contact']                  = 'admin/settings/contact';
$route['admin/settings/save_contact']             = 'admin/settings/save_contact';
$route['admin/settings/social']                   = 'admin/settings/social';
$route['admin/settings/save_social']              = 'admin/settings/save_social';
$route['admin/settings/system']                   = 'admin/settings/system';
$route['admin/settings/save_system']              = 'admin/settings/save_system';
$route['admin/settings/test_email']               = 'admin/settings/test_email';
$route['admin/settings/advanced']                 = 'admin/settings/advanced';
$route['admin/settings/save_advanced']            = 'admin/settings/save_advanced';
$route['admin/settings/add']                      = 'admin/settings/add';
$route['admin/settings/delete']                   = 'admin/settings/delete';

$route['admin/profile']                           = 'admin/profile/index';
$route['admin/profile/save']                      = 'admin/profile/save';
$route['admin/profile/password']                  = 'admin/profile/password';

$route['admin/reports']                           = 'admin/reports/index';
$route['admin/reports/export']                    = 'admin/reports/export';

/* ---------- Public: CMS pages (must stay last) ---------- */
$route['page/(:any)']                             = 'page/view/$1';
$route['admin/notifications/read_all']            = 'admin/notifications/read_all';
$route['admin/notifications/clear']               = 'admin/notifications/clear';
$route['admin/notifications/delete/(:any)']       = 'admin/notifications/delete/$1';

/* ---------- AI chat (public widget) ---------- */
$route['chat/token']           = 'chat/token';
