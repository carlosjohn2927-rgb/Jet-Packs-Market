<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Faqs extends Admin_Crud
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'faqs.manage';

    protected $model_name   = 'Faq_model';
    protected $redirect_url = 'admin/faqs';
    protected $order_by     = ['category' => 'ASC', 'sortOrder' => 'ASC'];
    protected $list_columns = [
        'Category' => 'category',
        'Order'    => 'sortOrder',
        'Question' => 'question',
        'Active'   => 'isActive',
    ];
    protected $search_fields = ['question', 'answer', 'category'];

    protected $unique_fields = [];

    protected $form_fields = [
        'Question'  => ['field' => 'question', 'type' => 'text', 'required' => true],
        'Answer'    => ['field' => 'answer', 'type' => 'textarea', 'rows' => 6, 'required' => true],
        'Category'  => ['field' => 'category', 'type' => 'text', 'help' => 'E.g. Requesting a Quote, Certification, Shipping & Logistics.'],
        'Sort order'=> ['field' => 'sortOrder', 'type' => 'number', 'default' => 0],
        'Active'    => ['field' => 'isActive', 'type' => 'checkbox', 'default' => 1],
    ];

    protected function _form()
    {
        $this->form_validation->set_rules('question', 'Question', 'required|max_length[500]');
        $this->form_validation->set_rules('category', 'Category', 'required|max_length[100]');
        $this->form_validation->set_rules('sortOrder', 'Sort order', 'integer');
    }
}
