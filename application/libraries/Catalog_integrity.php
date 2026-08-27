<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — catalog data-integrity corrections.
 *
 * Implements the data-layer side of "Category and Product Data Integrity —
 * Remove Duplicates":
 *
 *   • Normalize category / product names (trim, collapse whitespace, casefold)
 *   • Merge duplicate categories onto a canonical row (reassign products first)
 *   • Merge duplicate products onto a canonical row (reassign images, specs,
 *     downloads, related links, inventory lots/movements, quote items first)
 *   • Ensure nameNorm is populated and unique indexes exist
 *   • Seed one primary product_images row per part from its own illustration
 *
 * Runs once per install (flagged by the `catalog_integrity_v1` setting). Safe
 * to call repeatedly — subsequent runs are no-ops once the flag is set, unless
 * $force is true.
 */
class Catalog_integrity
{
    /** @var CI_Controller */
    protected $CI;

    /** Setting key written after a successful full run. */
    const FLAG_KEY = 'catalog_integrity_v1';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    /**
     * Normalize a display name for uniqueness comparison.
     * Trims edges, collapses internal whitespace, lowercases.
     */
    public static function normalize_name($name)
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $name));
        if ($name === '') return '';
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($name, 'UTF-8');
        }
        return strtolower($name);
    }

    /**
     * Run the full integrity pass if it has not run yet (or when $force).
     *
     * @return array{ran:bool, categories_merged:int, products_merged:int, images_seeded:int, indexes:bool}
     */
    public function ensure($force = false)
    {
        $result = [
            'ran' => false,
            'categories_merged' => 0,
            'products_merged' => 0,
            'images_seeded' => 0,
            'indexes' => false,
            'skipped' => false,
        ];

        if (!$this->CI->db->table_exists('categories') || !$this->CI->db->table_exists('products')) {
            $result['skipped'] = true;
            return $result;
        }

        // Ensure the nameNorm columns exist even if migration 010 has not been
        // imported yet (older installs that only pulled the application code).
        $this->_ensure_name_norm_column('categories', 190);
        $this->_ensure_name_norm_column('products', 255);

        if (!$force && $this->_already_ran()) {
            $result['skipped'] = true;
            return $result;
        }

        $result['ran'] = true;

        // 1. Normalize every name + nameNorm.
        $this->_normalize_all('categories');
        $this->_normalize_all('products');

        // 2. Merge duplicate categories (reassign products first).
        $result['categories_merged'] = $this->_merge_duplicate_categories();

        // 3. Merge duplicate products (reassign children first).
        $result['products_merged'] = $this->_merge_duplicate_products();

        // 4. Re-normalize after merges (canonical rows may have absorbed fields).
        $this->_normalize_all('categories');
        $this->_normalize_all('products');

        // 5. Unique indexes on nameNorm.
        $result['indexes'] = $this->_ensure_unique_indexes();

        // 6. One unique primary image per product from /assets/img/products/<slug>.jpg.
        $result['images_seeded'] = $this->_seed_product_images();

        $this->_mark_ran($result);
        return $result;
    }

    /* ----------------------------------------------------------------- */

    protected function _already_ran()
    {
        if (!$this->CI->db->table_exists('settings')) return false;
        $row = $this->CI->db->select('value')->from('settings')
            ->where('key', self::FLAG_KEY)->limit(1)->get()->row_array();
        return $row && (string) $row['value'] === '1';
    }

    protected function _mark_ran(array $result)
    {
        if (!$this->CI->db->table_exists('settings')) return;
        $payload = json_encode([
            'ok' => 1,
            'at' => date('c'),
            'categories_merged' => (int) $result['categories_merged'],
            'products_merged' => (int) $result['products_merged'],
            'images_seeded' => (int) $result['images_seeded'],
        ]);
        $existing = $this->CI->db->select('id')->from('settings')
            ->where('key', self::FLAG_KEY)->limit(1)->get()->row_array();
        if ($existing) {
            $this->CI->db->where('id', $existing['id'])->update('settings', [
                'value' => '1',
                'type' => 'STRING',
                'group' => 'SYSTEM',
            ]);
            // Keep a detail row too (optional, non-fatal).
            $detail = $this->CI->db->select('id')->from('settings')
                ->where('key', self::FLAG_KEY . '_detail')->limit(1)->get()->row_array();
            if ($detail) {
                $this->CI->db->where('id', $detail['id'])->update('settings', ['value' => $payload]);
            } else {
                $this->_insert_setting(self::FLAG_KEY . '_detail', $payload, 'JSON', 'SYSTEM');
            }
            return;
        }
        $this->_insert_setting(self::FLAG_KEY, '1', 'STRING', 'SYSTEM');
        $this->_insert_setting(self::FLAG_KEY . '_detail', $payload, 'JSON', 'SYSTEM');
    }

    protected function _insert_setting($key, $value, $type, $group)
    {
        $id = $this->_uuid();
        try {
            $this->CI->db->insert('settings', [
                'id' => $id,
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'sortOrder' => 0,
                'enabled' => 1,
            ]);
        } catch (Exception $e) {
            // ignore
        }
    }

    protected function _ensure_name_norm_column($table, $len)
    {
        if (!$this->CI->db->table_exists($table)) return;
        if ($this->CI->db->field_exists('nameNorm', $table)) return;
        $driver = $this->CI->db->dbdriver ?? '';
        try {
            if ($driver === 'sqlite3' || $driver === 'sqlite') {
                $this->CI->db->query("ALTER TABLE `{$table}` ADD COLUMN `nameNorm` TEXT DEFAULT NULL");
            } else {
                // Place after `name` when the engine supports it.
                $this->CI->db->query("ALTER TABLE `{$table}` ADD COLUMN `nameNorm` VARCHAR({$len}) DEFAULT NULL AFTER `name`");
            }
        } catch (Exception $e) {
            // Column may already exist under a race; ignore.
        }
    }

    protected function _normalize_all($table)
    {
        if (!$this->CI->db->table_exists($table)) return;
        if (!$this->CI->db->field_exists('nameNorm', $table)) return;

        $rows = $this->CI->db->select('id, name, nameNorm')->from($table)->get()->result_array();
        foreach ($rows as $row) {
            $display = trim(preg_replace('/\s+/u', ' ', (string) ($row['name'] ?? '')));
            $norm = self::normalize_name($display);
            if ($display === (string) ($row['name'] ?? '') && $norm === (string) ($row['nameNorm'] ?? '')) {
                continue;
            }
            $data = ['nameNorm' => $norm !== '' ? $norm : null];
            if ($display !== (string) ($row['name'] ?? '')) {
                $data['name'] = $display;
            }
            $this->CI->db->where('id', $row['id'])->update($table, $data);
        }
    }

    /**
     * Merge categories that share the same nameNorm.
     * Canonical = lowest sortOrder, then earliest createdAt, then lowest id.
     * Products and child categories are reassigned before the duplicate is removed.
     */
    protected function _merge_duplicate_categories()
    {
        $rows = $this->CI->db->select('id, name, nameNorm, slug, description, icon, image, parentId, sortOrder, isActive, metaTitle, metaDescription, createdAt')
            ->from('categories')->get()->result_array();
        $groups = [];
        foreach ($rows as $r) {
            $key = (string) ($r['nameNorm'] ?? '');
            if ($key === '') continue;
            $groups[$key][] = $r;
        }

        $merged = 0;
        foreach ($groups as $key => $group) {
            if (count($group) < 2) continue;
            usort($group, function ($a, $b) {
                $sa = (int) ($a['sortOrder'] ?? 0); $sb = (int) ($b['sortOrder'] ?? 0);
                if ($sa !== $sb) return $sa <=> $sb;
                $ca = (string) ($a['createdAt'] ?? ''); $cb = (string) ($b['createdAt'] ?? '');
                if ($ca !== $cb) return strcmp($ca, $cb);
                return strcmp((string) $a['id'], (string) $b['id']);
            });
            $keep = $group[0];
            $dups = array_slice($group, 1);

            // Absorb non-empty metadata from duplicates onto the keep row.
            $absorb = [];
            foreach (['description', 'icon', 'image', 'metaTitle', 'metaDescription', 'slug'] as $col) {
                if ($this->_blank($keep[$col] ?? null)) {
                    foreach ($dups as $d) {
                        if (!$this->_blank($d[$col] ?? null)) {
                            $absorb[$col] = $d[$col];
                            $keep[$col] = $d[$col];
                            break;
                        }
                    }
                }
            }
            // Prefer active if any duplicate is active.
            $anyActive = (int) ($keep['isActive'] ?? 0);
            foreach ($dups as $d) $anyActive = max($anyActive, (int) ($d['isActive'] ?? 0));
            if ($anyActive !== (int) ($keep['isActive'] ?? 0)) $absorb['isActive'] = $anyActive;
            if ($absorb) {
                $this->CI->db->where('id', $keep['id'])->update('categories', $absorb);
            }

            foreach ($dups as $dup) {
                // Reassign products first — never delete products with the category.
                $this->CI->db->where('categoryId', $dup['id'])
                    ->update('products', ['categoryId' => $keep['id']]);

                // Re-parent child categories.
                $this->CI->db->where('parentId', $dup['id'])
                    ->where('id !=', $keep['id'])
                    ->update('categories', ['parentId' => $keep['id']]);

                $this->CI->db->where('id', $dup['id'])->delete('categories');
                $merged++;
            }
        }
        return $merged;
    }

    /**
     * Merge products that share the same nameNorm (or that would collide on
     * sku/slug after a rename). Canonical = featured first, then most views,
     * then earliest createdAt.
     */
    protected function _merge_duplicate_products()
    {
        $rows = $this->CI->db->select('*')->from('products')->get()->result_array();

        // Group by nameNorm; also detect sku/slug collisions separately.
        $byName = [];
        foreach ($rows as $r) {
            $key = (string) ($r['nameNorm'] ?? '');
            if ($key === '') $key = self::normalize_name($r['name'] ?? '');
            if ($key === '') continue;
            $byName[$key][] = $r;
        }

        $merged = 0;
        foreach ($byName as $key => $group) {
            if (count($group) < 2) continue;
            $merged += $this->_merge_product_group($group);
        }

        // SKU collisions (exact, case-sensitive as stored).
        $rows = $this->CI->db->select('*')->from('products')->get()->result_array();
        $bySku = [];
        foreach ($rows as $r) {
            $sku = trim((string) ($r['sku'] ?? ''));
            if ($sku === '') continue;
            $bySku[$sku][] = $r;
        }
        foreach ($bySku as $sku => $group) {
            if (count($group) < 2) continue;
            $merged += $this->_merge_product_group($group);
        }

        // Slug collisions.
        $rows = $this->CI->db->select('*')->from('products')->get()->result_array();
        $bySlug = [];
        foreach ($rows as $r) {
            $slug = trim((string) ($r['slug'] ?? ''));
            if ($slug === '') continue;
            $bySlug[strtolower($slug)][] = $r;
        }
        foreach ($bySlug as $slug => $group) {
            if (count($group) < 2) continue;
            $merged += $this->_merge_product_group($group);
        }

        return $merged;
    }

    protected function _merge_product_group(array $group)
    {
        if (count($group) < 2) return 0;

        usort($group, function ($a, $b) {
            $fa = (int) ($a['featured'] ?? 0); $fb = (int) ($b['featured'] ?? 0);
            if ($fa !== $fb) return $fb <=> $fa; // featured first
            $va = (int) ($a['views'] ?? 0); $vb = (int) ($b['views'] ?? 0);
            if ($va !== $vb) return $vb <=> $va; // most views
            $ca = (string) ($a['createdAt'] ?? ''); $cb = (string) ($b['createdAt'] ?? '');
            if ($ca !== $cb) return strcmp($ca, $cb);
            return strcmp((string) $a['id'], (string) $b['id']);
        });

        $keep = $group[0];
        $dups = array_slice($group, 1);
        $merged = 0;

        // Absorb useful fields from duplicates.
        $absorb = [];
        foreach (['description', 'shortDescription', 'metaTitle', 'metaDescription', 'manufacturer', 'material', 'pressure', 'temperature', 'voltage', 'dimensions', 'weight', 'condition', 'availability', 'aircraftType'] as $col) {
            if ($this->_blank($keep[$col] ?? null)) {
                foreach ($dups as $d) {
                    if (!$this->_blank($d[$col] ?? null)) {
                        $absorb[$col] = $d[$col];
                        $keep[$col] = $d[$col];
                        break;
                    }
                }
            }
        }
        if ($this->_blank($keep['categoryId'] ?? null)) {
            foreach ($dups as $d) {
                if (!$this->_blank($d['categoryId'] ?? null)) {
                    $absorb['categoryId'] = $d['categoryId'];
                    break;
                }
            }
        }
        if ($this->_blank($keep['price'] ?? null)) {
            foreach ($dups as $d) {
                if (!$this->_blank($d['price'] ?? null)) {
                    $absorb['price'] = $d['price'];
                    break;
                }
            }
        }
        $feat = (int) ($keep['featured'] ?? 0);
        $views = (int) ($keep['views'] ?? 0);
        $qty = (int) ($keep['quantity'] ?? 0);
        foreach ($dups as $d) {
            $feat = max($feat, (int) ($d['featured'] ?? 0));
            $views = max($views, (int) ($d['views'] ?? 0));
            $qty = max($qty, (int) ($d['quantity'] ?? 0));
        }
        if ($feat !== (int) ($keep['featured'] ?? 0)) $absorb['featured'] = $feat;
        if ($views !== (int) ($keep['views'] ?? 0)) $absorb['views'] = $views;
        if ($qty !== (int) ($keep['quantity'] ?? 0)) $absorb['quantity'] = $qty;
        if ($absorb) {
            $this->CI->db->where('id', $keep['id'])->update('products', $absorb);
        }

        foreach ($dups as $dup) {
            $this->_reassign_product_children($dup['id'], $keep['id']);
            // Child rows that still reference the duplicate (e.g. colliding lots)
            // are removed with the product via ON DELETE CASCADE where defined;
            // for tables without cascade we clean explicitly first.
            $this->_delete_orphan_product_children($dup['id']);
            $this->CI->db->where('id', $dup['id'])->delete('products');
            $merged++;
        }
        return $merged;
    }

    protected function _reassign_product_children($fromId, $toId)
    {
        if ($fromId === $toId) return;

        // product_images — move unique URLs, drop exact URL dupes
        if ($this->CI->db->table_exists('product_images')) {
            $imgs = $this->CI->db->from('product_images')->where('productId', $fromId)->get()->result_array();
            $keepUrls = [];
            foreach ($this->CI->db->select('url')->from('product_images')->where('productId', $toId)->get()->result_array() as $r) {
                $keepUrls[(string) $r['url']] = true;
            }
            foreach ($imgs as $img) {
                $url = (string) ($img['url'] ?? '');
                if ($url !== '' && isset($keepUrls[$url])) {
                    $this->CI->db->where('id', $img['id'])->delete('product_images');
                    continue;
                }
                $this->CI->db->where('id', $img['id'])->update('product_images', [
                    'productId' => $toId,
                    // Demote so the keep product's existing primary stays primary.
                    'isPrimary' => 0,
                ]);
                $keepUrls[$url] = true;
            }
        }

        if ($this->CI->db->table_exists('specifications')) {
            $this->CI->db->where('productId', $fromId)->update('specifications', ['productId' => $toId]);
        }
        if ($this->CI->db->table_exists('product_downloads')) {
            $this->CI->db->where('productId', $fromId)->update('product_downloads', ['productId' => $toId]);
        }
        if ($this->CI->db->table_exists('related_products')) {
            // productId side
            $rels = $this->CI->db->from('related_products')->where('productId', $fromId)->get()->result_array();
            foreach ($rels as $rel) {
                $relatedId = $rel['relatedId'];
                if ($relatedId === $toId) {
                    $this->CI->db->where('id', $rel['id'])->delete('related_products');
                    continue;
                }
                $exists = $this->CI->db->from('related_products')
                    ->where(['productId' => $toId, 'relatedId' => $relatedId])->count_all_results();
                if ($exists) {
                    $this->CI->db->where('id', $rel['id'])->delete('related_products');
                } else {
                    $this->CI->db->where('id', $rel['id'])->update('related_products', ['productId' => $toId]);
                }
            }
            // relatedId side
            $rels = $this->CI->db->from('related_products')->where('relatedId', $fromId)->get()->result_array();
            foreach ($rels as $rel) {
                $productId = $rel['productId'];
                if ($productId === $toId) {
                    $this->CI->db->where('id', $rel['id'])->delete('related_products');
                    continue;
                }
                $exists = $this->CI->db->from('related_products')
                    ->where(['productId' => $productId, 'relatedId' => $toId])->count_all_results();
                if ($exists) {
                    $this->CI->db->where('id', $rel['id'])->delete('related_products');
                } else {
                    $this->CI->db->where('id', $rel['id'])->update('related_products', ['relatedId' => $toId]);
                }
            }
        }

        if ($this->CI->db->table_exists('inventory_lots')) {
            $lots = $this->CI->db->from('inventory_lots')->where('productId', $fromId)->get()->result_array();
            foreach ($lots as $lot) {
                $exists = $this->CI->db->from('inventory_lots')->where([
                    'productId' => $toId,
                    'warehouseId' => $lot['warehouseId'],
                    'lotNumber' => $lot['lotNumber'],
                ])->count_all_results();
                if ($exists) {
                    // Fold quantity into the keep lot, then drop the colliding one.
                    $keepLot = $this->CI->db->from('inventory_lots')->where([
                        'productId' => $toId,
                        'warehouseId' => $lot['warehouseId'],
                        'lotNumber' => $lot['lotNumber'],
                    ])->limit(1)->get()->row_array();
                    if ($keepLot) {
                        $this->CI->db->where('id', $keepLot['id'])->update('inventory_lots', [
                            'quantityOnHand' => (int) $keepLot['quantityOnHand'] + (int) $lot['quantityOnHand'],
                            'quantityReserved' => (int) $keepLot['quantityReserved'] + (int) $lot['quantityReserved'],
                        ]);
                        if ($this->CI->db->table_exists('inventory_movements')) {
                            $this->CI->db->where('inventoryLotId', $lot['id'])
                                ->update('inventory_movements', [
                                    'inventoryLotId' => $keepLot['id'],
                                    'productId' => $toId,
                                ]);
                        }
                    }
                    $this->CI->db->where('id', $lot['id'])->delete('inventory_lots');
                } else {
                    $this->CI->db->where('id', $lot['id'])->update('inventory_lots', ['productId' => $toId]);
                    if ($this->CI->db->table_exists('inventory_movements')) {
                        $this->CI->db->where('inventoryLotId', $lot['id'])
                            ->update('inventory_movements', ['productId' => $toId]);
                    }
                }
            }
            // Any movements still pointing at the old product id.
            if ($this->CI->db->table_exists('inventory_movements')) {
                $this->CI->db->where('productId', $fromId)
                    ->update('inventory_movements', ['productId' => $toId]);
            }
        }

        if ($this->CI->db->table_exists('quote_items') && $this->CI->db->field_exists('productId', 'quote_items')) {
            $this->CI->db->where('productId', $fromId)->update('quote_items', ['productId' => $toId]);
        }
    }

    protected function _delete_orphan_product_children($productId)
    {
        foreach (['product_images', 'specifications', 'product_downloads', 'related_products', 'inventory_lots', 'inventory_movements'] as $table) {
            if (!$this->CI->db->table_exists($table)) continue;
            if ($table === 'related_products') {
                $this->CI->db->group_start()
                    ->where('productId', $productId)
                    ->or_where('relatedId', $productId)
                    ->group_end()
                    ->delete('related_products');
                continue;
            }
            if ($this->CI->db->field_exists('productId', $table)) {
                // inventory_lots may still be referenced by movements — clear movements first.
                if ($table === 'inventory_lots' && $this->CI->db->table_exists('inventory_movements')) {
                    $lotIds = array_column(
                        $this->CI->db->select('id')->from('inventory_lots')->where('productId', $productId)->get()->result_array(),
                        'id'
                    );
                    if ($lotIds) {
                        $this->CI->db->where_in('inventoryLotId', $lotIds)->delete('inventory_movements');
                    }
                }
                $this->CI->db->where('productId', $productId)->delete($table);
            }
        }
    }

    protected function _ensure_unique_indexes()
    {
        $ok = true;
        $ok = $this->_add_unique_index('categories', 'uk_categories_name_norm', 'nameNorm') && $ok;
        $ok = $this->_add_unique_index('products', 'uk_products_name_norm', 'nameNorm') && $ok;
        // nameNorm must be unique; also keep slug/sku uniques (already in schema).
        return $ok;
    }

    protected function _add_unique_index($table, $indexName, $column)
    {
        if (!$this->CI->db->table_exists($table)) return false;
        if (!$this->CI->db->field_exists($column, $table)) return false;
        if ($this->_index_exists($table, $indexName)) return true;

        // Drop any residual null nameNorm rows that would block a unique index
        // (empty names become a synthetic unique value).
        $nulls = $this->CI->db->select('id')->from($table)
            ->group_start()
                ->where($column . ' IS NULL', null, false)
                ->or_where($column, '')
            ->group_end()
            ->get()->result_array();
        foreach ($nulls as $i => $row) {
            $this->CI->db->where('id', $row['id'])->update($table, [
                $column => '__empty_' . substr((string) $row['id'], 0, 8),
            ]);
        }

        $driver = $this->CI->db->dbdriver ?? '';
        try {
            if ($driver === 'sqlite3' || $driver === 'sqlite') {
                $this->CI->db->query("CREATE UNIQUE INDEX IF NOT EXISTS `{$indexName}` ON `{$table}` (`{$column}`)");
            } else {
                $this->CI->db->query("ALTER TABLE `{$table}` ADD UNIQUE KEY `{$indexName}` (`{$column}`)");
            }
            return true;
        } catch (Exception $e) {
            log_message('error', 'Catalog_integrity index ' . $indexName . ': ' . $e->getMessage());
            return false;
        }
    }

    protected function _index_exists($table, $indexName)
    {
        $driver = $this->CI->db->dbdriver ?? '';
        try {
            if ($driver === 'sqlite3' || $driver === 'sqlite') {
                $rows = $this->CI->db->query("PRAGMA index_list(`{$table}`)")->result_array();
                foreach ($rows as $r) {
                    if (strcasecmp((string) ($r['name'] ?? ''), $indexName) === 0) return true;
                }
                return false;
            }
            $dbName = $this->CI->db->database;
            $row = $this->CI->db->query(
                "SELECT 1 AS ok FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1",
                [$dbName, $table, $indexName]
            )->row_array();
            return !empty($row);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Seed one primary image per product from /assets/img/products/<slug>.jpg
     * when the product has no image rows yet. This puts unique artwork in the
     * DATA layer so listings do not all share a category image.
     */
    protected function _seed_product_images()
    {
        if (!$this->CI->db->table_exists('product_images') || !$this->CI->db->table_exists('products')) {
            return 0;
        }
        $products = $this->CI->db->select('id, name, slug')->from('products')->get()->result_array();
        $seeded = 0;
        foreach ($products as $p) {
            $slug = trim((string) ($p['slug'] ?? ''));
            if ($slug === '' || strpos($slug, '/') !== false || strpos($slug, '..') !== false) continue;

            $has = (int) $this->CI->db->where('productId', $p['id'])->count_all_results('product_images');
            if ($has > 0) continue;

            $rel = 'assets/img/products/' . $slug . '.jpg';
            $abs = FCPATH . $rel;
            // Always write the data row pointing at the per-product path; the
            // public helper still falls back gracefully if the file is missing.
            $url = '/' . $rel;
            try {
                $this->CI->db->insert('product_images', [
                    'id' => $this->_uuid(),
                    'productId' => $p['id'],
                    'url' => $url,
                    'alt' => $p['name'] ?? $slug,
                    'caption' => null,
                    'sortOrder' => 0,
                    'isPrimary' => 1,
                    'createdAt' => date('Y-m-d H:i:s'),
                ]);
                $seeded++;
            } catch (Exception $e) {
                // ignore individual failures
            }
        }
        return $seeded;
    }

    protected function _blank($v)
    {
        return $v === null || $v === '';
    }

    protected function _uuid()
    {
        if (class_exists('MY_Model')) {
            return MY_Model::uuid();
        }
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
