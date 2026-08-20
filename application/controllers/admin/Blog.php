<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Blog extends Admin_Controller
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'blog.manage';


    public function __construct()
    {
        parent::__construct();
        $this->load->model('Blog_model');
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url']);
    }

    public function index()
    {
        $this->page_title = 'Blog';
        $search = $this->input->get('q');
        $page = max(1, (int) $this->input->get('page'));
        $per = 25;
        $result = $this->Blog_model->paginate([], $per, $page, ['publishedAt' => 'DESC'], $search, ['title','excerpt','content']);
        $this->render('admin/blog/index', [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
            'page' => $result['page'],
            'search' => $search,
            'base_url' => base_url('admin/blog') . '?' . http_build_query(['q' => $search]) . '&page={page}',
        ]);
    }

    public function create()
    {
        $this->page_title = 'New article';
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->render('admin/blog/form', ['row' => null, 'staff' => $this->db->get('users')->result_array()]);
    }

    public function edit($id = null)
    {
        if (!$id) show_404();
        $row = $this->Blog_model->find($id);
        if (!$row) show_404();
        $this->page_title = 'Edit: ' . $row['title'];
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->render('admin/blog/form', ['row' => $row, 'staff' => $this->db->get('users')->result_array()]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();
        $id = $this->input->post('id');
        $this->form_validation->set_rules('title', 'Title', 'required');
        if ($this->form_validation->run() === false) {
            $this->flash('error', 'Title is required.');
            return $id ? redirect('admin/blog/edit/' . $id) : redirect('admin/blog/create');
        }
        $tags = array_filter(array_map('trim', explode(',', (string) $this->input->post('tags_csv'))));
        $data = [
            'title'          => $this->input->post('title'),
            'slug'           => $this->input->post('slug') ?: vp_slugify($this->input->post('title')),
            'excerpt'        => $this->input->post('excerpt'),
            'content'        => $this->input->post('content'),
            'featuredImage'  => $this->input->post('featuredImage'),
            'authorId'       => $this->input->post('authorId') ?: $this->vp_auth->id(),
            'category'       => $this->input->post('category'),
            'tags'           => json_encode(array_values($tags)),
            'status'         => $this->input->post('status') ?: 'DRAFT',
            'publishedAt'    => $this->input->post('publishedAt') ?: ($this->input->post('status') === 'PUBLISHED' ? date('Y-m-d H:i:s') : null),
            'metaTitle'      => $this->input->post('metaTitle'),
            'metaDescription'=> $this->input->post('metaDescription'),
        ];
        if ($id) {
            $this->Blog_model->update($id, $data);
            $this->audit->log(AUDIT_UPDATE, 'blog', $id, ['title' => $data['title']]);
            $this->flash('success', 'Updated.');
        } else {
            $id = $this->Blog_model->insert($data);
            $this->audit->log(AUDIT_CREATE, 'blog', $id, ['title' => $data['title']]);
            $this->flash('success', 'Created.');
        }
        redirect('admin/blog/edit/' . $id);
    }

    public function delete($id = null)
    {
        if (!$id) show_404();
        $row = $this->Blog_model->find($id);
        if (!$row) show_404();
        $this->Blog_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'blog', $id, ['title' => $row['title']]);
        $this->flash('success', 'Deleted.');
        redirect('admin/blog');
    }
}
