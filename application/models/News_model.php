<?php defined('BASEPATH') OR exit('No direct script access allowed');
class News_model extends MY_Model
{
    protected $table = 'news';
    protected $fillable = ['title','slug','summary','content','image','category','publishedAt','isActive'];
    protected $order_by = ['publishedAt' => 'DESC'];
}
