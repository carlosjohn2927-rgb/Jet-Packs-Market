<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — Administrator management (SUPER ADMIN ONLY).
 *
 * Create, edit, enable/disable, delete and reset administrator accounts, and
 * decide exactly which dashboard sections each of them may use.
 *
 * Hard rules enforced here, server-side, on every request:
 *   • Only a SUPER_ADMIN can open any action in this controller.
 *   • A SUPER_ADMIN account can never be edited, disabled, deleted, demoted
 *     or have its permissions changed by anybody but itself (and even then
 *     the role cannot be downgraded while it is the last Super Admin).
 *   • Super-only permissions (admins.manage, system.manage) can never be
 *     granted to a normal ADMIN.
 */
class Admins extends Admin_Controller
{
    protected $super_admin_only = true;
    protected $required_permission = 'admins.manage';

    /** Roles that may be assigned from this screen. */
    private $assignable_roles = [
        ROLE_ADMIN    => 'Admin',
        ROLE_SALES    => 'Sales',
        ROLE_ENGINEER => 'Engineer',
        ROLE_EDITOR   => 'Editor',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['User_model', 'User_permission_model']);
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url', 'security_helper']);
        $this->acl->sync_catalog();
    }

    /* ------------------------------------------------------------------ */

    public function index()
    {
        $this->page_title = 'Administrators';
        $search = trim((string) $this->input->get('q'));

        $this->db->where_in('role', [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_SALES, ROLE_ENGINEER, ROLE_EDITOR]);
        if ($search !== '') {
            $this->db->group_start()
                     ->like('email', $search)
                     ->or_like('firstName', $search)
                     ->or_like('lastName', $search)
                     ->group_end();
        }
        $rows = $this->db->order_by('role', 'ASC')->order_by('createdAt', 'ASC')->get('users')->result_array();

        // Effective permission count per admin, for the list view.
        foreach ($rows as &$r) {
            $r['permission_count'] = count($this->acl->effective($r));
            $r['is_super']         = ($r['role'] === ROLE_SUPER_ADMIN);
        }
        unset($r);

        $this->render('admin/admins/index', [
            'rows'         => $rows,
            'search'       => $search,
            'total_perms'  => count($this->acl->grantable_keys()),
        ]);
    }

    public function create()
    {
        $this->page_title = 'New administrator';
        $this->render('admin/admins/form', [
            'row'         => null,
            'roles'       => $this->assignable_roles,
            'groups'      => $this->acl->grouped_catalog(),
            'granted'     => $this->acl->role_defaults(ROLE_ADMIN),
            'descriptions'=> $this->acl->group_descriptions(),
        ]);
    }

    public function edit($id = null)
    {
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->_assert_manageable($row, 'edit');

        $this->page_title = 'Edit ' . trim($row['firstName'] . ' ' . $row['lastName']);
        $this->render('admin/admins/form', [
            'row'         => $row,
            'roles'       => $this->assignable_roles,
            'groups'      => $this->acl->grouped_catalog(),
            'granted'     => $this->acl->effective($row),
            'overrides'   => $this->acl->user_overrides($row['id']),
            'descriptions'=> $this->acl->group_descriptions(),
            'activity'    => $this->_activity($row['id'], 10),
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();

        $id       = $this->input->post('id');
        $email    = strtolower(trim((string) $this->input->post('email')));
        $role     = (string) $this->input->post('role');
        $password = (string) $this->input->post('password');
        $existing = $id ? $this->User_model->find($id) : null;

        if ($id && !$existing) show_404();
        if ($existing) $this->_assert_manageable($existing, 'edit');

        // A Super Admin can never be created from this form, and no account
        // may be promoted into the Super Admin role through it.
        if (!array_key_exists($role, $this->assignable_roles)) {
            $this->flash('error', 'Invalid role selected.');
            return redirect($id ? 'admin/admins/edit/' . $id : 'admin/admins/create');
        }

        $this->form_validation->set_data($this->input->post());
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[190]');
        $this->form_validation->set_rules('firstName', 'First name', 'required|max_length[100]');
        $this->form_validation->set_rules('lastName', 'Last name', 'required|max_length[100]');
        if (!$existing) {
            $this->form_validation->set_rules('password', 'Password', 'required|min_length[10]');
        } elseif ($password !== '') {
            $this->form_validation->set_rules('password', 'Password', 'min_length[10]');
        }

        if (!$this->form_validation->run()) {
            $this->flash('error', strip_tags(validation_errors()) ?: 'Please fix the highlighted errors.');
            return redirect($id ? 'admin/admins/edit/' . $id : 'admin/admins/create');
        }

        $dupe = $this->db->where('email', $email);
        if ($id) $dupe->where('id !=', $id);
        if ($dupe->count_all_results('users') > 0) {
            $this->flash('error', 'Another account already uses that email address.');
            return redirect($id ? 'admin/admins/edit/' . $id : 'admin/admins/create');
        }

        $now  = date('Y-m-d H:i:s');
        $data = [
            'email'     => $email,
            'firstName' => trim((string) $this->input->post('firstName')),
            'lastName'  => trim((string) $this->input->post('lastName')),
            'phone'     => trim((string) $this->input->post('phone')) ?: null,
            'company'   => trim((string) $this->input->post('company')) ?: null,
            'role'      => $role,
            'isActive'  => $this->input->post('isActive') ? 1 : 0,
            'updatedAt' => $now,
        ];

        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
            $data['mustChangePassword'] = $this->input->post('mustChangePassword') ? 1 : 0;
        }

        if ($existing) {
            $this->db->update('users', $data, ['id' => $id]);
            $this->audit->log(AUDIT_UPDATE, 'admin_user', $id, [
                'email' => $email, 'role' => $role, 'active' => $data['isActive'],
            ]);
            $this->flash('success', 'Administrator updated.');
        } else {
            $id = MY_Model::uuid();
            $data['id'] = $id;
            $data['createdAt'] = $now;
            $data['emailVerified'] = 1;
            if (!isset($data['password'])) $data['password'] = password_hash(bin2hex(random_bytes(12)), PASSWORD_BCRYPT);
            $this->db->insert('users', $data);
            $this->audit->log(AUDIT_CREATE, 'admin_user', $id, ['email' => $email, 'role' => $role]);
            $this->flash('success', 'Administrator created.');
        }

        // Permissions come from the same form.
        $keys = (array) $this->input->post('permissions');
        $this->acl->set_user_permissions($id, $keys, $this->vp_auth->id());
        $this->acl->clear_cache();
        $this->audit->log('PERMISSION_CHANGE', 'admin_user', $id, ['permissions' => array_values($keys)]);

        $this->_notify_admin($id, 'Your dashboard access was updated', 'A Super Admin updated your account permissions.');

        redirect('admin/admins/edit/' . $id);
    }

    /* ------------------------------------------------------------------ */
    /* Permissions                                                         */
    /* ------------------------------------------------------------------ */

    public function permissions($id = null)
    {
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->_assert_manageable($row, 'permissions');

        $this->page_title = 'Permissions — ' . trim($row['firstName'] . ' ' . $row['lastName']);
        $this->render('admin/admins/permissions', [
            'row'          => $row,
            'groups'       => $this->acl->grouped_catalog(),
            'granted'      => $this->acl->effective($row),
            'overrides'    => $this->acl->user_overrides($row['id']),
            'role_defaults'=> $this->acl->role_defaults($row['role']),
            'descriptions' => $this->acl->group_descriptions(),
        ]);
    }

    public function permissions_save($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->_assert_manageable($row, 'permissions');

        $keys = (array) $this->input->post('permissions');
        $this->acl->set_user_permissions($row['id'], $keys, $this->vp_auth->id());
        $this->acl->clear_cache();
        $this->audit->log('PERMISSION_CHANGE', 'admin_user', $row['id'], [
            'email' => $row['email'],
            'permissions' => array_values(array_intersect($this->acl->grantable_keys(), $keys)),
        ]);
        $this->_notify_admin($row['id'], 'Your permissions changed', 'A Super Admin updated the sections you can access.');
        $this->flash('success', 'Permissions saved.');
        redirect('admin/admins/permissions/' . $row['id']);
    }

    public function permissions_reset($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->_assert_manageable($row, 'permissions');

        $this->acl->reset_user_permissions($row['id']);
        $this->acl->clear_cache();
        $this->audit->log('PERMISSION_CHANGE', 'admin_user', $row['id'], ['reset' => true]);
        $this->flash('success', 'Permissions reset to the role defaults.');
        redirect('admin/admins/permissions/' . $row['id']);
    }

    /* ------------------------------------------------------------------ */
    /* Account state                                                       */
    /* ------------------------------------------------------------------ */

    public function toggle($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->_assert_manageable($row, 'disable');

        $new = empty($row['isActive']) ? 1 : 0;
        $this->db->update('users', ['isActive' => $new, 'updatedAt' => date('Y-m-d H:i:s')], ['id' => $row['id']]);
        $this->audit->log(AUDIT_UPDATE, 'admin_user', $row['id'], [
            'email' => $row['email'], 'action' => $new ? 'enabled' : 'disabled',
        ]);
        $this->flash('success', $new ? 'Administrator enabled.' : 'Administrator disabled — they can no longer sign in.');
        redirect('admin/admins');
    }

    public function reset_password($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->_assert_manageable($row, 'reset password');

        $new = $this->input->post('new_password');
        $generated = false;
        if (!$new) {
            $new = $this->_generate_password();
            $generated = true;
        }
        if (strlen((string) $new) < 10) {
            $this->flash('error', 'The new password must be at least 10 characters.');
            return redirect('admin/admins/edit/' . $row['id']);
        }

        $this->db->update('users', [
            'password'           => password_hash($new, PASSWORD_BCRYPT),
            'mustChangePassword' => $this->input->post('force_change') ? 1 : 0,
            'updatedAt'          => date('Y-m-d H:i:s'),
        ], ['id' => $row['id']]);

        $this->audit->log('PASSWORD_RESET', 'admin_user', $row['id'], ['email' => $row['email'], 'by' => 'super_admin']);
        $this->_notify_admin($row['id'], 'Your password was reset', 'A Super Admin reset your dashboard password.');

        if ($generated) {
            $this->flash('success', 'Password reset. Temporary password: ' . $new . ' — copy it now, it will not be shown again.');
        } else {
            $this->flash('success', 'Password updated.');
        }
        redirect('admin/admins/edit/' . $row['id']);
    }

    public function delete($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->_assert_manageable($row, 'delete');

        $this->db->delete('user_permissions', ['userId' => $row['id']]);
        $this->User_model->delete($row['id']);
        $this->audit->log(AUDIT_DELETE, 'admin_user', $row['id'], ['email' => $row['email'], 'role' => $row['role']]);
        $this->flash('success', 'Administrator deleted.');
        redirect('admin/admins');
    }

    /* ------------------------------------------------------------------ */
    /* Activity                                                            */
    /* ------------------------------------------------------------------ */

    public function activity($id = null)
    {
        $row = $id ? $this->User_model->find($id) : null;
        if (!$row) show_404();
        $this->page_title = 'Activity — ' . trim($row['firstName'] . ' ' . $row['lastName']);
        $page = max(1, (int) $this->input->get('page'));
        $per  = 50;
        $total = (int) $this->db->where('userId', $row['id'])->count_all_results('audit_logs');
        $this->render('admin/admins/activity', [
            'row'   => $row,
            'rows'  => $this->_activity($row['id'], $per, ($page - 1) * $per),
            'page'  => $page,
            'total' => $total,
            'total_pages' => max(1, (int) ceil($total / $per)),
            'base_url' => base_url('admin/admins/activity/' . $row['id']) . '?page={page}',
        ]);
    }

    private function _activity($user_id, $limit = 20, $offset = 0)
    {
        return $this->db->where('userId', $user_id)
                        ->order_by('createdAt', 'DESC')
                        ->limit($limit, $offset)
                        ->get('audit_logs')->result_array();
    }

    /* ------------------------------------------------------------------ */
    /* Guards                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * A Super Admin account is protected from every mutation except by itself.
     */
    private function _assert_manageable(array $row, $what = 'modify')
    {
        if ($row['role'] === ROLE_SUPER_ADMIN && $row['id'] !== $this->vp_auth->id()) {
            $this->audit->log('ACCESS_DENIED', 'admin_user', $row['id'], ['attempt' => $what]);
            $this->_deny('The Super Admin account is protected and cannot be modified from here.');
        }
        if ($what === 'delete' && $row['id'] === $this->vp_auth->id()) {
            $this->flash('error', 'You cannot delete your own account.');
            redirect('admin/admins');
        }
        if ($what === 'disable' && $row['id'] === $this->vp_auth->id()) {
            $this->flash('error', 'You cannot disable your own account.');
            redirect('admin/admins');
        }
        return true;
    }

    private function _generate_password()
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        $out = '';
        for ($i = 0; $i < 14; $i++) $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        return $out;
    }

    private function _notify_admin($user_id, $title, $message)
    {
        $this->notify('admin_account', $title, $message, [], $user_id);
    }
}
