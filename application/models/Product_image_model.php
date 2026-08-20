<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Product_image_model extends MY_Model
{
    protected $table = 'product_images';
    protected $fillable = ['productId','url','alt','caption','sortOrder','isPrimary'];
    protected $order_by = ['sortOrder' => 'ASC', 'isPrimary' => 'DESC'];
}
