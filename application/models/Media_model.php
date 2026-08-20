<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Media_model extends MY_Model
{
    protected $table = 'media';
    protected $fillable = ['filename','originalName','url','mimeType','size','folder','alt'];
    protected $order_by = ['createdAt' => 'DESC'];
}
