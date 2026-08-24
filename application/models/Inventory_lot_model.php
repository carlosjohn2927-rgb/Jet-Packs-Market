<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Multi-warehouse, lot-level inventory ledger.
 *
 * `products.quantity` remains a fast public-facing cache. This model is the
 * source of truth and refreshes that cache after every receipt, adjustment,
 * reservation or transfer. Lots never disappear; movements form the audit
 * trail needed for traceability and expiry control.
 */
class Inventory_lot_model extends MY_Model
{
    protected $table = 'inventory_lots';
    protected $fillable = [
        'productId','warehouseId','lotNumber','serialNumber','binLocation',
        'condition','certification','traceabilityRef','quantityOnHand',
        'quantityReserved','receivedAt','expiresAt','status','notes','createdBy',
    ];
    protected $order_by = ['expiresAt' => 'ASC', 'createdAt' => 'ASC'];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['inventory_helper', 'security_helper']);
    }

    public function schema_available()
    {
        return $this->db->table_exists('warehouses')
            && $this->db->table_exists('inventory_lots')
            && $this->db->table_exists('inventory_movements');
    }

    /** Expire all due active lots, optionally limited to a product. */
    public function expire_due($productId = null)
    {
        if (!$this->schema_available()) return 0;
        $today = date('Y-m-d');
        $affectedProducts = [];
        if (!$productId) {
            $affectedProducts = array_column($this->db->select('DISTINCT productId', false)
                ->where('status', 'ACTIVE')->where('expiresAt IS NOT NULL', null, false)
                ->where('expiresAt <', $today)->get($this->table)->result_array(), 'productId');
        }
        $this->db->where('status', 'ACTIVE')
            ->where('expiresAt IS NOT NULL', null, false)
            ->where('expiresAt <', $today);
        if ($productId) $this->db->where('productId', $productId);
        $this->db->update($this->table, ['status' => 'EXPIRED']);
        $changed = (int) $this->db->affected_rows();
        // When an expiry is discovered while browsing the catalog, refresh
        // only the affected product cache rather than writing every product.
        if (!$productId && $changed > 0) {
            foreach ($affectedProducts as $id) $this->sync_product_stock($id);
        }
        return $changed;
    }

    public function has_lots($productId)
    {
        if (!$this->schema_available()) return false;
        return (int) $this->db->where('productId', $productId)->count_all_results($this->table) > 0;
    }

    public function for_product($productId)
    {
        if (!$this->schema_available()) return [];
        $this->expire_due($productId);
        return $this->db->select('l.*, w.name AS warehouseName, w.code AS warehouseCode, w.isAogHub,
                (l.quantityOnHand - l.quantityReserved) AS quantityAvailable')
            ->from($this->table . ' l')
            ->join('warehouses w', 'w.id = l.warehouseId', 'left')
            ->where('l.productId', $productId)
            ->order_by('w.sortOrder', 'ASC')->order_by('l.expiresAt', 'ASC')
            ->order_by('l.createdAt', 'ASC')->get()->result_array();
    }

    /** Public-safe stock summary; exact bin/lot/warehouse information stays admin-only. */
    public function product_summary($productId)
    {
        if (!$this->schema_available()) {
            return ['available' => null, 'onHand' => null, 'reserved' => null, 'warehouseCount' => 0, 'aogAvailable' => false, 'nextExpiry' => null];
        }
        $this->expire_due($productId);
        $today = date('Y-m-d');
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN l.status = 'ACTIVE' AND (l.expiresAt IS NULL OR l.expiresAt >= ?) THEN l.quantityOnHand - l.quantityReserved ELSE 0 END), 0) AS available,
                    COALESCE(SUM(CASE WHEN l.status = 'ACTIVE' THEN l.quantityOnHand ELSE 0 END), 0) AS onHand,
                    COALESCE(SUM(CASE WHEN l.status = 'ACTIVE' THEN l.quantityReserved ELSE 0 END), 0) AS reserved,
                    COUNT(DISTINCT CASE WHEN l.status = 'ACTIVE' AND (l.expiresAt IS NULL OR l.expiresAt >= ?) AND (l.quantityOnHand - l.quantityReserved) > 0 THEN l.warehouseId END) AS warehouseCount,
                    COALESCE(MAX(CASE WHEN l.status = 'ACTIVE' AND w.isAogHub = 1 AND (l.expiresAt IS NULL OR l.expiresAt >= ?) AND (l.quantityOnHand - l.quantityReserved) > 0 THEN 1 ELSE 0 END), 0) AS aogAvailable,
                    MIN(CASE WHEN l.status = 'ACTIVE' AND l.expiresAt IS NOT NULL AND l.expiresAt >= ? AND (l.quantityOnHand - l.quantityReserved) > 0 THEN l.expiresAt END) AS nextExpiry
                FROM inventory_lots l
                INNER JOIN warehouses w ON w.id = l.warehouseId AND w.isActive = 1
                WHERE l.productId = ?";
        $row = $this->db->query($sql, [$today, $today, $today, $today, $productId])->row_array() ?: [];
        return [
            'available'      => (int) ($row['available'] ?? 0),
            'onHand'         => (int) ($row['onHand'] ?? 0),
            'reserved'       => (int) ($row['reserved'] ?? 0),
            'warehouseCount' => (int) ($row['warehouseCount'] ?? 0),
            'aogAvailable'   => !empty($row['aogAvailable']),
            'nextExpiry'     => $row['nextExpiry'] ?? null,
        ];
    }

    public function create_lot(array $data, $actorId = null)
    {
        if (!$this->schema_available()) return ['ok' => false, 'error' => 'Inventory tables are not installed.'];
        $data = $this->_normalise_lot($data);
        if (!$data['productId'] || !$data['warehouseId'] || $data['lotNumber'] === '') {
            return ['ok' => false, 'error' => 'Product, warehouse and lot number are required.'];
        }
        if ($data['quantityOnHand'] < 0 || $data['quantityReserved'] < 0 || $data['quantityReserved'] > $data['quantityOnHand']) {
            return ['ok' => false, 'error' => 'Reserved quantity must be between zero and quantity on hand.'];
        }
        $data['status'] = $this->_status_for($data['status'], $data['quantityOnHand'], $data['expiresAt']);
        if (!$this->db->get_where('products', ['id' => $data['productId']])->row_array()) return ['ok' => false, 'error' => 'Product not found.'];
        $warehouse = $this->db->get_where('warehouses', ['id' => $data['warehouseId'], 'isActive' => 1])->row_array();
        if (!$warehouse) return ['ok' => false, 'error' => 'Select an active warehouse.'];
        $dup = $this->db->get_where($this->table, [
            'productId' => $data['productId'], 'warehouseId' => $data['warehouseId'], 'lotNumber' => $data['lotNumber'],
        ])->row_array();
        if ($dup) return ['ok' => false, 'error' => 'That lot number already exists for this product at the selected warehouse.'];

        $data['id'] = MY_Model::uuid();
        $data['createdBy'] = $actorId ?: null;
        $data['createdAt'] = date('Y-m-d H:i:s');
        $data['updatedAt'] = $data['createdAt'];
        $this->db->trans_begin();
        $this->db->insert($this->table, $data);
        $this->_movement($data['id'], $data['productId'], $data['warehouseId'], 'RECEIPT', $data['quantityOnHand'], $data['quantityReserved'], 'Opening/received lot', $actorId);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Could not save the inventory lot.'];
        }
        $this->db->trans_commit();
        $this->sync_product_stock($data['productId']);
        return ['ok' => true, 'lot' => $this->find($data['id'])];
    }

    /** Adjust on-hand and reserved quantities atomically and write a movement. */
    public function adjust($lotId, $quantityDelta, $reservedDelta, $note, $actorId = null)
    {
        if (!$this->schema_available()) return ['ok' => false, 'error' => 'Inventory tables are not installed.'];
        $quantityDelta = (int) $quantityDelta;
        $reservedDelta = (int) $reservedDelta;
        if ($quantityDelta === 0 && $reservedDelta === 0) return ['ok' => false, 'error' => 'Enter a quantity or reservation adjustment.'];

        $this->db->trans_begin();
        $lot = $this->db->get_where($this->table, ['id' => $lotId])->row_array();
        if (!$lot) { $this->db->trans_rollback(); return ['ok' => false, 'error' => 'Inventory lot not found.']; }
        if (in_array($lot['status'], ['EXPIRED', 'QUARANTINE'], true) && $quantityDelta > 0) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Release the lot from its current status before adding available stock.'];
        }
        $onHand = (int) $lot['quantityOnHand'] + $quantityDelta;
        $reserved = (int) $lot['quantityReserved'] + $reservedDelta;
        if ($onHand < 0 || $reserved < 0 || $reserved > $onHand) {
            $this->db->trans_rollback();
            return ['ok' => false, 'error' => 'Adjustment would make stock or reserved quantity invalid.'];
        }
        $status = $this->_status_for($lot['status'], $onHand, $lot['expiresAt']);
        $this->db->where('id', $lotId)->update($this->table, [
            'quantityOnHand' => $onHand,
            'quantityReserved' => $reserved,
            'status' => $status,
            'updatedAt' => date('Y-m-d H:i:s'),
        ]);
        $type = $quantityDelta !== 0 ? ($quantityDelta > 0 ? 'ADJUST_IN' : 'ADJUST_OUT') : ($reservedDelta > 0 ? 'RESERVE' : 'RELEASE');
        $this->_movement($lotId, $lot['productId'], $lot['warehouseId'], $type, $quantityDelta, $reservedDelta, $note, $actorId);
        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return ['ok' => false, 'error' => 'Could not save the inventory adjustment.']; }
        $this->db->trans_commit();
        $this->sync_product_stock($lot['productId']);
        return ['ok' => true, 'lot' => $this->find($lotId)];
    }

    /** Move unreserved stock between warehouses while retaining lot traceability. */
    public function transfer($lotId, $targetWarehouseId, $quantity, $note, $actorId = null)
    {
        if (!$this->schema_available()) return ['ok' => false, 'error' => 'Inventory tables are not installed.'];
        $quantity = (int) $quantity;
        if ($quantity <= 0) return ['ok' => false, 'error' => 'Transfer quantity must be positive.'];

        $this->db->trans_begin();
        $source = $this->db->get_where($this->table, ['id' => $lotId])->row_array();
        $targetWarehouse = $this->db->get_where('warehouses', ['id' => $targetWarehouseId, 'isActive' => 1])->row_array();
        if (!$source || !$targetWarehouse) { $this->db->trans_rollback(); return ['ok' => false, 'error' => 'Source lot or target warehouse was not found.']; }
        if ($source['warehouseId'] === $targetWarehouseId) { $this->db->trans_rollback(); return ['ok' => false, 'error' => 'Choose a different warehouse for the transfer.']; }
        if ($source['status'] !== 'ACTIVE' || !empty($source['expiresAt']) && $source['expiresAt'] < date('Y-m-d')) { $this->db->trans_rollback(); return ['ok' => false, 'error' => 'Only active, unexpired lots can be transferred.']; }
        $available = (int) $source['quantityOnHand'] - (int) $source['quantityReserved'];
        if ($quantity > $available) { $this->db->trans_rollback(); return ['ok' => false, 'error' => 'Transfer quantity exceeds unreserved stock.']; }

        $target = $this->db->get_where($this->table, [
            'productId' => $source['productId'], 'warehouseId' => $targetWarehouseId, 'lotNumber' => $source['lotNumber'],
        ])->row_array();
        $now = date('Y-m-d H:i:s');
        $sourceOnHand = (int) $source['quantityOnHand'] - $quantity;
        $this->db->where('id', $source['id'])->update($this->table, [
            'quantityOnHand' => $sourceOnHand,
            'status' => $this->_status_for($source['status'], $sourceOnHand, $source['expiresAt']),
            'updatedAt' => $now,
        ]);
        $this->_movement($source['id'], $source['productId'], $source['warehouseId'], 'TRANSFER_OUT', -$quantity, 0, $note, $actorId);

        if ($target) {
            $this->db->where('id', $target['id'])->update($this->table, [
                'quantityOnHand' => (int) $target['quantityOnHand'] + $quantity,
                'status' => $this->_status_for($target['status'], (int) $target['quantityOnHand'] + $quantity, $target['expiresAt']),
                'updatedAt' => $now,
            ]);
            $targetId = $target['id'];
        } else {
            $targetId = MY_Model::uuid();
            $copy = $source;
            unset($copy['id'], $copy['createdAt'], $copy['updatedAt']);
            $copy['id'] = $targetId;
            $copy['warehouseId'] = $targetWarehouseId;
            $copy['quantityOnHand'] = $quantity;
            $copy['quantityReserved'] = 0;
            $copy['status'] = $this->_status_for('ACTIVE', $quantity, $copy['expiresAt']);
            $copy['createdBy'] = $actorId ?: null;
            $copy['createdAt'] = $now;
            $copy['updatedAt'] = $now;
            $this->db->insert($this->table, $copy);
        }
        $this->_movement($targetId, $source['productId'], $targetWarehouseId, 'TRANSFER_IN', $quantity, 0, $note, $actorId);
        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return ['ok' => false, 'error' => 'Could not complete the transfer.']; }
        $this->db->trans_commit();
        $this->sync_product_stock($source['productId']);
        return ['ok' => true, 'targetLotId' => $targetId];
    }

    /** Update traceability/status metadata without allowing silent quantity edits. */
    public function update_details($lotId, array $data, $actorId = null)
    {
        $lot = $this->find($lotId);
        if (!$lot) return ['ok' => false, 'error' => 'Inventory lot not found.'];
        $allowed = ['binLocation','serialNumber','condition','certification','traceabilityRef','receivedAt','expiresAt','notes','status'];
        $update = [];
        foreach ($allowed as $key) if (array_key_exists($key, $data)) $update[$key] = $data[$key];
        $update = $this->_normalise_lot($update + $lot, true);
        $update = array_intersect_key($update, array_flip($allowed));
        $update['status'] = $this->_status_for($update['status'] ?? $lot['status'], (int) $lot['quantityOnHand'], $update['expiresAt'] ?? $lot['expiresAt']);
        $update['updatedAt'] = date('Y-m-d H:i:s');
        $this->db->where('id', $lotId)->update($this->table, $update);
        $this->_movement($lotId, $lot['productId'], $lot['warehouseId'], 'DETAIL_UPDATE', 0, 0, 'Lot details updated.', $actorId);
        $this->sync_product_stock($lot['productId']);
        return ['ok' => true, 'lot' => $this->find($lotId)];
    }

    /** Seed / migrate a product's legacy quantity into one traceable opening lot. */
    public function bootstrap_legacy_stock($productId, $sku, $quantity, $actorId = null)
    {
        if (!$this->schema_available() || $this->has_lots($productId) || (int) $quantity <= 0) return null;
        $product = $this->db->get_where('products', ['id' => $productId])->row_array();
        if (!$product) return null;
        $warehouse = $this->db->where('isActive', 1)->where('isAogHub', 1)->order_by('sortOrder', 'ASC')->limit(1)->get('warehouses')->row_array();
        if (!$warehouse) $warehouse = $this->db->where('isActive', 1)->order_by('sortOrder', 'ASC')->limit(1)->get('warehouses')->row_array();
        if (!$warehouse) return null;
        return $this->create_lot([
            'productId' => $productId,
            'warehouseId' => $warehouse['id'],
            'lotNumber' => 'OPENING-' . substr(vp_inventory_lot_number($sku), 0, 70),
            'quantityOnHand' => (int) $quantity,
            'quantityReserved' => 0,
            'condition' => $product['condition'] ?? 'NEW',
            'status' => 'ACTIVE',
            'notes' => 'Opening balance migrated from legacy product quantity.',
        ], $actorId);
    }

    public function movements($lotId, $limit = 30)
    {
        if (!$this->schema_available()) return [];
        return $this->db->select('m.*, u.firstName, u.lastName')
            ->from('inventory_movements m')->join('users u', 'u.id = m.actorId', 'left')
            ->where('m.inventoryLotId', $lotId)->order_by('m.createdAt', 'DESC')
            ->limit($limit)->get()->result_array();
    }

    public function all_with_details(array $filters = [], $limit = 250)
    {
        if (!$this->schema_available()) return [];
        $this->expire_due();
        $this->db->select('l.*, p.name AS productName, p.sku AS productSku, w.name AS warehouseName, w.code AS warehouseCode, w.isAogHub,
                (l.quantityOnHand - l.quantityReserved) AS quantityAvailable')
            ->from('inventory_lots l')->join('products p', 'p.id = l.productId', 'inner')
            ->join('warehouses w', 'w.id = l.warehouseId', 'inner');
        if (!empty($filters['warehouseId'])) $this->db->where('l.warehouseId', $filters['warehouseId']);
        if (!empty($filters['status'])) $this->db->where('l.status', $filters['status']);
        if (!empty($filters['q'])) {
            $this->db->group_start()->like('p.name', $filters['q'])->or_like('p.sku', $filters['q'])
                ->or_like('l.lotNumber', $filters['q'])->or_like('l.serialNumber', $filters['q'])->group_end();
        }
        return $this->db->order_by('l.expiresAt', 'ASC')->order_by('p.name', 'ASC')->limit($limit)->get()->result_array();
    }

    public function warehouse_totals()
    {
        if (!$this->schema_available()) return [];
        $this->expire_due();
        $today = date('Y-m-d');
        $sql = "SELECT w.id, w.name, w.code, w.isAogHub, w.isActive,
                    COUNT(l.id) AS lotCount,
                    COALESCE(SUM(CASE WHEN l.status = 'ACTIVE' AND (l.expiresAt IS NULL OR l.expiresAt >= ?) THEN l.quantityOnHand - l.quantityReserved ELSE 0 END),0) AS available,
                    COALESCE(SUM(CASE WHEN l.status = 'ACTIVE' AND l.expiresAt >= ? AND l.expiresAt <= DATE_ADD(?, INTERVAL 30 DAY) THEN 1 ELSE 0 END),0) AS expiringCount
                FROM warehouses w LEFT JOIN inventory_lots l ON l.warehouseId = w.id
                GROUP BY w.id ORDER BY w.sortOrder ASC, w.name ASC";
        // SQLite has no DATE_ADD; calculate its date in PHP and use a simple comparison.
        if ($this->db->dbdriver === 'sqlite3') {
            $sql = "SELECT w.id, w.name, w.code, w.isAogHub, w.isActive,
                        COUNT(l.id) AS lotCount,
                        COALESCE(SUM(CASE WHEN l.status = 'ACTIVE' AND (l.expiresAt IS NULL OR l.expiresAt >= ?) THEN l.quantityOnHand - l.quantityReserved ELSE 0 END),0) AS available,
                        COALESCE(SUM(CASE WHEN l.status = 'ACTIVE' AND l.expiresAt >= ? AND l.expiresAt <= ? THEN 1 ELSE 0 END),0) AS expiringCount
                    FROM warehouses w LEFT JOIN inventory_lots l ON l.warehouseId = w.id
                    GROUP BY w.id ORDER BY w.sortOrder ASC, w.name ASC";
            return $this->db->query($sql, [$today, $today, date('Y-m-d', strtotime('+30 days'))])->result_array();
        }
        return $this->db->query($sql, [$today, $today, $today])->result_array();
    }

    /** Refresh product.quantity caches after scheduled/on-request expiry processing. */
    public function refresh_stock_caches($limit = 5000)
    {
        if (!$this->schema_available()) return 0;
        $this->expire_due();
        $rows = $this->db->select('DISTINCT productId', false)->limit($limit)->get($this->table)->result_array();
        foreach ($rows as $row) $this->sync_product_stock($row['productId']);
        return count($rows);
    }

    public function sync_product_stock($productId)
    {
        if (!$this->schema_available()) return null;
        $summary = $this->product_summary($productId);
        $product = $this->db->get_where('products', ['id' => $productId])->row_array();
        if (!$product) return $summary;
        $update = ['quantity' => (int) $summary['available']];
        if (($product['availability'] ?? '') === 'IN_STOCK' && $summary['available'] <= 0) $update['availability'] = 'OUT_OF_STOCK';
        if (($product['availability'] ?? '') === 'OUT_OF_STOCK' && $summary['available'] > 0) $update['availability'] = 'IN_STOCK';
        $this->db->where('id', $productId)->update('products', $update);
        return $summary;
    }

    private function _normalise_lot(array $data, $partial = false)
    {
        foreach (['receivedAt','expiresAt'] as $date) {
            if (array_key_exists($date, $data)) {
                $data[$date] = trim((string) $data[$date]) ?: null;
                if ($data[$date] && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$date])) $data[$date] = null;
            }
        }
        if (array_key_exists('lotNumber', $data)) $data['lotNumber'] = vp_inventory_lot_number($data['lotNumber']);
        foreach (['serialNumber','binLocation','condition','certification','traceabilityRef','notes'] as $key) {
            if (array_key_exists($key, $data)) $data[$key] = trim((string) $data[$key]) ?: null;
        }
        if (array_key_exists('quantityOnHand', $data)) $data['quantityOnHand'] = max(0, (int) $data['quantityOnHand']);
        if (array_key_exists('quantityReserved', $data)) $data['quantityReserved'] = max(0, (int) $data['quantityReserved']);
        if (array_key_exists('status', $data)) {
            $status = strtoupper(trim((string) $data['status']));
            $data['status'] = in_array($status, ['ACTIVE','QUARANTINE','EXPIRED','DEPLETED'], true) ? $status : 'ACTIVE';
        }
        return $data;
    }

    private function _status_for($requested, $onHand, $expiresAt)
    {
        if (!empty($expiresAt) && $expiresAt < date('Y-m-d')) return 'EXPIRED';
        if ($requested === 'QUARANTINE') return 'QUARANTINE';
        if ((int) $onHand <= 0) return 'DEPLETED';
        return 'ACTIVE';
    }

    private function _movement($lotId, $productId, $warehouseId, $type, $quantityDelta, $reservedDelta, $note, $actorId)
    {
        $this->db->insert('inventory_movements', [
            'id' => MY_Model::uuid(),
            'inventoryLotId' => $lotId,
            'productId' => $productId,
            'warehouseId' => $warehouseId,
            'movementType' => $type,
            'quantityDelta' => (int) $quantityDelta,
            'reservedDelta' => (int) $reservedDelta,
            'referenceType' => null,
            'referenceId' => null,
            'notes' => trim((string) $note) ?: null,
            'actorId' => $actorId ?: null,
            'createdAt' => date('Y-m-d H:i:s'),
        ]);
    }
}
