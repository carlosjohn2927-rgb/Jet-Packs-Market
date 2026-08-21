<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Careers extends Admin_Controller
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'careers.manage';


    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Career_model', 'Application_model']);
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url']);
    }

    public function index()
    {
        $this->page_title = 'Careers';
        $search = $this->input->get('q');
        $page = max(1, (int) $this->input->get('page'));
        $per = 25;
        $result = $this->Career_model->paginate([], $per, $page, ['postedAt' => 'DESC'], $search, ['title','department','location','description']);
        $this->render('admin/careers/index', [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
            'page' => $result['page'],
            'search' => $search,
            'base_url' => base_url('admin/careers') . '?' . http_build_query(['q' => $search]) . '&page={page}',
        ]);
    }

    public function create()
    {
        $this->page_title = 'New job posting';
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->render('admin/careers/form', ['row' => null]);
    }

    public function edit($id = null)
    {
        if (!$id) show_404();
        $row = $this->Career_model->find($id);
        if (!$row) show_404();
        $this->page_title = 'Edit: ' . $row['title'];
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->render('admin/careers/form', ['row' => $row]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();
        $id = $this->input->post('id');
        $this->form_validation->set_rules('title', 'Title', 'required');
        if ($this->form_validation->run() === false) {
            $this->flash('error', 'Title is required.');
            return $id ? redirect('admin/careers/edit/' . $id) : redirect('admin/careers/create');
        }
        $data = [
            'title'       => $this->input->post('title'),
            'slug'        => $this->input->post('slug') ?: vp_slugify($this->input->post('title')),
            'department'  => $this->input->post('department'),
            'location'    => $this->input->post('location'),
            'type'        => $this->input->post('type') ?: 'Full-time',
            'experience'  => $this->input->post('experience'),
            'salary'      => $this->input->post('salary'),
            'description' => $this->input->post('description'),
            'requirements'=> $this->input->post('requirements'),
            'benefits'    => $this->input->post('benefits'),
            'isActive'    => (int) $this->input->post('isActive', 1),
            'postedAt'    => $this->input->post('postedAt') ?: date('Y-m-d H:i:s'),
            'closingAt'   => $this->input->post('closingAt') ?: null,
        ];
        if ($id) {
            $this->Career_model->update($id, $data);
            $this->audit->log(AUDIT_UPDATE, 'career', $id, ['title' => $data['title']]);
            $this->flash('success', 'Updated.');
        } else {
            $id = $this->Career_model->insert($data);
            $this->audit->log(AUDIT_CREATE, 'career', $id, ['title' => $data['title']]);
            $this->flash('success', 'Created.');
        }
        redirect('admin/careers/edit/' . $id);
    }

    public function delete($id = null)
    {
        if (!$id) show_404();
        $row = $this->Career_model->find($id);
        if (!$row) show_404();
        $this->Career_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'career', $id, ['title' => $row['title']]);
        $this->flash('success', 'Deleted.');
        redirect('admin/careers');
    }

    public function applications($id = null)
    {
        if (!$id) show_404();
        $job = $this->Career_model->find($id);
        if (!$job) show_404();
        $this->page_title = 'Applications: ' . $job['title'];
        $rows = $this->Application_model->find_all(['careerId' => $id], ['createdAt' => 'DESC'], 200);
        $this->render('admin/careers/applications', ['job' => $job, 'rows' => $rows]);
    }
}
