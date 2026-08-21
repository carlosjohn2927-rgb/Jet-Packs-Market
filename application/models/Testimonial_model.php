<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Testimonial_model extends MY_Model
{
    protected $table = 'testimonials';
    protected $fillable = ['name','title','company','content','rating','avatar','industry','isActive','featured'];
    protected $order_by = ['createdAt' => 'DESC'];
}
