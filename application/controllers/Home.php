<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — public homepage.
 *
 * The page is assembled from the `page_sections` rows managed in
 * Dashboard → Website → Homepage. Nothing here is hard-coded: adding,
 * hiding or reordering a section in the dashboard changes this page
 * immediately.
 */
class Home extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Category_model', 'Product_model', 'Industry_model', 'Testimonial_model', 'Partner_model', 'Faq_model']);
    }

    public function index()
    {
        $sections = vp_sections('home');

        // Fall back to a sensible default layout if the CMS tables are empty
        // (e.g. an install that has not imported the CMS migration yet).
        if (empty($sections)) {
            $sections = $this->_fallback_sections();
        }

        // Empty falls back to the SEO default title (Settings → SEO).
        $this->page_title       = (string) vp_cms_setting('home_title', vp_site('tagline', ''));
        $this->page_description = vp_site('description', '');

        $this->render('home/index', [
            'sections'   => $sections,
            'blocks'     => vp_section_blocks($sections),
        ]);
    }

    /**
     * Minimal built-in layout used only when no sections exist in the database.
     */
    private function _fallback_sections()
    {
        return [
            [
                'id' => 'fallback-hero', 'type' => 'hero', 'name' => 'Hero',
                'title'    => vp_cms_setting('hero_title', 'Precision-engineered for the most demanding industries'),
                'subtitle' => vp_cms_setting('hero_subtitle', vp_site('description', '')),
                'body' => null, 'image' => IMG_URL . 'hero-industrial.jpg',
                'buttonText' => vp_cms_setting('hero_cta_primary', 'Request a Quote'), 'buttonUrl' => 'rfq',
                'buttonText2' => vp_cms_setting('hero_cta_secondary', 'Explore Products'), 'buttonUrl2' => 'products',
                'settings' => [], 'isActive' => 1,
            ],
            [
                'id' => 'fallback-products', 'type' => 'products', 'name' => 'Featured products',
                'title' => 'Featured products', 'subtitle' => 'Our most-requested equipment.',
                'body' => null, 'image' => null, 'buttonText' => 'View all', 'buttonUrl' => 'products',
                'buttonText2' => null, 'buttonUrl2' => null, 'settings' => ['limit' => 4], 'isActive' => 1,
            ],
            [
                'id' => 'fallback-cta', 'type' => 'cta', 'name' => 'CTA',
                'title' => 'Have a project in mind?',
                'subtitle' => 'Send us your specifications and our engineering team will respond with a formal quote.',
                'body' => null, 'image' => null, 'buttonText' => 'Request a Quote', 'buttonUrl' => 'rfq',
                'buttonText2' => null, 'buttonUrl2' => null, 'settings' => [], 'isActive' => 1,
            ],
        ];
    }
}
