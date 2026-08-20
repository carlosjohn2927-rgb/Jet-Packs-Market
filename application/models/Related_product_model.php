<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Related_product_model extends MY_Model
{
    protected $table = 'related_products';
    protected $fillable = ['productId','relatedId'];

    /**
     * Get related products for $productId up to $limit.
     */
    public function get_related($productId, $limit = 4)
    {
        $rows = $this->db->select('p.*')
                         ->from($this->table . ' rp')
                         ->join('products p', 'p.id = rp.relatedId', 'inner')
                         ->where('rp.productId', $productId)
                         ->where('p.isActive', 1)
                         ->order_by('p.featured', 'DESC')
                         ->order_by('p.createdAt', 'DESC')
                         ->limit($limit)
                         ->get()->result_array();
        return $rows;
    }
}
