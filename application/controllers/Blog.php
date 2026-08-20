<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Blog extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Blog_model');
    }

    public function index()
    {
        $this->page_title = 'Blog & insights';
        $this->page_description = 'Engineering articles, selection guides and industry insights from the Vortex Precision team.';
        $page = max(1, (int) $this->input->get('page'));
        $per = 9;
        $result = $this->Blog_model->paginate(['status' => 'PUBLISHED'], $per, $page, ['publishedAt' => 'DESC']);

        $this->render('blog/index', [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
            'page' => $result['page'],
            'base_url' => base_url('blog') . '?page={page}',
        ]);
    }

    public function view($slug = null)
    {
        if (!$slug) show_404();
        $p = $this->Blog_model->find_one(['slug' => $slug, 'status' => 'PUBLISHED']);
        if (!$p) show_404();

        $this->db->set('views', 'views+1', false)->where('id', $p['id'])->update('blog_posts');
        $author = $this->db->get_where('users', ['id' => $p['authorId']])->row_array();
        $this->page_title = $p['metaTitle'] ?: $p['title'];
        $this->page_description = vp_truncate(strip_tags($p['excerpt'] ?? $p['content']), 160);

        $this->render('blog/view', ['post' => $p, 'author' => $author]);
    }
}
