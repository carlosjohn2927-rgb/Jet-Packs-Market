<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Cross-warehouse inventory board; lot mutation happens from product editing. */
class Inventory extends Admin_Controller
{
    protected $required_permission = 'inventory.manage';

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Inventory_lot_model', 'Warehouse_model']);
        $this->load->helper(['form', 'url', 'inventory_helper']);
    }

    public function index()
    {
        $this->page_title = 'Inventory';
        $filters = [
            'warehouseId' => trim((string) $this->input->get('warehouse')),
            'status' => strtoupper(trim((string) $this->input->get('status'))),
            'q' => trim((string) $this->input->get('q')),
        ];
        if (!in_array($filters['status'], ['', 'ACTIVE', 'QUARANTINE', 'EXPIRED', 'DEPLETED'], true)) $filters['status'] = '';
        $this->render('admin/inventory/index', [
            'lots' => $this->Inventory_lot_model->all_with_details($filters),
            'warehouses' => $this->Warehouse_model->active(),
            'totals' => $this->Inventory_lot_model->warehouse_totals(),
            'filters' => $filters,
        ]);
    }

    public function transfer($lotId = null)
    {
        if (!$lotId || $this->input->method() !== 'post') show_404();
        $result = $this->Inventory_lot_model->transfer(
            $lotId,
            $this->input->post('targetWarehouseId'),
            $this->input->post('quantity'),
            trim((string) $this->input->post('note')),
            $this->jet_auth->id()
        );
        if (!empty($result['ok'])) {
            $this->audit->log(AUDIT_UPDATE, 'inventory_lot', $lotId, ['action' => 'transfer', 'targetLotId' => $result['targetLotId'] ?? null]);
            $this->flash('success', 'Stock transferred and the product total was refreshed.');
        } else {
            $this->flash('error', $result['error'] ?? 'Could not transfer stock.');
        }
        redirect('admin/inventory');
    }
}
