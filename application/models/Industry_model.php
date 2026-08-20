<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Industry_model extends MY_Model
{
    protected $table = 'industries';
    protected $fillable = ['name','slug','description','image','icon','capabilities','metaTitle','metaDescription','sortOrder','isActive'];
    protected $order_by = ['sortOrder' => 'ASC', 'name' => 'ASC'];

    public function find_by_slug($slug)
    {
        return $this->find_one(['slug' => $slug, 'isActive' => 1]);
    }
}
