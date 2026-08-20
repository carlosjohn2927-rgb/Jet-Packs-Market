<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Faq extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Faq_model');
    }

    public function index()
    {
        $this->page_title = 'Frequently asked questions';
        $this->page_description = 'Answers to common questions about Vortex Precision lead times, engineering, quality, installation, warranty and shipping.';
        $rows = $this->db->where('isActive', 1)->order_by('category', 'ASC')->order_by('sortOrder', 'ASC')->get('faqs')->result_array();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['category']][] = $r;
        }
        $this->render('faq/index', ['grouped' => $grouped]);
    }
}
