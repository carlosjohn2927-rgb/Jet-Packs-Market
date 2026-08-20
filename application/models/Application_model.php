<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Application_model extends MY_Model
{
    protected $table = 'applications';
    protected $fillable = ['careerId','userId','name','email','phone','coverLetter','resumeUrl','linkedin','status','notes'];
    protected $order_by = ['createdAt' => 'DESC'];
}
