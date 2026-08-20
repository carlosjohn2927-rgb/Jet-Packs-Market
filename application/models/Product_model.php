<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends MY_Model
{
    protected $table = 'products';
    protected $fillable = ['name','slug','sku','description','shortDescription','price','categoryId','industryIds','material','pressure','temperature','voltage','dimensions','weight','certifications','availability','featured','isActive','views','metaTitle','metaDescription','metaKeywords'];
    protected $order_by = ['createdAt' => 'DESC'];

    /**
     * Find a product by slug.
     */
    public function find_by_slug($slug)
    {
        return $this->find_one(['slug' => $slug, 'isActive' => 1]);
    }

    public function featured($limit = 4)
    {
        return $this->find_all(['isActive' => 1, 'featured' => 1], ['createdAt' => 'DESC'], $limit);
    }

    public function by_category($categoryId, $limit = 12, $offset = 0, $excludeId = null)
    {
        $where = ['isActive' => 1, 'categoryId' => $categoryId];
        if ($excludeId) $where['id !='] = $excludeId;
        return $this->find_all($where, ['createdAt' => 'DESC'], $limit, $offset);
    }

    /**
     * Eager-load the primary image + category slug for a set of product rows.
     *
     * Listings (catalog, home, search, industries, related) only ever selected
     * from `products`, so every card fell back to the generic placeholder even
     * when the product had uploaded photos. This attaches, in TWO queries for
     * the whole page:
     *
     *   imageUrl      uploaded primary image URL (isPrimary DESC, sortOrder ASC)
     *   imageAlt      its alt text (defaults to the product name)
     *   categorySlug  used by vp_product_image() to pick category artwork
     *                 when the product has no uploaded image yet
     *
     * @param  array $rows Rows from paginate()/find_all()
     * @return array       The same rows, enriched.
     */
    public function attach_images(array $rows)
    {
        if (empty($rows)) return $rows;

        $ids = [];
        $catIds = [];
        foreach ($rows as $r) {
            if (!empty($r['id'])) $ids[] = $r['id'];
            if (!empty($r['categoryId'])) $catIds[$r['categoryId']] = true;
        }
        if (empty($ids)) return $rows;

        // 1) primary image per product (single query)
        $images = [];
        try {
            $res = $this->db->select('productId, url, alt, isPrimary, sortOrder')
                            ->from('product_images')
                            ->where_in('productId', $ids)
                            ->order_by('isPrimary', 'DESC')
                            ->order_by('sortOrder', 'ASC')
                            ->get()->result_array();
            foreach ($res as $img) {
                if (!isset($images[$img['productId']]) && !empty($img['url'])) {
                    $images[$img['productId']] = $img;
                }
            }
        } catch (Exception $e) {
            $images = [];
        }

        // 2) category slugs for the artwork fallback (single query)
        $slugs = [];
        if ($catIds) {
            try {
                $res = $this->db->select('id, slug')->from('categories')
                                ->where_in('id', array_keys($catIds))->get()->result_array();
                foreach ($res as $c) $slugs[$c['id']] = $c['slug'];
            } catch (Exception $e) {
                $slugs = [];
            }
        }

        foreach ($rows as &$row) {
            $pid = $row['id'] ?? null;
            $row['categorySlug'] = (!empty($row['categoryId']) && isset($slugs[$row['categoryId']]))
                ? $slugs[$row['categoryId']] : null;
            $row['imageUrl'] = ($pid && isset($images[$pid])) ? $images[$pid]['url'] : null;
            $row['imageAlt'] = ($pid && isset($images[$pid]) && !empty($images[$pid]['alt']))
                ? $images[$pid]['alt'] : ($row['name'] ?? '');
        }
        unset($row);

        return $rows;
    }
}
