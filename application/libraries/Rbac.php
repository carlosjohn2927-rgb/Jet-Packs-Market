<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - Role-Based Access Control.
 * Caches role -> {resource: [actions]} map in session for the request.
 */
class Rbac
{
    /** @var CI_Controller */
    protected $CI;
    protected $cache = null;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    /**
     * Check whether $role may perform $action on $resource.
     */
    public function can($role, $resource, $action = 'read')
    {
        $map = $this->get_role_map($role);
        if (isset($map[$resource])) {
            if (in_array('*', $map[$resource], true)) return true;
            if (in_array($action, $map[$resource], true)) return true;
        }
        // Super admin always can
        if ($role === ROLE_SUPER_ADMIN) return true;
        return false;
    }

    /**
     * Return the permission map for a role.
     */
    public function get_role_map($role)
    {
        if ($this->cache !== null) return $this->cache;

        $rows = $this->CI->db->get('role_permissions')->result_array();
        $map = [];
        foreach ($rows as $r) {
            $actions = json_decode($r['actions'] ?? '[]', true);
            if (!is_array($actions)) $actions = [];
            $map[$r['role']][$r['resource']] = $actions;
        }
        $this->cache = $map;
        return $this->cache;
    }

    /**
     * Update a role's actions for a resource.
     */
    public function set_permission($role, $resource, array $actions)
    {
        $existing = $this->CI->db->get_where('role_permissions', ['role' => $role, 'resource' => $resource])->row_array();
        if ($existing) {
            $this->CI->db->update('role_permissions', ['actions' => json_encode($actions)], ['id' => $existing['id']]);
        } else {
            $this->CI->db->insert('role_permissions', [
                'id'        => MY_Model::uuid(),
                'role'      => $role,
                'resource'  => $resource,
                'actions'   => json_encode($actions),
                'createdAt' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
