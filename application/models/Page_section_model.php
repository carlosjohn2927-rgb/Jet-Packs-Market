<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Editable content sections that make up the homepage (and other CMS pages).
 */
class Page_section_model extends MY_Model
{
    protected $table = 'page_sections';
    protected $fillable = [
        'pageKey','type','name','title','subtitle','body','image',
        'buttonText','buttonUrl','buttonText2','buttonUrl2','settings','sortOrder','isActive','isSystem',
    ];
    protected $order_by = ['sortOrder' => 'ASC'];

    /** All sections of a page, ordered. */
    public function for_page($pageKey = 'home', $active_only = false)
    {
        $this->db->where('pageKey', $pageKey);
        if ($active_only) $this->db->where('isActive', 1);
        return $this->db->order_by('sortOrder', 'ASC')->get($this->table)->result_array();
    }

    public function next_order($pageKey = 'home')
    {
        $row = $this->db->select_max('sortOrder', 'm')->where('pageKey', $pageKey)->get($this->table)->row_array();
        return (int) ($row['m'] ?? 0) + 10;
    }

    /** Persist a new ordering: [id => sortOrder]. */
    public function reorder(array $map)
    {
        foreach ($map as $id => $order) {
            $this->db->update($this->table, ['sortOrder' => (int) $order, 'updatedAt' => date('Y-m-d H:i:s')], ['id' => $id]);
        }
        return true;
    }
}
