<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends MY_Model
{
    protected $table = 'settings';
    protected $fillable = ['key','value','type','group','version','enabled','sortOrder','updatedBy'];
    protected $timestamps = false;
}
