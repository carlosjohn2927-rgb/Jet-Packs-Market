<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Errors extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 404 handler — first give the CMS a chance: a page created in
     * Dashboard → Website → Pages is served at /{slug} without needing a
     * route of its own.
     */
    public function not_found()
    {
        $slug = trim((string) $this->uri->uri_string(), '/');
        if ($slug !== '' && strpos($slug, 'admin') !== 0 && strpos($slug, '/') === false) {
            $page = vp_cms_page($slug);
            if ($page) {
                $this->load->model('Page_model');
                $this->page_title       = $page['metaTitle'] ?: $page['title'];
                $this->page_description = $page['metaDescription'] ?: vp_truncate(strip_tags((string) $page['excerpt']), 160);
                return $this->render('pages/view', [
                    'page'     => $page,
                    'sections' => vp_sections('page:' . $page['slug']),
                    'is_draft' => false,
                ]);
            }
        }

        $this->output->set_status_header(404);
        $this->page_title = 'Page not found';
        $this->render('errors/404', [], '');
    }

    public function server_error()
    {
        $this->output->set_status_header(500);
        $this->page_title = 'Something went wrong';
        $this->render('errors/500', [], '');
    }
}
