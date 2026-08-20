<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_permission_model extends MY_Model
{
    protected $table = 'user_permissions';
    protected $fillable = ['userId','permission','granted','grantedBy'];
    protected $order_by = ['permission' => 'ASC'];

    public function for_user($user_id)
    {
        return $this->db->get_where($this->table, ['userId' => $user_id])->result_array();
    }
}
