<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Downloads extends Admin_Crud
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'downloads.manage';

    protected $model_name   = 'Download_model';
    protected $redirect_url = 'admin/downloads';
    protected $order_by     = ['category' => 'ASC', 'createdAt' => 'DESC'];
    protected $list_columns = [
        'Title'    => 'title',
        'Category' => 'category',
        'Type'     => 'type',
        'Size'     => 'fileSize',
        'Downloads'=> 'downloads',
        'Active'   => 'isActive',
    ];
    protected $search_fields = ['title','description','category'];
}
