<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CMS pages published on the public website.
 */
class Page_model extends MY_Model
{
    protected $table = 'pages';
    protected $fillable = [
        'title','slug','excerpt','content','featuredImage','template','metaTitle','metaDescription',
        'status','visibility','publishedAt','showInMenu','sortOrder','isSystem','authorId',
    ];
    protected $order_by = ['sortOrder' => 'ASC'];

    /** A published, publicly visible page by slug (or null). */
    public function published($slug)
    {
        $row = $this->db->get_where($this->table, ['slug' => $slug], 1)->row_array();
        if (!$row) return null;
        if ($row['status'] !== 'PUBLISHED' || $row['visibility'] !== 'PUBLIC') return null;
        if (!empty($row['publishedAt']) && strtotime($row['publishedAt']) > time()) return null;
        return $row;
    }

    public function menu_pages()
    {
        return $this->db->where(['status' => 'PUBLISHED', 'visibility' => 'PUBLIC', 'showInMenu' => 1])
                        ->order_by('sortOrder', 'ASC')
                        ->get($this->table)->result_array();
    }

    public function slug_taken($slug, $ignore_id = null)
    {
        $this->db->where('slug', $slug);
        if ($ignore_id) $this->db->where('id !=', $ignore_id);
        return $this->db->count_all_results($this->table) > 0;
    }
}
