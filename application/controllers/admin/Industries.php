<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Industries extends Admin_Crud
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'industries.manage';

    protected $model_name   = 'Industry_model';
    protected $redirect_url = 'admin/industries';
    protected $order_by     = ['sortOrder' => 'ASC', 'name' => 'ASC'];
    protected $list_columns = [
        'Order'   => 'sortOrder',
        'Name'    => 'name',
        'Slug'    => 'slug',
        'Active'  => 'isActive',
    ];
    protected $search_fields = ['name', 'slug', 'description'];

    /** Duplicate market names/slugs are rejected. */
    protected $unique_fields = ['name', 'slug'];

    /** Full editable form (markets Halyk Petroleum supplies with parts). */
    protected $form_fields = [
        'Name'        => ['field' => 'name', 'type' => 'text', 'required' => true],
        'Slug (URL)'  => ['field' => 'slug', 'type' => 'text', 'help' => 'Leave blank to auto-generate from the name. Used for /industries/<slug> and image lookup.'],
        'Description' => ['field' => 'description', 'type' => 'textarea', 'rows' => 4],
        'Icon (Remix)' => ['field' => 'icon', 'type' => 'text', 'help' => 'A Remix Icon class, e.g. ri-flight-line (optional).'],
        'Image path'  => ['field' => 'image', 'type' => 'image', 'help' => 'Optional. When empty, /assets/img/industries/<slug>.jpg is used.'],
        'Capabilities (comma separated)' => ['field' => 'capabilities_csv', 'type' => 'text', 'help' => 'Comma-separated capability badges, e.g. Rotables, Exchange pools.'],
        'Sort order'  => ['field' => 'sortOrder', 'type' => 'number', 'default' => 0],
        'Active'      => ['field' => 'isActive', 'type' => 'checkbox', 'default' => 1],
        'SEO title'   => ['field' => 'metaTitle', 'type' => 'text'],
        'SEO description' => ['field' => 'metaDescription', 'type' => 'textarea', 'rows' => 3],
    ];

    protected function _form()
    {
        $this->form_validation->set_rules('name', 'Name', 'required|max_length[190]');
        $this->form_validation->set_rules('slug', 'Slug', 'max_length[190]');
        $this->form_validation->set_rules('sortOrder', 'Sort order', 'integer');
    }

    protected function _form_row(array $row)
    {
        // Decode capabilities JSON into the comma-separated form field.
        $caps = json_decode((string) ($row['capabilities'] ?? ''), true);
        $row['capabilities_csv'] = is_array($caps) ? implode(', ', $caps) : '';
        return $row;
    }

    protected function _collect_post()
    {
        $data = parent::_collect_post();
        // Capabilities arrive as a comma-separated list in the form.
        $caps = array_filter(array_map('trim', explode(',', (string) $this->input->post('capabilities_csv'))));
        $data['capabilities'] = json_encode(array_values($caps));
        return $data;
    }
}
