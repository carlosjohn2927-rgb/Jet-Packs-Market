<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - autoload configuration.
 */
$autoload['packages'] = [];
$autoload['libraries'] = [
    'database', 'session', 'jet_auth', 'rbac', 'acl', 'rate_limiter', 'mailer',
    // Aliased: the classes are Jet_settings / Jet_audit (so they never collide
    // with the admin Settings / Audit controllers) but stay available as
    // $this->settings and $this->audit everywhere in the application.
    'jet_settings' => 'settings',
    'jet_audit'    => 'audit',
    'jet_assistant',
    'jet_upload',
];
$autoload['drivers'] = [];
$autoload['helper'] = ['url', 'form', 'text', 'date', 'app', 'cms', 'admin_form', 'security_helper'];
$autoload['config'] = [];
$autoload['language'] = ['app_lang'];
$autoload['model'] = [];
