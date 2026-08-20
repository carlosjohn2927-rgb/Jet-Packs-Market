<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Permission_model extends MY_Model
{
    protected $table = 'permissions';
    protected $fillable = ['key','label','description','groupName','superOnly','sortOrder'];
    protected $timestamps = false;
    protected $order_by = ['sortOrder' => 'ASC'];
}
