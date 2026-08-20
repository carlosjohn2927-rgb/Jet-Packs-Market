<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - SEO settings (search engine optimisation).
 *
 * Edits the settings-table keys under group "SEO", rendered on every public
 * page via vp_seo_head(). Also drives robots.txt and sitemap.xml.
 */
class Seo extends Admin_Controller
{
    /** Permission enforced server-side for every action (see Admin_Controller). */
    protected $required_permission = 'seo.manage';


    /** Field => [label, type] */
    private $fields = [
        'seo_title_suffix'        => ['Title suffix', 'text'],
        'seo_default_title'       => ['Default title', 'text'],
        'seo_default_description' => ['Default meta description', 'textarea'],
        'seo_keywords'            => ['Meta keywords', 'text'],
        'seo_robots'              => ['Robots directive', 'robots'],
        'seo_canonical_domain'    => ['Canonical domain', 'text'],
        'seo_og_image'            => ['Social share image (og:image)', 'text'],
        'seo_twitter_site'        => ['Twitter @handle', 'text'],
        'seo_facebook_app_id'     => ['Facebook App ID', 'text'],
        'seo_google_verification' => ['Google site verification', 'text'],
        'seo_bing_verification'   => ['Bing site verification', 'text'],
        'seo_enable_jsonld'       => ['Structured data (JSON-LD)', 'bool'],
        'seo_schema_type'         => ['Schema type', 'text'],
        'seo_schema_name'         => ['Schema name', 'text'],
        'seo_schema_logo'         => ['Schema logo URL', 'text'],
        'seo_schema_json'         => ['Custom JSON-LD (advanced)', 'textarea'],
    ];

    public function __construct()
    {
        parent::__construct();
        
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url']);
    }

    public function index()
    {
        $this->page_title = 'SEO Settings';

        // Reflect effective values (defaults included) so the form shows
        // exactly what is being rendered on the public site.
        $cfg = vp_seo_config();
        $values = [
            'seo_title_suffix'        => $cfg['title_suffix'],
            'seo_default_title'       => $cfg['default_title'],
            'seo_default_description' => $cfg['default_description'],
            'seo_keywords'            => $cfg['keywords'],
            'seo_robots'              => $cfg['robots'],
            'seo_canonical_domain'    => $cfg['canonical_domain'],
            'seo_og_image'            => $cfg['og_image'],
            'seo_twitter_site'        => $cfg['twitter_site'],
            'seo_facebook_app_id'     => $cfg['facebook_app_id'],
            'seo_google_verification' => $cfg['google_verification'],
            'seo_bing_verification'   => $cfg['bing_verification'],
            'seo_enable_jsonld'       => $cfg['enable_jsonld'] ? '1' : '0',
            'seo_schema_type'         => $cfg['schema_type'],
            'seo_schema_name'         => $cfg['schema_name'],
            'seo_schema_logo'         => $cfg['schema_logo'],
            'seo_schema_json'         => $cfg['schema_json'],
        ];

        $this->render('admin/seo/index', [
            'fields' => $this->fields,
            'values' => $values,
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();

        $this->form_validation->set_rules('seo_canonical_domain', 'Canonical domain', 'max_length[190]');
        $this->form_validation->set_rules('seo_default_description', 'Default description', 'max_length[500]');
        $this->form_validation->set_rules('seo_keywords', 'Keywords', 'max_length[500]');

        if ($this->form_validation->run() === false) {
            $this->flash('error', 'Please correct the highlighted fields.');
            redirect('admin/seo');
        }

        foreach ($this->fields as $key => $def) {
            $type = 'STRING';
            $value = $this->input->post($key);

            if ($def[1] === 'bool') {
                $type = 'BOOL';
                $value = ($value === '1' || $value === 'on') ? '1' : '0';
            } elseif ($def[1] === 'textarea') {
                $type = 'TEXT';
            }

            // Validate any custom JSON-LD before persisting it.
            if ($key === 'seo_schema_json' && trim((string) $value) !== '' && json_decode((string) $value) === null) {
                $this->flash('error', 'Custom JSON-LD is not valid JSON. Please fix it and try again.');
                redirect('admin/seo');
            }

            $this->settings->set($key, $value === null ? '' : $value, $type, 'SEO');
        }

        $this->settings->clear_cache();
        $this->audit->log(AUDIT_UPDATE, 'settings', null, ['group' => 'SEO']);
        $this->flash('success', 'SEO settings saved.');
        redirect('admin/seo');
    }
}
