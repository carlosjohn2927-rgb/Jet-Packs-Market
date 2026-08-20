<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — the signed-in administrator's own profile.
 *
 * Available to every staff account (no extra permission needed) and is also
 * the page a temporary password must be changed on.
 */
class Profile extends Admin_Controller
{
    protected $required_permission = null;   // any authenticated staff account

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['User_model', 'Media_model']);
        $this->load->library(['form_validation', 'vp_upload']);
        $this->load->helper(['form', 'url', 'security_helper', 'cms_schema_helper']);
        vp_ensure_user_avatar_column();
    }

    public function index()
    {
        $this->page_title = 'My profile';
        $user = $this->vp_auth->user();
        $this->render('admin/profile/index', [
            'row'         => $user,
            'permissions' => $this->acl->effective($user),
            'catalog'     => $this->acl->catalog(),
            'activity'    => $this->db->where('userId', $user['id'])->order_by('createdAt', 'DESC')->limit(10)->get('audit_logs')->result_array(),
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();
        $user = $this->vp_auth->user();

        $email = strtolower(trim((string) $this->input->post('email')));
        $this->form_validation->set_data($this->input->post());
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('firstName', 'First name', 'required|max_length[100]');
        $this->form_validation->set_rules('lastName', 'Last name', 'required|max_length[100]');
        if (!$this->form_validation->run()) {
            $this->flash('error', strip_tags(validation_errors()) ?: 'Please check the form.');
            return redirect('admin/profile');
        }

        if ($this->db->where('email', $email)->where('id !=', $user['id'])->count_all_results('users') > 0) {
            $this->flash('error', 'That email address is already in use.');
            return redirect('admin/profile');
        }

        $updates = [
            'email'     => $email,
            'firstName' => trim((string) $this->input->post('firstName')),
            'lastName'  => trim((string) $this->input->post('lastName')),
            'phone'     => trim((string) $this->input->post('phone')) ?: null,
            'updatedAt' => date('Y-m-d H:i:s'),
        ];

        // Optional profile picture upload. Role and isActive are deliberately
        // NOT editable here — an administrator can never promote themselves.
        $upload = $this->vp_upload->handle('avatar', 'avatars', 'jpg|jpeg|png|webp|gif', 2048);
        if (is_array($upload) && !empty($upload['error'])) {
            $this->flash('error', $upload['error']);
            return redirect('admin/profile');
        }
        if (is_array($upload) && !empty($upload['url'])) {
            $updates['avatar'] = $upload['url'];
            if ($this->db->table_exists('media')) {
                $this->Media_model->insert([
                    'filename'     => $upload['filename'],
                    'originalName' => $upload['name'],
                    'url'          => $upload['url'],
                    'mimeType'     => $upload['mime'],
                    'size'         => $upload['size'],
                    'folder'       => 'avatars',
                    'alt'          => trim($updates['firstName'] . ' ' . $updates['lastName']) . ' profile picture',
                ]);
            }
        }
        if ($this->input->post('remove_avatar')) {
            $updates['avatar'] = null;
        }

        $this->db->update('users', $updates, ['id' => $user['id']]);

        $this->audit->log(AUDIT_UPDATE, 'profile', $user['id'], ['email' => $email, 'avatar' => array_key_exists('avatar', $updates)]);
        $this->flash('success', 'Profile updated.');
        redirect('admin/profile');
    }

    public function password()
    {
        if ($this->input->method() !== 'post') show_404();
        $user = $this->vp_auth->user();

        $current = (string) $this->input->post('current_password');
        $new     = (string) $this->input->post('new_password');
        $confirm = (string) $this->input->post('confirm_password');

        if (!password_verify($current, $user['password'])) {
            $this->audit->log('PASSWORD_CHANGE_FAILED', 'profile', $user['id']);
            $this->flash('error', 'Your current password is not correct.');
            return redirect('admin/profile');
        }
        if (strlen($new) < 10) {
            $this->flash('error', 'The new password must be at least 10 characters.');
            return redirect('admin/profile');
        }
        if ($new !== $confirm) {
            $this->flash('error', 'The new passwords do not match.');
            return redirect('admin/profile');
        }

        $this->db->update('users', [
            'password'           => password_hash($new, PASSWORD_BCRYPT),
            'mustChangePassword' => 0,
            'updatedAt'          => date('Y-m-d H:i:s'),
        ], ['id' => $user['id']]);

        $this->audit->log('PASSWORD_CHANGE', 'profile', $user['id']);
        $this->flash('success', 'Password changed.');
        redirect('admin/profile');
    }
}
