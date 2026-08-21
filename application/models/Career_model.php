<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Career_model extends MY_Model
{
    protected $table = 'careers';
    protected $fillable = ['title','slug','department','location','type','experience','salary','description','requirements','benefits','isActive','postedAt','closingAt'];
    protected $order_by = ['postedAt' => 'DESC'];
}
