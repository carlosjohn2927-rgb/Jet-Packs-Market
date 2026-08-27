<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — Homepage / page-section builder.
 *
 * Every block on the public homepage is a row in `page_sections`. Sections can
 * be added, edited, reordered, enabled/disabled and deleted here, and the
 * public homepage renders exactly what this screen contains — nothing about
 * the homepage is hard-coded.
 */
class Homepage extends Admin_Controller
{
    protected $required_permission = 'homepage.manage';

    /** Built-in website pages this builder can take over. */
    private $page_keys = [
        'home'       => 'Homepage',
        'about'      => 'About',
        'services'   => 'Services',
        'products'   => 'Products',
        'industries' => 'Industries',
        'contact'    => 'Contact',
        'blog'       => 'Blog',
        'news'       => 'News',
        'careers'    => 'Careers',
        'faq'        => 'FAQ',
        'downloads'  => 'Downloads',
        'rfq'        => 'Request a quote',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Page_section_model');
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url', 'security_helper', 'cms_schema_helper']);
        vp_ensure_cms_tables();
    }

    /* ------------------------------------------------------------------ */

    public function index($pageKey = 'home')
    {
        $pageKey = $this->_page_key($pageKey);
        $labels = $this->_all_page_keys();
        $this->page_title = ($labels[$pageKey] ?? 'Page') . ' — page builder';

        $this->render('admin/homepage/index', [
            'pageKey'    => $pageKey,
            'page_keys'  => $this->_all_page_keys(),
            'preview'    => $this->_preview_url($pageKey),
            'sections'   => $this->Page_section_model->for_page($pageKey),
            'types'      => vp_section_types(),
        ]);
    }

    public function create($pageKey = 'home')
    {
        $pageKey = $this->_page_key($pageKey);
        $type = $this->input->get('type') ?: 'richtext';
        if (!array_key_exists($type, vp_section_types())) $type = 'richtext';

        $this->page_title = 'Add section';
        $this->render('admin/homepage/form', [
            'row'     => null,
            'type'    => $type,
            'pageKey' => $pageKey,
            'types'   => vp_section_types(),
        ]);
    }

    public function edit($id = null)
    {
        $row = $id ? $this->Page_section_model->find($id) : null;
        if (!$row) show_404();
        $this->page_title = 'Edit section: ' . ($row['name'] ?: vp_section_type_label($row['type']));
        $this->render('admin/homepage/form', [
            'row'     => $row,
            'type'    => $row['type'],
            'pageKey' => $row['pageKey'],
            'types'   => vp_section_types(),
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();

        $id      = $this->input->post('id');
        $pageKey = $this->_page_key($this->input->post('pageKey'));
        $type    = (string) $this->input->post('type');
        if (!array_key_exists($type, vp_section_types())) {
            $this->flash('error', 'Unknown section type.');
            return redirect('admin/homepage');
        }

        $existing = $id ? $this->Page_section_model->find($id) : null;
        if ($id && !$existing) show_404();

        $data = [
            'pageKey'     => $pageKey,
            'type'        => $type,
            'name'        => trim((string) $this->input->post('name')) ?: vp_section_type_label($type),
            'title'       => $this->_clean($this->input->post('title')),
            'subtitle'    => $this->_clean($this->input->post('subtitle')),
            'body'        => $this->_html($this->input->post('body', false)),
            'image'       => $this->_clean($this->input->post('image')),
            'buttonText'  => $this->_clean($this->input->post('buttonText')),
            'buttonUrl'   => $this->_clean($this->input->post('buttonUrl')),
            'buttonText2' => $this->_clean($this->input->post('buttonText2')),
            'buttonUrl2'  => $this->_clean($this->input->post('buttonUrl2')),
            'isActive'    => $this->input->post('isActive') ? 1 : 0,
            'settings'    => json_encode($this->_collect_settings($type), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];

        if ($existing) {
            $data['updatedAt'] = date('Y-m-d H:i:s');
            $this->db->update('page_sections', $data, ['id' => $id]);
            $this->audit->log(AUDIT_UPDATE, 'page_section', $id, ['type' => $type, 'page' => $pageKey]);
            $this->flash('success', 'Section saved — the public page is updated.');
        } else {
            $id = MY_Model::uuid();
            $data['id']        = $id;
            $data['sortOrder'] = $this->Page_section_model->next_order($pageKey);
            $data['createdAt'] = date('Y-m-d H:i:s');
            $data['updatedAt'] = $data['createdAt'];
            $this->db->insert('page_sections', $data);
            $this->audit->log(AUDIT_CREATE, 'page_section', $id, ['type' => $type, 'page' => $pageKey]);
            $this->flash('success', 'Section added.');
        }

        redirect('admin/homepage/edit/' . $id);
    }

    public function toggle($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Page_section_model->find($id) : null;
        if (!$row) show_404();
        $new = empty($row['isActive']) ? 1 : 0;
        $this->db->update('page_sections', ['isActive' => $new, 'updatedAt' => date('Y-m-d H:i:s')], ['id' => $id]);
        $this->audit->log(AUDIT_UPDATE, 'page_section', $id, ['isActive' => $new]);
        $this->flash('success', $new ? 'Section is now visible on the website.' : 'Section hidden from the website.');
        redirect('admin/homepage/index/' . $row['pageKey']);
    }

    public function move($id = null, $dir = 'up')
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Page_section_model->find($id) : null;
        if (!$row) show_404();

        $sections = $this->Page_section_model->for_page($row['pageKey']);
        $ids = array_column($sections, 'id');
        $pos = array_search($row['id'], $ids, true);
        $swap = $dir === 'down' ? $pos + 1 : $pos - 1;
        if ($pos !== false && isset($sections[$swap])) {
            $a = $sections[$pos];
            $b = $sections[$swap];
            $this->Page_section_model->reorder([
                $a['id'] => (int) $b['sortOrder'],
                $b['id'] => (int) $a['sortOrder'],
            ]);
            $this->audit->log(AUDIT_UPDATE, 'page_section', $row['id'], ['reorder' => $dir]);
        }
        redirect('admin/homepage/index/' . $row['pageKey']);
    }

    /** Persist a whole new order (drag-and-drop or the order form). */
    public function reorder()
    {
        if ($this->input->method() !== 'post') show_404();
        $order = (array) $this->input->post('order');
        $pageKey = $this->_page_key($this->input->post('pageKey'));
        $map = [];
        $i = 10;
        foreach ($order as $id) {
            $id = (string) $id;
            if ($id === '') continue;
            $map[$id] = $i;
            $i += 10;
        }
        if ($map) {
            $this->Page_section_model->reorder($map);
            $this->audit->log(AUDIT_UPDATE, 'page_section', null, ['reorder' => count($map), 'page' => $pageKey]);
        }
        if ($this->input->post('ajax')) {
            return $this->json(['ok' => true, 'csrf' => $this->security->get_csrf_hash()]);
        }
        $this->flash('success', 'Section order saved.');
        redirect('admin/homepage/index/' . rawurlencode($pageKey));
    }

    public function duplicate($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Page_section_model->find($id) : null;
        if (!$row) show_404();
        $copy = $row;
        unset($copy['id']);
        $copy['id']        = MY_Model::uuid();
        $copy['name']      = trim((string) $row['name']) . ' (copy)';
        $copy['isSystem']  = 0;
        $copy['sortOrder'] = $this->Page_section_model->next_order($row['pageKey']);
        $copy['createdAt'] = date('Y-m-d H:i:s');
        $copy['updatedAt'] = $copy['createdAt'];
        $this->db->insert('page_sections', $copy);
        $this->audit->log(AUDIT_CREATE, 'page_section', $copy['id'], ['duplicate_of' => $id]);
        $this->flash('success', 'Section duplicated.');
        redirect('admin/homepage/edit/' . $copy['id']);
    }

    public function delete($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Page_section_model->find($id) : null;
        if (!$row) show_404();

        if (!empty($row['isSystem']) && !$this->is_super_admin()) {
            $this->flash('error', 'This is a core section — only the Super Admin can delete it.');
            return redirect('admin/homepage/index/' . $row['pageKey']);
        }

        $this->Page_section_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'page_section', $id, ['type' => $row['type'], 'name' => $row['name']]);
        $this->flash('success', 'Section deleted.');
        redirect('admin/homepage/index/' . $row['pageKey']);
    }

    /* ------------------------------------------------------------------ */

    private function _all_page_keys()
    {
        $keys = $this->page_keys;
        if ($this->db->table_exists('pages')) {
            $pages = $this->db->select('slug, title')->order_by('title', 'ASC')->get('pages')->result_array();
            foreach ($pages as $p) {
                $slug = trim((string) $p['slug']);
                if ($slug === '') continue;
                $keys['page:' . $slug] = 'Page: ' . $p['title'];
            }
        }
        return $keys;
    }

    private function _page_key($key)
    {
        $key = rawurldecode((string) $key);
        $all = $this->_all_page_keys();
        return array_key_exists($key, $all) ? $key : 'home';
    }

    private function _preview_url($pageKey)
    {
        if ($pageKey === 'home') return base_url();
        if (strpos($pageKey, 'page:') === 0) return base_url(substr($pageKey, 5));
        return base_url($pageKey);
    }

    private function _clean($v)
    {
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    /** Body content may contain formatting HTML, but never scripts. */
    private function _html($v)
    {
        $v = (string) $v;
        if (trim($v) === '') return null;
        return vp_sanitize_html($v);
    }

    /**
     * Type-specific options stored in the JSON `settings` column.
     */
    private function _collect_settings($type)
    {
        $out = [];
        $limit = (int) $this->input->post('limit');
        if ($limit > 0) $out['limit'] = min(48, $limit);

        $eyebrow = $this->_clean($this->input->post('eyebrow'));
        if ($eyebrow) $out['eyebrow'] = $eyebrow;

        foreach (['bg_color', 'text_color', 'heading_color'] as $ck) {
            $raw = trim((string) $this->input->post($ck));
            if ($raw === '' || strtolower($raw) === 'default') continue;
            $hex = vp_sanitize_hex_color($raw, '');
            if ($hex !== '') $out[$ck] = $hex;
        }

        $video = $this->_clean($this->input->post('video'));
        if ($video) $out['video'] = $video;
        $fileUrl = $this->_clean($this->input->post('fileUrl'));
        if ($fileUrl) $out['fileUrl'] = $fileUrl;
        $fileLabel = $this->_clean($this->input->post('fileLabel'));
        if ($fileLabel) $out['fileLabel'] = $fileLabel;

        $gallery = array_values(array_filter(array_map('trim', (array) $this->input->post('gallery'))));
        if ($gallery) $out['gallery'] = $gallery;

        $badges = array_values(array_filter(array_map('trim', (array) $this->input->post('badges'))));
        if ($badges) $out['badges'] = $badges;

        $style = $this->_clean($this->input->post('style'));
        if ($style) $out['style'] = $style;

        // Repeatable items (stats values, service cards, …)
        $labels = (array) $this->input->post('item_label');
        $values = (array) $this->input->post('item_value');
        $icons  = (array) $this->input->post('item_icon');
        $texts  = (array) $this->input->post('item_text');
        $urls   = (array) $this->input->post('item_url');
        $items = [];
        $count = max(count($labels), count($values), count($icons), count($texts), count($urls));
        for ($i = 0; $i < $count; $i++) {
            $item = array_filter([
                'label' => trim((string) ($labels[$i] ?? '')),
                'value' => trim((string) ($values[$i] ?? '')),
                'icon'  => trim((string) ($icons[$i] ?? '')),
                'text'  => trim((string) ($texts[$i] ?? '')),
                'url'   => trim((string) ($urls[$i] ?? '')),
            ], function ($v) { return $v !== ''; });
            if ($item) $items[] = $item;
        }
        if ($items) $out['items'] = $items;

        return $out;
    }
}
