<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Downloads extends Admin_Crud
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'downloads.manage';

    protected $model_name   = 'Download_model';
    protected $redirect_url = 'admin/downloads';
    protected $order_by     = ['category' => 'ASC', 'title' => 'ASC'];
    protected $list_columns = [
        'Title'     => 'title',
        'Category'  => 'category',
        'Type'      => 'type',
        'Size'      => 'fileSize',
        'Downloads' => 'downloads',
        'Active'    => 'isActive',
    ];
    protected $search_fields = ['title', 'description', 'category'];

    protected $unique_fields = ['title'];

    protected $form_fields = [
        'Title'       => ['field' => 'title', 'type' => 'text', 'required' => true],
        'Description' => ['field' => 'description', 'type' => 'textarea', 'rows' => 4],
        'File URL'    => ['field' => 'fileUrl', 'type' => 'text', 'required' => true, 'help' => 'Path/URL to the resource, e.g. /assets/files/guide.pdf'],
        'Type'        => ['field' => 'type', 'type' => 'select', 'options' => ['PDF' => 'PDF', 'XLSX' => 'XLSX', 'DOCX' => 'DOCX', 'ZIP' => 'ZIP', 'LINK' => 'Link']],
        'Category'    => ['field' => 'category', 'type' => 'text', 'help' => 'E.g. Company, Quoting, Quality, AOG.'],
        'File size'   => ['field' => 'fileSize', 'type' => 'text', 'help' => 'Human-readable label, e.g. 1.2 MB.'],
        'Active'      => ['field' => 'isActive', 'type' => 'checkbox', 'default' => 1],
    ];

    protected function _form()
    {
        $this->form_validation->set_rules('title', 'Title', 'required|max_length[255]');
        $this->form_validation->set_rules('fileUrl', 'File URL', 'required|max_length[500]');
    }
}
