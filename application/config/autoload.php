<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - autoload configuration.
 */
$autoload['packages'] = [];
$autoload['libraries'] = [
    'database', 'session', 'vp_auth', 'rbac', 'acl', 'rate_limiter', 'mailer',
    // Aliased: the classes are Vp_settings / Vp_audit (so they never collide
    // with the admin Settings / Audit controllers) but stay available as
    // $this->settings and $this->audit everywhere in the application.
    'vp_settings' => 'settings',
    'vp_audit'    => 'audit',
];
$autoload['drivers'] = [];
$autoload['helper'] = ['url', 'form', 'text', 'date', 'app', 'cms', 'admin_form', 'security_helper'];
$autoload['config'] = [];
$autoload['language'] = ['app_lang'];
$autoload['model'] = [];
