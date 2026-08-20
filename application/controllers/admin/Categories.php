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
        'Order'        => 'sortOrder',
        'Name'         => 'name',
        'Slug'         => 'slug',
        'Active'       => 'isActive',
        'Created'      => 'createdAt',
    ];
    protected $search_fields = ['name','slug','description'];

    protected function _form()
    {
        $this->form_validation->set_rules('name', 'Name', 'required|max_length[190]');
        $this->form_validation->set_rules('slug', 'Slug', 'max_length[190]');
    }
}
