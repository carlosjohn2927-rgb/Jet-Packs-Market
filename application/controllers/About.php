<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class About extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Testimonial_model', 'Partner_model', 'Setting_model']);
    }

    public function index()
    {
        // Sections created in Dashboard → Website → Homepage (About page tab)
        // take over the page completely when they exist.
        $sections = vp_sections('about');
        if (!empty($sections)) {
            $this->page_title       = vp_cms_setting('about_title', 'About ' . vp_site('name'));
            $this->page_description = vp_cms_setting('about_description', vp_site('description', ''));
            return $this->render('home/index', [
                'sections' => $sections,
                'blocks'   => vp_section_blocks($sections),
            ]);
        }

        $this->page_title = 'About ' . vp_site('name');
        $this->page_description = vp_site('description', 'Industrial manufacturing excellence.');

        $data = [
            'intro'       => $this->settings->get('about_intro', ''),
            'testimonials'=> $this->Testimonial_model->find_all(['isActive' => 1], ['createdAt' => 'DESC'], 6),
            'partners'    => $this->Partner_model->find_all(['isActive' => 1], ['sortOrder' => 'ASC'], 12),
        ];
        $this->render('about/index', $data);
    }
}
