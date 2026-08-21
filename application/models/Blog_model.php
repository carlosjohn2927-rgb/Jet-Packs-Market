<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Blog_model extends MY_Model
{
    protected $table = 'blog_posts';
    protected $fillable = ['title','slug','excerpt','content','featuredImage','authorId','category','tags','status','publishedAt','views','metaTitle','metaDescription'];
    protected $order_by = ['publishedAt' => 'DESC'];
}
