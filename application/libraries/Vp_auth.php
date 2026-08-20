<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - Authentication library.
 *
 * - Native PHP password_hash() (BCRYPT)
 * - CI3 database session (sess_driver=database)
 * - Optional "remember me" cookie (HMAC-signed, 30 days)
 * - Built-in throttling on login (5 fails / 15 min per IP+email)
 */
class Vp_auth
{
    const REMEMBER_COOKIE = 'vp_remember';
    const REMEMBER_DAYS   = 30;
    const MAX_ATTEMPTS    = 5;
    const WINDOW_MINUTES  = 15;

    /** @var CI_Controller */
    protected $CI;

    /**
     * Why the most recent attempt() failed in this request:
     * 'locked' | 'inactive' | 'credentials' | null (no attempt / success).
     * Lets the login controllers show an actionable message instead of a
     * generic one (a lockout in particular looks exactly like "wrong
     * password" and has driven repeated false reports of a broken login).
     */
    public $last_attempt_error = null;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->library('session');
        $this->CI->load->helper('security_helper');
    }

    /** Current user array, or null. */
    public function user()
    {
        $id = $this->id();
        if (!$id) return null;
        try {
            $row = $this->CI->db->get_where('users', ['id' => $id])->row_array();
        } catch (Exception $e) {
            return null;
        }
        if (!$row) return null;
        // Deactivated accounts are signed out immediately, even mid-session.
        if (empty($row['isActive'])) {
            $this->logout();
            return null;
        }
        return $row;
    }

    /** Current user id, or null. */
    public function id()
    {
        // If the database is unreachable (e.g. install.sql not run yet), return null
        // instead of throwing, so the public site still renders.
        try {
            if ($this->CI->session->userdata('vp_user_id')) {
                return $this->CI->session->userdata('vp_user_id');
            }
        } catch (Exception $e) {
            return null;
        }
        try {
            $remembered = $this->check_remember_cookie();
            if ($remembered) {
                $this->login_by_id($remembered, true);
                return $remembered;
            }
        } catch (Exception $e) {
            return null;
        }
        return null;
    }

    public function check()
    {
        return $this->id() !== null;
    }

    public function is_staff()
    {
        $u = $this->user();
        if (!$u) return false;
        return in_array($u['role'], [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_SALES, ROLE_ENGINEER, ROLE_EDITOR], true);
    }

    public function has_role($role)
    {
        $u = $this->user();
        return $u && $u['role'] === $role;
    }

    public function has_any_role(array $roles)
    {
        $u = $this->user();
        return $u && in_array($u['role'], $roles, true);
    }

    public function can($resource, $action)
    {
        $u = $this->user();
        if (!$u) return false;
        $this->CI->load->library('rbac');
        return $this->CI->rbac->can($u['role'], $resource, $action);
    }

    /**
     * Attempt to log in by email + password.
     * Returns the user row on success, or null on failure.
     */
    public function attempt($email, $password, $remember = false)
    {
        $email = strtolower(trim((string) $email));
        $ip = vp_get_client_ip();
        // File-based lockout: independent of the session cookie, so an
        // attacker cannot reset the counter by dropping cookies.
        $lock_key = 'login:' . $ip . ':' . hash('sha256', $email);

        $this->CI->load->library('rate_limiter');
        if ($this->CI->rate_limiter->too_many($lock_key, self::MAX_ATTEMPTS, self::WINDOW_MINUTES * 60)) {
            
            $this->CI->audit->log(AUDIT_LOGIN_FAILED, 'user', null, ['email' => $email, 'reason' => 'locked']);
            $this->last_attempt_error = 'locked';
            return null;
        }

        try {
            $row = $this->CI->db->get_where('users', ['email' => $email])->row_array();
        } catch (Exception $e) {
            $row = null;
        }
        if (!$row || !password_verify((string) $password, $row['password'])) {
            
            $this->CI->audit->log(AUDIT_LOGIN_FAILED, 'user', null, ['email' => $email]);
            $this->last_attempt_error = 'credentials';
            return null;
        }

        if (!$row['isActive']) {
            
            $this->CI->audit->log(AUDIT_LOGIN_FAILED, 'user', $row['id'], ['email' => $email, 'reason' => 'inactive']);
            $this->last_attempt_error = 'inactive';
            return null;
        }

        $this->CI->rate_limiter->clear($lock_key);
        $this->login_by_id($row['id'], $remember, $row);
        return $row;
    }

    /**
     * Set the session to a user (already authenticated by another mechanism).
     */
    public function login_by_id($user_id, $remember = false, $user = null)
    {
        $user = $user ?: $this->CI->db->get_where('users', ['id' => $user_id])->row_array();
        if (!$user) return false;

        // Prevent session fixation: new session id on every login.
        $this->CI->session->sess_regenerate(FALSE);

        $old_id = $this->CI->session->userdata('vp_session_id');
        $this->CI->session->set_userdata([
            'vp_user_id'   => $user['id'],
            'vp_user_role' => $user['role'],
            'vp_session_id'=> $old_id ?: $this->CI->session->userdata('session_id'),
        ]);

        $this->CI->db->update('users', [
            'lastLoginAt' => date('Y-m-d H:i:s'),
        ], ['id' => $user['id']]);

        
        $this->CI->audit->log(AUDIT_LOGIN, 'user', $user['id']);

        if ($remember) {
            $this->set_remember_cookie($user['id']);
        } else {
            $this->clear_remember_cookie();
        }
        return true;
    }

    /**
     * TRUE when the current staff user is using a temporary/administrator
     * password and must change it before using the admin area.
     */
    public function must_change_password()
    {
        $u = $this->user();
        return $u && !empty($u['mustChangePassword']) && $this->is_staff();
    }

    public function logout()
    {
        $uid = $this->id();
        if ($uid) {
            
            $this->CI->audit->log(AUDIT_LOGOUT, 'user', $uid);
        }
        $this->CI->session->unset_userdata(['vp_user_id', 'vp_user_role']);
        $this->clear_remember_cookie();
    }

    /**
     * Create a new user.
     */
    public function register(array $data)
    {
        $email = strtolower(trim($data['email'] ?? ''));
        if ($this->CI->db->get_where('users', ['email' => $email])->num_rows() > 0) {
            return ['ok' => false, 'error' => 'An account with that email already exists.'];
        }
        $now = date('Y-m-d H:i:s');
        $row = [
            'id'         => MY_Model::uuid(),
            'email'      => $email,
            'password'   => password_hash($data['password'] ?? '', PASSWORD_BCRYPT),
            'firstName'  => $data['firstName'] ?? '',
            'lastName'   => $data['lastName'] ?? '',
            'phone'      => $data['phone'] ?? null,
            'company'    => $data['company'] ?? null,
            'role'       => ROLE_CUSTOMER,
            'isActive'   => 1,
            'createdAt'  => $now,
            'updatedAt'  => $now,
        ];
        $this->CI->db->insert('users', $row);
        $this->login_by_id($row['id'], $data['remember'] ?? false, $row);
        return ['ok' => true, 'user' => $row];
    }

    /**
     * Self-heal the CI session store.
     *
     * With sess_driver=database a missing `ci_sessions` table breaks login
     * SILENTLY: the login POST succeeds, but the session is never persisted,
     * so the very next request bounces back to the login form. This is the
     * most common "admin does not log in" cause on installs where the schema
     * was imported by hand (phpMyAdmin) and the import stopped early.
     *
     * Creates the table with the exact schema from install/install.sql.
     * Runs at most once per request; returns TRUE when the table is usable.
     */
    public function ensure_session_store()
    {
        static $done = null;
        if ($done !== null) return $done;

        if ($this->CI->config->item('sess_driver') !== 'database') {
            return $done = true;
        }

        try {
            $exists = $this->CI->db->table_exists('ci_sessions');
        } catch (\Throwable $e) {
            log_message('error', 'Vp_auth: session store check failed - ' . $e->getMessage());
            return $done = false;
        }
        if ($exists) return $done = true;

        log_message('error', 'Vp_auth: ci_sessions table missing - creating it (see install/install.sql).');
        $sql = "CREATE TABLE IF NOT EXISTS `ci_sessions` (
            `id`            VARCHAR(128) NOT NULL,
            `ip_address`    VARCHAR(45)  NOT NULL,
            `timestamp`     INT UNSIGNED NOT NULL DEFAULT 0,
            `data`          BLOB         NOT NULL,
            `primary_key`   VARCHAR(64)  NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`,`ip_address`),
            KEY `ci_sessions_timestamp` (`timestamp`),
            KEY `ci_sessions_primary_key` (`primary_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $ok = (bool) $this->CI->db->query($sql);
        } catch (\Throwable $e) {
            log_message('error', 'Vp_auth: could not create ci_sessions - ' . $e->getMessage());
            return $done = false;
        }
        if ($ok) {
            // list_tables() caches per request; invalidate so later checks pass.
            $this->CI->db->data_cache = [];
            
            $this->CI->audit->log(AUDIT_UPDATE, 'ci_sessions', null, ['action' => 'auto_create']);
        }
        return $done = $ok;
    }

    /* ---------- Remember-me cookie ---------- */

    private function set_remember_cookie($user_id)
    {
        $exp = time() + (self::REMEMBER_DAYS * 86400);
        $payload = $user_id . '|' . $exp;
        $sig = vp_hmac_sign($payload);
        $value = $payload . '|' . $sig;
        $this->CI->input->set_cookie(self::REMEMBER_COOKIE, $value, self::REMEMBER_DAYS * 86400, '', '', $this->CI->config->item('cookie_secure'), true);
    }

    private function clear_remember_cookie()
    {
        $this->CI->input->set_cookie(self::REMEMBER_COOKIE, '', -3600);
    }

    private function check_remember_cookie()
    {
        $c = $this->CI->input->cookie(self::REMEMBER_COOKIE);
        if (!$c) return null;
        $parts = explode('|', $c);
        if (count($parts) !== 3) return null;
        [$uid, $exp, $sig] = $parts;
        if (!ctype_digit((string) $exp) || (int) $exp < time()) return null;
        if (!vp_hmac_verify($uid . '|' . $exp, $sig)) return null;
        $row = $this->CI->db->get_where('users', ['id' => $uid, 'isActive' => 1])->row_array();
        return $row ? $row['id'] : null;
    }
}
