<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — CMS pages.
 *
 * Pages created here are published on the public website at /{slug} and can
 * be linked from any navigation menu.
 */
class Pages extends Admin_Controller
{
    protected $required_permission = 'pages.manage';

    /** Slugs that belong to built-in controllers and cannot be taken. */
    private $reserved = [
        'admin', 'login', 'logout', 'register', 'forgot', 'reset', 'products', 'industries',
        'services', 'about', 'contact', 'rfq', 'blog', 'news', 'careers', 'faq', 'downloads',
        'search', 'chat', 'assets', 'sitemap.xml', 'robots.txt', 'index.php',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Page_model');
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url', 'security_helper', 'cms_schema_helper']);
        vp_ensure_cms_tables();
    }

    public function index()
    {
        $this->page_title = 'Pages';
        $search = trim((string) $this->input->get('q'));
        $page   = max(1, (int) $this->input->get('page'));
        $per    = 25;

        $result = $this->Page_model->paginate([], $per, $page, ['sortOrder' => 'ASC'], $search, ['title', 'slug']);

        $this->render('admin/pages/index', [
            'rows'        => $result['rows'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
            'page'        => $result['page'],
            'search'      => $search,
            'base_url'    => base_url('admin/pages') . '?' . http_build_query(array_filter(['q' => $search])) . '&page={page}',
        ]);
    }

    public function create()
    {
        $this->page_title = 'New page';
        $this->render('admin/pages/form', ['row' => null]);
    }

    public function edit($id = null)
    {
        $row = $id ? $this->Page_model->find($id) : null;
        if (!$row) show_404();
        $this->page_title = 'Edit page: ' . $row['title'];
        $this->render('admin/pages/form', ['row' => $row]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();

        $id       = $this->input->post('id');
        $existing = $id ? $this->Page_model->find($id) : null;
        if ($id && !$existing) show_404();

        $title = trim((string) $this->input->post('title'));
        if ($title === '') {
            $this->flash('error', 'A page title is required.');
            return redirect($id ? 'admin/pages/edit/' . $id : 'admin/pages/create');
        }

        $slug = vp_slugify($this->input->post('slug') ?: $title);
        if (in_array($slug, $this->reserved, true)) {
            $this->flash('error', 'That slug is reserved by a built-in section of the website. Choose another one.');
            return redirect($id ? 'admin/pages/edit/' . $id : 'admin/pages/create');
        }
        if ($this->Page_model->slug_taken($slug, $id)) {
            $this->flash('error', 'Another page already uses the slug "' . $slug . '".');
            return redirect($id ? 'admin/pages/edit/' . $id : 'admin/pages/create');
        }

        $status = $this->input->post('status') === 'PUBLISHED' ? 'PUBLISHED' : 'DRAFT';
        $publishedAt = trim((string) $this->input->post('publishedAt'));
        if ($status === 'PUBLISHED' && $publishedAt === '') $publishedAt = date('Y-m-d H:i:s');

        $data = [
            'title'           => $title,
            'slug'            => $slug,
            'excerpt'         => trim((string) $this->input->post('excerpt')) ?: null,
            'content'         => vp_sanitize_html((string) $this->input->post('content', false)),
            'featuredImage'   => trim((string) $this->input->post('featuredImage')) ?: null,
            'template'        => in_array($this->input->post('template'), ['default', 'wide', 'sidebar'], true) ? $this->input->post('template') : 'default',
            'metaTitle'       => trim((string) $this->input->post('metaTitle')) ?: null,
            'metaDescription' => trim((string) $this->input->post('metaDescription')) ?: null,
            'status'          => $status,
            'visibility'      => $this->input->post('visibility') === 'PRIVATE' ? 'PRIVATE' : 'PUBLIC',
            'publishedAt'     => $publishedAt ?: null,
            'showInMenu'      => $this->input->post('showInMenu') ? 1 : 0,
            'sortOrder'       => (int) $this->input->post('sortOrder'),
            'updatedAt'       => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->update('pages', $data, ['id' => $id]);
            $this->audit->log(AUDIT_UPDATE, 'page', $id, ['title' => $title, 'slug' => $slug, 'status' => $status]);
            $this->flash('success', 'Page saved.');
        } else {
            $id = MY_Model::uuid();
            $data['id']        = $id;
            $data['authorId']  = $this->vp_auth->id();
            $data['createdAt'] = date('Y-m-d H:i:s');
            $this->db->insert('pages', $data);
            $this->audit->log(AUDIT_CREATE, 'page', $id, ['title' => $title, 'slug' => $slug, 'status' => $status]);
            $this->flash('success', 'Page created.');
        }

        redirect('admin/pages/edit/' . $id);
    }

    public function toggle($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Page_model->find($id) : null;
        if (!$row) show_404();
        $new = $row['status'] === 'PUBLISHED' ? 'DRAFT' : 'PUBLISHED';
        $this->db->update('pages', [
            'status'      => $new,
            'publishedAt' => $new === 'PUBLISHED' && empty($row['publishedAt']) ? date('Y-m-d H:i:s') : $row['publishedAt'],
            'updatedAt'   => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
        $this->audit->log(AUDIT_UPDATE, 'page', $id, ['status' => $new]);
        $this->flash('success', $new === 'PUBLISHED' ? 'Page published.' : 'Page unpublished.');
        redirect('admin/pages');
    }

    public function delete($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Page_model->find($id) : null;
        if (!$row) show_404();
        if (!empty($row['isSystem']) && !$this->is_super_admin()) {
            $this->flash('error', 'System pages can only be deleted by the Super Admin.');
            return redirect('admin/pages');
        }
        $this->Page_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'page', $id, ['title' => $row['title'], 'slug' => $row['slug']]);
        $this->flash('success', 'Page deleted.');
        redirect('admin/pages');
    }
}
