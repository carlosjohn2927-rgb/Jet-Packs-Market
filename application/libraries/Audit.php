<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - Audit log.
 */
class Audit
{
    protected $CI;
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper('security_helper');
    }

    /**
     * Log an action.
     *
     * @param string $action   One of the AUDIT_* constants
     * @param string $resource 'user', 'product', 'quote', etc.
     * @param string|null $resourceId
     * @param array  $details  Arbitrary JSON-serialisable detail
     */
    public function log($action, $resource, $resourceId = null, array $details = [])
    {
        if (!$this->CI->db->table_exists('audit_logs')) return;
        $uid = null;
        if ($this->CI->session->userdata('vp_user_id')) {
            $uid = $this->CI->session->userdata('vp_user_id');
        }
        $row = [
            'id'         => MY_Model::uuid(),
            'userId'     => $uid,
            'action'     => $action,
            'resource'   => $resource,
            'resourceId' => $resourceId,
            'details'    => json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ipAddress'  => vp_get_client_ip(),
            'userAgent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'createdAt'  => date('Y-m-d H:i:s'),
        ];
        $this->CI->db->insert('audit_logs', $row);
    }
}
