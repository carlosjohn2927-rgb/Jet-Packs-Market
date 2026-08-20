<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — CMS helper.
 *
 * Every piece of website chrome the dashboard can edit (site identity, logo,
 * favicon, colours, navigation, header, footer, homepage sections, pages) is read
 * through these functions. Views must never hard-code the values.
 *
 * Resolution order for a setting:
 *   1. `settings` table (managed from the dashboard)
 *   2. application/config/config.php (environment / .env defaults)
 *   3. the built-in fallback passed by the caller
 */

if (!function_exists('vp_cms_setting')) {
    /**
     * Raw setting lookup with a config fallback.
     */
    function vp_cms_setting($key, $default = null, $config_key = null)
    {
        $CI =& get_instance();
        $value = null;
        if (isset($CI->settings)) {
            $value = $CI->settings->get($key, null);
        }
        if ($value === null || $value === '') {
            if ($config_key) {
                $cfg = $CI->config->item($config_key);
                if ($cfg !== null && $cfg !== '') return $cfg;
            }
            return $default;
        }
        return $value;
    }
}

if (!function_exists('vp_site')) {
    /**
     * Site identity/branding values, all dashboard-managed.
     *
     * Keys: name, title, description, url, language, logo, logo_dark,
     *       logo_footer, logo_alt, favicon, email, phone, address, hours,
     *       copyright, footer_about, maintenance, maintenance_message
     */
    function vp_site($key = null, $default = null)
    {
        static $cache = null;
        if ($cache === null) {
            $CI =& get_instance();
            $name = vp_cms_setting('site_name', 'Halyk Petroleum', 'site_name');
            $cache = [
                'name'        => $name,
                'tagline'     => vp_cms_setting('site_tagline', '', 'site_tagline'),
                'title'       => vp_cms_setting('site_title', $name, null),
                'description' => vp_cms_setting('site_description', vp_cms_setting('seo_default_description', ''), null),
                'url'         => rtrim((string) vp_cms_setting('site_url', rtrim(base_url(), '/')), '/'),
                'language'    => vp_cms_setting('site_language', 'en'),

                'logo'        => vp_cms_setting('logo_light', ''),
                'logo_dark'   => vp_cms_setting('logo_dark', ''),
                'logo_footer' => vp_cms_setting('logo_footer', ''),
                'logo_alt'    => vp_cms_setting('logo_alt', $name),
                'logo_height' => (int) vp_cms_setting('logo_height', 44),
                'favicon'     => vp_cms_setting('favicon', ''),

                'email'       => vp_cms_setting('contact_email', '', 'contact_email'),
                'support_email' => vp_cms_setting('support_email', '', 'support_email'),
                'phone'       => vp_cms_setting('phone', '', 'phone'),
                'address'     => vp_cms_setting('address', '', 'address'),
                'hours'       => vp_cms_setting('contact_hours', ''),

                'copyright'   => vp_cms_setting('footer_copyright', ''),
                'footer_about'=> vp_cms_setting('footer_about', vp_cms_setting('site_tagline', '')),
                'footer_note' => vp_cms_setting('footer_note', ''),

                'header_cta_label'   => vp_cms_setting('header_cta_label', 'Request a Quote'),
                'header_cta_url'     => vp_cms_setting('header_cta_url', 'rfq'),
                'header_cta_enabled' => (string) vp_cms_setting('header_cta_enabled', '1') === '1',
                'topbar_enabled'     => (string) vp_cms_setting('header_topbar_enabled', '0') === '1',
                'topbar_text'        => vp_cms_setting('header_topbar_text', ''),

                'maintenance'         => (string) vp_cms_setting('maintenance_mode', '0') === '1',
                'maintenance_message' => vp_cms_setting('maintenance_message', 'We are performing scheduled maintenance. Please check back shortly.'),
            ];
        }
        if ($key === null) return $cache;
        return array_key_exists($key, $cache) && $cache[$key] !== '' && $cache[$key] !== null
            ? $cache[$key]
            : $default;
    }
}

if (!function_exists('vp_asset_url')) {
    /**
     * Normalise a stored media path/URL into something a browser can load.
     */
    function vp_asset_url($path, $fallback = '')
    {
        $path = trim((string) $path);
        if ($path === '') $path = (string) $fallback;
        if ($path === '') return '';
        if (preg_match('~^(https?:)?//~i', $path) || strpos($path, 'data:') === 0) return $path;
        if ($path[0] === '/') return $path;
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('vp_map_embed_url')) {
    /**
     * Build a Google Maps embed URL for a place/address string. Used by the
     * contact page so the map always points at the configured address, with a
     * sensible fallback when no address has been entered yet.
     */
    function vp_map_embed_url($query = '', $fallback = 'Houston, TX')
    {
        $q = trim((string) $query);
        if ($q === '') $q = trim((string) $fallback);
        if ($q === '') $q = 'Houston, TX';
        return 'https://maps.google.com/maps?q=' . rawurlencode($q) . '&hl=en&z=15&output=embed';
    }
}

if (!function_exists('vp_map_query')) {
    /** Normalise an address used by the contact map. */
    function vp_map_query($query = '', $fallback = 'Houston, TX')
    {
        $q = trim((string) $query);
        if ($q === '') $q = trim((string) $fallback);
        if ($q === '') $q = 'Houston, TX';
        return $q;
    }
}

if (!function_exists('vp_maps_search_url')) {
    /** Google Maps search URL that opens in a new tab. */
    function vp_maps_search_url($query = '', $fallback = 'Houston, TX')
    {
        $q = vp_map_query($query, $fallback);
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q);
    }
}

if (!function_exists('vp_logo_url')) {
    /**
     * Website logo. $variant: 'light' (header), 'dark', 'footer'.
     * Falls back to the bundled asset so the site never renders without one.
     */
    function vp_logo_url($variant = 'light')
    {
        $map = [
            'light'  => ['logo',        IMG_URL . 'logo-header.png'],
            'dark'   => ['logo_dark',   IMG_URL . 'logo-footer.png'],
            'footer' => ['logo_footer', IMG_URL . 'logo-footer.png'],
        ];
        [$key, $fallback] = $map[$variant] ?? $map['light'];
        $value = vp_site($key, '');
        if ($value === '' && $variant !== 'light') {
            $value = vp_site('logo', '');            // fall back to the main logo
        }
        return vp_asset_url($value, $fallback);
    }
}

if (!function_exists('vp_favicon_url')) {
    function vp_favicon_url()
    {
        return vp_asset_url(vp_site('favicon', ''), IMG_URL . 'favicon.ico');
    }
}

if (!function_exists('vp_social_links')) {
    /**
     * Social links, dashboard-managed. Returns [network => url] (non-empty only).
     */
    function vp_social_links()
    {
        static $out = null;
        if ($out !== null) return $out;
        $CI =& get_instance();
        $networks = ['linkedin', 'twitter', 'facebook', 'youtube', 'instagram', 'telegram', 'whatsapp'];
        $stored = $CI->settings->get('social', []);
        if (is_string($stored)) {
            $decoded = json_decode($stored, true);
            $stored = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($stored)) $stored = [];
        $cfg = (array) $CI->config->item('social');
        $out = [];
        foreach ($networks as $n) {
            $val = $CI->settings->get('social_' . $n, null);
            if ($val === null || $val === '') $val = $stored[$n] ?? ($cfg[$n] ?? '');
            $val = trim((string) $val);
            if ($val !== '') {
                // Accept admin entries such as facebook.com/company as well as
                // full URLs. Footer links should always open as valid URLs.
                if (!preg_match('~^(https?:)?//~i', $val) && strpos($val, 'mailto:') !== 0 && strpos($val, 'tel:') !== 0) {
                    $val = 'https://' . ltrim($val, '/');
                }
                $out[$n] = $val;
            }
        }
        return $out;
    }
}

if (!function_exists('vp_social_icon')) {
    function vp_social_icon($network)
    {
        $icons = [
            'linkedin'  => 'ri-linkedin-box-fill',
            'twitter'   => 'ri-twitter-x-fill',
            'facebook'  => 'ri-facebook-box-fill',
            'youtube'   => 'ri-youtube-fill',
            'instagram' => 'ri-instagram-line',
            'telegram'  => 'ri-telegram-fill',
            'whatsapp'  => 'ri-whatsapp-fill',
        ];
        return $icons[$network] ?? 'ri-global-line';
    }
}

if (!function_exists('vp_menu_item_url')) {
    /**
     * Resolve a menu row to a browsable URL.
     */
    function vp_menu_item_url(array $item)
    {
        $type = $item['type'] ?? 'INTERNAL';
        if ($type === 'EXTERNAL') {
            return (string) ($item['url'] ?? '#');
        }
        if ($type === 'PAGE' && !empty($item['pageSlug'])) {
            return base_url($item['pageSlug']);
        }
        $url = trim((string) ($item['url'] ?? ''));
        if ($url === '' || $url === '/') return base_url();
        if (preg_match('~^(https?:)?//~i', $url)) return $url;
        return base_url(ltrim($url, '/'));
    }
}

if (!function_exists('vp_menu')) {
    /**
     * Navigation items for a menu location, straight from the database.
     * Returns [] when the CMS tables are not installed yet, so callers can
     * fall back to their built-in defaults.
     */
    function vp_menu($menu = 'header')
    {
        static $cache = [];
        if (isset($cache[$menu])) return $cache[$menu];
        $CI =& get_instance();
        $out = [];
        try {
            if (!$CI->db->table_exists('menu_items')) return $cache[$menu] = [];
            $CI->db->select('menu_items.*, pages.slug AS pageSlug, pages.status AS pageStatus');
            $CI->db->from('menu_items');
            $CI->db->join('pages', 'pages.id = menu_items.pageId', 'left');
            $CI->db->where('menu_items.menu', $menu);
            $CI->db->where('menu_items.isActive', 1);
            $CI->db->order_by('menu_items.sortOrder', 'ASC');
            $rows = $CI->db->get()->result_array();
        } catch (\Throwable $e) {
            log_message('error', 'vp_menu - ' . $e->getMessage());
            return $cache[$menu] = [];
        }
        foreach ($rows as $r) {
            if (($r['type'] ?? '') === 'PAGE' && ($r['pageStatus'] ?? '') !== 'PUBLISHED') continue;
            $r['href'] = vp_menu_item_url($r);
            $out[] = $r;
        }
        return $cache[$menu] = $out;
    }
}

if (!function_exists('vp_menu_is_active')) {
    function vp_menu_is_active(array $item)
    {
        $CI =& get_instance();
        $href = parse_url($item['href'] ?? '', PHP_URL_PATH);
        $here = '/' . ltrim((string) $CI->uri->uri_string(), '/');
        if (!$href) return false;
        $href = '/' . trim($href, '/');
        if ($href === '/') return $here === '/' || $here === '';
        return strpos($here, $href) === 0;
    }
}

if (!function_exists('vp_sections')) {
    /**
     * Active content sections for a page key ('home' by default).
     */
    function vp_sections($pageKey = 'home', $active_only = true)
    {
        static $cache = [];
        $ck = $pageKey . ($active_only ? ':1' : ':0');
        if (isset($cache[$ck])) return $cache[$ck];
        $CI =& get_instance();
        try {
            if (!$CI->db->table_exists('page_sections')) return $cache[$ck] = [];
            $CI->db->where('pageKey', $pageKey);
            if ($active_only) $CI->db->where('isActive', 1);
            $rows = $CI->db->order_by('sortOrder', 'ASC')->get('page_sections')->result_array();
        } catch (\Throwable $e) {
            log_message('error', 'vp_sections - ' . $e->getMessage());
            return $cache[$ck] = [];
        }
        foreach ($rows as &$r) {
            $r['settings'] = vp_section_settings($r);
        }
        return $cache[$ck] = $rows;
    }
}

if (!function_exists('vp_section_settings')) {
    function vp_section_settings($section)
    {
        $raw = is_array($section) ? ($section['settings'] ?? null) : $section;
        if (is_array($raw)) return $raw;
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('vp_section_option')) {
    function vp_section_option($section, $key, $default = null)
    {
        $s = vp_section_settings($section);
        return array_key_exists($key, $s) && $s[$key] !== '' ? $s[$key] : $default;
    }
}

if (!function_exists('vp_section_types')) {
    /**
     * The section types the public renderer understands.
     * key => [label, icon, description, supports]
     */
    function vp_section_types()
    {
        return [
            'hero'         => ['Hero banner',        'ri-image-line',        'Full-width banner with heading, text, image and up to two buttons.'],
            'stats'        => ['Statistics strip',   'ri-bar-chart-box-line','Row of key numbers (value + label).'],
            'categories'   => ['Product categories', 'ri-price-tag-3-line',  'Grid of active product categories.'],
            'products'     => ['Featured products',  'ri-box-3-line',        'Grid of featured products from the catalogue.'],
            'industries'   => ['Industries',         'ri-building-2-line',   'Grid of the industries you serve.'],
            'services'     => ['Services / features','ri-tools-line',        'Custom cards with icon, title and text.'],
            'testimonials' => ['Testimonials',       'ri-chat-quote-line',   'Customer testimonials marked as featured.'],
            'partners'     => ['Partners',           'ri-shake-hands-line',  'Partner logo strip.'],
            'faq'          => ['FAQ',                'ri-question-line',     'Frequently asked questions.'],
            'richtext'     => ['Rich text / About',  'ri-file-text-line',    'Free-form HTML content block.'],
            'banner'       => ['Promotional banner', 'ri-megaphone-line',    'Image banner with text and a button.'],
            'newsletter'   => ['Newsletter',         'ri-mail-send-line',    'Email sign-up block.'],
            'cta'          => ['Call to action',     'ri-cursor-line',       'Coloured band with heading and button.'],
            'image'        => ['Image',              'ri-image-add-line',    'Full-width or contained photo with optional caption.'],
            'gallery'      => ['Image gallery',      'ri-gallery-line',      'Grid of images visitors can browse.'],
            'video'        => ['Video',              'ri-video-line',        'Uploaded video file or YouTube/Vimeo embed URL.'],
            'file'         => ['File download',      'ri-file-upload-line',  'Downloadable document (PDF, Office, ZIP).'],
        ];
    }
}

if (!function_exists('vp_section_style_attr')) {
    function vp_section_style_attr($section)
    {
        $s = vp_section_settings($section);
        $css = [];
        $bg = trim((string) ($s['bg_color'] ?? ''));
        $fg = trim((string) ($s['text_color'] ?? ''));
        $hd = trim((string) ($s['heading_color'] ?? ''));
        if ($bg !== '' && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $bg)) {
            $css[] = 'background-color:' . vp_sanitize_hex_color($bg, $bg);
        }
        if ($fg !== '' && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $fg)) {
            $css[] = 'color:' . vp_sanitize_hex_color($fg, $fg);
        }
        if ($hd !== '' && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $hd)) {
            $css[] = '--vp-heading:' . vp_sanitize_hex_color($hd, $hd);
        }
        return $css ? ' style="' . implode(';', $css) . '"' : '';
    }
}

if (!function_exists('vp_section_type_label')) {
    function vp_section_type_label($type)
    {
        $t = vp_section_types();
        return $t[$type][0] ?? ucfirst((string) $type);
    }
}

if (!function_exists('vp_cms_page')) {
    /** Fetch a published CMS page by slug (null when missing/unpublished). */
    function vp_cms_page($slug)
    {
        $CI =& get_instance();
        try {
            if (!$CI->db->table_exists('pages')) return null;
        } catch (\Throwable $e) {
            return null;
        }
        $CI->load->model('Page_model');
        return $CI->Page_model->published($slug);
    }
}

if (!function_exists('vp_maintenance_active')) {
    /** TRUE when maintenance mode should hide the public site from visitors. */
    function vp_maintenance_active()
    {
        return (bool) vp_site('maintenance', false);
    }
}

if (!function_exists('vp_can')) {
    /**
     * Permission check usable from any view/controller.
     * SUPER_ADMIN always passes; everybody else is checked against the
     * effective permission set (role defaults + per-user overrides).
     */
    function vp_can($key)
    {
        $CI =& get_instance();
        if (!isset($CI->acl) || !isset($CI->vp_auth)) return false;
        return $CI->acl->user_can($CI->vp_auth->user(), $key);
    }
}

if (!function_exists('vp_is_super_admin')) {
    function vp_is_super_admin()
    {
        $CI =& get_instance();
        return isset($CI->vp_auth) && $CI->vp_auth->has_role(ROLE_SUPER_ADMIN);
    }
}

if (!function_exists('vp_dashboard_label')) {
    /** "Super Admin Dashboard" vs "Admin Dashboard". */
    function vp_dashboard_label()
    {
        return vp_is_super_admin() ? 'Super Admin' : 'Admin';
    }
}

if (!function_exists('vp_section_blocks')) {
    /**
     * Load only the catalogue data the given CMS sections need
     * (categories, featured products, industries, testimonials, partners, FAQ).
     *
     * Shared by the homepage and every other section-driven page so the
     * rendering of a section type is identical everywhere.
     */
    function vp_section_blocks(array $sections)
    {
        $CI =& get_instance();
        $out = [];
        foreach ($sections as $s) {
            $limit = (int) vp_section_option($s, 'limit', 6);
            switch ($s['type']) {
                case 'categories':
                    $CI->load->model('Category_model');
                    $out['categories'] = $CI->Category_model->find_all(['isActive' => 1], ['sortOrder' => 'ASC'], $limit ?: 6);
                    break;
                case 'products':
                    $CI->load->model('Product_model');
                    $rows = $CI->Product_model->find_all(['isActive' => 1, 'featured' => 1], ['views' => 'DESC', 'createdAt' => 'DESC'], $limit ?: 4);
                    if (empty($rows)) {
                        $rows = $CI->Product_model->find_all(['isActive' => 1], ['createdAt' => 'DESC'], $limit ?: 4);
                    }
                    $out['products'] = $CI->Product_model->attach_images($rows);
                    break;
                case 'industries':
                    $CI->load->model('Industry_model');
                    $out['industries'] = $CI->Industry_model->find_all(['isActive' => 1], ['sortOrder' => 'ASC'], $limit ?: 6);
                    break;
                case 'testimonials':
                    $CI->load->model('Testimonial_model');
                    $out['testimonials'] = $CI->Testimonial_model->find_all(['isActive' => 1], ['featured' => 'DESC', 'createdAt' => 'DESC'], $limit ?: 4);
                    break;
                case 'partners':
                    $CI->load->model('Partner_model');
                    $out['partners'] = $CI->Partner_model->find_all(['isActive' => 1], ['sortOrder' => 'ASC'], $limit ?: 12);
                    break;
                case 'faq':
                    $CI->load->model('Faq_model');
                    $out['faqs'] = $CI->Faq_model->find_all(['isActive' => 1], ['sortOrder' => 'ASC'], $limit ?: 6);
                    break;
            }
        }
        return $out;
    }
}

if (!function_exists('vp_sanitize_hex_color')) {
    /**
     * Accept only #RGB / #RRGGBB. Anything else falls back so a crafted
     * setting cannot inject CSS.
     */
    function vp_sanitize_hex_color($value, $fallback = '#000000')
    {
        $value = trim((string) $value);
        if (preg_match('/^#([0-9a-fA-F]{3})$/', $value, $m)) {
            $h = strtolower($m[1]);
            return '#' . $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $value)) {
            return strtolower($value);
        }
        return $fallback;
    }
}

if (!function_exists('vp_theme_defaults')) {
    /** Built-in theme colours used when a setting is empty or invalid. */
    function vp_theme_defaults()
    {
        return [
            'bg'               => '#ffffff',
            'writeup'          => '#000000',
            'sidebar_bg'       => '#000000',
            'sidebar_writeup'  => '#ffffff',
        ];
    }
}

if (!function_exists('vp_theme')) {
    /**
     * Dashboard-managed site colours.
     *
     * Keys: bg, writeup, sidebar_bg, sidebar_writeup (always 7-char hex).
     */
    function vp_theme($key = null)
    {
        static $cache = null;
        if ($cache === null) {
            $d = vp_theme_defaults();
            $cache = [
                'bg'              => vp_sanitize_hex_color(vp_cms_setting('theme_bg', $d['bg']), $d['bg']),
                'writeup'         => vp_sanitize_hex_color(vp_cms_setting('theme_writeup', $d['writeup']), $d['writeup']),
                'sidebar_bg'      => vp_sanitize_hex_color(vp_cms_setting('theme_sidebar_bg', $d['sidebar_bg']), $d['sidebar_bg']),
                'sidebar_writeup' => vp_sanitize_hex_color(vp_cms_setting('theme_sidebar_writeup', $d['sidebar_writeup']), $d['sidebar_writeup']),
            ];
        }
        if ($key === null) return $cache;
        return $cache[$key] ?? vp_theme_defaults()[$key] ?? '#000000';
    }
}

if (!function_exists('vp_theme_style_tag')) {
    /**
     * Last-in-document colour lock. Tailwind CDN and the readability pass
     * both hard-code greys; this block re-applies the dashboard colours so
     * Admin / Super Admin changes actually show on the live site.
     */
    function vp_theme_style_tag()
    {
        $t = vp_theme();
        $bg      = $t['bg'];
        $writeup = $t['writeup'];
        $sbg     = $t['sidebar_bg'];
        $stext   = $t['sidebar_writeup'];
        return '<style id="vp-theme">'
            . ':root{--vp-bg:' . $bg . ';--vp-writeup:' . $writeup
            . ';--vp-sidebar-bg:' . $sbg . ';--vp-sidebar-writeup:' . $stext . ';}'
            . 'html,body{background-color:var(--vp-bg)!important;color:var(--vp-writeup)!important;}'
            . '.vp-prose,.vp-prose p,.vp-prose li,.vp-card p,.vp-card li,.vp-review p,.vp-review,'
            . '.vp-label,form label,'
            . '.text-ink-800,.text-ink-900,.text-ink-700{color:var(--vp-writeup)!important;}'
            . 'body .bg-white,body .bg-gray-50,header.bg-white{background-color:var(--vp-bg)!important;}'
            . '.text-white{color:#ffffff!important;}'
            . '.bg-ink-900 p,.bg-ink-800 p,.from-ink-900 p,.from-brand-600 p,'
            . '.bg-ink-900 li,.from-ink-900 li,.from-brand-600 li,footer p,footer li{color:#ffffff;}'
            . '.bg-ink-900 .text-ink-800,.from-ink-900 .text-ink-800,'
            . '.bg-ink-900 .text-ink-900,.from-ink-900 .text-ink-900,'
            . '.from-brand-600 .text-ink-800,footer .text-ink-800,footer .text-ink-900,'
            . '.bg-ink-900 h1,.bg-ink-900 h2,.bg-ink-900 h3,.bg-ink-900 h4,'
            . '.from-ink-900 h1,.from-ink-900 h2,.from-ink-900 h3,.from-ink-900 h4,'
            . 'footer h1,footer h2,footer h3,footer h4{color:#ffffff!important;}'
            . '.vp-writeup-band{background-color:#000000!important;color:#ffffff!important;}'
            . '.vp-writeup-band h1,.vp-writeup-band h2,.vp-writeup-band h3,.vp-writeup-band h4,'
            . '.vp-writeup-band p,.vp-writeup-band span,.vp-writeup-band li,.vp-writeup-band div,'
            . '.vp-writeup-band .text-white,.vp-writeup-band .text-ink-700,'
            . '.vp-writeup-band .text-ink-800,.vp-writeup-band .text-ink-900,'
            . '.bg-ink-900 .vp-writeup-band,.bg-ink-900 .vp-writeup-band h1,.bg-ink-900 .vp-writeup-band h2,'
            . '.bg-ink-900 .vp-writeup-band h3,.bg-ink-900 .vp-writeup-band p,'
            . '.from-ink-900 .vp-writeup-band,.from-ink-900 .vp-writeup-band h1,.from-ink-900 .vp-writeup-band p,'
            . '.from-brand-600.vp-writeup-band,.from-brand-600.vp-writeup-band h2,.from-brand-600.vp-writeup-band p{color:#ffffff!important;}'
            . 'body .vp-writeup-band{background-color:#000000!important;color:#ffffff!important;}'
            . 'body .vp-writeup-overlay{background-color:rgba(255,255,255,0.10)!important;box-shadow:none!important;}'
            . '.vp-writeup-overlay h1,.vp-writeup-overlay h2,.vp-writeup-overlay h3,.vp-writeup-overlay h4,'
            . '.vp-writeup-overlay p,.vp-writeup-overlay span,.vp-writeup-overlay li,.vp-writeup-overlay div{'
            . 'color:#ffffff!important;text-shadow:0 1px 3px rgba(0,0,0,0.9),0 0 14px rgba(0,0,0,0.45);}'
            . '.vp-writeup-band .vp-btn-primary,.vp-writeup-band a.bg-brand-500,'
            . '.vp-writeup-band a.bg-brand-600,.vp-writeup-band a.bg-brand-700,'
            . '.vp-writeup-band .vp-cta-quote{color:#ffffff!important;}'
            . '.vp-writeup-band a.bg-white,.vp-writeup-band a.bg-gray-50{color:#000000!important;}'
            . 'aside.vp-admin-sidebar{background-color:var(--vp-sidebar-bg)!important;color:var(--vp-sidebar-writeup)!important;}'
            . 'aside.vp-admin-sidebar,aside.vp-admin-sidebar a,aside.vp-admin-sidebar span,'
            . 'aside.vp-admin-sidebar div,aside.vp-admin-sidebar nav,aside.vp-admin-sidebar p,'
            . 'aside.vp-admin-sidebar i{color:var(--vp-sidebar-writeup)!important;}'
            . 'aside.vp-admin-sidebar .bg-brand-600,aside.vp-admin-sidebar .bg-brand-600 *,'
            . 'aside.vp-admin-sidebar .bg-red-500{color:#ffffff!important;}'
            . '</style>';
    }
}

if (!function_exists('vp_with_query')) {
    /**
     * Return the current request path with one query parameter added/replaced
     * (or removed when $value is null). Preserves every other parameter, so
     * the inline-edit toggle never drops ?category= / ?page= filters.
     */
    function vp_with_query($key, $value = null)
    {
        $CI    =& get_instance();
        $uri   = (string) $CI->input->server('REQUEST_URI');
        $parts = parse_url($uri);
        $path  = $parts['path'] ?? '/';
        parse_str($parts['query'] ?? '', $qs);
        if ($value === null) {
            unset($qs[$key]);
        } else {
            $qs[$key] = $value;
        }
        $query = http_build_query($qs);
        return $path . ($query !== '' ? '?' . $query : '');
    }
}

if (!function_exists('vp_inline_editing')) {
    /**
     * TRUE when the current public page is being edited inline (the
     * WordPress-style live editor). The real permission/role gate lives in
     * MY_Controller::render(); this only reads the flag it sets.
     */
    function vp_inline_editing()
    {
        $CI =& get_instance();
        return !empty($CI->data['inline_edit']);
    }
}

if (!function_exists('vp_inline_section_data')) {
    /**
     * Normalise a page section into the JSON the inline editor panel needs.
     * Output is safe to place inside a <script type="application/json"> block
     * (tags are hex-escaped so a "</script>" sequence can never appear).
     */
    function vp_inline_section_data(array $section)
    {
        $s = vp_section_settings($section);
        $obj = [
            'id'          => (string) ($section['id'] ?? ''),
            'type'        => (string) ($section['type'] ?? ''),
            'name'        => (string) ($section['name'] ?? ''),
            'title'       => (string) ($section['title'] ?? ''),
            'subtitle'    => (string) ($section['subtitle'] ?? ''),
            'body'        => (string) ($section['body'] ?? ''),
            'buttonText'  => (string) ($section['buttonText'] ?? ''),
            'buttonUrl'   => (string) ($section['buttonUrl'] ?? ''),
            'buttonText2' => (string) ($section['buttonText2'] ?? ''),
            'buttonUrl2'  => (string) ($section['buttonUrl2'] ?? ''),
            'colors'      => [
                'bg'      => (string) ($s['bg_color'] ?? ''),
                'text'    => (string) ($s['text_color'] ?? ''),
                'heading' => (string) ($s['heading_color'] ?? ''),
            ],
        ];
        return json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG);
    }
}

if (!function_exists('vp_inline_section_open')) {
    /**
     * Wrapper that owns one rendered page section. In inline-edit mode it
     * carries an "Edit" button and the section's data so the live editor can
     * open it without a round-trip.
     */
    function vp_inline_section_open(array $section)
    {
        $id = (string) ($section['id'] ?? '');
        $html = '<div class="vp-inline-section" data-vp-section="' . vp_safe_html($id) . '"'
              . ' data-vp-section-type="' . vp_safe_html((string) ($section['type'] ?? '')) . '">';
        if (vp_inline_editing() && $id !== '' && strpos($id, 'fallback-') !== 0) {
            $html .= '<button type="button" class="vp-inline-edit-btn" data-vp-inline-edit="' . vp_safe_html($id) . '" aria-label="Edit this section">'
                   . '<i class="ri-edit-line"></i> Edit</button>';
            $html .= '<script type="application/json" data-vp-section-data="' . vp_safe_html($id) . '">'
                   . vp_inline_section_data($section) . '</script>';
        }
        return $html;
    }
}

if (!function_exists('vp_inline_section_close')) {
    function vp_inline_section_close()
    {
        return '</div>';
    }
}

if (!function_exists('vp_inline_text')) {
    /**
     * Render a page text block that, in inline-edit mode, can be rewritten
     * directly on the live page. The value is stored as a site setting under
     * $key so changes persist without touching any view file.
     */
    function vp_inline_text($key, $fallback, $tag = 'p', $class = '')
    {
        $tag = in_array($tag, ['h1', 'h2', 'h3', 'h4', 'p', 'span', 'div'], true) ? $tag : 'p';
        $value = (string) vp_cms_setting($key, $fallback);
        $cls   = $class !== '' ? ' class="' . vp_safe_html($class) . '"' : '';
        if (vp_inline_editing()) {
            return '<' . $tag . $cls . ' data-vp-editable data-vp-setting="' . vp_safe_html($key) . '">'
                 . vp_safe_html($value) . '</' . $tag . '>';
        }
        return '<' . $tag . $cls . '>' . vp_safe_html($value) . '</' . $tag . '>';
    }
}

if (!function_exists('vp_email_health')) {
    /**
     * Normalised outgoing-email health for the dashboard.
     * Mailer::health() nests the transport description; flatten it so views
     * never have to deal with the shape.
     */
    function vp_email_health()
    {
        $CI =& get_instance();
        $h = $CI->mailer->health();
        $t = $h['transport'] ?? [];
        return [
            'transport' => is_array($t) ? ($t['transport'] ?? 'unknown') : (string) $t,
            'message'   => is_array($t) ? ($t['reason'] ?? '') : '',
            'ok'        => is_array($t) ? empty($t['misconfigured']) : true,
            'sent_7d'   => (int) ($h['sent_7d'] ?? 0),
            'failed_7d' => (int) ($h['failed_7d'] ?? 0),
            'last_error'=> $h['last_error'] ?? null,
        ];
    }
}
