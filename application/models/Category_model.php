<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — product categories.
 *
 * nameNorm is the case- and whitespace-insensitive form of `name` used by the
 * unique index (uk_categories_name_norm). It is always written on insert/update
 * so the database, not just the admin form, rejects duplicate category names.
 */
class Category_model extends MY_Model
{
    protected $table = 'categories';
    protected $fillable = ['name','nameNorm','slug','description','icon','image','parentId','sortOrder','isActive','metaTitle','metaDescription'];
    protected $order_by = ['sortOrder' => 'ASC', 'name' => 'ASC'];

    /** @inheritDoc */
    public function insert(array $data)
    {
        $data = $this->_with_name_norm($data);
        return parent::insert($data);
    }

    /** @inheritDoc */
    public function update($id, array $data)
    {
        $data = $this->_with_name_norm($data);
        return parent::update($id, $data);
    }

    /**
     * Normalize the display name and keep nameNorm in sync.
     * Collapses internal whitespace so "Wheels  &  Brakes" matches "Wheels & Brakes".
     */
    private function _with_name_norm(array $data)
    {
        if (!array_key_exists('name', $data)) return $data;
        $display = trim(preg_replace('/\s+/u', ' ', (string) $data['name']));
        $data['name'] = $display;
        if (class_exists('Catalog_integrity')) {
            $data['nameNorm'] = Catalog_integrity::normalize_name($display);
        } else {
            $data['nameNorm'] = function_exists('mb_strtolower')
                ? mb_strtolower($display, 'UTF-8')
                : strtolower($display);
        }
        if ($data['nameNorm'] === '') $data['nameNorm'] = null;
        return $data;
    }
}
