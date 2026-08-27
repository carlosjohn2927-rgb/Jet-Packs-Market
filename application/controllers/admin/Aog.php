<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — admin AOG dispatch management.
 *
 * Staff record emergency / priority part dispatches against a customer so the
 * customer can track them from /account/dispatches. Gated by the
 * 'customers.manage' permission (same section as the Customers list).
 */
class Aog extends Admin_Controller
{
    protected $required_permission = 'customers.manage';

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Aog_dispatch_model', 'User_model', 'Quote_model']);
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = 'AOG dispatches';
        $search = $this->input->get('q');
        $page   = max(1, (int) $this->input->get('page'));
        $result = $this->Aog_dispatch_model->admin_list($search ?: null, 25, $page);

        $this->render('admin/aog/index', [
            'rows'         => $result['rows'],
            'total'        => $result['total'],
            'total_pages'  => $result['total_pages'],
            'page'         => $result['page'],
            'search'       => $search,
            'base_url'     => base_url('admin/aog') . '?' . http_build_query(array_filter(['q' => $search])) . '&page={page}',
        ]);
    }

    public function create()
    {
        $this->page_title = 'New AOG dispatch';
        $this->render('admin/aog/form', [
            'row'      => null,
            'customers' => $this->_customers(),
            'quotes'   => [],
            'form_url' => base_url('admin/aog/save'),
        ]);
    }

    public function edit($id = null)
    {
        if (!$id) show_404();
        $row = $this->Aog_dispatch_model->find($id);
        if (!$row) show_404();
        $this->page_title = 'Edit ' . $row['reference'];

        $quotes = [];
        if (!empty($row['userId'])) {
            $quotes = $this->Quote_model->list_with_filters(['userId' => $row['userId']], 100, 1)['rows'] ?? [];
        }
        $this->render('admin/aog/form', [
            'row'       => $row,
            'customers' => $this->_customers(),
            'quotes'    => $quotes,
            'form_url'  => base_url('admin/aog/save'),
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();
        $id = $this->input->post('id');

        $this->form_validation->set_rules('userId', 'Customer', 'required');
        $this->form_validation->set_rules('aircraft', 'Aircraft', 'max_length[120]');
        $this->form_validation->set_rules('partDescription', 'Part description', 'required');
        $this->form_validation->set_rules('quantity', 'Quantity', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('priority', 'Priority', 'in_list[STANDARD,AOG]');
        $this->form_validation->set_rules('status', 'Status', 'in_list[REQUESTED,CONFIRMED,IN_TRANSIT,DELIVERED,CANCELLED]');

        if (!$this->form_validation->run()) {
            $this->flash('error', 'Please fix the highlighted errors.');
            return $id ? redirect('admin/aog/edit/' . $id) : redirect('admin/aog/create');
        }

        $data = [
            'userId'         => $this->input->post('userId'),
            'quoteId'        => $this->input->post('quoteId') ?: null,
            'aircraft'       => $this->input->post('aircraft') ?: null,
            'partDescription'=> $this->input->post('partDescription'),
            'quantity'       => max(1, (int) $this->input->post('quantity')),
            'priority'       => $this->input->post('priority') ?: 'AOG',
            'status'         => $this->input->post('status') ?: 'REQUESTED',
            'pickupLocation' => $this->input->post('pickupLocation') ?: null,
            'carrier'        => $this->input->post('carrier') ?: null,
            'trackingNumber' => $this->input->post('trackingNumber') ?: null,
            'eta'            => $this->_dt($this->input->post('eta')),
            'notes'          => $this->input->post('notes') ?: null,
        ];

        if ($data['status'] === 'DELIVERED') {
            $data['deliveredAt'] = $this->_dt($this->input->post('deliveredAt')) ?: date('Y-m-d H:i:s');
        } else {
            $data['deliveredAt'] = $this->_dt($this->input->post('deliveredAt'));
        }

        if ($id) {
            $this->Aog_dispatch_model->update($id, $data);
            $this->audit->log(AUDIT_UPDATE, 'aog_dispatch', $id, ['reference' => $data['reference'] ?? $id]);
            $this->flash('success', 'Dispatch updated.');
        } else {
            $data['reference']  = $this->Aog_dispatch_model->next_reference();
            $data['createdBy']  = $this->jet_auth->id();
            $id = $this->Aog_dispatch_model->insert($data);
            $this->audit->log(AUDIT_CREATE, 'aog_dispatch', $id, ['reference' => $data['reference']]);
            $this->flash('success', 'Dispatch ' . $data['reference'] . ' created.');
        }

        redirect('admin/aog/edit/' . $id);
    }

    public function view($id = null)
    {
        if (!$id) show_404();
        $row = $this->Aog_dispatch_model->find($id);
        if (!$row) show_404();
        $this->page_title = $row['reference'];

        $customer = !empty($row['userId']) ? $this->User_model->find($row['userId']) : null;
        $quote    = !empty($row['quoteId']) ? $this->Quote_model->find($row['quoteId']) : null;

        $this->render('admin/aog/view', [
            'row'      => $row,
            'customer' => $customer,
            'quote'    => $quote,
        ]);
    }

    /** Quick status update from the detail page. */
    public function status($id = null)
    {
        if ($this->input->method() !== 'post' || !$id) show_404();
        $row = $this->Aog_dispatch_model->find($id);
        if (!$row) show_404();

        $new = $this->input->post('status');
        if (!in_array($new, ['REQUESTED', 'CONFIRMED', 'IN_TRANSIT', 'DELIVERED', 'CANCELLED'], true)) {
            $this->flash('error', 'Invalid status.');
            redirect('admin/aog/view/' . $id);
        }
        $data = ['status' => $new];
        if ($new === 'DELIVERED' && empty($row['deliveredAt'])) {
            $data['deliveredAt'] = date('Y-m-d H:i:s');
        }
        $this->Aog_dispatch_model->update($id, $data);
        $this->audit->log(AUDIT_STATUS, 'aog_dispatch', $id, ['to' => $new]);
        $this->flash('success', 'Status set to ' . $new . '.');
        redirect('admin/aog/view/' . $id);
    }

    public function delete($id = null)
    {
        if (!$id) show_404();
        $row = $this->Aog_dispatch_model->find($id);
        if (!$row) show_404();
        $this->Aog_dispatch_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'aog_dispatch', $id, ['reference' => $row['reference']]);
        $this->flash('success', 'Dispatch deleted.');
        redirect('admin/aog');
    }

    /** Customer accounts (role CUSTOMER) for the select. */
    private function _customers()
    {
        return $this->db->where('role', ROLE_CUSTOMER)
            ->order_by('company', 'ASC')->order_by('lastName', 'ASC')
            ->get('users')->result_array();
    }

    /** Convert a datetime-local string to MySQL DATETIME (or null). */
    private function _dt($v)
    {
        if (empty($v)) return null;
        $t = strtotime((string) $v);
        return $t ? date('Y-m-d H:i:s', $t) : null;
    }
}
