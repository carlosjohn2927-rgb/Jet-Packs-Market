<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum - Quote (RFQ) model with hardened state machine.
 *
 * Ported from the NestJS implementation:
 *   - Forward-only transitions per QUOTE_TRANSITIONS
 *   - assignedTo required when leaving NEW
 *   - Optimistic locking via `version`
 *   - Status history + activity log written on every change
 *   - Idempotent email sends via email_logs
 */
class Quote_model extends MY_Model
{
    protected $table = 'quotes';
    protected $fillable = [
        'quoteNumber','userId','companyName','contactPerson','email','phone','country',
        'address','industry','status','deadline','validUntil','totalAmount','currency',
        'notes','internalNotes','pdfUrl','assignedTo','statusUpdatedAt','lastNotifiedAt','version',
    ];
    protected $order_by = ['createdAt' => 'DESC'];

    /** Item fields an administrator may persist. */
    public $item_fillable = [
        'productId','productName','partNumber','description','manufacturer',
        'condition','quantity','specifications','leadTime','availability','notes',
        'unitPrice','total','currency',
    ];

    /**
     * Insert a new quote and items in a transaction.
     * @return array ['ok' => bool, 'id' => ..., 'quoteNumber' => ..., 'error' => ...]
     */
    public function create_quote(array $quoteData, array $items)
    {
        $this->db->trans_start();
        $quoteData['quoteNumber'] = vp_quote_number();
        $quoteData['status']      = QUOTE_NEW;
        $quoteData['version']     = 1;
        $quoteData['statusUpdatedAt'] = date('Y-m-d H:i:s');
        $id = $this->insert($quoteData);
        foreach ($items as $it) {
            $row = array_intersect_key($it, array_flip(array_merge($this->item_fillable, ['quoteId'])));
            $row['quoteId'] = $id;
            $row['id'] = $it['id'] ?? MY_Model::uuid();
            $row['currency'] = $row['currency'] ?? ($quoteData['currency'] ?? 'USD');
            $this->db->insert('quote_items', $row);
        }
        // history
        $this->db->insert('quote_status_history', [
            'id'         => MY_Model::uuid(),
            'quoteId'    => $id,
            'fromStatus' => null,
            'toStatus'   => QUOTE_NEW,
            'changedBy'  => null,
            'notes'      => 'Quote submitted',
            'createdAt'  => date('Y-m-d H:i:s'),
        ]);
        // activity
        $this->_log_activity($id, null, QA_CREATED, 'Quote submitted via web form', null);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return ['ok' => false, 'error' => 'Database error.'];
        }
        return ['ok' => true, 'id' => $id, 'quoteNumber' => $quoteData['quoteNumber']];
    }

    /**
     * Apply a status transition with optimistic locking.
     *
     * @return array ['ok' => bool, 'error' => string, 'quote' => array]
     */
    public function transition_status($quoteId, $toStatus, $actorId, $assignedTo = null, $notes = null, $expectedVersion = null)
    {
        $this->db->trans_start();
        $q = $this->db->get_where('quotes', ['id' => $quoteId])->row_array();
        if (!$q) { $this->db->trans_complete(); return ['ok' => false, 'error' => 'Quote not found.']; }

        if ($expectedVersion !== null && (int) $q['version'] !== (int) $expectedVersion) {
            $this->db->trans_complete();
            return ['ok' => false, 'error' => 'This quote was modified by someone else. Please refresh and try again.', 'conflict' => true, 'quote' => $q];
        }

        $from = $q['status'];
        $allowed = QUOTE_TRANSITIONS[$from] ?? [];
        if (!in_array($toStatus, $allowed, true)) {
            $this->db->trans_complete();
            return ['ok' => false, 'error' => "Cannot transition from {$from} to {$toStatus}."];
        }

        // assignment rule
        if ($from === QUOTE_NEW) {
            if (empty($assignedTo) && empty($q['assignedTo'])) {
                $this->db->trans_complete();
                return ['ok' => false, 'error' => 'An assignee is required to leave the NEW state.'];
            }
        }

        $update = [
            'status'          => $toStatus,
            'version'         => (int) $q['version'] + 1,
            'statusUpdatedAt' => date('Y-m-d H:i:s'),
        ];
        if (!empty($assignedTo)) $update['assignedTo'] = $assignedTo;
        if (!empty($notes))      $update['notes']      = $notes;

        $this->db->where('id', $quoteId);
        $this->db->where('version', $q['version']);
        $this->db->update('quotes', $update);
        if ($this->db->affected_rows() === 0) {
            $this->db->trans_complete();
            return ['ok' => false, 'error' => 'Concurrent update detected.', 'conflict' => true, 'quote' => $q];
        }

        $this->db->insert('quote_status_history', [
            'id'         => MY_Model::uuid(),
            'quoteId'    => $quoteId,
            'fromStatus' => $from,
            'toStatus'   => $toStatus,
            'changedBy'  => $actorId,
            'notes'      => $notes,
            'createdAt'  => date('Y-m-d H:i:s'),
        ]);

        $this->_log_activity($quoteId, $actorId, QA_STATUS, "Status changed: {$from} -> {$toStatus}", ['from' => $from, 'to' => $toStatus]);
        if (!empty($assignedTo) && $assignedTo !== $q['assignedTo']) {
            $this->_log_activity($quoteId, $actorId, QA_ASSIGNED, "Assigned to: {$assignedTo}", ['assignedTo' => $assignedTo]);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return ['ok' => false, 'error' => 'Database error.'];
        }
        $q2 = $this->db->get_where('quotes', ['id' => $quoteId])->row_array();
        return ['ok' => true, 'quote' => $q2, 'from' => $from, 'to' => $toStatus];
    }

    public function assign($quoteId, $assignedTo, $actorId, $expectedVersion = null)
    {
        $q = $this->db->get_where('quotes', ['id' => $quoteId])->row_array();
        if (!$q) return ['ok' => false, 'error' => 'Quote not found.'];
        if ($expectedVersion !== null && (int) $q['version'] !== (int) $expectedVersion) {
            return ['ok' => false, 'error' => 'Concurrent update detected.', 'conflict' => true];
        }
        $this->db->where('id', $quoteId)->update('quotes', [
            'assignedTo'      => $assignedTo,
            'version'         => (int) $q['version'] + 1,
            'statusUpdatedAt' => date('Y-m-d H:i:s'),
        ]);
        $this->_log_activity($quoteId, $actorId, QA_ASSIGNED, "Assigned to: {$assignedTo}", ['assignedTo' => $assignedTo]);
        return ['ok' => true];
    }

    public function add_internal_note($quoteId, $note, $actorId, $expectedVersion = null)
    {
        $q = $this->db->get_where('quotes', ['id' => $quoteId])->row_array();
        if (!$q) return ['ok' => false, 'error' => 'Quote not found.'];
        if ($expectedVersion !== null && (int) $q['version'] !== (int) $expectedVersion) {
            return ['ok' => false, 'error' => 'Concurrent update detected.', 'conflict' => true];
        }
        $merged = trim(($q['internalNotes'] ?? '') . "\n" . '[' . date('Y-m-d H:i') . '] ' . $note);
        $this->db->where('id', $quoteId)->update('quotes', [
            'internalNotes'   => $merged,
            'version'         => (int) $q['version'] + 1,
        ]);
        $this->_log_activity($quoteId, $actorId, QA_NOTE, $note, null);
        return ['ok' => true];
    }

    public function list_with_filters(array $where = [], $perPage = 25, $page = 1, $search = null, $searchFields = [], $orderBy = ['createdAt' => 'DESC'])
    {
        return $this->paginate($where, $perPage, $page, $orderBy, $search, $searchFields);
    }

    /**
     * Paginate quotes with explicit WHERE conditions plus an optional callback
     * that applies extra query-builder constraints (used for the cross-table
     * RFQ search that spans quotes + quote_items).
     */
    public function list_rfqs(array $where = [], $perPage = 25, $page = 1, callable $scope = null, $orderBy = ['createdAt' => 'DESC'])
    {
        $page = max(1, (int) $page);

        $build = function () use ($where, $scope) {
            if (!empty($where)) $this->db->where($where);
            if ($scope) $scope();
        };

        $build();
        $total = $this->db->count_all_results($this->table);

        $build();
        foreach ($orderBy ?: $this->order_by as $col => $dir) {
            $this->db->order_by($this->db->protect_identifiers($col), $dir);
        }
        $this->db->limit($perPage, ($page - 1) * $perPage);
        $rows = $this->db->get($this->table)->result_array();

        // Attach item counts + part numbers for the list view.
        foreach ($rows as &$r) {
            $items = $this->db->select('partNumber, productName, quantity')
                ->get_where('quote_items', ['quoteId' => $r['id']])->result_array();
            $r['item_count'] = count($items);
            $r['part_numbers'] = implode(', ', array_filter(array_column($items, 'partNumber')));
        }
        unset($r);

        return [
            'rows'        => $rows,
            'total'       => (int) $total,
            'per_page'    => $perPage,
            'page'        => $page,
            'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    public function get_items($quoteId)
    {
        return $this->db->get_where('quote_items', ['quoteId' => $quoteId])->result_array();
    }

    public function get_status_history($quoteId)
    {
        return $this->db->order_by('createdAt', 'ASC')->get_where('quote_status_history', ['quoteId' => $quoteId])->result_array();
    }

    public function get_activities($quoteId)
    {
        return $this->db->order_by('createdAt', 'ASC')->get_where('quote_activities', ['quoteId' => $quoteId])->result_array();
    }

    public function get_attachments($quoteId)
    {
        return $this->db->order_by('createdAt', 'ASC')->get_where('quote_attachments', ['quoteId' => $quoteId])->result_array();
    }

    public function set_pdf_url($quoteId, $url, $actorId)
    {
        $this->db->where('id', $quoteId)->update('quotes', ['pdfUrl' => $url]);
        $this->_log_activity($quoteId, $actorId, QA_PDF, "PDF generated: {$url}", null);
    }

    /** Recompute totalAmount from line items (unit price * qty). */
    public function recalc_total($quoteId)
    {
        $total = 0.0;
        foreach ($this->get_items($quoteId) as $it) {
            if ($it['total'] !== null && $it['total'] !== '') {
                $total += (float) $it['total'];
            } elseif ($it['unitPrice'] !== null && $it['unitPrice'] !== '') {
                $total += (float) $it['unitPrice'] * (int) $it['quantity'];
            }
        }
        $this->db->where('id', $quoteId)->update('quotes', ['totalAmount' => round($total, 2)]);
        return round($total, 2);
    }

    /** Add a requested part / line item (admin pricing). */
    public function add_item($quoteId, array $data, $actorId)
    {
        $row = array_intersect_key($data, array_flip($this->item_fillable));
        if (empty($row['productName'])) {
            return ['ok' => false, 'error' => 'Part name is required.'];
        }
        $row += [
            'id'       => MY_Model::uuid(),
            'quoteId'  => $quoteId,
            'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
        ];
        $q = $this->find($quoteId);
        $row['currency'] = $row['currency'] ?? ($q['currency'] ?? 'USD');
        $row['total'] = isset($row['unitPrice']) && $row['unitPrice'] !== ''
            ? round((float) $row['unitPrice'] * (int) $row['quantity'], 2)
            : ($row['total'] ?? null);
        $this->db->insert('quote_items', $row);
        $this->recalc_total($quoteId);
        $this->bump_version($quoteId);
        $this->_log_activity($quoteId, $actorId, QA_UPDATED,
            'Line item added: ' . $row['productName'] . ($row['partNumber'] ? ' (' . $row['partNumber'] . ')' : ''), null);
        return ['ok' => true, 'id' => $row['id']];
    }

    /** Update pricing/details of a line item. */
    public function update_item($quoteId, $itemId, array $data, $actorId)
    {
        $item = $this->db->get_where('quote_items', ['id' => $itemId, 'quoteId' => $quoteId])->row_array();
        if (!$item) return ['ok' => false, 'error' => 'Line item not found.'];
        $row = array_intersect_key($data, array_flip($this->item_fillable));
        if (isset($row['quantity'])) $row['quantity'] = max(1, (int) $row['quantity']);
        if (array_key_exists('unitPrice', $row) && $row['unitPrice'] !== '' && $row['unitPrice'] !== null) {
            $row['total'] = round((float) $row['unitPrice'] * (int) ($row['quantity'] ?? $item['quantity']), 2);
        }
        $this->db->where('id', $itemId)->update('quote_items', $row);
        $this->recalc_total($quoteId);
        $this->bump_version($quoteId);
        $this->_log_activity($quoteId, $actorId, QA_UPDATED, 'Line item updated: ' . ($item['partNumber'] ?: $item['productName']), null);
        return ['ok' => true];
    }

    public function delete_item($quoteId, $itemId, $actorId)
    {
        $item = $this->db->get_where('quote_items', ['id' => $itemId, 'quoteId' => $quoteId])->row_array();
        if (!$item) return ['ok' => false, 'error' => 'Line item not found.'];
        $this->db->delete('quote_items', ['id' => $itemId]);
        $this->recalc_total($quoteId);
        $this->bump_version($quoteId);
        $this->_log_activity($quoteId, $actorId, QA_UPDATED, 'Line item removed: ' . ($item['partNumber'] ?: $item['productName']), null);
        return ['ok' => true];
    }

    /** Update quote header details (validity, currency, deadline, totals). */
    public function update_details($quoteId, array $data, $actorId)
    {
        $allowed = array_intersect_key($data, array_flip(['deadline','validUntil','currency','totalAmount','internalNotes','industry']));
        if (!empty($allowed)) {
            foreach (['deadline','validUntil'] as $d) {
                if (array_key_exists($d, $allowed) && $allowed[$d] === '') $allowed[$d] = null;
            }
            $this->db->where('id', $quoteId)->update('quotes', $allowed);
            $this->bump_version($quoteId);
            $this->_log_activity($quoteId, $actorId, QA_UPDATED, 'Quote details updated: ' . implode(', ', array_keys($allowed)), null);
        }
        return ['ok' => true];
    }

    /** Record that the quotation was emailed to the customer. */
    public function log_email_sent($quoteId, $actorId, $to, $status)
    {
        $this->db->where('id', $quoteId)->update('quotes', ['lastNotifiedAt' => date('Y-m-d H:i:s')]);
        $this->_log_activity($quoteId, $actorId, QA_EMAIL_SENT,
            'Quotation emailed to ' . $to . ' (' . $status . ')', ['to' => $to, 'status' => $status]);
    }

    private function bump_version($quoteId)
    {
        $this->db->set('version', 'version+1', false)->where('id', $quoteId)->update('quotes');
    }

    private function _log_activity($quoteId, $actorId, $action, $description, $metadata)
    {
        $this->db->insert('quote_activities', [
            'id'          => MY_Model::uuid(),
            'quoteId'     => $quoteId,
            'actorId'     => $actorId,
            'action'      => $action,
            'description' => $description,
            'metadata'    => $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'ipAddress'   => vp_get_client_ip(),
            'userAgent'   => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'createdAt'   => date('Y-m-d H:i:s'),
        ]);
    }
}
