<?php /** @var array $fields @var array $values */ ?>
<div class="flex items-start justify-between gap-4 mb-4">
    <div>
        <h2 class="text-lg font-bold text-ink-900">Search engine optimisation</h2>
        <p class="text-sm text-ink-800 mt-1">
            These values drive the <code>&lt;title&gt;</code>, meta description, canonical URL, Open Graph, Twitter Card,
            site verification and JSON-LD structured data on every public page. A <code>robots.txt</code> and
            <code>sitemap.xml</code> are generated automatically.
        </p>
        <div class="flex gap-3 mt-3 text-sm">
            <a class="vp-btn vp-btn-secondary" href="<?= base_url('robots.txt') ?>" target="_blank">View robots.txt</a>
            <a class="vp-btn vp-btn-secondary" href="<?= base_url('sitemap.xml') ?>" target="_blank">View sitemap.xml</a>
        </div>
    </div>
</div>

<form method="post" action="<?= base_url('admin/seo/save') ?>">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">

    <div class="vp-card vp-card-pad mb-4">
        <h3 class="font-semibold mb-3">Basics</h3>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_default_title', 'field' => $fields['seo_default_title'], 'value' => $values['seo_default_title']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_title_suffix', 'field' => $fields['seo_title_suffix'], 'value' => $values['seo_title_suffix']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_default_description', 'field' => $fields['seo_default_description'], 'value' => $values['seo_default_description']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_keywords', 'field' => $fields['seo_keywords'], 'value' => $values['seo_keywords']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_robots', 'field' => $fields['seo_robots'], 'value' => $values['seo_robots']]); ?>
    </div>

    <div class="vp-card vp-card-pad mb-4">
        <h3 class="font-semibold mb-3">Canonical &amp; social sharing</h3>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_canonical_domain', 'field' => $fields['seo_canonical_domain'], 'value' => $values['seo_canonical_domain']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_og_image', 'field' => $fields['seo_og_image'], 'value' => $values['seo_og_image']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_twitter_site', 'field' => $fields['seo_twitter_site'], 'value' => $values['seo_twitter_site']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_facebook_app_id', 'field' => $fields['seo_facebook_app_id'], 'value' => $values['seo_facebook_app_id']]); ?>
    </div>

    <div class="vp-card vp-card-pad mb-4">
        <h3 class="font-semibold mb-3">Search engine verification</h3>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_google_verification', 'field' => $fields['seo_google_verification'], 'value' => $values['seo_google_verification']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_bing_verification', 'field' => $fields['seo_bing_verification'], 'value' => $values['seo_bing_verification']]); ?>
    </div>

    <div class="vp-card vp-card-pad mb-4">
        <h3 class="font-semibold mb-3">Structured data (JSON-LD)</h3>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_enable_jsonld', 'field' => $fields['seo_enable_jsonld'], 'value' => $values['seo_enable_jsonld']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_schema_type', 'field' => $fields['seo_schema_type'], 'value' => $values['seo_schema_type']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_schema_name', 'field' => $fields['seo_schema_name'], 'value' => $values['seo_schema_name']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_schema_logo', 'field' => $fields['seo_schema_logo'], 'value' => $values['seo_schema_logo']]); ?>
        <?php $this->load->view('admin/seo/_seo_field', ['key' => 'seo_schema_json', 'field' => $fields['seo_schema_json'], 'value' => $values['seo_schema_json']]); ?>
    </div>

    <button class="vp-btn vp-btn-primary" type="submit">Save SEO settings</button>
</form>
