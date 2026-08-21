<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Contacts extends Admin_Controller
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'contacts.manage';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Contact_model');
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url']);
    }

    public function index()
    {
        $this->page_title = 'Contacts';
        $search = $this->input->get('q');
        $status = $this->input->get('status');
        $page = max(1, (int) $this->input->get('page'));
        $per = 25;
        $where = [];
        if ($status) $where['status'] = $status;
        $result = $this->Contact_model->paginate($where, $per, $page, ['createdAt' => 'DESC'], $search, ['name','email','subject','message']);
        $this->render('admin/contacts/index', [
            'rows' => $result['rows'],
            'total' => $result['total'],
            'total_pages' => $result['total_pages'],
            'page' => $result['page'],
            'search' => $search,
            'status' => $status,
            'base_url' => base_url('admin/contacts') . '?' . http_build_query(array_filter(['q' => $search, 'status' => $status])) . '&page={page}',
        ]);
    }

    public function view($id = null)
    {
        if (!$id) show_404();
        $row = $this->Contact_model->find($id);
        if (!$row) show_404();
        // Mark as read
        if ($row['status'] === 'NEW') {
            $this->Contact_model->update($id, ['status' => 'READ']);
        }
        $this->page_title = 'Contact from ' . $row['name'];
        $this->render('admin/contacts/view', ['row' => $row]);
    }

    public function delete($id = null)
    {
        if (!$id) show_404();
        $row = $this->Contact_model->find($id);
        if (!$row) show_404();
        $this->Contact_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'contact', $id, ['subject' => $row['subject']]);
        $this->flash('success', 'Deleted.');
        redirect('admin/contacts');
    }
}
