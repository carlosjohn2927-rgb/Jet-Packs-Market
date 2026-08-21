<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Product_download_model extends MY_Model
{
    protected $table = 'product_downloads';
    protected $fillable = ['productId','title','url','type','size'];
    protected $order_by = ['createdAt' => 'DESC'];
}
