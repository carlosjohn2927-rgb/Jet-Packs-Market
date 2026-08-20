<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — administrator activity / audit log.
 *
 * Every privileged action (sign-in, content change, permission change,
 * denied access attempt …) is written here by the Vp_audit library. The Super
 * Admin reviews them from this screen; an Admin needs the audit.view
 * permission.
 */
class Audit extends Admin_Controller
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'audit.view';

    public function index()
    {
        $this->page_title = 'Activity log';

        $filters = [
            'userId'   => $this->input->get('userId'),
            'action'   => $this->input->get('action'),
            'resource' => $this->input->get('resource'),
        ];
        $search = trim((string) $this->input->get('q'));
        $page   = max(1, (int) $this->input->get('page'));
        $per    = 50;

        $apply = function () use ($filters, $search) {
            foreach ($filters as $col => $val) {
                if ($val) $this->db->where('audit_logs.' . $col, $val);
            }
            if ($search !== '') {
                $this->db->group_start()
                         ->like('audit_logs.details', $search)
                         ->or_like('audit_logs.resourceId', $search)
                         ->or_like('audit_logs.ipAddress', $search)
                         ->group_end();
            }
        };

        $apply();
        $total = $this->db->count_all_results('audit_logs');

        $this->db->select('audit_logs.*, users.firstName, users.lastName, users.email, users.role')
                 ->from('audit_logs')
                 ->join('users', 'users.id = audit_logs.userId', 'left')
                 ->order_by('audit_logs.createdAt', 'DESC')
                 ->limit($per, ($page - 1) * $per);
        $apply();
        $rows = $this->db->get()->result_array();

        $actions = [];
        foreach ($this->db->select('action')->distinct()->order_by('action', 'ASC')->get('audit_logs')->result_array() as $r) {
            $actions[] = $r['action'];
        }
        $resources = [];
        foreach ($this->db->select('resource')->distinct()->order_by('resource', 'ASC')->get('audit_logs')->result_array() as $r) {
            $resources[] = $r['resource'];
        }

        $this->render('admin/audit/index', [
            'rows'        => $rows,
            'total'       => $total,
            'total_pages' => max(1, (int) ceil($total / $per)),
            'page'        => $page,
            'user'        => $filters['userId'],
            'action'      => $filters['action'],
            'resource'    => $filters['resource'],
            'search'      => $search,
            'actions'     => $actions,
            'resources'   => $resources,
            'users'       => $this->db->where_in('role', [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_SALES, ROLE_ENGINEER, ROLE_EDITOR])
                                      ->order_by('firstName', 'ASC')->get('users')->result_array(),
            'base_url'    => base_url('admin/audit') . '?' . http_build_query(array_filter([
                                'userId' => $filters['userId'], 'action' => $filters['action'],
                                'resource' => $filters['resource'], 'q' => $search,
                             ])) . '&page={page}',
        ]);
    }
}
