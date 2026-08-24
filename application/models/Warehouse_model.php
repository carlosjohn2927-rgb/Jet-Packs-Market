<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warehouse_model extends MY_Model
{
    protected $table = 'warehouses';
    protected $fillable = [
        'name','code','address','city','region','country','timezone','phone',
        'isAogHub','isActive','sortOrder','notes',
    ];
    protected $order_by = ['sortOrder' => 'ASC', 'name' => 'ASC'];

    public function active()
    {
        return $this->find_all(['isActive' => 1], $this->order_by, 200);
    }

    /** Prefer an active AOG hub, then the first active warehouse. */
    public function default_warehouse()
    {
        $row = $this->db->where('isActive', 1)->where('isAogHub', 1)
            ->order_by('sortOrder', 'ASC')->limit(1)->get($this->table)->row_array();
        if ($row) return $row;
        return $this->db->where('isActive', 1)->order_by('sortOrder', 'ASC')
            ->limit(1)->get($this->table)->row_array();
    }
}
