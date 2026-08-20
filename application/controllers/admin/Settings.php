<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — website settings.
 *
 * One central place for every site-wide value (identity, contact details,
 * social links, email, system/maintenance) so nothing is scattered through
 * the source code. Values are stored in `settings` and read by the public
 * theme through the CMS helper.
 *
 * The System tab includes outgoing SMTP and is available to Admin/Super Admin
 * accounts with settings access. Raw advanced settings remain Super Admin only.
 */
class Settings extends Admin_Controller
{
    protected $required_permission = 'settings.manage';
    protected $method_permissions  = [
        // Admins may manage email/SMTP from the System tab; raw key/value and
        // destructive settings actions remain Super Admin only.
        'system'        => 'settings.manage',
        'save_system'   => 'settings.manage',
        'test_email'    => 'settings.manage',
        'advanced'      => 'system.manage',
        'save_advanced' => 'system.manage',
        'add'           => 'system.manage',
        'delete'        => 'system.manage',
    ];

    /** Tabs rendered by the settings screen. */
    private $tabs = [
        'general'  => ['General',    'ri-global-line'],
        'contact'  => ['Contact',    'ri-phone-line'],
        'social'   => ['Social',     'ri-share-line'],
        'system'   => ['System',     'ri-server-line'],
        'advanced' => ['All values', 'ri-code-s-slash-line'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Setting_model');
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url', 'security_helper']);
    }

    /* ---------------- General ------------------------------------------ */

    public function index()
    {
        $this->page_title = 'Website settings';
        $this->render('admin/settings/general', [
            'tabs'    => $this->_tabs(),
            'tab'     => 'general',
            'site'    => vp_site(),
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();

        $this->settings->set('site_name', trim((string) $this->input->post('site_name')), 'STRING', 'WEBSITE');
        $this->settings->set('site_title', trim((string) $this->input->post('site_title')), 'STRING', 'WEBSITE');
        $this->settings->set('site_tagline', trim((string) $this->input->post('site_tagline')), 'STRING', 'WEBSITE');
        $this->settings->set('site_description', trim((string) $this->input->post('site_description')), 'TEXT', 'WEBSITE');
        $this->settings->set('site_url', rtrim(trim((string) $this->input->post('site_url')), '/'), 'STRING', 'WEBSITE');
        $this->settings->set('site_language', trim((string) $this->input->post('site_language')) ?: 'en', 'STRING', 'WEBSITE');

        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'WEBSITE']);
        $this->flash('success', 'Website settings saved.');
        redirect('admin/settings');
    }

    /* ---------------- Contact ------------------------------------------ */

    public function contact()
    {
        $this->page_title = 'Contact settings';
        $this->render('admin/settings/contact', [
            'tabs' => $this->_tabs(),
            'tab'  => 'contact',
            'site' => vp_site(),
        ]);
    }

    public function save_contact()
    {
        if ($this->input->method() !== 'post') show_404();

        foreach ([
            'contact_email' => 'STRING',
            'support_email' => 'STRING',
            'rfq_email'     => 'STRING',
            'phone'         => 'STRING',
            'address'       => 'STRING',
            'contact_hours' => 'STRING',
        ] as $key => $type) {
            $this->settings->set($key, trim((string) $this->input->post($key)), $type, 'CONTACT');
        }

        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'CONTACT']);
        $this->flash('success', 'Contact information saved — the website footer is updated.');
        redirect('admin/settings/contact');
    }

    /* ---------------- Social ------------------------------------------- */

    public function social()
    {
        $this->page_title = 'Social links';
        $this->render('admin/settings/social', [
            'tabs'   => $this->_tabs(),
            'tab'    => 'social',
            'social' => vp_social_links(),
        ]);
    }

    public function save_social()
    {
        if ($this->input->method() !== 'post') show_404();
        $networks = ['linkedin', 'twitter', 'facebook', 'youtube', 'instagram', 'telegram', 'whatsapp'];
        $combined = [];
        foreach ($networks as $n) {
            $val = trim((string) $this->input->post('social_' . $n));
            $this->settings->set('social_' . $n, $val, 'STRING', 'SOCIAL');
            if ($val !== '') $combined[$n] = $val;
        }
        // Keep the legacy JSON blob in sync for anything still reading it.
        $this->settings->set('social', json_encode($combined, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'JSON', 'SOCIAL');

        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'SOCIAL']);
        $this->flash('success', 'Social links saved.');
        redirect('admin/settings/social');
    }

    /* ---------------- System / SMTP -------------------------------------- */

    public function system()
    {
        $this->page_title = 'System settings';
        $this->render('admin/settings/system', [
            'tabs'   => $this->_tabs(),
            'tab'    => 'system',
            'site'   => vp_site(),
            'email'  => vp_email_health(),
            'values' => [
                'maintenance_mode'    => (string) $this->settings->get('maintenance_mode', '0'),
                'maintenance_message' => (string) $this->settings->get('maintenance_message', ''),
                'chat_enabled'        => (string) $this->settings->get('chat_enabled', '0'),
                'chat_title'          => (string) $this->settings->get('chat_title', ''),
                'chat_bot_name'       => (string) $this->settings->get('chat_bot_name', ''),
                'chat_welcome'        => (string) $this->settings->get('chat_welcome', ''),
                'chat_quick_replies'  => is_array($q = $this->settings->get('chat_quick_replies', []))
                                            ? implode(', ', $q)
                                            : trim((string) $q, '[]"'),
                'chat_rate_limit_per_hour' => (string) $this->settings->get('chat_rate_limit_per_hour', '60'),
                'rfq_enabled'         => (string) $this->settings->get('rfq_enabled', '1'),
                'rfq_admin_email'     => (string) $this->settings->get('rfq_admin_email', ''),
                'rfq_rate_limit_per_hour' => (string) $this->settings->get('rfq_rate_limit_per_hour', '5'),
                'mail_from_email'     => (string) $this->settings->get('mail_from_email', ''),
                'mail_from_name'      => (string) $this->settings->get('mail_from_name', ''),
                'mail_reply_to'       => (string) $this->settings->get('mail_reply_to', ''),
                'smtp_host'           => (string) $this->settings->get('smtp_host', (string) $this->config->item('smtp_host')),
                'smtp_port'           => (string) $this->settings->get('smtp_port', (string) ($this->config->item('smtp_port') ?: '465')),
                'smtp_user'           => (string) $this->settings->get('smtp_user', (string) $this->config->item('smtp_user')),
                'smtp_crypto'         => (string) $this->settings->get('smtp_crypto', (string) ($this->config->item('smtp_crypto') ?: 'ssl')),
                'smtp_has_password'   => (trim((string) $this->settings->get('smtp_pass', '')) !== '' || trim((string) $this->config->item('smtp_pass')) !== '') ? '1' : '0',
            ],
        ]);
    }

    public function save_system()
    {
        if ($this->input->method() !== 'post') show_404();

        $this->settings->set('maintenance_mode', $this->input->post('maintenance_mode') ? '1' : '0', 'BOOL', 'SYSTEM');
        $this->settings->set('maintenance_message', trim((string) $this->input->post('maintenance_message')), 'TEXT', 'SYSTEM');
        $this->settings->set('chat_enabled', $this->input->post('chat_enabled') ? '1' : '0', 'BOOL', 'CHAT');
        $this->settings->set('chat_title', trim((string) $this->input->post('chat_title')), 'STRING', 'CHAT');
        $this->settings->set('chat_bot_name', trim((string) $this->input->post('chat_bot_name')), 'STRING', 'CHAT');
        $this->settings->set('chat_welcome', trim((string) $this->input->post('chat_welcome')), 'TEXT', 'CHAT');
        $quick = array_values(array_filter(array_map('trim', explode(',', (string) $this->input->post('chat_quick_replies')))));
        $this->settings->set('chat_quick_replies', json_encode($quick, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'JSON', 'CHAT');
        $rate = (int) $this->input->post('chat_rate_limit_per_hour');
        $this->settings->set('chat_rate_limit_per_hour', $rate >= 5 ? $rate : 60, 'INT', 'CHAT');
        $this->settings->set('rfq_enabled', $this->input->post('rfq_enabled') ? '1' : '0', 'BOOL', 'RFQ');
        $this->settings->set('rfq_admin_email', trim((string) $this->input->post('rfq_admin_email')), 'STRING', 'RFQ');
        $this->settings->set('rfq_rate_limit_per_hour', (int) $this->input->post('rfq_rate_limit_per_hour'), 'INT', 'RFQ');

        // Outgoing email identity + SMTP server. SMTP password is write-only:
        // blank keeps the existing setting/env value, unless Clear is checked.
        $this->settings->set('mail_from_email', trim((string) $this->input->post('mail_from_email')), 'STRING', 'EMAIL');
        $this->settings->set('mail_from_name', trim((string) $this->input->post('mail_from_name')), 'STRING', 'EMAIL');
        $this->settings->set('mail_reply_to', trim((string) $this->input->post('mail_reply_to')), 'STRING', 'EMAIL');

        $port = (int) $this->input->post('smtp_port');
        if ($port <= 0 || $port > 65535) $port = 465;
        $crypto = (string) $this->input->post('smtp_crypto');
        if (!in_array($crypto, ['ssl', 'tls'], true)) $crypto = 'ssl';
        $this->settings->set('smtp_host', trim((string) $this->input->post('smtp_host')), 'STRING', 'EMAIL');
        $this->settings->set('smtp_port', (string) $port, 'INT', 'EMAIL');
        $this->settings->set('smtp_user', trim((string) $this->input->post('smtp_user')), 'STRING', 'EMAIL');
        $this->settings->set('smtp_crypto', $crypto, 'STRING', 'EMAIL');
        $postedPass = (string) $this->input->post('smtp_pass');
        if ($this->input->post('smtp_clear_password')) {
            $this->settings->set('smtp_pass', '', 'STRING', 'EMAIL');
        } elseif ($postedPass !== '') {
            $this->settings->set('smtp_pass', $postedPass, 'STRING', 'EMAIL');
        }

        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'SYSTEM']);
        $this->flash('success', 'System settings saved.');
        redirect('admin/settings/system');
    }

    public function test_email()
    {
        if ($this->input->method() !== 'post') show_404();
        $to = strtolower(trim((string) $this->input->post('test_email')));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Enter a valid email address for the SMTP test.');
            return redirect('admin/settings/system');
        }

        $site = vp_site('name', 'Website');
        $html = '<p>This is a test email from ' . htmlspecialchars($site, ENT_QUOTES, 'UTF-8') . '.</p>'
              . '<p>If you received it, your outgoing mail settings are working.</p>';
        $result = $this->mailer->send($to, $site . ' SMTP test', $html, 'smtp_test', 'smtp_test:' . $to . ':' . time(), ['source' => 'admin_settings']);
        if (($result['status'] ?? '') === EMAIL_SENT) {
            $this->flash('success', 'Test email sent to ' . $to . '.');
        } else {
            $this->flash('error', 'Test email failed. Check the email health detail and application logs.');
        }
        redirect('admin/settings/system');
    }

    /* ---------------- Advanced key/value -------------------------------- */

    public function advanced()
    {
        $this->page_title = 'All settings';
        $rows = $this->db->order_by('group', 'ASC')->order_by('sortOrder', 'ASC')->order_by('key', 'ASC')
                         ->get('settings')->result_array();
        $grouped = [];
        foreach ($rows as $r) $grouped[$r['group']][] = $r;

        $this->render('admin/settings/index', [
            'tabs'    => $this->_tabs(),
            'tab'     => 'advanced',
            'grouped' => $grouped,
        ]);
    }

    /**
     * Bulk save of the raw key/value editor.
     */
    public function save_advanced()
    {
        if ($this->input->method() !== 'post') show_404();
        $keys   = (array) $this->input->post('key');
        $values = (array) $this->input->post('value');
        $types  = (array) $this->input->post('type');
        $groups = (array) $this->input->post('group');

        $protected = ['maintenance_mode'];   // needs system.manage
        $count = 0;
        foreach ($keys as $i => $k) {
            $k = trim((string) $k);
            if ($k === '') continue;
            if (in_array($k, $protected, true) && !$this->has_permission('system.manage')) continue;
            $this->settings->set($k, $values[$i] ?? '', $types[$i] ?? 'STRING', $groups[$i] ?? 'GENERAL');
            $count++;
        }
        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['count' => $count, 'via' => 'advanced']);
        $this->flash('success', "Saved {$count} settings.");
        redirect('admin/settings/advanced');
    }

    /** Add a brand new setting key from the advanced tab. */
    public function add()
    {
        if ($this->input->method() !== 'post') show_404();
        $key = trim((string) $this->input->post('new_key'));
        if ($key === '' || !preg_match('/^[a-z0-9_\.]+$/i', $key)) {
            $this->flash('error', 'Use letters, numbers, dots and underscores for the key.');
            return redirect('admin/settings/advanced');
        }
        $this->settings->set($key, (string) $this->input->post('new_value'),
            (string) ($this->input->post('new_type') ?: 'STRING'),
            strtoupper((string) ($this->input->post('new_group') ?: 'GENERAL')));
        $this->settings->clear_cache();
        $this->audit->log(AUDIT_CREATE, 'settings', null, ['key' => $key]);
        $this->flash('success', 'Setting added.');
        redirect('admin/settings/advanced');
    }

    public function delete()
    {
        if ($this->input->method() !== 'post') show_404();
        if (!$this->is_super_admin()) {
            $this->_deny('Only the Super Admin can delete settings.');
        }
        $key = trim((string) $this->input->post('key'));
        if ($key === '') show_404();
        $this->db->delete('settings', ['key' => $key]);
        $this->settings->clear_cache();
        $this->audit->log(AUDIT_DELETE, 'settings', null, ['key' => $key]);
        $this->flash('success', 'Setting deleted.');
        redirect('admin/settings/advanced');
    }

    /* -------------------------------------------------------------------- */

    private function _tabs()
    {
        $out = [];
        foreach ($this->tabs as $key => $def) {
            if ($key === 'advanced' && !$this->has_permission('system.manage')) continue;
            $out[$key] = [
                'label' => $def[0],
                'icon'  => $def[1],
                'url'   => $key === 'general' ? 'admin/settings' : 'admin/settings/' . $key,
            ];
        }
        return $out;
    }
}
