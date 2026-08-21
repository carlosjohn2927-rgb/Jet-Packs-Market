<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Partner_model extends MY_Model
{
    protected $table = 'partners';
    protected $fillable = ['name','logo','website','category','sortOrder','isActive'];
    protected $order_by = ['sortOrder' => 'ASC', 'name' => 'ASC'];
}
