<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Partners extends Admin_Crud
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'partners.manage';

    protected $model_name   = 'Partner_model';
    protected $redirect_url = 'admin/partners';
    protected $order_by     = ['sortOrder' => 'ASC', 'name' => 'ASC'];
    protected $list_columns = [
        'Order'   => 'sortOrder',
        'Name'    => 'name',
        'Website' => 'website',
        'Active'  => 'isActive',
    ];
    protected $search_fields = ['name', 'website', 'category'];

    protected $unique_fields = ['name'];

    protected $form_fields = [
        'Name'     => ['field' => 'name', 'type' => 'text', 'required' => true],
        'Logo URL' => ['field' => 'logo', 'type' => 'text', 'required' => true, 'help' => 'Path/URL to the partner logo image.'],
        'Website'  => ['field' => 'website', 'type' => 'text'],
        'Category' => ['field' => 'category', 'type' => 'text', 'help' => 'E.g. OEM, Distributor, MRO.'],
        'Sort order' => ['field' => 'sortOrder', 'type' => 'number', 'default' => 0],
        'Active'   => ['field' => 'isActive', 'type' => 'checkbox', 'default' => 1],
    ];

    protected function _form()
    {
        $this->form_validation->set_rules('name', 'Name', 'required|max_length[190]');
        $this->form_validation->set_rules('website', 'Website', 'max_length[500]');
        $this->form_validation->set_rules('sortOrder', 'Sort order', 'integer');
    }
}
