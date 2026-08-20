<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — media library.
 *
 * Upload, replace, rename, delete and re-use files across the whole website.
 * Files referenced by critical settings (logo, favicon) are protected: they
 * cannot be deleted until the setting points somewhere else.
 */
class Media extends Admin_Controller
{
    protected $required_permission = 'media.manage';

    /**
     * The picker is opened from every content editor, so browsing is allowed
     * for any account that can edit content of some kind.
     */
    protected $method_permissions = ['browse' => null];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Media_model');
        $this->load->library('vp_upload');
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    public function index()
    {
        $this->page_title = 'Media library';
        $folder = $this->input->get('folder');
        $search = trim((string) $this->input->get('q'));
        $page   = max(1, (int) $this->input->get('page'));
        $per    = 36;

        $where = [];
        if ($folder) $where['folder'] = $folder;
        $result = $this->Media_model->paginate($where, $per, $page, ['createdAt' => 'DESC'], $search, ['originalName', 'filename', 'alt']);

        $folders = [];
        foreach ($this->db->select('folder')->distinct()->get('media')->result_array() as $r) {
            if (!empty($r['folder'])) $folders[] = $r['folder'];
        }

        $rows = $result['rows'];
        $in_use = $this->_protected_urls();
        foreach ($rows as &$r) {
            $r['in_use'] = in_array($r['url'], $in_use, true);
        }
        unset($r);

        $this->render('admin/media/index', [
            'rows'        => $rows,
            'folders'     => $folders,
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
            'page'        => $result['page'],
            'folder'      => $folder,
            'search'      => $search,
            'base_url'    => base_url('admin/media') . '?' . http_build_query(array_filter(['folder' => $folder, 'q' => $search])) . '&page={page}',
        ]);
    }

    /** JSON feed used by the media picker modal in every editor. */
    public function browse()
    {
        // Any content-editing permission is enough to *pick* an image.
        $allowed = ['media.manage', 'homepage.manage', 'pages.manage', 'appearance.manage',
                    'products.manage', 'blog.manage', 'news.manage', 'partners.manage',
                    'testimonials.manage', 'industries.manage', 'categories.manage'];
        $ok = false;
        foreach ($allowed as $perm) {
            if ($this->has_permission($perm)) { $ok = true; break; }
        }
        if (!$ok) $this->_deny('You do not have permission to browse the media library.');

        $q = trim((string) $this->input->get('q'));
        $this->db->order_by('createdAt', 'DESC')->limit(60);
        if ($q !== '') {
            $this->db->group_start()->like('originalName', $q)->or_like('filename', $q)->or_like('alt', $q)->group_end();
        }
        $items = $this->db->get('media')->result_array();
        $this->json(['ok' => true, 'items' => $items]);
    }

    public function upload()
    {
        if ($this->input->method() !== 'post') show_404();
        $this->require_permission('media.manage');

        $folder = $this->input->post('folder') ?: 'general';
        $ajax   = (bool) $this->input->post('ajax');

        // SVG deliberately excluded: SVG can carry scripts and would be an
        // XSS vector when opened directly.
        $result = $this->vp_upload->handle('file', $folder, 'jpg|jpeg|png|webp|gif|ico|pdf|doc|docx|xls|xlsx|zip|mp4|webm|ogg|mov', 51200);

        if (is_array($result) && empty($result['error'])) {
            $id = $this->Media_model->insert([
                'filename'     => $result['filename'],
                'originalName' => $result['name'],
                'url'          => $result['url'],
                'mimeType'     => $result['mime'],
                'size'         => $result['size'],
                'folder'       => $result['folder'],
                'alt'          => trim((string) $this->input->post('alt')) ?: null,
            ]);
            if (strpos((string) $result['mime'], 'image/') === 0) {
                $this->vp_upload->resize_image($result['path'], 1600);
            }
            $this->audit->log(AUDIT_CREATE, 'media', $id, ['name' => $result['name'], 'folder' => $result['folder']]);

            if ($ajax) {
                return $this->json(['ok' => true, 'url' => $result['url'], 'id' => $id, 'csrf' => $this->security->get_csrf_hash()]);
            }
            $this->flash('success', 'File uploaded.');
        } else {
            $error = is_array($result) ? $result['error'] : 'Upload failed — choose a file first.';
            if ($ajax) return $this->json(['ok' => false, 'error' => $error, 'csrf' => $this->security->get_csrf_hash()], 400);
            $this->flash('error', $error);
        }
        redirect('admin/media?folder=' . urlencode($folder));
    }

    /** Replace the binary of an existing library item, keeping its URL usage. */
    public function replace($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Media_model->find($id) : null;
        if (!$row) show_404();

        $result = $this->vp_upload->handle('file', $row['folder'] ?: 'general', 'jpg|jpeg|png|webp|gif|ico|pdf|doc|docx|xls|xlsx|zip', 16384);
        if (!is_array($result) || !empty($result['error'])) {
            $this->flash('error', is_array($result) ? $result['error'] : 'Upload failed.');
            return redirect('admin/media');
        }

        $old = VP_UPLOAD_PATH . $row['folder'] . '/' . $row['filename'];
        if (is_file($old)) @unlink($old);

        $this->Media_model->update($id, [
            'filename'     => $result['filename'],
            'originalName' => $result['name'],
            'url'          => $result['url'],
            'mimeType'     => $result['mime'],
            'size'         => $result['size'],
        ]);

        // Keep every setting that pointed at the old file pointing at the new one.
        foreach ($this->db->like('value', $row['url'])->get('settings')->result_array() as $s) {
            if ($s['value'] === $row['url']) {
                $this->db->update('settings', ['value' => $result['url']], ['id' => $s['id']]);
            }
        }
        $this->settings->clear_cache();

        $this->audit->log(AUDIT_UPDATE, 'media', $id, ['replaced' => $row['originalName'], 'with' => $result['name']]);
        $this->flash('success', 'File replaced everywhere it was used.');
        redirect('admin/media');
    }

    /** Rename / alt text. */
    public function update($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Media_model->find($id) : null;
        if (!$row) show_404();

        $data = [
            'originalName' => trim((string) $this->input->post('originalName')) ?: $row['originalName'],
            'alt'          => trim((string) $this->input->post('alt')) ?: null,
        ];
        if ($this->db->field_exists('title', 'media')) {
            $data['title'] = trim((string) $this->input->post('title')) ?: null;
        }
        $this->Media_model->update($id, $data);
        $this->audit->log(AUDIT_UPDATE, 'media', $id, ['name' => $data['originalName']]);
        $this->flash('success', 'File details updated.');
        redirect('admin/media');
    }

    public function delete($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Media_model->find($id) : null;
        if (!$row) show_404();

        if (in_array($row['url'], $this->_protected_urls(), true)) {
            $this->flash('error', 'This file is in use as the logo/favicon. Point that setting somewhere else first.');
            return redirect('admin/media');
        }

        $path = VP_UPLOAD_PATH . $row['folder'] . '/' . $row['filename'];
        if (is_file($path)) @unlink($path);
        $this->Media_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'media', $id, ['name' => $row['originalName']]);
        $this->flash('success', 'File deleted.');
        redirect('admin/media?folder=' . urlencode((string) $row['folder']));
    }

    /** URLs that critical settings depend on — never deletable. */
    private function _protected_urls()
    {
        $keys = ['logo_light', 'logo_dark', 'logo_footer', 'favicon', 'seo_og_image', 'seo_schema_logo'];
        $out = [];
        foreach ($keys as $k) {
            $v = (string) $this->settings->get($k, '');
            if ($v !== '') $out[] = $v;
        }
        return $out;
    }
}
