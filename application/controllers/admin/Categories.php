<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Categories extends Admin_Crud
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'categories.manage';

    protected $model_name   = 'Category_model';
    protected $view_prefix  = 'categories';
    protected $redirect_url = 'admin/categories';
    protected $order_by     = ['sortOrder' => 'ASC', 'name' => 'ASC'];

    protected $list_columns = [
        'Order'   => 'sortOrder',
        'Name'    => 'name',
        'Slug'    => 'slug',
        'Icon'    => 'icon',
        'Active'  => 'isActive',
        'Created' => 'createdAt',
    ];
    protected $search_fields = ['name', 'slug', 'description'];

    /** Duplicate names and slugs are rejected. */
    protected $unique_fields = ['name', 'slug'];

    /** Full editable form (the category edit now works for every field). */
    protected $form_fields = [
        'Name'        => ['field' => 'name', 'type' => 'text', 'required' => true],
        'Slug (URL)'  => ['field' => 'slug', 'type' => 'text', 'help' => 'Leave blank to auto-generate from the name. Used for /products?category=slug and image lookup.'],
        'Description' => ['field' => 'description', 'type' => 'textarea', 'rows' => 4],
        'Icon (Remix)' => ['field' => 'icon', 'type' => 'text', 'help' => 'A Remix Icon class, e.g. ri-wheel-line (optional).'],
        'Image path'  => ['field' => 'image', 'type' => 'image', 'help' => 'Optional. When empty, the category image /assets/img/products/<slug>.jpg is used.'],
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
}
