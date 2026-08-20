<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — logo, favicon, colours, header and footer management.
 *
 * Everything edited here is stored in the `settings` table and read by the
 * public theme through the CMS helper, so a change is live immediately and
 * no logo path is hard-coded anywhere in the views.
 */
class Appearance extends Admin_Controller
{
    protected $required_permission = 'appearance.manage';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Media_model');
        $this->load->library('vp_upload');
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    /* ---------------- Branding: logo + favicon ------------------------- */

    public function index()
    {
        $this->page_title = 'Logo & branding';
        $this->render('admin/appearance/branding', [
            'site' => vp_site(),
        ]);
    }

    public function save_branding()
    {
        if ($this->input->method() !== 'post') show_404();

        $map = [
            'logo_light'  => 'STRING',
            'logo_dark'   => 'STRING',
            'logo_footer' => 'STRING',
            'favicon'     => 'STRING',
            'logo_alt'    => 'STRING',
            'logo_height' => 'INT',
        ];
        foreach ($map as $key => $type) {
            $this->settings->set($key, (string) $this->input->post($key), $type, 'BRANDING');
        }

        // Identity fields live in the same screen for convenience.
        $this->settings->set('site_name', trim((string) $this->input->post('site_name')), 'STRING', 'WEBSITE');
        $this->settings->set('site_title', trim((string) $this->input->post('site_title')), 'STRING', 'WEBSITE');
        $this->settings->set('site_description', trim((string) $this->input->post('site_description')), 'TEXT', 'WEBSITE');

        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'BRANDING']);
        $this->flash('success', 'Branding saved — the public website now uses it.');
        redirect('admin/appearance');
    }

    /**
     * Direct upload of a logo/favicon straight into the media library and the
     * matching setting (so there is no two-step dance for the common case).
     */
    public function upload()
    {
        if ($this->input->method() !== 'post') show_404();
        $target = (string) $this->input->post('target');
        $allowed = ['logo_light', 'logo_dark', 'logo_footer', 'favicon'];
        if (!in_array($target, $allowed, true)) {
            $this->flash('error', 'Unknown upload target.');
            return redirect('admin/appearance');
        }

        $types = $target === 'favicon' ? 'png|ico|jpg|jpeg|gif|webp' : 'png|jpg|jpeg|webp|gif';
        $result = $this->vp_upload->handle('file', 'branding', $types, 4096);
        if (!is_array($result) || !empty($result['error'])) {
            $this->flash('error', is_array($result) ? $result['error'] : 'Upload failed.');
            return redirect('admin/appearance');
        }

        $media_id = $this->Media_model->insert([
            'filename'     => $result['filename'],
            'originalName' => $result['name'],
            'url'          => $result['url'],
            'mimeType'     => $result['mime'],
            'size'         => $result['size'],
            'folder'       => 'branding',
            'alt'          => $target,
        ]);

        $this->settings->set($target, $result['url'], 'STRING', 'BRANDING');
        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', $media_id, ['key' => $target, 'value' => $result['url']]);
        $this->flash('success', ucfirst(str_replace('_', ' ', $target)) . ' updated.');
        redirect('admin/appearance');
    }

    public function remove()
    {
        if ($this->input->method() !== 'post') show_404();
        $target = (string) $this->input->post('target');
        if (!in_array($target, ['logo_light', 'logo_dark', 'logo_footer', 'favicon'], true)) show_404();
        $this->settings->set($target, '', 'STRING', 'BRANDING');
        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['key' => $target, 'value' => '']);
        $this->flash('success', 'Removed — the built-in default is used again.');
        redirect('admin/appearance');
    }

    /* ---------------- Header & footer ---------------------------------- */

    public function header()
    {
        $this->page_title = 'Header & footer';
        $this->render('admin/appearance/header', [
            'site'   => vp_site(),
            'social' => vp_social_links(),
        ]);
    }

    public function save_header()
    {
        if ($this->input->method() !== 'post') show_404();

        // Header
        $this->settings->set('header_cta_enabled', $this->input->post('header_cta_enabled') ? '1' : '0', 'BOOL', 'HEADER');
        $this->settings->set('header_cta_label', trim((string) $this->input->post('header_cta_label')), 'STRING', 'HEADER');
        $this->settings->set('header_cta_url', trim((string) $this->input->post('header_cta_url')), 'STRING', 'HEADER');
        $this->settings->set('header_topbar_enabled', $this->input->post('header_topbar_enabled') ? '1' : '0', 'BOOL', 'HEADER');
        $this->settings->set('header_topbar_text', trim((string) $this->input->post('header_topbar_text')), 'STRING', 'HEADER');

        // Footer
        $this->settings->set('footer_about', trim((string) $this->input->post('footer_about')), 'TEXT', 'FOOTER');
        $this->settings->set('footer_copyright', trim((string) $this->input->post('footer_copyright')), 'STRING', 'FOOTER');
        $this->settings->set('footer_note', trim((string) $this->input->post('footer_note')), 'STRING', 'FOOTER');
        $this->settings->set('footer_newsletter_enabled', $this->input->post('footer_newsletter_enabled') ? '1' : '0', 'BOOL', 'FOOTER');

        // Contact block (header + footer both use it)
        $this->settings->set('contact_email', trim((string) $this->input->post('contact_email')), 'STRING', 'CONTACT');
        $this->settings->set('phone', trim((string) $this->input->post('phone')), 'STRING', 'CONTACT');
        $this->settings->set('address', trim((string) $this->input->post('address')), 'STRING', 'CONTACT');
        $this->settings->set('contact_hours', trim((string) $this->input->post('contact_hours')), 'STRING', 'CONTACT');

        // Social links
        $social = [];
        foreach (['linkedin', 'twitter', 'facebook', 'youtube', 'instagram', 'telegram', 'whatsapp'] as $n) {
            $val = trim((string) $this->input->post('social_' . $n));
            $this->settings->set('social_' . $n, $val, 'STRING', 'SOCIAL');
            if ($val !== '') $social[$n] = $val;
        }
        // Keep the legacy JSON blob in sync too, so every footer/social reader
        // sees the same values no matter which admin screen saved them.
        $this->settings->set('social', json_encode($social, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'JSON', 'SOCIAL');

        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'HEADER_FOOTER']);
        $this->flash('success', 'Header and footer updated — visible on the website now.');
        redirect('admin/appearance/header');
    }

    /* ---------------- Colours ------------------------------------------ */

    public function colors()
    {
        $this->page_title = 'Colours';
        $this->render('admin/appearance/colors', [
            'theme'    => vp_theme(),
            'defaults' => vp_theme_defaults(),
        ]);
    }

    public function save_colors()
    {
        if ($this->input->method() !== 'post') show_404();

        $defaults = vp_theme_defaults();
        $map = [
            'theme_bg'              => $defaults['bg'],
            'theme_writeup'         => $defaults['writeup'],
            'theme_sidebar_bg'      => $defaults['sidebar_bg'],
            'theme_sidebar_writeup' => $defaults['sidebar_writeup'],
        ];

        if ($this->input->post('reset')) {
            foreach ($map as $key => $fallback) {
                $this->settings->set($key, $fallback, 'STRING', 'THEME');
            }
            $this->settings->clear_cache();
            $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'THEME', 'reset' => 1]);
            $this->flash('success', 'Colours reset to the defaults (white write-up on the site, black sidebar with white menu text).');
            redirect('admin/appearance/colors');
            return;
        }

        foreach ($map as $key => $fallback) {
            $this->settings->set($key, vp_sanitize_hex_color($this->input->post($key), $fallback), 'STRING', 'THEME');
        }

        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'THEME']);
        $this->flash('success', 'Colours saved — the public website and both dashboards now use them.');
        redirect('admin/appearance/colors');
    }
}
