<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Specification_model extends MY_Model
{
    protected $table = 'specifications';
    protected $fillable = ['productId','key','value','unit','sortOrder'];
    protected $order_by = ['sortOrder' => 'ASC', 'key' => 'ASC'];
}
