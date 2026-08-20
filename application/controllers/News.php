<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class News extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->page_title = 'News';
        $this->page_description = 'Latest news and announcements from Vortex Precision.';
        $page = max(1, (int) $this->input->get('page'));
        $per = 9;
        $rows = $this->db->where('isActive', 1)->order_by('publishedAt', 'DESC')
                         ->limit($per, ($page - 1) * $per)->get('news')->result_array();
        $total = (int) $this->db->where('isActive', 1)->count_all_results('news');

        $this->render('news/index', [
            'rows' => $rows,
            'total' => $total,
            'total_pages' => (int) ceil($total / $per),
            'page' => $page,
            'base_url' => base_url('news') . '?page={page}',
        ]);
    }

    public function view($slug = null)
    {
        if (!$slug) show_404();
        $row = $this->db->get_where('news', ['slug' => $slug, 'isActive' => 1])->row_array();
        if (!$row) show_404();
        $this->page_title = $row['title'];
        $this->page_description = vp_truncate(strip_tags($row['summary'] ?? $row['content']), 160);
        $this->render('news/view', ['row' => $row]);
    }
}
