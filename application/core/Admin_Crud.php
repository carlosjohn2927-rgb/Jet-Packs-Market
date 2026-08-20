<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - reusable CRUD base for admin controllers.
 *
 * Subclasses set $model, $view_prefix, $fields, $list_columns, $search_fields,
 * $order_by, $redirect_url, and override save() for any custom logic.
 */
class Admin_Crud extends Admin_Controller
{
    /** @var string Model class name (without _model suffix) */
    protected $model_name = '';

    /** @var string View folder under views/admin/ */
    protected $view_prefix = '';

    /** @var array  Columns the list view displays (label => db_column) */
    protected $list_columns = [];

    /** @var array  Columns searched by the list view's ?q= */
    protected $search_fields = [];

    /** @var array  [col => dir] default ordering */
    protected $order_by = ['createdAt' => 'DESC'];

    /** @var string URL to redirect to after save/delete */
    protected $redirect_url = '';

    /** @var int  Items per page */
    protected $per_page = 25;

    public function __construct()
    {
        parent::__construct();
        if (!$this->model_name) {
            show_error('Admin_Crud subclass must set $model_name.');
        }
        $this->load->model($this->model_name);
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url', 'security_helper']);
        if (!$this->redirect_url) {
            $cls = strtolower(get_class($this));
            $this->redirect_url = 'admin/' . $cls;
        }
    }

    /** @return MY_Model */
    protected function M()
    {
        return $this->{$this->model_name};
    }

    public function index()
    {
        $this->page_title = $this->_title('index');
        $search = $this->input->get('q');
        $page = max(1, (int) $this->input->get('page'));

        $result = $this->M()->paginate([], $this->per_page, $page, $this->order_by, $search, $this->search_fields);

        $data = [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
            'page' => $result['page'],
            'search' => $search,
            'columns' => $this->list_columns,
            'base_url' => base_url($this->redirect_url) . '?' . http_build_query(array_filter(['q' => $search])) . '&page={page}',
        ];
        $this->render('admin/_crud_index', $data);
    }

    public function create()
    {
        $this->page_title = $this->_title('create');
        $this->_form();
        $this->render('admin/_crud_form', [
            'row' => null,
            'columns' => $this->list_columns,
            'form_url' => base_url($this->redirect_url . '/save'),
        ]);
    }

    public function edit($id = null)
    {
        if (!$id) show_404();
        $row = $this->M()->find($id);
        if (!$row) show_404();
        $this->page_title = $this->_title('edit', $row);
        $this->_form();
        $this->render('admin/_crud_form', [
            'row' => $row,
            'columns' => $this->list_columns,
            'form_url' => base_url($this->redirect_url . '/save'),
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();
        $id = $this->input->post('id');

        // Set the validation rules defined by the subclass (they are
        // normally registered in _form(), which only create()/edit() call).
        // Without this, form_validation->run() has zero rules and always
        // returns FALSE, making every save() redirect back to the form.
        if (method_exists($this, '_form')) {
            $this->_form();
        }

        // Run form_validation rules set by subclass
        if (!$this->form_validation->run()) {
            $this->flash('error', 'Please fix the highlighted errors.');
            return $id ? redirect($this->redirect_url . '/edit/' . $id) : redirect($this->redirect_url . '/create');
        }

        $data = $this->_collect_post();
        if (!empty($data['slug']) && !$data['slug']) $data['slug'] = vp_slugify($data['name'] ?? 'item');

        if ($id) {
            $this->M()->update($id, $data);
            $this->audit->log(AUDIT_UPDATE, $this->model_name, $id, ['name' => $data['name'] ?? $id]);
            $this->flash('success', 'Updated.');
        } else {
            $id = $this->M()->insert($data);
            $this->audit->log(AUDIT_CREATE, $this->model_name, $id, ['name' => $data['name'] ?? $id]);
            $this->flash('success', 'Created.');
        }
        redirect($this->redirect_url . '/edit/' . $id);
    }

    public function delete($id = null)
    {
        if (!$id) show_404();
        $row = $this->M()->find($id);
        if (!$row) show_404();
        $this->M()->delete($id);
        $this->audit->log(AUDIT_DELETE, $this->model_name, $id, ['name' => $row['name'] ?? $id]);
        $this->flash('success', 'Deleted.');
        redirect($this->redirect_url);
    }

    /** Override in subclass to set custom validation rules. */
    protected function _form() {}

    /** Override in subclass to customise the post collection. */
    protected function _collect_post()
    {
        $data = [];
        foreach ($this->list_columns as $col) {
            if (in_array($col, ['id', 'createdAt', 'updatedAt', 'views', 'slug'], true)) continue;
            $val = $this->input->post($col);
            if ($val !== null) {
                $data[$col] = is_string($val) ? trim($val) : $val;
            }
        }
        // Always allow slug override
        $slug = $this->input->post('slug');
        if ($slug !== null) $data['slug'] = vp_slugify($slug);
        return $data;
    }

    private function _title($verb, $row = null)
    {
        $name = preg_replace('/_model$/', '', $this->model_name);
        $name = preg_replace('/(?<!^)([A-Z])/', ' $1', $name);
        if ($verb === 'index') return $name;
        if ($verb === 'create') return 'New ' . $name;
        if ($verb === 'edit')   return 'Edit ' . ($row['name'] ?? $row['title'] ?? $name);
        return $name;
    }
}
