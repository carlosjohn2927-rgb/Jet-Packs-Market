<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Notification_model extends MY_Model
{
    protected $table = 'notifications';
    protected $fillable = ['userId','type','title','message','data','read'];
    protected $order_by = ['createdAt' => 'DESC'];
}
