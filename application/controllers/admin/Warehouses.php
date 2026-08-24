<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Warehouse master data for the lot-level inventory module. */
class Warehouses extends Admin_Controller
{
    protected $required_permission = 'inventory.manage';

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Warehouse_model', 'Inventory_lot_model']);
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = 'Warehouses';
        $this->render('admin/warehouses/index', [
            'rows' => $this->Warehouse_model->find_all([], ['sortOrder' => 'ASC', 'name' => 'ASC'], 250),
            'totals' => $this->Inventory_lot_model->warehouse_totals(),
        ]);
    }

    public function create()
    {
        $this->page_title = 'New warehouse';
        $this->_render_form(null);
    }

    public function edit($id = null)
    {
        $row = $id ? $this->Warehouse_model->find($id) : null;
        if (!$row) show_404();
        $this->page_title = 'Edit: ' . $row['name'];
        $this->_render_form($row);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();
        $id = $this->input->post('id');
        if ($id && !$this->Warehouse_model->find($id)) show_404();
        $this->form_validation->set_rules('name', 'Warehouse name', 'required|max_length[190]');
        $this->form_validation->set_rules('code', 'Warehouse code', 'required|max_length[30]');
        if (!$this->form_validation->run()) {
            $this->flash('error', 'Enter a warehouse name and short code.');
            return redirect($id ? 'admin/warehouses/edit/' . $id : 'admin/warehouses/create');
        }
        $code = strtoupper(preg_replace('/[^A-Z0-9_-]/', '', trim((string) $this->input->post('code'))));
        if ($code === '') {
            $this->flash('error', 'Warehouse code may contain only letters, numbers, hyphens and underscores.');
            return redirect($id ? 'admin/warehouses/edit/' . $id : 'admin/warehouses/create');
        }
        $this->db->where('code', $code);
        if ($id) $this->db->where('id !=', $id);
        if ($this->db->count_all_results('warehouses') > 0) {
            $this->flash('error', 'Another warehouse already uses that code.');
            return redirect($id ? 'admin/warehouses/edit/' . $id : 'admin/warehouses/create');
        }
        $data = [
            'name' => trim((string) $this->input->post('name')),
            'code' => $code,
            'address' => trim((string) $this->input->post('address')) ?: null,
            'city' => trim((string) $this->input->post('city')) ?: null,
            'region' => trim((string) $this->input->post('region')) ?: null,
            'country' => trim((string) $this->input->post('country')) ?: null,
            'timezone' => trim((string) $this->input->post('timezone')) ?: 'UTC',
            'phone' => trim((string) $this->input->post('phone')) ?: null,
            'isAogHub' => $this->input->post('isAogHub') ? 1 : 0,
            'isActive' => $this->input->post('isActive') ? 1 : 0,
            'sortOrder' => max(0, (int) $this->input->post('sortOrder')),
            'notes' => trim((string) $this->input->post('notes')) ?: null,
        ];
        if ($id) {
            $this->Warehouse_model->update($id, $data);
            $this->audit->log(AUDIT_UPDATE, 'warehouse', $id, ['name' => $data['name'], 'code' => $code]);
            $this->flash('success', 'Warehouse updated.');
        } else {
            $id = $this->Warehouse_model->insert($data);
            $this->audit->log(AUDIT_CREATE, 'warehouse', $id, ['name' => $data['name'], 'code' => $code]);
            $this->flash('success', 'Warehouse created.');
        }
        redirect('admin/warehouses');
    }

    public function delete($id = null)
    {
        if (!$id) show_404();
        $row = $this->Warehouse_model->find($id);
        if (!$row) show_404();
        if ($this->db->where('warehouseId', $id)->count_all_results('inventory_lots') > 0) {
            $this->flash('error', 'A warehouse with inventory lots cannot be deleted. Mark it inactive instead.');
            return redirect('admin/warehouses');
        }
        $this->Warehouse_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'warehouse', $id, ['name' => $row['name']]);
        $this->flash('success', 'Warehouse deleted.');
        redirect('admin/warehouses');
    }

    private function _render_form($row)
    {
        $this->render('admin/warehouses/form', ['row' => $row]);
    }
}
