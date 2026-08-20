<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Faq_model extends MY_Model
{
    protected $table = 'faqs';
    protected $fillable = ['question','answer','category','sortOrder','isActive'];
    protected $order_by = ['category' => 'ASC', 'sortOrder' => 'ASC'];
}
