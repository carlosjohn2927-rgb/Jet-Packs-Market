<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum - Settings (key-value store) with in-request cache.
 *
 * Loaded as $this->settings (see application/config/autoload.php). The class is
 * prefixed Vp_ so it cannot collide with the admin `Settings` controller —
 * CodeIgniter resolves libraries and controllers in the same class namespace.
 */
class Vp_settings
{
    protected $CI;
    protected $cache = null;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function get($key, $default = null)
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    public function set($key, $value, $type = 'STRING', $group = 'GENERAL')
    {
        $existing = $this->CI->db->get_where('settings', ['key' => $key])->row_array();
        $now = date('Y-m-d H:i:s');
        $data = [
            'value'     => is_array($value) || is_object($value) ? json_encode($value) : (string) $value,
            'type'      => $type,
            'group'     => $group,
            'updatedAt' => $now,
        ];
        if ($existing) {
            $this->CI->db->update('settings', $data, ['id' => $existing['id']]);
        } else {
            $data['id'] = MY_Model::uuid();
            $data['key'] = $key;
            $data['version'] = 1;
            $data['enabled'] = 1;
            $data['sortOrder'] = 0;
            $data['createdAt'] = $now;
            $this->CI->db->insert('settings', $data);
        }
        $this->clear_cache();
        return true;
    }

    /**
     * Invalidate the in-request cache.
     * Call this if you mutate the settings table outside the library
     * and want the next read to re-query the DB.
     */
    public function clear_cache()
    {
        $this->cache = null;
        return $this;
    }

    public function by_group($group)
    {
        if (!$this->CI->db->table_exists('settings')) return [];
        $rows = $this->CI->db->get_where('settings', ['group' => $group])->result_array();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['key']] = $this->coerce($r);
        }
        return $out;
    }

    public function all()
    {
        if ($this->cache !== null) return $this->cache;
        if (!$this->_db_ok()) {
            $this->cache = [];
            return $this->cache;
        }
        $rows = $this->CI->db->get('settings')->result_array();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['key']] = $this->coerce($r);
        }
        $this->cache = $out;
        return $this->cache;
    }

    /**
     * Cheap health check: did the database connect and is the settings table there?
     * Caches the result so we don't probe on every read.
     */
    private function _db_ok()
    {
        static $ok = null;
        if ($ok !== null) return $ok;
        try {
            $ok = $this->CI->db->table_exists('settings');
        } catch (\Throwable $e) { // Catch Error too: a failed connection throws a TypeError, not just Exception.
            log_message('error', 'Settings: DB unreachable - ' . $e->getMessage());
            $ok = false;
        }
        return $ok;
    }

    private function coerce($row)
    {
        $v = $row['value'];
        switch ($row['type'] ?? 'STRING') {
            case 'JSON':  $d = json_decode($v, true); return is_array($d) ? $d : $v;
            case 'INT':   return (int) $v;
            case 'BOOL':  return (bool) $v;
            default:      return $v;
        }
    }
}
