<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - Auth controller.
 * Handles public login, registration, logout, password reset, and admin login.
 */
class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    /* ---------- Public login ---------- */

    public function login()
    {
        if ($this->vp_auth->check()) {
            return $this->_redirect_after_login($this->vp_auth->user());
        }
        $this->page_title = 'Sign in';
        $this->page_description = 'Sign in to your ' . ($this->config->item('site_name') ?: 'Halyk Petroleum') . ' account.';

        // Heal the session store so a missing ci_sessions table cannot
        // silently swallow the login.
        $this->vp_auth->ensure_session_store();

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            $this->form_validation->set_rules('password', 'Password', 'required|min_length[4]');
            $this->form_validation->set_rules('remember', 'Remember me', 'in_list[0,1]');

            if ($this->form_validation->run()) {
                $user = $this->vp_auth->attempt(
                    $this->input->post('email'),
                    $this->input->post('password'),
                    (bool) $this->input->post('remember')
                );
                if ($user) {
                    $this->flash('success', 'Welcome back, ' . $user['firstName'] . '.');
                    return $this->_redirect_after_login($user);
                }
                // Show immediately (flashdata alone only appears on the next request).
                $this->flash('error', 'Invalid email or password.');
                $this->data['flash'] = ['type' => 'error', 'message' => 'Invalid email or password.'];
                if ($this->vp_auth->last_attempt_error === 'locked') {
                    $this->data['flash']['message'] = 'Too many failed attempts - please try again in '
                        . Vp_auth::WINDOW_MINUTES . ' minutes.';
                }
            }
        }

        $next = $this->input->get('next');
        $this->render('auth/login', ['next' => $next]);
    }

    public function register()
    {
        if ($this->vp_auth->check()) redirect('');
        $this->page_title = 'Create account';
        $this->page_description = 'Create a ' . ($this->config->item('site_name') ?: 'Halyk Petroleum') . ' account to manage quotes and downloads.';

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('firstName', 'First name', 'required|max_length[100]');
            $this->form_validation->set_rules('lastName',  'Last name',  'required|max_length[100]');
            $this->form_validation->set_rules('email',     'Email',      'required|valid_email');
            $this->form_validation->set_rules('password',  'Password',   'required|min_length[8]');
            $this->form_validation->set_rules('company',   'Company',    'max_length[190]');
            $this->form_validation->set_rules('phone',     'Phone',      'max_length[50]');

            if ($this->form_validation->run()) {
                $res = $this->vp_auth->register([
                    'firstName' => $this->input->post('firstName'),
                    'lastName'  => $this->input->post('lastName'),
                    'email'     => $this->input->post('email'),
                    'password'  => $this->input->post('password'),
                    'company'   => $this->input->post('company'),
                    'phone'     => $this->input->post('phone'),
                ]);
                if ($res['ok']) {
                    $this->flash('success', 'Your account has been created.');
                    redirect('login');
                }
                $this->flash('error', $res['error']);
            }
        }

        $this->render('auth/register');
    }

    public function logout()
    {
        $this->vp_auth->logout();
        $this->flash('success', 'You have been signed out.');
        redirect('');
    }

    public function forgot()
    {
        $this->page_title = 'Forgot password';
        $this->page_description = 'Enter your email and we will send you a link to reset your password.';

        if ($this->input->method() === 'post') {
            $email = strtolower(trim((string) $this->input->post('email')));
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');

            if ($this->form_validation->run()) {
                // Always show the same success message to avoid email enumeration.
                $this->load->model(['User_model', 'Password_reset_model']);
                $this->load->library('mailer');

                $user = $this->User_model->find_by_email($email);
                if ($user && $user['isActive']) {
                    $token = $this->Password_reset_model->create_for_email(
                        $user['email'], $user['id'], vp_get_client_ip()
                    );
                    $resetUrl = base_url('reset/' . $token);
                    $this->mailer->send(
                        $user['email'],
                        'Reset your ' . ($this->config->item('site_name') ?: 'Halyk Petroleum') . ' password',
                        $this->load->view('emails/password_reset', [
                            'firstName' => $user['firstName'],
                            'resetUrl'  => $resetUrl,
                            'ttlMinutes'=> (int) (Password_reset_model::TTL_SECONDS / 60),
                        ], TRUE),
                        'password_reset',
                        'password_reset:' . $user['email'] . ':' . substr($token, 0, 8),
                        []
                    );
                }
                $this->flash('success', 'If that email is registered, we have sent a reset link.');
                redirect('forgot');
            }
        }

        $this->render('auth/forgot');
    }

    public function reset($token = null)
    {
        $this->load->model('Password_reset_model');
        $this->load->model('User_model');
        $this->page_title = 'Reset password';
        $this->page_description = 'Choose a new password for your ' . ($this->config->item('site_name') ?: 'Halyk Petroleum') . ' account.';

        $valid = $token ? $this->Password_reset_model->find_valid($token) : null;
        if (!$valid) {
            $this->flash('error', 'This reset link is invalid or has expired. Please request a new one.');
            redirect('forgot');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('password',  'New password', 'required|min_length[8]');
            $this->form_validation->set_rules('password2', 'Confirm',     'required|matches[password]');

            if ($this->form_validation->run()) {
                $pwd = (string) $this->input->post('password');
                $this->User_model->update($valid['userId'], [
                    'password' => password_hash($pwd, PASSWORD_BCRYPT),
                ]);
                $this->Password_reset_model->mark_used($valid['id']);
                $this->audit->log(AUDIT_UPDATE, 'user', $valid['userId'], ['action' => 'password_reset']);
                $this->flash('success', 'Your password has been reset. Please sign in.');
                redirect('login');
            }
        }

        $this->render('auth/reset', ['token' => $token]);
    }

    /* ---------- Admin login (same form, different redirect + RBAC check) ---------- */

    public function admin_login()
    {
        if ($this->vp_auth->check() && $this->vp_auth->is_staff()) {
            redirect('admin');
        }
        $this->layout = '';
        $this->page_title = 'Admin sign in';

        // A missing ci_sessions table is the #1 cause of "admin login does
        // nothing": the login succeeds but the session cannot persist, so the
        // next request bounces back here. Heal it before anyone even tries.
        $sessionStoreOk = $this->vp_auth->ensure_session_store();

        if ($this->input->method() === 'post') {
            if (!$sessionStoreOk) {
                return $this->render('admin/login', ['flash' => [
                    'type'    => 'error',
                    'message' => 'Login is temporarily unavailable: the session store '
                        . '(ci_sessions table) is missing and could not be created automatically. '
                        . 'Re-import install/install.sql or run: php install/fix-admin.php',
                ]]);
            }

            $user = $this->vp_auth->attempt(
                $this->input->post('email'),
                $this->input->post('password'),
                false
            );
            if ($user && $this->vp_auth->is_staff()) {
                $this->flash('success', 'Welcome back, ' . $user['firstName'] . '.');
                // Return to the page that triggered the sign-in, when it is a
                // safe relative admin path (never an absolute URL).
                $next = (string) ($this->input->post('next') ?: $this->input->get('next'));
                if ($next !== '' && strpos($next, '/') === 0 && strpos($next, '//') !== 0) {
                    redirect($next);
                }
                redirect('admin');
            }
            $this->vp_auth->logout();

            // Tell the admin WHY it failed. On the (non-public) admin login
            // this helps far more than it helps an attacker.
            switch ($this->vp_auth->last_attempt_error) {
                case 'locked':
                    $mins = (int) ceil($this->rate_limiter->ttl(
                        'login:' . vp_get_client_ip() . ':' . hash('sha256', strtolower(trim((string) $this->input->post('email')))),
                        Vp_auth::WINDOW_MINUTES * 60
                    ) / 60);
                    $message = 'Too many failed attempts - this login is locked for about ' . max(1, $mins) . ' more minute(s). '
                        . 'Once you are sure of the password you can clear the lock with: php install/fix-admin.php '
                        . '(or wait for the lock to expire before trying again).';
                    break;
                case 'inactive':
                    $message = 'This account is deactivated. A Super Admin can re-enable it under Admin -> Users, '
                        . 'or run: php install/fix-admin.php';
                    break;
                default:
                    $message = 'Invalid credentials or insufficient permissions.';
            }
            // Render the error on THIS response too (flashdata alone only
            // shows up on the next page load).
            return $this->render('admin/login', ['flash' => ['type' => 'error', 'message' => $message]]);
        }

        if (!$sessionStoreOk) {
            // Render directly - flashdata would be lost without a working
            // session store anyway.
            return $this->render('admin/login', ['flash' => [
                'type'    => 'error',
                'message' => 'Sessions are not persisting (ci_sessions table missing). '
                    . 'Repair before logging in: php install/fix-admin.php',
            ]]);
        }
        $this->render('admin/login');
    }

    public function admin_logout()
    {
        $this->vp_auth->logout();
        redirect('admin/login');
    }

    /* ---------- helpers ---------- */

    private function _redirect_after_login($user)
    {
        $next = $this->input->get('next') ?: $this->input->post('next');
        if ($next && strpos($next, '/') === 0) redirect($next);
        if ($this->vp_auth->is_staff()) redirect('admin');
        redirect('');
    }
}
