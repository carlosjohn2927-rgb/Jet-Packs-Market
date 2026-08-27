<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Testimonials extends Admin_Crud
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'testimonials.manage';

    protected $model_name   = 'Testimonial_model';
    protected $redirect_url = 'admin/testimonials';
    protected $order_by     = ['sortOrder' => 'ASC', 'name' => 'ASC'];
    protected $list_columns = [
        'Name'     => 'name',
        'Title'    => 'title',
        'Company'  => 'company',
        'Industry' => 'industry',
        'Rating'   => 'rating',
        'Featured' => 'featured',
        'Active'   => 'isActive',
    ];
    protected $search_fields = ['name', 'title', 'company', 'content', 'industry'];

    protected $unique_fields = [];

    protected $form_fields = [
        'Name'      => ['field' => 'name', 'type' => 'text', 'required' => true],
        'Title'     => ['field' => 'title', 'type' => 'text', 'help' => 'Job title, e.g. Director of Maintenance.'],
        'Company'   => ['field' => 'company', 'type' => 'text'],
        'Industry'  => ['field' => 'industry', 'type' => 'text', 'help' => 'E.g. Business Aviation, MRO & Maintenance.'],
        'Quote'     => ['field' => 'content', 'type' => 'textarea', 'rows' => 5, 'required' => true],
        'Rating'    => ['field' => 'rating', 'type' => 'number', 'default' => 5, 'help' => '1 to 5.'],
        'Avatar URL'=> ['field' => 'avatar', 'type' => 'image', 'help' => 'Optional portrait image path.'],
        'Featured'  => ['field' => 'featured', 'type' => 'checkbox', 'default' => 1],
        'Active'    => ['field' => 'isActive', 'type' => 'checkbox', 'default' => 1],
    ];

    protected function _form()
    {
        $this->form_validation->set_rules('name', 'Name', 'required|max_length[190]');
        $this->form_validation->set_rules('title', 'Title', 'max_length[190]');
        $this->form_validation->set_rules('company', 'Company', 'required|max_length[190]');
        $this->form_validation->set_rules('content', 'Quote', 'required|max_length[5000]');
        $this->form_validation->set_rules('rating', 'Rating', 'integer');
    }
}
