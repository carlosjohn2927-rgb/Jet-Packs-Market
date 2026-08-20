<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends MY_Model
{
    protected $table = 'users';
    protected $fillable = [
        'email','password','firstName','lastName','role','phone','company',
        'avatar','isActive','emailVerified','twoFactorEnabled','lastLoginAt',
        'mustChangePassword',
    ];
    protected $order_by = ['createdAt' => 'DESC'];

    public function find_by_email($email)
    {
        return $this->find_one(['email' => strtolower(trim($email))]);
    }

    public function staff()
    {
        return $this->db->where_in('role', [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_SALES, ROLE_ENGINEER, ROLE_EDITOR])
                        ->order_by('firstName', 'ASC')
                        ->get($this->table)
                        ->result_array();
    }
}
