<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Category_model extends MY_Model
{
    protected $table = 'categories';
    protected $fillable = ['name','slug','description','icon','image','parentId','sortOrder','isActive','metaTitle','metaDescription'];
    protected $order_by = ['sortOrder' => 'ASC', 'name' => 'ASC'];
}
