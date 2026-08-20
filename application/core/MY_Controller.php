<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - base controller.
 * - Loads shared view data (site_name, meta, user, etc).
 * - Provides render() to wrap content in a layout.
 */
class MY_Controller extends CI_Controller
{
    /** @var string Page title set by child controllers */
    protected $page_title = '';

    /** @var string Page meta description */
    protected $page_description = '';

    /** @var string Layout name ('public' or 'admin') */
    protected $layout = 'public';

    /** @var array Body class hooks for the layout */
    protected $body_class = '';

    /** @var array Data bag passed to views */
    protected $data = [];

    public function __construct()
    {
        parent::__construct();

        // Always have the language file loaded
        $this->lang->load('app_lang');

        // Per-request CSP nonce so JSON-LD structured data (and any future
        // inline script) can be allowed without opening up 'unsafe-inline'.
        $csp_nonce = bin2hex(random_bytes(16));
        $this->_set_security_headers($csp_nonce);

        // Global view data. Site identity/contact/social all come from the
        // dashboard-managed settings (with config/.env as the fallback), so
        // nothing user-facing is hard-coded in the views.
        $site = vp_site();
        $this->data = [
            'site_name'        => $site['name'],
            'site_tagline'     => $site['tagline'],
            'site'             => $site,
            'contact'          => [
                'email'   => $site['email'],
                'phone'   => $site['phone'],
                'address' => $site['address'],
            ],
            'social'           => vp_social_links(),
            'current_user'     => $this->vp_auth->user(),
            'is_admin'         => $this->vp_auth->check() && $this->vp_auth->is_staff(),
            'page_title'       => '',
            'page_description' => '',
            'body_class'       => '',
            'flash'            => $this->_get_flash(),
            'csrf_token_name'  => $this->config->item('csrf_token_name'),
            'csrf_token'       => $this->security->get_csrf_hash(),
            'current_url'      => current_url(),
            'vp_settings'      => $this->settings ? $this->settings->all() : [],
            'seo'              => vp_seo_config(),
            'chat'             => vp_chat_config(),
            'csp_nonce'        => $csp_nonce,
            'unread_notifications' => 0,
        ];

        $this->data['recent_notifications'] = [];
        if ($this->vp_auth->check() && $this->vp_auth->is_staff()) {
            $this->data['unread_notifications'] = $this->_count_unread();
            $this->data['recent_notifications'] = $this->_recent_notifications();
        }

        // Toggle for the WordPress-style inline page editor (?vp_edit=1 to
        // switch editing on, ?vp_edit=0 to switch it off). Kept in the session
        // so the mode survives navigation between pages.
        if ($this->input->get('vp_edit') !== null) {
            $this->session->set_userdata('vp_inline_edit', $this->input->get('vp_edit') === '1' ? 1 : 0);
        }

        $this->_maintenance_gate();
    }

    /**
     * Maintenance mode (Dashboard → Settings → System).
     * Visitors get a 503 maintenance page; staff keep browsing the live site,
     * and the admin area / auth routes always stay reachable.
     */
    private function _maintenance_gate()
    {
        if (!vp_maintenance_active()) return;
        if ($this->vp_auth->check() && $this->vp_auth->is_staff()) return;

        $uri = strtolower((string) $this->uri->uri_string());
        foreach (['admin', 'login', 'logout', 'auth', 'forgot', 'reset', 'assets'] as $allowed) {
            if (strpos($uri, $allowed) === 0) return;
        }

        $this->output->set_status_header(503);
        $this->output->set_header('Retry-After: 3600');
        $this->load->view('errors/maintenance', [
            'site'    => vp_site(),
            'message' => vp_site('maintenance_message', 'We are performing scheduled maintenance.'),
        ]);
        $this->output->_display();
        exit;
    }

    /** Latest notifications for the dashboard bell. */
    private function _recent_notifications()
    {
        if (!$this->db->table_exists('notifications')) return [];
        return $this->db->where('userId', $this->vp_auth->id())
                        ->order_by('createdAt', 'DESC')
                        ->limit(6)
                        ->get('notifications')->result_array();
    }

    /**
     * Emit the Content-Security-Policy header (moved here from .htaccess so we
     * can include a per-request nonce for JSON-LD structured data). Mirrors the
     * original policy; inline scripts are only allowed when they carry the nonce.
     */
    private function _set_security_headers($nonce)
    {
        $csp = "default-src 'self'; base-uri 'self'; object-src 'none'; form-action 'self'; "
             . "img-src 'self' data: blob: https:; font-src 'self' data: https://fonts.gstatic.com https:; "
             . "script-src 'self' 'nonce-{$nonce}' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://code.jquery.com; "
             . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
             . "connect-src 'self' https:; frame-src https://www.youtube.com https://player.vimeo.com https://www.google.com https://maps.google.com https://www.openstreetmap.org";
        $this->output->set_header('Content-Security-Policy: ' . $csp);
    }

    /**
     * Render a view inside a layout.
     *
     * @param string $view   View path relative to application/views (without .php)
     * @param array  $data   Extra data to merge into the view scope
     * @param string $layout Layout name: 'public', 'admin', or '' for none
     */
    protected function render($view, $data = [], $layout = null)
    {
        $data = array_merge($this->data, $data);
        $data['page_title']       = $data['page_title'] ?: $this->page_title;
        $data['page_description'] = $data['page_description'] ?: $this->page_description;
        $data['body_class']       = $data['body_class'] ?: $this->body_class;

        // Super Admins and Admins get a contextual "Edit this page" shortcut
        // while browsing the public website. The destination is the editor
        // that owns the current content (CMS page, product, post, settings,
        // etc.), rather than a misleading one-size-fits-all form.
        $data['admin_edit'] = $this->_admin_edit_target($data);

        // WordPress-style inline page editor state.
        $canInline = $this->_inline_can();
        $capable   = $this->_inline_capable($data);
        $editing   = $canInline && $capable && (int) $this->session->userdata('vp_inline_edit') === 1;
        $data['inline_edit_can']     = $canInline && $capable;
        $data['inline_edit_capable'] = $capable;
        $data['inline_edit']         = $editing;
        $data['inline_edit_url']     = vp_with_query('vp_edit', $editing ? '0' : '1');
        $data['inline_builder_url']  = ($canInline && $capable) ? $this->_inline_builder_url($data) : null;
        // Mirrored onto the controller so view helpers (vp_inline_editing) see it.
        $this->data['inline_edit']   = $editing;

        $layout = $layout !== null ? $layout : $this->layout;

        // Prepend page-builder blocks on built-in public pages (catalogs, forms)
        // that still keep their native content underneath.
        $ctrl = strtolower((string) $this->router->fetch_class());
        $method = strtolower((string) $this->router->fetch_method());
        $builderPages = ['contact', 'products', 'industries', 'blog', 'news', 'careers', 'faq', 'downloads', 'rfq'];
        if (($layout === 'public') && $method === 'index' && in_array($ctrl, $builderPages, true) && empty($data['cms_sections'])) {
            $data['cms_sections'] = vp_sections($ctrl);
            $data['cms_blocks'] = vp_section_blocks($data['cms_sections']);
        }

        $content = $this->load->view($view, $data, TRUE);
        if (!empty($data['cms_sections'])) {
            $content = $this->load->view('partials/cms_sections', $data, TRUE) . $content;
        }

        if ($layout === '' || $layout === null) {
            $this->output->set_output($content);
            return;
        }

        $data['content'] = $content;
        $this->load->view('layouts/' . $layout, $data);
    }

    /**
     * Resolve the dashboard editor for the current public route.
     *
     * @return array|null ['url' => absolute URL, 'label' => button label]
     */
    private function _admin_edit_target(array $data)
    {
        $user = $this->vp_auth->user();
        $role = $user['role'] ?? '';
        if (!in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN], true)) return null;

        $controller = strtolower((string) $this->router->fetch_class());
        $method     = strtolower((string) $this->router->fetch_method());
        $target     = null;
        $permission = null;

        switch ($controller) {
            case 'home':
                $target = 'admin/homepage/index/home'; $permission = 'homepage.manage';
                break;
            case 'about':
                $target = 'admin/homepage/index/about'; $permission = 'homepage.manage';
                break;
            case 'services':
                $target = 'admin/homepage/index/services'; $permission = 'homepage.manage';
                break;
            case 'page':
            case 'errors':
                if (!empty($data['page']['id'])) {
                    $target = 'admin/pages/edit/' . rawurlencode($data['page']['id']);
                    $permission = 'pages.manage';
                }
                break;
            case 'products':
                if (!empty($data['product']['id'])) {
                    $target = 'admin/products/edit/' . rawurlencode($data['product']['id']);
                    $permission = 'products.manage';
                } else {
                    $target = 'admin/homepage/index/products';
                    $permission = 'homepage.manage';
                }
                break;
            case 'industries':
                $target = !empty($data['industry']['id'])
                    ? 'admin/industries/edit/' . rawurlencode($data['industry']['id'])
                    : 'admin/industries';
                $permission = 'industries.manage';
                break;
            case 'blog':
                if (!empty($data['post']['id'])) {
                    $target = 'admin/blog/edit/' . rawurlencode($data['post']['id']);
                    $permission = 'blog.manage';
                } else {
                    $target = 'admin/homepage/index/blog';
                    $permission = 'homepage.manage';
                }
                break;
            case 'news':
                $target = !empty($data['row']['id'])
                    ? 'admin/news/edit/' . rawurlencode($data['row']['id'])
                    : 'admin/news';
                $permission = 'news.manage';
                break;
            case 'careers':
                $target = !empty($data['job']['id'])
                    ? 'admin/careers/edit/' . rawurlencode($data['job']['id'])
                    : 'admin/careers';
                $permission = 'careers.manage';
                break;
            case 'faq':
                $target = 'admin/homepage/index/faq'; $permission = 'homepage.manage';
                break;
            case 'downloads':
                $target = 'admin/homepage/index/downloads'; $permission = 'homepage.manage';
                break;
            case 'contact':
                $target = 'admin/homepage/index/contact'; $permission = 'homepage.manage';
                break;
            case 'rfq':
                if ($method === 'index') {
                    $target = 'admin/homepage/index/rfq'; $permission = 'homepage.manage';
                }
                break;
            case 'blog':
                if (empty($data['post']['id'])) {
                    $target = 'admin/homepage/index/blog'; $permission = 'homepage.manage';
                }
                break;
        }

        if (!$target || !$permission || !$this->acl->user_can($user, $permission)) return null;
        return ['url' => base_url($target), 'label' => 'Edit this page'];
    }

    /**
     * Whether the signed-in account may use the inline page editor at all
     * (a staff account holding either the homepage or pages edit permission).
     */
    private function _inline_can()
    {
        if (!$this->vp_auth->check() || !$this->vp_auth->is_staff()) return false;
        $user = $this->vp_auth->user();
        return $this->acl->user_can($user, 'homepage.manage') || $this->acl->user_can($user, 'pages.manage');
    }

    /**
     * Whether the current route renders page-builder sections that the inline
     * editor can edit. Detail views (product/blog/career/industry/news) keep
     * the normal deep-link editor instead.
     */
    private function _inline_capable(array $data)
    {
        $controller = strtolower((string) $this->router->fetch_class());
        $method     = strtolower((string) $this->router->fetch_method());

        if ($controller === 'page' && $method === 'view') return true;
        if ($controller === 'errors' && $method === 'not_found') return !empty($data['page']['slug']);

        $listing = ['home', 'about', 'services', 'products', 'industries', 'contact',
                    'blog', 'news', 'careers', 'faq', 'downloads', 'rfq'];
        return $method === 'index' && in_array($controller, $listing, true);
    }

    /**
     * Dashboard page-builder URL for the current route (the "Manage sections"
     * shortcut shown while inline editing).
     */
    private function _inline_builder_url(array $data)
    {
        $controller = strtolower((string) $this->router->fetch_class());

        if ($controller === 'page' || $controller === 'errors') {
            $slug = (string) ($data['page']['slug'] ?? '');
            if ($slug === '') return null;
            return base_url('admin/homepage/index/page:' . rawurlencode($slug));
        }

        $listing = ['home', 'about', 'services', 'products', 'industries', 'contact',
                    'blog', 'news', 'careers', 'faq', 'downloads', 'rfq'];
        if (in_array($controller, $listing, true)) {
            return base_url('admin/homepage/index/' . $controller);
        }
        return null;
    }

    /**
     * Render JSON response and exit.
     */
    protected function json($payload, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Set a flash message visible on the next request.
     */
    protected function flash($type, $message)
    {
        $this->session->set_flashdata('vp_flash', ['type' => $type, 'message' => $message]);
    }

    /**
     * Get and clear the current flash message.
     */
    private function _get_flash()
    {
        $f = $this->session->flashdata('vp_flash');
        return $f ?: null;
    }

    /**
     * Count unread admin notifications.
     */
    private function _count_unread()
    {
        if (!$this->db->table_exists('notifications')) return 0;
        return (int) $this->db->where('userId', $this->vp_auth->id())
                              ->where('read', 0)
                              ->count_all_results('notifications');
    }

    /**
     * Send a notification to every staff user, or to a single userId.
     *
     * @param string $type      Short type tag, e.g. 'rfq_new', 'rfq_assigned'
     * @param string $title
     * @param string $message
     * @param array  $data      Extra context (e.g. ['quoteId' => '...'])
     * @param string|null $userId  Null = broadcast to all staff. Else single user.
     * @return int  Number of notifications created.
     */
    protected function notify($type, $title, $message, array $data = [], $userId = null)
    {
        if (!$this->db->table_exists('notifications')) return 0;
        $now = date('Y-m-d H:i:s');
        if ($userId) {
            $users = [['id' => $userId]];
        } else {
            $users = $this->db->where_in('role', [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_SALES])
                              ->where('isActive', 1)
                              ->get('users')->result_array();
        }
        $count = 0;
        foreach ($users as $u) {
            $this->db->insert('notifications', [
                'id'        => MY_Model::uuid(),
                'userId'    => $u['id'],
                'type'      => $type,
                'title'     => $title,
                'message'   => $message,
                'data'      => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'read'      => 0,
                'createdAt' => $now,
            ]);
            $count++;
        }
        return $count;
    }
}
