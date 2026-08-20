<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - Quote (RFQ) model with hardened state machine.
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
        'address','industry','status','deadline','totalAmount','notes','internalNotes',
        'pdfUrl','assignedTo','statusUpdatedAt','lastNotifiedAt','version',
    ];
    protected $order_by = ['createdAt' => 'DESC'];

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
            $it['quoteId'] = $id;
            $this->db->insert('quote_items', $it + ['id' => MY_Model::uuid()]);
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
