<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum - reusable CRUD base for admin controllers.
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

    /**
     * Editable form fields, as label => config. When empty the form is built
     * from $list_columns (legacy behaviour). Each value may be:
     *   - a column name string, or
     *   - ['field' => 'col', 'type' => 'text|textarea|checkbox|number|select|image',
     *      'options' => [..], 'required' => bool, 'help' => '..']
     */
    protected $form_fields = [];

    /** Columns that should not be duplicated across rows (uniqueness check). */
    protected $unique_fields = [];

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
            'controller_url' => $this->redirect_url,
            'redirect_url' => $this->redirect_url,
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
            'fields' => $this->_form_fields(),
            'form_url' => base_url($this->redirect_url . '/save'),
        ]);
    }

    public function edit($id = null)
    {
        if (!$id) show_404();
        $row = $this->M()->find($id);
        if (!$row) show_404();
        // Let a subclass expose JSON columns as form-friendly text, e.g.
        // ["a","b"] -> "a, b".
        if (method_exists($this, '_form_row')) {
            $row = $this->_form_row($row);
        }
        $this->page_title = $this->_title('edit', $row);
        $this->_form();
        $this->render('admin/_crud_form', [
            'row' => $row,
            'columns' => $this->list_columns,
            'fields' => $this->_form_fields(),
            'form_url' => base_url($this->redirect_url . '/save'),
        ]);
    }

    /** Override in a subclass to prepare a DB row for the edit form. */
    protected function _form_row(array $row)
    {
        return $row;
    }

    /**
     * Normalise $form_fields (or fall back to list columns) into a consistent
     * field definition list for the form view and for save().
     */
    protected function _form_fields()
    {
        $out = [];
        if (!empty($this->form_fields)) {
            foreach ($this->form_fields as $label => $cfg) {
                if (is_string($cfg)) $cfg = ['field' => $cfg];
                if (!isset($cfg['field'])) $cfg['field'] = $cfg;
                $cfg['label'] = $label;
                $out[$cfg['field']] = $cfg;
            }
            return $out;
        }
        foreach ($this->list_columns as $label => $col) {
            $out[$col] = ['field' => $col, 'label' => $label];
        }
        return $out;
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
        if (!empty($data['name']) && empty($data['slug'])) {
            $data['slug'] = vp_slugify($data['name']);
        }

        // Reject duplicate values on uniqueness-protected columns (case-
        // insensitive), excluding the row currently being edited.
        $uniqueError = $this->_duplicate_violation($data, $id);
        if ($uniqueError !== null) {
            $this->flash('error', $uniqueError);
            return $id ? redirect($this->redirect_url . '/edit/' . $id) : redirect($this->redirect_url . '/create');
        }

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

    /**
     * Check $data against the controller's $unique_fields (and the model
     * table), excluding the row $ignoreId. Returns an error string or null.
     */
    protected function _duplicate_violation(array $data, $ignoreId = null)
    {
        $checks = !empty($this->unique_fields) ? $this->unique_fields : [];
        $table = $this->M()->table();
        foreach ($checks as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) continue;
            $this->db->where('LOWER(' . $this->db->protect_identifiers($field) . ')', strtolower(trim((string) $data[$field])));
            if ($ignoreId) $this->db->where('id !=', $ignoreId);
            $exists = $this->db->count_all_results($table);
            if ($exists > 0) {
                $label = ucfirst($field === 'slug' ? 'slug (URL)' : $field);
                return 'A record with the same ' . $label . ' already exists: "' . $data[$field] . '". Choose a unique value.';
            }
        }
        return null;
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
        // Collect every editable field, not just the list columns, so the
        // create/edit forms can contain columns the list does not show.
        foreach ($this->_form_fields() as $col => $cfg) {
            if (in_array($col, ['id', 'createdAt', 'updatedAt'], true)) continue;
            $type = $cfg['type'] ?? 'text';
            $val = $this->input->post($col);
            if ($type === 'checkbox') {
                $data[$col] = $val ? 1 : 0;
                continue;
            }
            if ($type === 'number') {
                if ($val !== null && $val !== '') $data[$col] = is_numeric($val) ? ($val + 0) : $val;
                continue;
            }
            if ($val !== null) {
                $data[$col] = is_string($val) ? trim($val) : $val;
            }
        }
        // Always allow slug override, normalised.
        $slug = $this->input->post('slug');
        if ($slug !== null && $slug !== '') {
            $data['slug'] = vp_slugify($slug);
        } elseif (!empty($data['name']) && empty($data['slug'])) {
            $data['slug'] = vp_slugify($data['name']);
        }
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
