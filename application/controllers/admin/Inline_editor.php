<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — front-end inline page editor (AJAX endpoints).
 *
 * Backs the WordPress-style "Edit this page" mode. An Admin/Super Admin clicks
 * Edit on a section of the live page, rewrites its text and/or changes its
 * colours, and the change is saved straight to the database and re-rendered.
 */
class Inline_editor extends Admin_Controller
{
    /** Permission is enforced per-action (homepage.manage OR pages.manage). */
    protected $required_permission = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Page_section_model');
        $this->load->helper(['form', 'url', 'security_helper', 'cms_schema_helper']);
        vp_ensure_cms_tables();
    }

    /* ------------------------------------------------------------------ */

    private function _authorize()
    {
        $user = $this->vp_auth->user();
        if ($this->acl->user_can($user, 'homepage.manage') || $this->acl->user_can($user, 'pages.manage')) {
            return;
        }
        $this->_deny('You do not have permission to edit pages.');
    }

    /**
     * Save a single page section edited inline on the public website.
     */
    public function section_save()
    {
        if ($this->input->method() !== 'post') show_404();
        $this->_authorize();

        $id  = (string) $this->input->post('id');
        $row = $id !== '' ? $this->Page_section_model->find($id) : null;
        if (!$row) {
            return $this->json(['ok' => false, 'error' => 'Section not found.', 'csrf' => $this->security->get_csrf_hash()], 404);
        }

        $data = [
            'title'       => $this->_clean($this->input->post('title')),
            'subtitle'    => $this->_clean($this->input->post('subtitle')),
            'body'        => $this->_html($this->input->post('body', false)),
            'buttonText'  => $this->_clean($this->input->post('buttonText')),
            'buttonUrl'   => $this->_clean($this->input->post('buttonUrl')),
            'buttonText2' => $this->_clean($this->input->post('buttonText2')),
            'buttonUrl2'  => $this->_clean($this->input->post('buttonUrl2')),
        ];

        // Only the colours are touched inside `settings` — everything else the
        // section stores there (repeatable items, badges, galleries, videos…)
        // is preserved untouched.
        $settings = vp_section_settings($row);
        foreach (['bg_color', 'text_color', 'heading_color'] as $ck) {
            $raw = trim((string) $this->input->post($ck));
            if ($raw === '' || strtolower($raw) === 'default') {
                unset($settings[$ck]);
                continue;
            }
            $hex = vp_sanitize_hex_color($raw, '');
            if ($hex !== '') $settings[$ck] = $hex;
        }
        $data['settings']  = json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $data['updatedAt'] = date('Y-m-d H:i:s');

        $this->db->update('page_sections', $data, ['id' => $id]);
        $this->audit->log(AUDIT_UPDATE, 'page_section', $id, ['type' => $row['type'], 'page' => $row['pageKey'], 'inline' => 1]);
        $this->flash('success', 'Page updated.');

        return $this->json(['ok' => true, 'csrf' => $this->security->get_csrf_hash()]);
    }

    /**
     * Save a single page text block edited inline on the public website.
     * Values live in the `settings` table under a per-page key, so editing a
     * heading/subtitle never requires a code change.
     */
    public function setting_save()
    {
        if ($this->input->method() !== 'post') show_404();
        $this->_authorize();

        $key = trim((string) $this->input->post('key'));
        if ($key === '' || !preg_match('/^[a-zA-Z0-9_.-]{1,80}$/', $key)) {
            return $this->json(['ok' => false, 'error' => 'Invalid field.', 'csrf' => $this->security->get_csrf_hash()], 400);
        }

        $this->settings->set($key, (string) $this->input->post('value'), 'TEXT', 'PAGE');
        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['key' => $key, 'inline' => 1]);
        $this->flash('success', 'Text updated.');

        return $this->json(['ok' => true, 'csrf' => $this->security->get_csrf_hash()]);
    }

    /**
     * Save the site-wide page colours edited inline (background + write-up).
     */
    public function theme_save()
    {
        if ($this->input->method() !== 'post') show_404();
        $this->_authorize();

        $user = $this->vp_auth->user();
        if (!$this->acl->user_can($user, 'appearance.manage') && !$this->acl->user_can($user, 'homepage.manage')) {
            $this->_deny('You do not have permission to change site colours.');
        }

        $defaults = vp_theme_defaults();
        $this->settings->set('theme_bg',      vp_sanitize_hex_color($this->input->post('theme_bg'), $defaults['bg']), 'STRING', 'THEME');
        $this->settings->set('theme_writeup', vp_sanitize_hex_color($this->input->post('theme_writeup'), $defaults['writeup']), 'STRING', 'THEME');
        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'THEME', 'inline' => 1]);
        $this->flash('success', 'Colours updated.');

        return $this->json(['ok' => true, 'csrf' => $this->security->get_csrf_hash()]);
    }

    /* ------------------------------------------------------------------ */

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
}
