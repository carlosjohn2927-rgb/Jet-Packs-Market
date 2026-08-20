<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Product_model');
    }

    public function index()
    {
        $q = trim((string) $this->input->get('q'));
        $this->page_title = $q ? "Search: $q" : 'Search';
        $this->page_description = 'Search Vortex Precision products, blog posts, downloads and FAQs.';

        $products = [];
        $posts = [];
        $faqs = [];

        if ($q !== '') {
            $products = $this->db->group_start()
                                 ->like('name', $q)->or_like('description', $q)->or_like('shortDescription', $q)->or_like('sku', $q)
                                 ->group_end()
                                 ->where('isActive', 1)->order_by('createdAt', 'DESC')->limit(24)
                                 ->get('products')->result_array();
            $products = $this->Product_model->attach_images($products);
            $posts = $this->db->group_start()
                             ->like('title', $q)->or_like('content', $q)->or_like('excerpt', $q)
                             ->group_end()
                             ->where('status', 'PUBLISHED')->order_by('publishedAt', 'DESC')->limit(12)
                             ->get('blog_posts')->result_array();
            $faqs = $this->db->group_start()
                            ->like('question', $q)->or_like('answer', $q)
                            ->group_end()
                            ->where('isActive', 1)->limit(12)
                            ->get('faqs')->result_array();
        }

        $this->render('search/index', [
            'q' => $q,
            'products' => $products,
            'posts' => $posts,
            'faqs' => $faqs,
        ]);
    }
}
