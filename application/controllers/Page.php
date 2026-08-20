<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — public CMS pages.
 *
 * Renders pages created in Dashboard → Website → Pages. Reachable both at
 * /page/{slug} and directly at /{slug} (via the 404 fallback in Errors).
 */
class Page extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Page_model');
    }

    public function view($slug = null)
    {
        $slug = trim((string) $slug, '/');
        if ($slug === '') show_404();

        $page = $this->Page_model->published($slug);

        // Private pages are visible to signed-in staff (useful for previewing).
        if (!$page) {
            $raw = $this->db->get_where('pages', ['slug' => $slug], 1)->row_array();
            if ($raw && $this->vp_auth->is_staff()) {
                $page = $raw;
            }
        }
        if (!$page) show_404();

        $this->page_title       = $page['metaTitle'] ?: $page['title'];
        $this->page_description = $page['metaDescription'] ?: vp_truncate(strip_tags((string) $page['excerpt']), 160);

        $sections = vp_sections('page:' . $page['slug']);
        $this->render('pages/view', [
            'page'     => $page,
            'sections' => $sections,
            'blocks'   => vp_section_blocks($sections),
            'is_draft' => $page['status'] !== 'PUBLISHED',
        ]);
    }
}
