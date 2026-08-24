<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JetPacks Market — AOG (Aircraft On Ground) dispatch tracking.
 *
 * Staff create dispatches against a customer (and optionally a quote). The
 * customer tracks them from their account area (/account/dispatches).
 */
class Aog_dispatch_model extends MY_Model
{
    protected $table = 'aog_dispatches';
    protected $primary_key = 'id';
    protected $fillable = [
        'userId', 'quoteId', 'reference', 'aircraft', 'partDescription',
        'quantity', 'priority', 'status', 'pickupLocation', 'carrier',
        'trackingNumber', 'eta', 'deliveredAt', 'notes', 'createdBy',
    ];
    protected $order_by = ['createdAt' => 'DESC'];

    /** Dispatches belonging to one customer, newest first. */
    public function list_for_user($userId)
    {
        return $this->db->order_by('createdAt', 'DESC')
            ->get_where($this->table, ['userId' => $userId])
            ->result_array();
    }

    /**
     * Admin listing with customer name + optional search.
     *
     * @return array ['rows','total','total_pages','page']
     */
    public function admin_list($search = null, $perPage = 25, $page = 1)
    {
        $this->db->select('d.*, u.firstName, u.lastName, u.email AS customerEmail, u.company AS customerCompany, q.quoteNumber')
            ->from($this->table . ' d')
            ->join('users u', 'u.id = d.userId', 'left')
            ->join('quotes q', 'q.id = d.quoteId', 'left');

        if ($search) {
            $this->db->group_start()
                ->like('d.reference', $search)
                ->or_like('d.aircraft', $search)
                ->or_like('d.partDescription', $search)
                ->or_like('d.trackingNumber', $search)
                ->or_like('u.email', $search)
                ->or_like('u.company', $search)
                ->group_end();
        }

        $total = $this->db->count_all_results('', false);
        $rows = $this->db->order_by('d.createdAt', 'DESC')
            ->limit((int) $perPage, (int) (($page - 1) * $perPage))
            ->get()
            ->result_array();

        return [
            'rows'         => $rows,
            'total'        => $total,
            'total_pages'  => max(1, (int) ceil($total / $perPage)),
            'page'         => $page,
        ];
    }

    /** Most recent dispatches across all customers (admin dashboard widget). */
    public function recent($limit = 5)
    {
        return $this->db->select('d.*, u.firstName, u.lastName, u.email AS customerEmail')
            ->from($this->table . ' d')
            ->join('users u', 'u.id = d.userId', 'left')
            ->order_by('d.createdAt', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    /** Next sequential reference, e.g. AOG-2026-0001. */
    public function next_reference()
    {
        $year = date('Y');
        $count = (int) $this->db
            ->like('reference', 'AOG-' . $year . '-', 'after')
            ->count_all_results($this->table);
        return 'AOG-' . $year . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
