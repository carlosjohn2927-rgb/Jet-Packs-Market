<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - SEO endpoints (robots.txt + sitemap.xml).
 * Routed via config/routes.php so no physical files are required.
 */
class Seo extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * robots.txt
     */
    public function robots()
    {
        $seo  = vp_seo_config();
        $base = !empty($seo['canonical_domain'])
            ? $seo['canonical_domain']
            : rtrim($this->config->item('base_url'), '/');
        $sitemap = $base . '/sitemap.xml';

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /logout',
            'Disallow: /forgot',
            'Disallow: /reset',
            'Disallow: /search',
            '',
            'Sitemap: ' . $sitemap,
        ];

        $this->output
            ->set_content_type('text/plain')
            ->set_output(implode("\n", $lines) . "\n");
    }

    /**
     * sitemap.xml — all public, indexable pages.
     */
    public function sitemap()
    {
        $seo  = vp_seo_config();
        $base = !empty($seo['canonical_domain'])
            ? $seo['canonical_domain']
            : rtrim($this->config->item('base_url'), '/');

        $urls = [
            ['loc' => $base . '/', 'priority' => '1.0'],
            ['loc' => $base . '/about', 'priority' => '0.6'],
            ['loc' => $base . '/services', 'priority' => '0.7'],
            ['loc' => $base . '/contact', 'priority' => '0.6'],
            ['loc' => $base . '/rfq', 'priority' => '0.8'],
            ['loc' => $base . '/products', 'priority' => '0.9'],
            ['loc' => $base . '/industries', 'priority' => '0.8'],
            ['loc' => $base . '/blog', 'priority' => '0.6'],
            ['loc' => $base . '/news', 'priority' => '0.5'],
            ['loc' => $base . '/faq', 'priority' => '0.4'],
            ['loc' => $base . '/careers', 'priority' => '0.5'],
            ['loc' => $base . '/downloads', 'priority' => '0.5'],
        ];

        $this->_add_slugs($urls, 'products', '/products/', '0.8', $base, ['isActive' => 1]);
        $this->_add_slugs($urls, 'industries', '/industries/', '0.7', $base, ['isActive' => 1]);
        $this->_add_slugs($urls, 'blog_posts', '/blog/', '0.5', $base, ['status' => 'PUBLISHED']);
        $this->_add_slugs($urls, 'news', '/news/', '0.5', $base, ['isActive' => 1]);
        $this->_add_slugs($urls, 'careers', '/careers/', '0.5', $base, ['isActive' => 1]);

        // CMS pages published from the dashboard (Website → Pages)
        if ($this->db->table_exists('pages')) {
            $this->_add_slugs($urls, 'pages', '/', '0.6', $base, ['status' => 'PUBLISHED', 'visibility' => 'PUBLIC']);
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . vp_safe_html($u['loc']) . "</loc>\n";
            $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>' . "\n";

        $this->output
            ->set_content_type('application/xml')
            ->set_output($xml);
    }

    /**
     * Append slug-based URLs from a table, guarding against missing tables.
     */
    private function _add_slugs(array &$urls, $table, $prefix, $priority, $base, array $where = [])
    {
        if (!$this->db->table_exists($table)) return;
        $this->db->select('slug');
        if (!empty($where)) $this->db->where($where);
        $this->db->limit(5000);
        $rows = $this->db->get($table)->result_array();
        foreach ($rows as $r) {
            if (empty($r['slug'])) continue;
            $urls[] = ['loc' => $base . $prefix . $r['slug'], 'priority' => $priority];
        }
    }
}
