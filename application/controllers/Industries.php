<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Industries extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Industry_model', 'Product_model']);
    }

    public function index()
    {
        $this->page_title = 'Industries we serve';
        $this->page_description = 'Vortex Precision serves oil & gas, chemical processing, power generation, water & wastewater, pharmaceutical and food & beverage industries.';
        $this->render('industries/index', [
            'industries' => $this->Industry_model->find_all(['isActive' => 1], ['sortOrder' => 'ASC'], 50),
        ]);
    }

    public function view($slug = null)
    {
        if (!$slug) show_404();
        $ind = $this->Industry_model->find_by_slug($slug);
        if (!$ind) show_404();
        $caps = $ind['capabilities'] ? json_decode($ind['capabilities'], true) : [];

        // Find products for this industry
        $rows = $this->db->like('industryIds', $ind['id'])->where('isActive', 1)
                         ->order_by('featured', 'DESC')->order_by('createdAt', 'DESC')
                         ->limit(12)->get('products')->result_array();
        $rows = $this->Product_model->attach_images($rows);

        $this->page_title = $ind['metaTitle'] ?: $ind['name'];
        $this->page_description = $ind['metaDescription'] ?: vp_truncate(strip_tags($ind['description']), 160);

        $this->render('industries/view', [
            'industry' => $ind,
            'capabilities' => $caps,
            'products' => $rows,
        ]);
    }
}
