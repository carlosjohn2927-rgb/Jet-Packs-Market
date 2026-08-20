<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — Access Control List.
 *
 * Resolves the effective permissions of a staff account:
 *
 *   SUPER_ADMIN  -> every permission, always, and it cannot be taken away.
 *   everyone else-> role defaults (role_permissions)
 *                   + per-user grants   (user_permissions.granted = 1)
 *                   - per-user denials  (user_permissions.granted = 0)
 *                   - super-only permissions (never grantable)
 *
 * Permission keys are `<resource>.<action>` and are declared in
 * application/config/permissions.php. Enforcement happens server-side in
 * Admin_Controller (and in every controller action that mutates data), never
 * only by hiding menu items.
 */
class Acl
{
    /** @var CI_Controller */
    protected $CI;

    /** @var array|null in-request cache of effective permissions per user id */
    protected $cache = [];

    /** @var array|null in-request cache of role default keys */
    protected $role_cache = [];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->config->load('permissions', TRUE);
    }

    /* ------------------------------------------------------------------ */
    /* Catalogue                                                           */
    /* ------------------------------------------------------------------ */

    /** @return array key => ['label' => .., 'group' => .., 'super_only' => bool] */
    public function catalog()
    {
        static $out = null;
        if ($out !== null) return $out;
        $raw = (array) $this->CI->config->item('permissions', 'permissions');
        $out = [];
        foreach ($raw as $key => $def) {
            $out[$key] = [
                'label'      => $def[0] ?? $key,
                'group'      => $def[1] ?? 'General',
                'super_only' => !empty($def[2]),
            ];
        }
        return $out;
    }

    /** Catalogue grouped by section, for the permission editor UI. */
    public function grouped_catalog()
    {
        $groups = (array) $this->CI->config->item('permission_groups', 'permissions');
        $out = [];
        foreach (array_keys($groups) as $g) $out[$g] = [];
        foreach ($this->catalog() as $key => $def) {
            $out[$def['group']][$key] = $def;
        }
        return array_filter($out, function ($v) { return !empty($v); });
    }

    public function group_descriptions()
    {
        return (array) $this->CI->config->item('permission_groups', 'permissions');
    }

    public function exists($key)
    {
        $c = $this->catalog();
        return isset($c[$key]);
    }

    public function is_super_only($key)
    {
        $c = $this->catalog();
        return !empty($c[$key]['super_only']);
    }

    public function label($key)
    {
        $c = $this->catalog();
        return $c[$key]['label'] ?? $key;
    }

    /** Permission keys a Super Admin may hand to a normal administrator. */
    public function grantable_keys()
    {
        $out = [];
        foreach ($this->catalog() as $key => $def) {
            if (!$def['super_only']) $out[] = $key;
        }
        return $out;
    }

    /**
     * Mirror the code catalogue into the `permissions` table so the data model
     * is complete (and reportable) in SQL. Cheap: one query, runs at most once
     * per request and only inserts what is missing.
     */
    public function sync_catalog()
    {
        static $done = false;
        if ($done) return true;
        $done = true;
        try {
            if (!$this->CI->db->table_exists('permissions')) return false;
            $existing = [];
            foreach ($this->CI->db->get('permissions')->result_array() as $r) {
                $existing[$r['key']] = $r;
            }
            $i = 0;
            foreach ($this->catalog() as $key => $def) {
                $i++;
                $row = [
                    'label'     => $def['label'],
                    'groupName' => $def['group'],
                    'superOnly' => $def['super_only'] ? 1 : 0,
                    'sortOrder' => $i,
                ];
                if (isset($existing[$key])) {
                    $this->CI->db->update('permissions', $row, ['key' => $key]);
                } else {
                    $row['id']  = MY_Model::uuid();
                    $row['key'] = $key;
                    $row['createdAt'] = date('Y-m-d H:i:s');
                    $this->CI->db->insert('permissions', $row);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Acl::sync_catalog - ' . $e->getMessage());
            return false;
        }
        return true;
    }

    /* ------------------------------------------------------------------ */
    /* Role defaults                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Permission keys granted to a role by default.
     *
     * Reads `role_permissions` (resource + actions JSON). Legacy CRUD action
     * sets are mapped onto the catalogue: any of create/update/delete/manage
     * implies `<resource>.manage`, a bare `read` implies `<resource>.view`.
     */
    public function role_defaults($role)
    {
        $role = (string) $role;
        if ($role === ROLE_SUPER_ADMIN) return array_keys($this->catalog());
        if (isset($this->role_cache[$role])) return $this->role_cache[$role];

        $keys = [];
        $rows = [];
        try {
            if ($this->CI->db->table_exists('role_permissions')) {
                $rows = $this->CI->db->get_where('role_permissions', ['role' => $role])->result_array();
            }
        } catch (\Throwable $e) {
            $rows = [];
        }

        // Shipped role defaults are always included. Database rows can add
        // legacy/custom grants, while per-user denies can still remove access
        // for a specific account. This keeps older installs in sync when new
        // admin modules are added in code.
        $defaults = (array) $this->CI->config->item('role_default_permissions', 'permissions');
        foreach ((array) ($defaults[$role] ?? []) as $k) {
            if ($this->exists($k) && !$this->is_super_only($k)) $keys[$k] = true;
        }

        foreach ($rows as $r) {
            $actions = json_decode((string) ($r['actions'] ?? '[]'), true);
            if (!is_array($actions)) $actions = [];
            $resource = $r['resource'];
            if ($resource === '*' && in_array('*', $actions, true)) {
                return $this->role_cache[$role] = array_keys($this->catalog());
            }
            foreach ($actions as $a) {
                $candidates = [$resource . '.' . $a];
                if (in_array($a, ['create', 'update', 'delete', '*'], true)) {
                    $candidates[] = $resource . '.manage';
                }
                if ($a === 'read' || $a === '*') {
                    $candidates[] = $resource . '.view';
                }
                foreach ($candidates as $c) {
                    if ($this->exists($c) && !$this->is_super_only($c)) $keys[$c] = true;
                }
            }
        }

        return $this->role_cache[$role] = array_keys($keys);
    }

    /**
     * Replace the default permission set of a role (Super Admin only feature).
     */
    public function set_role_defaults($role, array $keys)
    {
        if ($role === ROLE_SUPER_ADMIN) return false; // never editable
        $by_resource = [];
        foreach ($keys as $k) {
            if (!$this->exists($k) || $this->is_super_only($k)) continue;
            [$resource, $action] = array_pad(explode('.', $k, 2), 2, 'manage');
            $by_resource[$resource][$action] = true;
            if ($action === 'manage') {
                foreach (['read', 'create', 'update', 'delete'] as $legacy) {
                    $by_resource[$resource][$legacy] = true;
                }
            }
            if ($action === 'view') $by_resource[$resource]['read'] = true;
        }

        $this->CI->db->delete('role_permissions', ['role' => $role]);
        $now = date('Y-m-d H:i:s');
        foreach ($by_resource as $resource => $actions) {
            $this->CI->db->insert('role_permissions', [
                'id'        => MY_Model::uuid(),
                'role'      => $role,
                'resource'  => $resource,
                'actions'   => json_encode(array_keys($actions)),
                'createdAt' => $now,
            ]);
        }
        $this->role_cache = [];
        $this->cache = [];
        return true;
    }

    /* ------------------------------------------------------------------ */
    /* Per-user overrides                                                  */
    /* ------------------------------------------------------------------ */

    /** @return array key => bool (true = granted, false = explicitly denied) */
    public function user_overrides($user_id)
    {
        if (!$user_id) return [];
        try {
            if (!$this->CI->db->table_exists('user_permissions')) return [];
            $rows = $this->CI->db->get_where('user_permissions', ['userId' => $user_id])->result_array();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[$r['permission']] = (bool) $r['granted'];
        }
        return $out;
    }

    /** Permissions every ADMIN receives as part of the website-editor role. */
    private function admin_website_permissions()
    {
        return [
            'products.manage', 'categories.manage', 'industries.manage', 'downloads.manage',
            'blog.manage', 'news.manage', 'faqs.manage', 'careers.manage',
            'testimonials.manage', 'partners.manage',
            'homepage.manage', 'pages.manage', 'menus.manage', 'appearance.manage',
            'media.manage', 'seo.manage', 'settings.manage',
        ];
    }

    /**
     * Effective permission keys for a user row.
     */
    public function effective(array $user = null)
    {
        if (!$user) return [];
        $uid = $user['id'] ?? null;
        if ($uid && isset($this->cache[$uid])) return $this->cache[$uid];

        $role = $user['role'] ?? '';
        if ($role === ROLE_SUPER_ADMIN) {
            $keys = array_keys($this->catalog());
            if ($uid) $this->cache[$uid] = $keys;
            return $keys;
        }

        $keys = array_fill_keys($this->role_defaults($role), true);
        foreach ($this->user_overrides($uid) as $key => $granted) {
            if (!$this->exists($key) || $this->is_super_only($key)) continue;
            if ($granted) $keys[$key] = true;
            else unset($keys[$key]);
        }

        // ADMIN is a full website-editor role. Keep these grants mandatory so
        // accounts created before this policy change (whose permission rows
        // contain explicit denials) can still edit every public-facing page.
        // Operational areas such as customers, audit and administrator
        // management remain independently controlled.
        if ($role === ROLE_ADMIN) {
            foreach ($this->admin_website_permissions() as $key) {
                if ($this->exists($key) && !$this->is_super_only($key)) $keys[$key] = true;
            }
        }

        // Super-only permissions can never leak to a non super admin.
        foreach ($this->catalog() as $key => $def) {
            if ($def['super_only']) unset($keys[$key]);
        }
        $out = array_keys($keys);
        if ($uid) $this->cache[$uid] = $out;
        return $out;
    }

    /**
     * Can this user perform $key?
     */
    public function user_can(array $user = null, $key = null)
    {
        if (!$user || !$key) return false;
        if (($user['role'] ?? '') === ROLE_SUPER_ADMIN) return true;
        if (!$this->exists($key)) return false;
        if ($this->is_super_only($key)) return false;
        return in_array($key, $this->effective($user), true);
    }

    /**
     * Persist the exact permission set of an administrator.
     * Every catalogue permission is written as an explicit grant or denial so
     * later changes to the role defaults cannot silently widen access.
     */
    public function set_user_permissions($user_id, array $keys, $actor_id = null)
    {
        // Website-editing grants are part of the ADMIN role contract and must
        // not be removed by the per-account permission form.
        $account = $this->CI->db->select('role')->get_where('users', ['id' => $user_id], 1)->row_array();
        if (($account['role'] ?? '') === ROLE_ADMIN) {
            $keys = array_merge($keys, $this->admin_website_permissions());
        }

        $keys = array_values(array_filter(array_unique($keys), function ($k) {
            return $this->exists($k) && !$this->is_super_only($k);
        }));
        $now = date('Y-m-d H:i:s');
        $existing = [];
        foreach ($this->CI->db->get_where('user_permissions', ['userId' => $user_id])->result_array() as $r) {
            $existing[$r['permission']] = $r;
        }

        foreach ($this->grantable_keys() as $key) {
            $granted = in_array($key, $keys, true) ? 1 : 0;
            if (isset($existing[$key])) {
                if ((int) $existing[$key]['granted'] !== $granted) {
                    $this->CI->db->update('user_permissions', [
                        'granted'   => $granted,
                        'grantedBy' => $actor_id,
                        'updatedAt' => $now,
                    ], ['id' => $existing[$key]['id']]);
                }
            } else {
                $this->CI->db->insert('user_permissions', [
                    'id'         => MY_Model::uuid(),
                    'userId'     => $user_id,
                    'permission' => $key,
                    'granted'    => $granted,
                    'grantedBy'  => $actor_id,
                    'createdAt'  => $now,
                    'updatedAt'  => $now,
                ]);
            }
        }
        unset($this->cache[$user_id]);
        return true;
    }

    /** Remove every explicit override, returning the user to role defaults. */
    public function reset_user_permissions($user_id)
    {
        $this->CI->db->delete('user_permissions', ['userId' => $user_id]);
        unset($this->cache[$user_id]);
        return true;
    }

    /** Forget cached results (after a permission change). */
    public function clear_cache()
    {
        $this->cache = [];
        $this->role_cache = [];
        return $this;
    }
}
