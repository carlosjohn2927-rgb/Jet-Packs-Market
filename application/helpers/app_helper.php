<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - app-wide helper functions.
 */

if (!function_exists('vp_setting')) {
    /**
     * Get a setting by key with optional default.
     * Returns the value, or $default if not set.
     */
    function vp_setting($key, $default = null)
    {
        $CI =& get_instance();
        if (!isset($CI->settings)) return $default;
        return $CI->settings->get($key, $default);
    }
}

if (!function_exists('vp_settings_group')) {
    /**
     * Get all settings in a group.
     */
    function vp_settings_group($group)
    {
        $CI =& get_instance();
        if (!isset($CI->settings)) return [];
        return $CI->settings->by_group($group);
    }
}

if (!function_exists('vp_money')) {
    /**
     * Format a money amount.
     */
    function vp_money($amount, $currency = 'USD')
    {
        if ($amount === null || $amount === '') return '—';
        $sym = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹'][$currency] ?? '$';
        return $sym . number_format((float) $amount, 2);
    }
}

if (!function_exists('vp_slugify')) {
    /**
     * Make a URL slug from a string.
     */
    function vp_slugify($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) return 'n-a';
        return $text;
    }
}

if (!function_exists('vp_truncate')) {
    /**
     * Truncate a string to $limit chars at word boundary.
     */
    function vp_truncate($text, $limit = 200, $suffix = '…')
    {
        $text = strip_tags((string) $text);
        if (mb_strlen($text) <= $limit) return $text;
        $cut = mb_substr($text, 0, $limit);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false) $cut = mb_substr($cut, 0, $space);
        return $cut . $suffix;
    }
}

if (!function_exists('vp_human_date')) {
    /**
     * Format a datetime for display.
     */
    function vp_human_date($dt, $format = 'M j, Y')
    {
        if (!$dt) return '';
        if (is_string($dt)) $dt = strtotime($dt);
        return date($format, $dt);
    }
}

if (!function_exists('vp_time_ago')) {
    /**
     * Returns "3 hours ago" style string.
     */
    function vp_time_ago($dt)
    {
        if (!$dt) return '';
        if (is_string($dt)) $dt = strtotime($dt);
        $diff = time() - $dt;
        if ($diff < 60)        return $diff . 's ago';
        if ($diff < 3600)      return floor($diff / 60) . 'm ago';
        if ($diff < 86400)     return floor($diff / 3600) . 'h ago';
        if ($diff < 2592000)   return floor($diff / 86400) . 'd ago';
        if ($diff < 31536000)  return floor($diff / 2592000) . 'mo ago';
        return floor($diff / 31536000) . 'y ago';
    }
}

if (!function_exists('vp_quote_status_label')) {
    /**
     * Display label + Bootstrap class for a quote status.
     */
    function vp_quote_status_label($status)
    {
        $map = [
            QUOTE_NEW       => ['label' => 'New',       'class' => 'bg-blue-100 text-blue-800'],
            QUOTE_REVIEWING => ['label' => 'Reviewing', 'class' => 'bg-yellow-100 text-yellow-800'],
            QUOTE_QUOTED    => ['label' => 'Quoted',    'class' => 'bg-indigo-100 text-indigo-800'],
            QUOTE_APPROVED  => ['label' => 'Approved',  'class' => 'bg-green-100 text-green-800'],
            QUOTE_REJECTED  => ['label' => 'Rejected',  'class' => 'bg-red-100 text-red-800'],
            QUOTE_COMPLETED => ['label' => 'Completed', 'class' => 'bg-gray-200 text-gray-800'],
        ];
        return $map[$status] ?? ['label' => $status, 'class' => 'bg-gray-100 text-gray-800'];
    }
}

if (!function_exists('vp_role_label')) {
    function vp_role_label($role)
    {
        $map = [
            ROLE_SUPER_ADMIN => 'Super Admin',
            ROLE_ADMIN       => 'Admin',
            ROLE_SALES       => 'Sales',
            ROLE_ENGINEER    => 'Engineer',
            ROLE_EDITOR      => 'Editor',
            ROLE_CUSTOMER    => 'Customer',
        ];
        return $map[$role] ?? $role;
    }
}

if (!function_exists('vp_avatar_url')) {
    /**
     * Return a saved profile avatar when available, otherwise fall back to
     * Gravatar. Accepts either a user row array or an email string.
     */
    function vp_avatar_url($user_or_email, $size = 80, $avatar = null)
    {
        if (is_array($user_or_email)) {
            $avatar = $user_or_email['avatar'] ?? $avatar;
            $email = $user_or_email['email'] ?? '';
        } else {
            $email = $user_or_email;
        }

        $avatar = trim((string) $avatar);
        if ($avatar !== '') {
            if (function_exists('vp_asset_url')) return vp_asset_url($avatar);
            if (preg_match('~^(https?:)?//~i', $avatar) || strpos($avatar, 'data:') === 0 || $avatar[0] === '/') return $avatar;
            return '/' . ltrim($avatar, '/');
        }

        $hash = md5(strtolower(trim((string) $email)));
        return 'https://www.gravatar.com/avatar/' . $hash . '?s=' . (int) $size . '&d=mp';
    }
}

if (!function_exists('vp_safe_html')) {
    /**
     * Output a string with HTML escaping.
     */
    function vp_safe_html($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('vp_excerpt_from_html')) {
    /**
     * Strip tags and truncate.
     */
    function vp_excerpt_from_html($html, $limit = 200)
    {
        return vp_truncate($html, $limit);
    }
}

if (!function_exists('vp_pagination_links')) {
    /**
     * Build a simple Bootstrap-style pagination block.
     *
     * @param string $base  Base URL with {page} placeholder, e.g. '/products?page={page}'
     */
    function vp_pagination_links($total_pages, $page, $base)
    {
        if ($total_pages <= 1) return '';
        $page = max(1, (int) $page);
        $html = '<nav class="vp-pagination" aria-label="Pagination"><ul class="inline-flex -space-x-px">';
        $prev = max(1, $page - 1);
        $next = min($total_pages, $page + 1);
        $url_for = function ($p) use ($base) { return str_replace('{page}', (string) $p, $base); };

        $html .= '<li><a class="px-3 py-2 border rounded-l-lg ' . ($page <= 1 ? 'text-ink-800 pointer-events-none' : 'hover:bg-gray-100') . '" href="' . $url_for($prev) . '">Prev</a></li>';
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i === 1 || $i === $total_pages || abs($i - $page) <= 2) {
                $cls = ($i === $page) ? 'bg-blue-600 text-white' : 'hover:bg-gray-100';
                $html .= '<li><a class="px-3 py-2 border ' . $cls . '" href="' . $url_for($i) . '">' . $i . '</a></li>';
            } elseif (abs($i - $page) === 3) {
                $html .= '<li><span class="px-3 py-2 border">…</span></li>';
            }
        }
        $html .= '<li><a class="px-3 py-2 border rounded-r-lg ' . ($page >= $total_pages ? 'text-ink-800 pointer-events-none' : 'hover:bg-gray-100') . '" href="' . $url_for($next) . '">Next</a></li>';
        $html .= '</ul></nav>';
        return $html;
    }
}

if (!function_exists('vp_product_image')) {
    /**
     * Resolve the best available image URL for a product row.
     *
     * Resolution order:
     *   1. `imageUrl`  - uploaded primary image (Product_model::attach_images)
     *   2. `image`     - a legacy single-image column, if present
     *   3. dedicated artwork for each seeded catalog product
     *   4. category artwork  /assets/img/products/<category-slug>.jpg
     *      (valves, pumps, heat-exchangers, pressure-vessels, filtration,
     *       instrumentation - the files shipped in app/assets/img/products/)
     *   5. a keyword guess from the product name, so a product with no
     *      category still gets a relevant photo instead of the placeholder
     *   6. /assets/img/products/default.jpg
     *
     * @param  array|null  $product   Product row
     * @param  string|null $categorySlug Optional explicit category slug
     * @return string      Absolute-from-root image URL
     */
    function vp_product_image($product, $categorySlug = null)
    {
        $product = (array) $product;
        $default = IMG_URL . 'products/default.jpg';

        // 1 + 2: a real uploaded image always wins.
        foreach (['imageUrl', 'image'] as $k) {
            if (!empty($product[$k])) return $product[$k];
        }

        // Seeded catalog products have dedicated, curated artwork. Uploaded
        // primary images above still take precedence for CMS-managed content.
        $productArtwork = [
            'vortexpro-ball-valve-vp150'     => 'vortexpro-ball-valve-vp150.jpg',
            'vortexpro-gate-valve-vgs'       => 'vortexpro-gate-valve-vgs.jpg',
            'vortexpro-centrifugal-pump-vp220' => 'vortexpro-centrifugal-pump-vp220.jpg',
            'vortexpro-pd-pump-vppd'         => 'vortexpro-pd-pump-vppd.jpg',
            'vortexpro-phe-vpphe'            => 'vortexpro-phe-vpphe.jpg',
            'vortexpro-sh-vpsh'              => 'vortexpro-sh-vpsh.jpg',
            'vortexpro-pv-vppv'              => 'vortexpro-pv-vppv.jpg',
            'vortexpro-bf-vpbf'              => 'vortexpro-bf-vpbf.jpg',
            'vortexpro-cf-vpcf'              => 'vortexpro-cf-vpcf.jpg',
            'vortexpro-pg-vppg'              => 'vortexpro-pg-vppg.jpg',
            'vortexpro-lt-vplt'              => 'vortexpro-lt-vplt.jpg',
            'vortexpro-cv-vpcv'              => 'vortexpro-cv-vpcv.jpg',
        ];
        $productSlug = $product['slug'] ?? '';
        if (isset($productArtwork[$productSlug])) {
            return IMG_URL . 'products/' . $productArtwork[$productSlug];
        }

        // Category artwork shipped with the theme.
        $known = ['valves', 'pumps', 'heat-exchangers', 'pressure-vessels', 'filtration', 'instrumentation'];

        $slug = $categorySlug ?: ($product['categorySlug'] ?? null);
        if ($slug && in_array($slug, $known, true)) {
            return IMG_URL . 'products/' . $slug . '.jpg';
        }

        // 4: keyword guess from the product name / sku / description.
        $hay = strtolower(trim(($product['name'] ?? '') . ' ' . ($product['shortDescription'] ?? '')));
        if ($hay !== '') {
            $map = [
                'valves'           => ['valve', 'ball ', 'gate ', 'globe ', 'butterfly', 'check ', 'actuator', 'choke'],
                'pumps'            => ['pump', 'impeller', 'centrifugal'],
                'heat-exchangers'  => ['heat exchanger', 'exchanger', 'shell and tube', 'shell & tube', 'cooler', 'condenser', 'chiller'],
                'pressure-vessels' => ['pressure vessel', 'vessel', 'separator', 'tank', 'accumulator', 'reactor', 'drum'],
                'filtration'       => ['filter', 'filtration', 'strainer', 'coalescer', 'separator element', 'cartridge'],
                'instrumentation'  => ['gauge', 'transmitter', 'sensor', 'instrument', 'meter', 'flow meter', 'indicator', 'switch'],
            ];
            foreach ($map as $folderSlug => $needles) {
                foreach ($needles as $n) {
                    if (strpos($hay, $n) !== false) {
                        return IMG_URL . 'products/' . $folderSlug . '.jpg';
                    }
                }
            }
        }

        return $default;
    }
}

if (!function_exists('vp_product_image_tag')) {
    /**
     * Render a complete <img> for a product card, with an onerror fallback so
     * a deleted upload can never leave a broken-image icon on the page.
     *
     * @param array|null $product
     * @param string     $class  CSS classes for the <img>
     * @param string|null $categorySlug
     * @param string     $loading 'lazy' (default) or 'eager'
     */
    function vp_product_image_tag($product, $class = 'w-full h-full object-cover', $categorySlug = null, $loading = 'lazy')
    {
        $product = (array) $product;
        $src = vp_product_image($product, $categorySlug);
        $alt = $product['imageAlt'] ?? ($product['name'] ?? 'Product');
        $fallback = IMG_URL . 'products/default.jpg';
        return '<img src="' . vp_safe_html($src) . '"'
            . ' alt="' . vp_safe_html($alt) . '"'
            . ' loading="' . vp_safe_html($loading) . '" decoding="async"'
            . ' class="' . vp_safe_html($class) . '"'
            . ' onerror="this.onerror=null;this.src=\'' . $fallback . '\'">';
    }
}

if (!function_exists('vp_industry_image')) {
    /** Resolve the supplied industry artwork with a safe, relevant local fallback. */
    function vp_industry_image($industry)
    {
        $industry = (array) $industry;
        if (!empty($industry['image'])) return $industry['image'];
        $slug = vp_slugify($industry['slug'] ?? $industry['name'] ?? 'oil-gas');
        $known = ['oil-gas', 'chemical-processing', 'power-generation', 'water-wastewater', 'pharmaceutical', 'food-beverage'];
        if (!in_array($slug, $known, true)) $slug = 'oil-gas';
        return IMG_URL . 'industries/' . $slug . '.jpg';
    }
}

if (!function_exists('vp_blog_image')) {
    /** Resolve editorial artwork. Uploaded artwork wins over curated local fallbacks. */
    function vp_blog_image($post)
    {
        $post = (array) $post;
        if (!empty($post['featuredImage'])) return $post['featuredImage'];
        $slug = $post['slug'] ?? '';
        if ($slug === 'choosing-the-right-ball-valve') return IMG_URL . 'blog/ball-valve-selection.jpg';
        if (strpos($slug, 'pressure-vessel') !== false || strpos($slug, 'asme') !== false) {
            return IMG_URL . 'blog/asme-pressure-vessel.jpg';
        }
        return IMG_URL . 'products/default.jpg';
    }
}

if (!function_exists('vp_testimonial_image')) {
    /**
     * Resolve a customer-review portrait. Uploaded avatar wins; otherwise
     * fall back to the curated headshots shipped with the theme.
     */
    function vp_testimonial_image($testimonial)
    {
        $t = (array) $testimonial;
        if (!empty($t['avatar'])) {
            $avatar = (string) $t['avatar'];
            if (preg_match('~^https?://~i', $avatar) || strpos($avatar, '/') === 0) {
                return $avatar;
            }
            return IMG_URL . ltrim($avatar, '/');
        }

        $slug = vp_slugify($t['name'] ?? '');
        $map = [
            'mark-henderson' => 'reviews/mark-henderson.jpg',
            'linda-park'     => 'reviews/linda-park.jpg',
            'akhil-raman'    => 'reviews/akhil-raman.jpg',
            'jonas-weber'    => 'reviews/jonas-weber.jpg',
        ];
        if (isset($map[$slug])) {
            return IMG_URL . $map[$slug];
        }
        return IMG_URL . 'reviews/mark-henderson.jpg';
    }
}

if (!function_exists('vp_news_image')) {
    /** Resolve news artwork from the CMS or the subject of a seeded story. */
    function vp_news_image($story)
    {
        $story = (array) $story;
        if (!empty($story['image'])) return $story['image'];
        $slug = $story['slug'] ?? '';
        if (strpos($slug, 'skid') !== false || strpos($slug, 'heat') !== false) return IMG_URL . 'news/skid-delivery.jpg';
        if (strpos($slug, 'pump') !== false) return IMG_URL . 'news/sanitary-pump.jpg';
        if (strpos($slug, 'iso') !== false || strpos($slug, 'quality') !== false) return IMG_URL . 'news/iso-quality.jpg';
        return IMG_URL . 'hero-industrial.jpg';
    }
}

if (!function_exists('vp_format_bytes')) {
    function vp_format_bytes($bytes, $precision = 1)
    {
        $units = ['B','KB','MB','GB','TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('vp_quote_number')) {
    /**
     * Generate a human-friendly quote number, e.g. VP-2026-000123.
     */
    function vp_quote_number()
    {
        static $seq = null;
        $year = date('Y');
        $CI =& get_instance();
        if ($seq === null) {
            $CI->db->select('COUNT(*) AS c');
            $CI->db->from('quotes');
            $CI->db->like('quoteNumber', 'VP-' . $year, 'after');
            $row = $CI->db->get()->row_array();
            $seq = (int) ($row['c'] ?? 0);
        }
        $seq++;
        return 'VP-' . $year . '-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}

/* --------------------------------------------------------------------- */
/* SEO                                                                   */
/* --------------------------------------------------------------------- */

if (!function_exists('vp_seo_config')) {
    /**
     * Merge the SEO settings (settings table, group "SEO") with safe defaults.
     * Every key has a fallback so the site renders correct SEO even before
     * the admin has configured anything.
     */
    function vp_seo_config()
    {
        $CI   =& get_instance();
        // Dashboard-managed identity wins over the config defaults.
        $site    = $CI->settings->get('site_name') ?: ($CI->config->item('site_name') ?: 'Halyk Petroleum');
        $tagline = $CI->settings->get('site_description')
                    ?: ($CI->settings->get('site_tagline') ?: ($CI->config->item('site_tagline') ?: 'Industrial Manufacturing Excellence'));

        // Fall back to $default when the stored value is missing OR empty,
        // so clearing a field in admin restores the sensible default.
        $val = function ($key, $default = '') use ($CI) {
            $v = $CI->settings->get($key);
            if ($v === null || $v === '') return $default;
            return $v;
        };
        $bool = function ($key, $default = true) use ($CI) {
            $v = $CI->settings->get($key);
            if ($v === null || $v === '') return $default;
            if (is_bool($v)) return $v;
            return $v === '1' || $v === 'true' || $v === 'on';
        };

        return [
            'title_suffix'        => (string) $val('seo_title_suffix', ' | ' . $site),
            'default_title'       => (string) $val('seo_default_title', $site),
            'default_description' => (string) $val('seo_default_description', $tagline),
            'keywords'            => (string) $val('seo_keywords', ''),
            'robots'              => (string) $val('seo_robots', 'index, follow'),
            'og_image'            => (string) $val('seo_og_image', IMG_URL . 'hero-industrial.jpg'),
            'canonical_domain'    => rtrim((string) $val('seo_canonical_domain', ''), '/'),
            'twitter_site'        => (string) $val('seo_twitter_site', ''),
            'facebook_app_id'     => (string) $val('seo_facebook_app_id', ''),
            'google_verification' => (string) $val('seo_google_verification', ''),
            'bing_verification'   => (string) $val('seo_bing_verification', ''),
            'enable_jsonld'       => $bool('seo_enable_jsonld', true),
            'schema_type'         => (string) $val('seo_schema_type', 'Organization'),
            'schema_name'         => (string) $val('seo_schema_name', $site),
            'schema_logo'         => (string) $val('seo_schema_logo', function_exists('vp_logo_url') ? vp_logo_url('light') : IMG_URL . 'logo-header.png'),
            'schema_json'         => trim((string) $val('seo_schema_json', '')),
        ];
    }
}

if (!function_exists('vp_seo_canonical_url')) {
    /**
     * Build the canonical URL for the current request.
     * Uses the configured canonical domain when set, otherwise the base URL.
     * Query strings are dropped (canonical points at the canonical resource).
     */
    function vp_seo_canonical_url($url = null)
    {
        $CI  =& get_instance();
        $seo = vp_seo_config();
        $url = $url ?: current_url();

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        if (!empty($seo['canonical_domain'])) {
            return $seo['canonical_domain'] . $path;
        }
        $base = rtrim($CI->config->item('base_url'), '/');
        return $base . $path;
    }
}

if (!function_exists('vp_seo_jsonld')) {
    /**
     * Build the JSON-LD structured data block (Organization by default, or a
     * fully custom document when seo_schema_json is configured).
     */
    function vp_seo_jsonld($nonce = '')
    {
        $seo = vp_seo_config();
        if (!$seo['enable_jsonld']) return '';

        $url = vp_seo_canonical_url();

        if ($seo['schema_json'] !== '') {
            $json = $seo['schema_json'];
            if (json_decode($json) === null) return ''; // refuse to emit invalid JSON
        } else {
            $schema = [
                '@context' => 'https://schema.org',
                '@type'    => $seo['schema_type'],
                'name'     => $seo['schema_name'],
                'url'      => $url,
            ];
            if (strtolower($seo['schema_type']) === 'organization') {
                $schema['logo'] = $seo['schema_logo'];
                $CI =& get_instance();
                $contact = [
                    '@type'       => 'ContactPoint',
                    'contactType' => 'sales',
                    'telephone'   => $CI->config->item('phone') ?: '',
                    'email'       => $CI->config->item('contact_email') ?: '',
                ];
                if ($contact['telephone'] || $contact['email']) $schema['contactPoint'] = $contact;
            }
            $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $nonceAttr = $nonce !== '' ? ' nonce="' . vp_safe_html($nonce) . '"' : '';
        return '<script type="application/ld+json"' . $nonceAttr . '>' . $json . '</script>';
    }
}

if (!function_exists('vp_seo_head')) {
    /**
     * Render the full <head> SEO block: title, description, keywords, robots,
     * canonical, Open Graph, Twitter Card, site verification and JSON-LD.
     *
     * @param string $page_title       Page-specific title (no site suffix)
     * @param string $page_description Page-specific meta description
     * @param string $url              Canonical URL (defaults to current URL)
     * @param string $nonce            CSP nonce for the JSON-LD script tag
     */
    function vp_seo_head($page_title, $page_description, $url = null, $nonce = '')
    {
        $CI   =& get_instance();
        $seo  = vp_seo_config();
        $site = function_exists('vp_site') ? vp_site('name') : ($CI->config->item('site_name') ?: 'Halyk Petroleum');

        $title = trim((string) $page_title) !== '' ? $page_title : $seo['default_title'];
        // Do not repeat the site name when the page title already carries it.
        if (stripos($title, trim((string) $site)) === false) {
            $title .= $seo['title_suffix'];
        }
        $desc  = trim((string) $page_description) !== '' ? $page_description : $seo['default_description'];
        $canonical = vp_seo_canonical_url($url);

        $out  = '<title>' . vp_safe_html($title) . '</title>' . "\n";
        $out .= '<meta name="description" content="' . vp_safe_html($desc) . '">' . "\n";
        if ($seo['keywords'] !== '') {
            $out .= '<meta name="keywords" content="' . vp_safe_html($seo['keywords']) . '">' . "\n";
        }
        $out .= '<meta name="robots" content="' . vp_safe_html($seo['robots']) . '">' . "\n";
        $out .= '<link rel="canonical" href="' . vp_safe_html($canonical) . '">' . "\n";

        // Open Graph
        $out .= '<meta property="og:type" content="website">' . "\n";
        $out .= '<meta property="og:site_name" content="' . vp_safe_html($site) . '">' . "\n";
        $out .= '<meta property="og:title" content="' . vp_safe_html($title) . '">' . "\n";
        $out .= '<meta property="og:description" content="' . vp_safe_html($desc) . '">' . "\n";
        $out .= '<meta property="og:url" content="' . vp_safe_html($canonical) . '">' . "\n";
        $out .= '<meta property="og:image" content="' . vp_safe_html($seo['og_image']) . '">' . "\n";
        if ($seo['facebook_app_id'] !== '') {
            $out .= '<meta property="fb:app_id" content="' . vp_safe_html($seo['facebook_app_id']) . '">' . "\n";
        }

        // Twitter Card
        $out .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $out .= '<meta name="twitter:title" content="' . vp_safe_html($title) . '">' . "\n";
        $out .= '<meta name="twitter:description" content="' . vp_safe_html($desc) . '">' . "\n";
        $out .= '<meta name="twitter:image" content="' . vp_safe_html($seo['og_image']) . '">' . "\n";
        if ($seo['twitter_site'] !== '') {
            $out .= '<meta name="twitter:site" content="' . vp_safe_html($seo['twitter_site']) . '">' . "\n";
        }

        // Search-engine site verification
        if ($seo['google_verification'] !== '') {
            $out .= '<meta name="google-site-verification" content="' . vp_safe_html($seo['google_verification']) . '">' . "\n";
        }
        if ($seo['bing_verification'] !== '') {
            $out .= '<meta name="msvalidate.01" content="' . vp_safe_html($seo['bing_verification']) . '">' . "\n";
        }

        // Structured data
        $out .= vp_seo_jsonld($nonce);

        return $out;
    }
}

/* --------------------------------------------------------------------- */
/* AI Chat                                                               */
/* --------------------------------------------------------------------- */

if (!function_exists('vp_chat_config')) {
    /**
     * Merge the AI chat settings (settings table, group "CHAT") with defaults.
     */
    function vp_chat_config()
    {
        $CI   =& get_instance();
        $site = $CI->config->item('site_name') ?: 'Halyk Petroleum';

        $quick = vp_setting('chat_quick_replies', []);
        if (is_string($quick)) {
            $decoded = json_decode($quick, true);
            $quick = is_array($decoded) ? $decoded : [];
        }

        // Secrets / provider config can come from the settings table or the
        // environment (VP_AI_*). Env wins when both are present.
        $env = function_exists('vp_config_env') ? 'vp_config_env' : null;

        return [
            'enabled'       => (bool) vp_setting('chat_enabled', true),
            'title'         => (string) vp_setting('chat_title', $site . ' Assistant'),
            'bot_name'      => (string) vp_setting('chat_bot_name', 'Assistant'),
            'avatar'        => (string) vp_setting('chat_avatar', IMG_URL . 'chat-bot-avatar.png'),
            'welcome'       => (string) vp_setting('chat_welcome', 'Hi there! 👋 I can help you with our products, industries, pricing and quotes. What would you like to know?'),
            'provider'      => (string) vp_setting('chat_ai_provider', 'local'),
            'api_key'       => $env ? (vp_config_env('VP_AI_API_KEY') ?: (string) vp_setting('chat_ai_api_key', '')) : (string) vp_setting('chat_ai_api_key', ''),
            'api_url'       => $env ? (vp_config_env('VP_AI_API_URL') ?: (string) vp_setting('chat_ai_api_url', 'https://api.openai.com/v1/chat/completions')) : (string) vp_setting('chat_ai_api_url', 'https://api.openai.com/v1/chat/completions'),
            'model'         => $env ? (vp_config_env('VP_AI_MODEL') ?: (string) vp_setting('chat_ai_model', 'gpt-4o-mini')) : (string) vp_setting('chat_ai_model', 'gpt-4o-mini'),
            'system_prompt' => (string) vp_setting('chat_ai_system_prompt', ''),
            'quick_replies' => $quick,
        ];
    }
}
