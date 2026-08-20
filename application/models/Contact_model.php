<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Contact_model extends MY_Model
{
    protected $table = 'contacts';
    protected $fillable = ['userId','name','email','phone','company','subject','message','department','status','assignedTo','repliedAt'];
    protected $order_by = ['createdAt' => 'DESC'];
}
