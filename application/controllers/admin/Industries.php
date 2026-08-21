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
    protected $search_fields = ['name','slug','description'];

    protected function _form()
    {
        $this->form_validation->set_rules('name', 'Name', 'required|max_length[190]');
    }

    protected function _collect_post()
    {
        $data = parent::_collect_post();
        $caps = array_filter(array_map('trim', explode(',', (string) $this->input->post('capabilities_csv'))));
        $data['capabilities'] = json_encode(array_values($caps));
        return $data;
    }
}
