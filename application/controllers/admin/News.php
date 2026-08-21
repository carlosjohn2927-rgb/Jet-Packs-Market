<?php defined('BASEPATH') OR exit('No direct script access allowed');

class News extends Admin_Controller
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'news.manage';


    public function __construct()
    {
        parent::__construct();
        $this->load->model('News_model');
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url']);
    }

    public function index()
    {
        $this->page_title = 'News';
        $search = $this->input->get('q');
        $page = max(1, (int) $this->input->get('page'));
        $per = 25;
        $result = $this->News_model->paginate([], $per, $page, ['publishedAt' => 'DESC'], $search, ['title','summary','content']);
        $this->render('admin/news/index', [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
            'page' => $result['page'],
            'search' => $search,
            'base_url' => base_url('admin/news') . '?' . http_build_query(['q' => $search]) . '&page={page}',
        ]);
    }

    public function create()
    {
        $this->page_title = 'New news item';
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->render('admin/news/form', ['row' => null]);
    }

    public function edit($id = null)
    {
        if (!$id) show_404();
        $row = $this->News_model->find($id);
        if (!$row) show_404();
        $this->page_title = 'Edit: ' . $row['title'];
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->render('admin/news/form', ['row' => $row]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();
        $id = $this->input->post('id');
        $this->form_validation->set_rules('title', 'Title', 'required');
        if ($this->form_validation->run() === false) {
            $this->flash('error', 'Title is required.');
            return $id ? redirect('admin/news/edit/' . $id) : redirect('admin/news/create');
        }
        $data = [
            'title'       => $this->input->post('title'),
            'slug'        => $this->input->post('slug') ?: vp_slugify($this->input->post('title')),
            'summary'     => $this->input->post('summary'),
            'content'     => $this->input->post('content'),
            'image'       => $this->input->post('image'),
            'category'    => $this->input->post('category'),
            'publishedAt' => $this->input->post('publishedAt') ?: date('Y-m-d H:i:s'),
            'isActive'    => (int) $this->input->post('isActive', 1),
        ];
        if ($id) {
            $this->News_model->update($id, $data);
            $this->audit->log(AUDIT_UPDATE, 'news', $id, ['title' => $data['title']]);
            $this->flash('success', 'Updated.');
        } else {
            $id = $this->News_model->insert($data);
            $this->audit->log(AUDIT_CREATE, 'news', $id, ['title' => $data['title']]);
            $this->flash('success', 'Created.');
        }
        redirect('admin/news/edit/' . $id);
    }

    public function delete($id = null)
    {
        if (!$id) show_404();
        $row = $this->News_model->find($id);
        if (!$row) show_404();
        $this->News_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'news', $id, ['title' => $row['title']]);
        $this->flash('success', 'Deleted.');
        redirect('admin/news');
    }
}
