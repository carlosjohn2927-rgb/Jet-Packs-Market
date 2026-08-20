<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — customer accounts.
 *
 * This screen manages CUSTOMER accounts only. Staff accounts (Super Admin,
 * Admin, Sales, Engineer, Editor) are managed exclusively by the Super Admin
 * in Dashboard → People → Administrators, which is how self-promotion is made
 * impossible: no role field here can ever produce a staff account.
 */
class Users extends Admin_Controller
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'customers.manage';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = 'Customers';
        $search = $this->input->get('q');
        $page   = max(1, (int) $this->input->get('page'));
        $per    = 25;

        $result = $this->User_model->paginate(['role' => ROLE_CUSTOMER], $per, $page, ['createdAt' => 'DESC'],
            $search, ['email', 'firstName', 'lastName', 'company']);

        $this->render('admin/users/index', [
            'rows'        => $result['rows'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
            'page'        => $result['page'],
            'search'      => $search,
            'role'        => ROLE_CUSTOMER,
            'base_url'    => base_url('admin/users') . '?' . http_build_query(array_filter(['q' => $search])) . '&page={page}',
        ]);
    }

    public function create()
    {
        $this->page_title = 'New customer';
        $this->render('admin/users/form', ['row' => null]);
    }

    public function edit($id = null)
    {
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->_assert_customer($row);
        $this->page_title = 'Edit: ' . trim($row['firstName'] . ' ' . $row['lastName']);
        $this->render('admin/users/form', ['row' => $row]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();
        $id  = $this->input->post('id');
        $row = $id ? $this->User_model->find($id) : null;
        if ($id && !$row) show_404();
        if ($row) $this->_assert_customer($row);

        $this->form_validation->set_data($this->input->post());
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('firstName', 'First name', 'required|max_length[100]');
        $this->form_validation->set_rules('lastName',  'Last name',  'required|max_length[100]');
        $pwd = (string) $this->input->post('password');
        if ($pwd !== '') $this->form_validation->set_rules('password', 'Password', 'min_length[8]');

        if ($this->form_validation->run() === false) {
            $this->flash('error', trim(validation_errors(' ', ' ')) ?: 'Please correct the errors.');
            return $id ? redirect('admin/users/edit/' . $id) : redirect('admin/users/create');
        }

        $email    = strtolower(trim((string) $this->input->post('email')));
        $existing = $this->User_model->find_by_email($email);
        if ($existing && (!$id || $existing['id'] !== $id)) {
            $this->flash('error', 'Email already in use.');
            return $id ? redirect('admin/users/edit/' . $id) : redirect('admin/users/create');
        }

        $data = [
            'email'     => $email,
            'firstName' => trim((string) $this->input->post('firstName')),
            'lastName'  => trim((string) $this->input->post('lastName')),
            'phone'     => trim((string) $this->input->post('phone')) ?: null,
            'company'   => trim((string) $this->input->post('company')) ?: null,
            // Hard-coded: this screen can only ever produce customer accounts.
            'role'      => ROLE_CUSTOMER,
            'isActive'  => $this->input->post('isActive') ? 1 : 0,
        ];
        if ($pwd !== '' && strlen($pwd) >= 8) {
            $data['password'] = password_hash($pwd, PASSWORD_BCRYPT);
        }

        if ($id) {
            $this->User_model->update($id, $data);
            $this->audit->log(AUDIT_UPDATE, 'customer', $id, ['email' => $email]);
            $this->flash('success', 'Customer updated.');
        } else {
            $data['password'] = $data['password'] ?? password_hash(bin2hex(random_bytes(9)), PASSWORD_BCRYPT);
            $id = $this->User_model->insert($data);
            $this->audit->log(AUDIT_CREATE, 'customer', $id, ['email' => $email]);
            $this->flash('success', 'Customer created.');
        }
        redirect('admin/users/edit/' . $id);
    }

    public function delete($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->_assert_customer($row);

        $this->User_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'customer', $id, ['email' => $row['email']]);
        $this->flash('success', 'Customer deleted.');
        redirect('admin/users');
    }

    /**
     * Staff accounts are out of bounds here — including the Super Admin.
     * This is what stops an Admin from editing/promoting anybody via /admin/users.
     */
    private function _assert_customer(array $row)
    {
        if (($row['role'] ?? ROLE_CUSTOMER) !== ROLE_CUSTOMER) {
            $this->audit->log('ACCESS_DENIED', 'user', $row['id'], ['reason' => 'staff account via customers screen']);
            $this->_deny('Staff accounts are managed by the Super Admin under Administrators.');
        }
        return true;
    }
}
