<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Download_model extends MY_Model
{
    protected $table = 'downloads';
    protected $fillable = ['title','description','fileUrl','type','category','fileSize','downloads','isActive'];
    protected $order_by = ['category' => 'ASC', 'createdAt' => 'DESC'];
}
