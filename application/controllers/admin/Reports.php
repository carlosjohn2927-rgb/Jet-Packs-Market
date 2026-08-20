<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — reports.
 *
 * Real aggregate queries over the live tables (no mock data): quote volume,
 * conversion, catalogue health, traffic-driving content and website content
 * counts. Also exports the quote pipeline as CSV.
 */
class Reports extends Admin_Controller
{
    protected $required_permission = 'reports.view';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = 'Reports';

        $days  = max(7, min(365, (int) ($this->input->get('days') ?: 30)));
        $since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));

        /* Quotes per day */
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $series[date('Y-m-d', strtotime("-{$i} days"))] = 0;
        }
        foreach ($this->db->where('createdAt >=', $since)->get('quotes')->result_array() as $q) {
            $d = date('Y-m-d', strtotime($q['createdAt']));
            if (isset($series[$d])) $series[$d]++;
        }

        /* Quote status split */
        $by_status = [];
        foreach ($this->db->select('status, COUNT(*) AS c')->group_by('status')->get('quotes')->result_array() as $r) {
            $by_status[$r['status']] = (int) $r['c'];
        }

        /* Most viewed products */
        $top_products = $this->db->select('name, sku, views, slug')
                                 ->order_by('views', 'DESC')->limit(10)
                                 ->get('products')->result_array();

        /* Category distribution */
        $categories = $this->db->select('categories.name AS name, COUNT(products.id) AS c')
                               ->from('categories')
                               ->join('products', 'products.categoryId = categories.id', 'left')
                               ->group_by('categories.id')
                               ->order_by('c', 'DESC')
                               ->get()->result_array();

        /* Contact messages per day */
        $contacts = (int) $this->db->where('createdAt >=', $since)->count_all_results('contacts');

        /* Website content inventory */
        $content = [
            'pages'     => $this->db->table_exists('pages') ? (int) $this->db->count_all('pages') : 0,
            'published' => $this->db->table_exists('pages') ? (int) $this->db->where('status', 'PUBLISHED')->count_all_results('pages') : 0,
            'sections'  => $this->db->table_exists('page_sections') ? (int) $this->db->count_all('page_sections') : 0,
            'menu'      => $this->db->table_exists('menu_items') ? (int) $this->db->count_all('menu_items') : 0,
            'media'     => (int) $this->db->count_all('media'),
            'blog'      => (int) $this->db->count_all('blog_posts'),
            'news'      => (int) $this->db->count_all('news'),
        ];

        /* Administrator activity in the period */
        $admin_activity = $this->db->select('audit_logs.action AS action, COUNT(*) AS c')
                                   ->where('audit_logs.createdAt >=', $since)
                                   ->group_by('audit_logs.action')
                                   ->order_by('c', 'DESC')
                                   ->limit(12)
                                   ->get('audit_logs')->result_array();

        $this->render('admin/reports/index', [
            'days'           => $days,
            'series'         => $series,
            'by_status'      => $by_status,
            'top_products'   => $top_products,
            'categories'     => $categories,
            'contacts'       => $contacts,
            'content'        => $content,
            'admin_activity' => $admin_activity,
            'totals'         => [
                'quotes'   => (int) $this->db->count_all('quotes'),
                'period'   => array_sum($series),
                'products' => (int) $this->db->count_all('products'),
                'customers'=> (int) $this->db->where('role', ROLE_CUSTOMER)->count_all_results('users'),
            ],
        ]);
    }

    /** CSV export of the quote pipeline (audited). */
    public function export()
    {
        $rows = $this->db->order_by('createdAt', 'DESC')->limit(5000)->get('quotes')->result_array();
        $this->audit->log(AUDIT_EXPORT, 'quotes', null, ['count' => count($rows)]);

        $this->output->set_content_type('text/csv')
                     ->set_header('Content-Disposition: attachment; filename="quotes-' . date('Y-m-d') . '.csv"');

        $fh = fopen('php://output', 'w');
        fputcsv($fh, ['Reference', 'Created', 'Status', 'Contact', 'Email', 'Company', 'Phone', 'Country', 'Total']);
        foreach ($rows as $r) {
            fputcsv($fh, [
                $r['quoteNumber'] ?? substr($r['id'], 0, 8),
                $r['createdAt'],
                $r['status'],
                $r['contactPerson'] ?? '',
                $r['email'] ?? '',
                $r['companyName'] ?? '',
                $r['phone'] ?? '',
                $r['country'] ?? '',
                $r['totalAmount'] ?? '',
            ]);
        }
        fclose($fh);
    }
}
