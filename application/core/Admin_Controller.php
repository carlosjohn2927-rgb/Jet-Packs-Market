<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — admin area base controller.
 *
 * Responsibilities
 *   1. Require an authenticated staff account.
 *   2. Force a temporary-password change before anything else.
 *   3. Enforce permissions SERVER-SIDE for every request that reaches the
 *      admin area — hiding a sidebar link is never the security boundary.
 *      A controller declares what it needs:
 *
 *          protected $required_permission = 'pages.manage';
 *          protected $method_permissions  = ['delete' => 'pages.manage'];
 *          protected $super_admin_only    = true;   // Super Admin section
 *
 *      Anything not declared falls back to $required_permission, and a
 *      controller with no declaration at all requires 'dashboard.view'.
 *   4. Provide the permission-filtered navigation to the layout.
 */
class Admin_Controller extends Auth_Controller
{
    /** @var array Roles allowed to access the controller. Empty = any staff role. */
    protected $allowed_roles = [];

    /** @var string|null Permission key required for every action of this controller. */
    protected $required_permission = 'dashboard.view';

    /** @var array Per-method overrides: ['method' => 'permission.key'] */
    protected $method_permissions = [];

    /** @var bool When TRUE only a SUPER_ADMIN may enter the controller. */
    protected $super_admin_only = false;

    public function __construct()
    {
        parent::__construct();

        $this->layout = 'admin';
        $this->body_class = 'admin';

        if (!$this->vp_auth->is_staff()) {
            $this->_deny('You do not have permission to access the admin area.');
        }

        // Temporary passwords (created by install/install.php without
        // VP_ADMIN_PASSWORD) must be changed before anything else.
        // users/edit + users/save are the only pages allowed so the user
        // can actually perform the change (plus logout).
        //
        // This check MUST run before the permission gate: otherwise an
        // account with a temporary password gets a 403 on the very page it is
        // redirected to and can never get in at all.
        if ($this->vp_auth->must_change_password()) {
            $uri = strtolower((string) $this->uri->ruri_string());
            $allowed = strpos($uri, 'admin/profile') === 0
                || strpos($uri, 'admin/users/edit/' . $this->vp_auth->id()) === 0
                || strpos($uri, 'admin/users/save') === 0
                || strpos($uri, 'auth/admin_logout') !== false
                || strpos($uri, 'auth/logout') !== false;
            if (!$allowed) {
                $this->flash('warning', 'You must change your temporary password before continuing.');
                redirect('admin/profile');
            }
            return; // skip gating for the forced password change itself
        }

        if (!empty($this->allowed_roles) && !$this->vp_auth->has_any_role($this->allowed_roles)) {
            $this->_deny('You do not have permission to access this section.');
        }

        if ($this->super_admin_only && !$this->is_super_admin()) {
            $this->_deny('This area is reserved for the Super Admin.');
        }

        // --- permission gate -------------------------------------------
        $method = strtolower((string) $this->router->fetch_method());
        $needed = array_key_exists($method, $this->method_permissions)
            ? $this->method_permissions[$method]
            : $this->required_permission;

        if ($needed !== null && !$this->has_permission($needed)) {
            $this->_deny('You do not have permission to use this section.', $needed);
        }

        // --- shared admin view data ------------------------------------
        $this->data['admin_nav']       = $this->_nav();
        $this->data['can']             = $this->_permission_set();
        $this->data['is_super_admin']  = $this->is_super_admin();
        $this->data['public_site_url'] = base_url();
    }

    /* ------------------------------------------------------------------ */
    /* Permission helpers                                                  */
    /* ------------------------------------------------------------------ */

    /** TRUE when the signed-in account is the Super Admin. */
    protected function is_super_admin()
    {
        return $this->vp_auth->has_role(ROLE_SUPER_ADMIN);
    }

    /** TRUE when the signed-in account holds $key. */
    protected function has_permission($key)
    {
        return $this->acl->user_can($this->vp_auth->user(), $key);
    }

    /**
     * Hard gate usable inside an action (e.g. a destructive sub-operation).
     * Terminates the request with 403 when the permission is missing.
     */
    protected function require_permission($key)
    {
        if (!$this->has_permission($key)) {
            $this->_deny('You do not have permission to perform this action.', $key);
        }
        return true;
    }

    /** Effective permission keys as [key => true] for quick view lookups. */
    private function _permission_set()
    {
        $out = [];
        foreach ($this->acl->effective($this->vp_auth->user()) as $k) $out[$k] = true;
        return $out;
    }

    /**
     * Refuse the request: audit it, then render a 403 (or JSON for XHR).
     */
    protected function _deny($message, $permission = null)
    {
        $this->audit->log('ACCESS_DENIED', 'admin', null, [
            'uri'        => (string) $this->uri->ruri_string(),
            'permission' => $permission,
        ]);

        if ($this->input->is_ajax_request()) {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'error' => $message]));
            exit;
        }

        $this->output->set_status_header(403);
        // Rendered and flushed here because the request stops immediately —
        // CI's output buffer is never reached after exit().
        echo $this->load->view('admin/denied', [
            'message'    => $message,
            'permission' => $permission,
            'user'       => $this->vp_auth->user(),
        ], TRUE);
        exit;
    }

    /* ------------------------------------------------------------------ */
    /* Navigation                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Sidebar sections the current account may actually open.
     */
    private function _nav()
    {
        $this->config->load('admin_nav', TRUE);
        $groups = (array) $this->config->item('admin_nav', 'admin_nav');
        $out = [];
        foreach ($groups as $group) {
            $items = [];
            foreach ($group['items'] as $item) {
                if (!empty($item['permission']) && !$this->has_permission($item['permission'])) continue;
                $items[] = $item;
            }
            if ($items) $out[] = ['group' => $group['group'], 'items' => $items];
        }
        return $out;
    }
}
