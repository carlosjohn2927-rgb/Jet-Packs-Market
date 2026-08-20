<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — requires the user to be signed in.
 *
 * Admin-area URLs bounce to the admin sign-in screen (and back again after a
 * successful login); everything else uses the public sign-in page.
 */
class Auth_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->vp_auth->check()) {
            $this->flash('warning', 'Please sign in to continue.');
            // Relative path only: the login controllers refuse absolute URLs
            // as an open-redirect guard.
            $back = urlencode('/' . ltrim((string) $this->uri->uri_string(), '/'));
            $is_admin_area = strpos(strtolower((string) $this->uri->uri_string()), 'admin') === 0;
            redirect(($is_admin_area ? 'admin/login?next=' : 'login?next=') . $back);
        }
    }
}
