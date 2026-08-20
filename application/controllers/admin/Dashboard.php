<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — dashboard home.
 *
 * Shows only the panels the signed-in account is allowed to see, plus quick
 * links into the sections it may actually use. The Super Admin additionally
 * gets the administrator/security overview.
 */
class Dashboard extends Admin_Controller
{
    protected $required_permission = 'dashboard.view';

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Quote_model', 'Product_model', 'Contact_model', 'User_model']);
        $this->load->helper(['url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = vp_is_super_admin() ? 'Super Admin Dashboard' : 'Admin Dashboard';

        $data = [
            'counts'          => [],
            'quote_by_status' => [],
            'recent_quotes'   => [],
            'recent_activity' => [],
            'email_health'    => null,
            'admins'          => [],
            'content'         => [],
            'site'            => vp_site(),
        ];

        if ($this->has_permission('quotes.manage')) {
            foreach ($this->db->select('status, COUNT(*) AS c')->group_by('status')->get('quotes')->result_array() as $r) {
                $data['quote_by_status'][$r['status']] = (int) $r['c'];
            }
            $data['recent_quotes'] = $this->db->order_by('createdAt', 'DESC')->limit(8)->get('quotes')->result_array();
        }

        if ($this->has_permission('audit.view')) {
            $data['recent_activity'] = $this->db->select('audit_logs.*, users.firstName, users.lastName, users.email, users.role')
                                                ->from('audit_logs')
                                                ->join('users', 'users.id = audit_logs.userId', 'left')
                                                ->order_by('audit_logs.createdAt', 'DESC')
                                                ->limit(12)->get()->result_array();
        }

        $counts = [];
        if ($this->has_permission('quotes.manage')) {
            $counts['quotes_total'] = (int) $this->db->count_all('quotes');
            $counts['quotes_new']   = (int) ($data['quote_by_status'][QUOTE_NEW] ?? 0);
        }
        if ($this->has_permission('products.manage')) {
            $counts['products'] = (int) $this->db->where('isActive', 1)->count_all_results('products');
        }
        if ($this->has_permission('contacts.manage')) {
            $counts['contacts_new'] = (int) $this->db->where('status', 'NEW')->count_all_results('contacts');
        }
        if ($this->has_permission('customers.manage')) {
            $counts['customers'] = (int) $this->db->where('role', ROLE_CUSTOMER)->count_all_results('users');
        }
        if ($this->has_permission('pages.manage') && $this->db->table_exists('pages')) {
            $counts['pages'] = (int) $this->db->where('status', 'PUBLISHED')->count_all_results('pages');
        }
        if ($this->has_permission('media.manage')) {
            $counts['media'] = (int) $this->db->count_all('media');
        }
        $data['counts'] = $counts;

        if ($this->has_permission('homepage.manage') && $this->db->table_exists('page_sections')) {
            $data['content']['sections_total']  = (int) $this->db->where('pageKey', 'home')->count_all_results('page_sections');
            $data['content']['sections_active'] = (int) $this->db->where(['pageKey' => 'home', 'isActive' => 1])->count_all_results('page_sections');
        }

        if ($this->has_permission('settings.manage')) {
            $data['email_health'] = vp_email_health();
        }

        if ($this->is_super_admin()) {
            $data['admins'] = $this->db->where_in('role', [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_SALES, ROLE_ENGINEER, ROLE_EDITOR])
                                       ->order_by('lastLoginAt', 'DESC')->limit(6)->get('users')->result_array();
            $data['failed_logins'] = (int) $this->db->where('action', AUDIT_LOGIN_FAILED)
                                                    ->where('createdAt >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                                                    ->count_all_results('audit_logs');
        }

        $this->render('admin/dashboard', $data);
    }
}
