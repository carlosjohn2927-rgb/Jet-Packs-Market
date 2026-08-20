<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public website navigation (header, footer columns, legal links).
 */
class Menu_item_model extends MY_Model
{
    protected $table = 'menu_items';
    protected $fillable = [
        'menu','label','type','url','pageId','target','icon','parentId','sortOrder','isActive',
    ];
    protected $order_by = ['sortOrder' => 'ASC'];

    public function for_menu($menu, $active_only = true)
    {
        $this->db->where('menu', $menu);
        if ($active_only) $this->db->where('isActive', 1);
        return $this->db->order_by('sortOrder', 'ASC')->get($this->table)->result_array();
    }

    public function all_grouped($active_only = false)
    {
        $this->db->order_by('menu', 'ASC')->order_by('sortOrder', 'ASC');
        if ($active_only) $this->db->where('isActive', 1);
        $out = [];
        foreach ($this->db->get($this->table)->result_array() as $r) {
            $out[$r['menu']][] = $r;
        }
        return $out;
    }

    public function next_order($menu)
    {
        $row = $this->db->select_max('sortOrder', 'm')->where('menu', $menu)->get($this->table)->row_array();
        return (int) ($row['m'] ?? 0) + 10;
    }
}
