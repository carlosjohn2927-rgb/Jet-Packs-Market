<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JetPacks Market - CodeIgniter 3 base config.
 *
 * PRODUCTION SECRETS
 * ------------------
 * Portable cPanel deployments use one source of truth: the document-root
 * `.env` file. VP_ENCRYPTION_KEY and VP_AUTH_SECRET must be stable across a
 * migration so existing sessions, remember-me cookies and reset links retain
 * their expected security boundary. The application deliberately does NOT
 * create application/config/.secrets.php or any other hidden server-specific
 * configuration file on first boot.
 */

// --------------------------------------------------------------------
// Helpers
// --------------------------------------------------------------------
if (!function_exists('vp_config_env')) {
    function vp_config_env($key, $default = '')
    {
        // Real environment first, then the values index.php's .env loader
        // stored in $_ENV / $_SERVER (some hosts restrict putenv/getenv).
        foreach ([getenv($key), isset($_ENV[$key]) ? $_ENV[$key] : null, isset($_SERVER[$key]) ? $_SERVER[$key] : null] as $v) {
            if ($v !== false && $v !== null && $v !== '') {
                return $v;
            }
        }
        return $default;
    }
}

if (!function_exists('vp_config_secret_is_valid')) {
    /** Reject empty/template values while allowing existing non-hex legacy secrets. */
    function vp_config_secret_is_valid($value)
    {
        $value = trim((string) $value);
        if (strlen($value) < 32) return false;
        $lower = strtolower($value);
        foreach (['change_me', 'replace_with', 'your_', 'example', 'random_hex', 'xxxxxxxx'] as $needle) {
            if (strpos($lower, $needle) !== false) return false;
        }
        return true;
    }
}

if (!function_exists('vp_config_secrets')) {
    /**
     * Resolve encryption_key + auth_secret strictly from `.env` / environment.
     * Development gets ephemeral values for local experimentation only; they are
     * never written to disk and production always fails closed with a clear
     * File-Manager-editable configuration message.
     *
     * @return array ['encryption_key' => string, 'auth_secret' => string]
     */
    function vp_config_secrets()
    {
        $enc  = vp_config_env('VP_ENCRYPTION_KEY');
        $auth = vp_config_env('VP_AUTH_SECRET');
        if (vp_config_secret_is_valid($enc) && vp_config_secret_is_valid($auth)) {
            return ['encryption_key' => $enc, 'auth_secret' => $auth];
        }

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            $msg = 'JetPacks Market: VP_ENCRYPTION_KEY and VP_AUTH_SECRET must be set to stable, non-placeholder values in .env.';
            error_log($msg);
            if (!headers_sent()) header('HTTP/1.1 503 Service Unavailable', true, 503);
            exit('The application is not configured: set VP_ENCRYPTION_KEY and VP_AUTH_SECRET in .env.');
        }

        // Local development only. No secret file is created, so deployments
        // can never accidentally depend on a machine-generated config file.
        return [
            'encryption_key' => vp_config_secret_is_valid($enc) ? $enc : bin2hex(random_bytes(32)),
            'auth_secret'    => vp_config_secret_is_valid($auth) ? $auth : bin2hex(random_bytes(32)),
        ];
    }
}

// --------------------------------------------------------------------
// Base URL (honour proxies such as Cloudflare)
// --------------------------------------------------------------------
if (!function_exists('vp_config_is_https')) {
    function vp_config_is_https()
    {
        if (vp_config_env('VP_FORCE_HTTPS') === '1') return true;
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') return true;
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (strtolower((string) $proto) === 'https') return true;
        // Cloudflare: HTTP_CF_VISITOR = {"scheme":"https"}
        if (isset($_SERVER['HTTP_CF_VISITOR']) && stripos((string) $_SERVER['HTTP_CF_VISITOR'], 'https') !== false) return true;
        return false;
    }
}

$vp_secrets = vp_config_secrets();
$vp_is_https = vp_config_is_https();

$vp_base_url = vp_config_env('VP_BASE_URL');
if ($vp_base_url === '') {
    $vp_base_url = ($vp_is_https ? 'https' : 'http') . '://'
        . (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '' ? $_SERVER['HTTP_HOST'] : 'localhost')
        . '/';
}
$config['base_url'] = rtrim($vp_base_url, '/') . '/';

$config['index_page'] = '';
$config['uri_protocol'] = 'REQUEST_URI';
$config['url_suffix'] = '';
$config['language'] = 'english';
$config['charset'] = 'UTF-8';
$config['enable_hooks'] = FALSE;
$config['subclass_prefix'] = 'MY_';
$config['composer_autoload'] = FALSE;
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';
$config['allow_get_array'] = TRUE;
$config['enable_query_strings'] = FALSE;
$config['controller_trigger'] = 'c';
$config['function_trigger'] = 'm';
$config['directory_trigger'] = 'd';
$config['log_threshold'] = (int) (vp_config_env('VP_LOG_THRESHOLD') !== '' ? vp_config_env('VP_LOG_THRESHOLD') : '1'); // 0=off, 1=error, 2=debug, 3=info, 4=all
$config['log_path'] = APPPATH . '../assets/logs/';
$config['log_file_extension'] = 'log';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';
$config['error_views_path'] = APPPATH . 'views/errors/';
$config['cache_path'] = APPPATH . '../assets/logs/cache/';

// Cookie & session
$config['cookie_prefix']   = 'vp_';
$config['cookie_domain']   = vp_config_env('VP_COOKIE_DOMAIN'); // e.g. '.yourdomain.com'
$config['cookie_path']     = '/';
$config['cookie_secure']   = $vp_is_https;
$config['cookie_httponly'] = TRUE;
$config['cookie_samesite'] = 'Lax';
$config['sess_driver']     = 'database';
$config['sess_cookie_name'] = 'vp_session';
$config['sess_expiration']  = (int) (vp_config_env('VP_SESSION_EXPIRATION') !== '' ? vp_config_env('VP_SESSION_EXPIRATION') : '7200');
$config['sess_save_path']   = 'ci_sessions';
$config['sess_match_ip']    = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = FALSE;

// CSRF - enabled, rotated per request
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'csrf_token';
$config['csrf_cookie_name'] = 'vp_csrf';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = TRUE;

/**
 * URIs excluded from the global CSRF filter.
 *
 * `chat/message` is a public, read-only, rate-limited JSON endpoint posted to
 * repeatedly from a single page view. Because CodeIgniter rotates the CSRF
 * cookie on every POST, a proxy/CDN that strips the rotated Set-Cookie made the
 * second chat message fail with an HTML 403 the widget could not parse. The
 * controller enforces its own same-origin + rate-limit protection instead and
 * always answers with JSON (see application/controllers/Chat.php).
 *
 * `payments/stripe/webhook` receives server-to-server callbacks from Stripe.
 * It cannot carry a browser CSRF token, so it verifies the Stripe-Signature
 * HMAC over the exact raw request body before processing an event.
 */
$config['csrf_exclude_uris'] = ['chat/message', 'payments/stripe/webhook'];
$config['csrf_use_ssl'] = $vp_is_https;

// Encryption key - NEVER hard-code a production value. See header comment.
$config['encryption_key'] = $vp_secrets['encryption_key'];

// Global XSS filtering (CI default behaviour)
$config['global_xss_filtering'] = FALSE; // We do it explicitly in models/output.

// Timezone
$config['time_reference'] = 'local';

// Rewrite for missing trailing slashes
$config['rewrite_short_tags'] = FALSE;

// Composer autoload - none on shared hosting
$config['composer_autoload'] = FALSE;

// JetPacks Market runtime config (read from environment when present).
// IMPORTANT: these are exposed at the TOP LEVEL of $config because the
// consumers use $this->config->item('key') - CI3 item() does not reach
// into nested arrays.
$config['site_name']       = vp_config_env('VP_SITE_NAME', 'JetPacks Market');
$config['site_tagline']    = vp_config_env('VP_SITE_TAGLINE', 'Aircraft Parts Marketplace');
$config['contact_email']   = vp_config_env('VP_CONTACT_EMAIL', 'sales@jetpacksmarket.com');
$config['support_email']   = vp_config_env('VP_SUPPORT_EMAIL', 'support@jetpacksmarket.com');
$config['rfq_email']       = vp_config_env('VP_RFQ_EMAIL', 'rfq@jetpacksmarket.com');
$config['phone']           = vp_config_env('VP_PHONE', '+1 (214) 350-0107');
$config['address']         = vp_config_env('VP_ADDRESS', 'Hangar 4, Dallas Executive Airport, Dallas, TX 75209, USA');
// Social profiles. Empty values are simply not rendered by the footer, so
// leave a channel blank until the real account exists.
$config['social']          = [
    'linkedin'   => vp_config_env('VP_SOCIAL_LINKEDIN'),
    'twitter'    => vp_config_env('VP_SOCIAL_TWITTER'),
    'facebook'   => vp_config_env('VP_SOCIAL_FACEBOOK'),
    'youtube'    => vp_config_env('VP_SOCIAL_YOUTUBE'),
];
$config['from_email']      = vp_config_env('VP_FROM_EMAIL', 'no-reply@jetpacksmarket.com');
$config['from_name']       = vp_config_env('VP_FROM_NAME', 'JetPacks Market');
$config['reply_to']        = vp_config_env('VP_REPLY_TO', 'sales@jetpacksmarket.com');
$config['resend_api_key']  = vp_config_env('RESEND_API_KEY');
$config['resend_api_url']  = vp_config_env('VP_RESEND_API_URL', 'https://api.resend.com/emails');

// SMTP transport (cPanel / shared-hosting email accounts). Used by the Mailer
// when BOTH VP_SMTP_HOST and VP_SMTP_PASS are set; otherwise it falls back to
// Resend, then PHP mail(). Create the email account in cPanel, then set the
// password. Typical cPanel values: host = mail.yourdomain.com (or localhost),
// port 465 + ssl, or port 587 + tls; user = the full email address.
$vp_smtp_port = vp_config_env('VP_SMTP_PORT', '465');
$config['smtp_host']   = vp_config_env('VP_SMTP_HOST');
$config['smtp_port']   = $vp_smtp_port;
$config['smtp_user']   = vp_config_env('VP_SMTP_USER', $config['from_email']);
$config['smtp_pass']   = vp_config_env('VP_SMTP_PASS');
$config['smtp_crypto'] = vp_config_env('VP_SMTP_CRYPTO', ($vp_smtp_port == '587' || $vp_smtp_port == '25') ? 'tls' : 'ssl');

// Stripe is intentionally environment-only: card processor credentials must
// never be saved in the dashboard database or rendered in the browser. The
// dashboard controls enablement, currency and link lifetime separately.
$config['stripe_secret_key']     = vp_config_env('VP_STRIPE_SECRET_KEY');
$config['stripe_webhook_secret'] = vp_config_env('VP_STRIPE_WEBHOOK_SECRET');
$config['auth_secret']     = $vp_secrets['auth_secret']; // HMAC key for remember-me cookies / reset tokens
$config['pagination_per_page'] = 12;
$config['admin_pagination']    = 25;
$config['rfq_rate_limit']      = 5;        // per hour
$config['global_rate_limit']   = (int) (vp_config_env('VP_GLOBAL_RATE_LIMIT') !== '' ? vp_config_env('VP_GLOBAL_RATE_LIMIT') : '100'); // per 15 minutes
$config['session_lifetime']    = $config['sess_expiration'];

// Nested bag for anything that prefers $config['vp'] access.
$config['vp'] = [
    'site_name'         => $config['site_name'],
    'site_tagline'      => $config['site_tagline'],
    'contact_email'     => $config['contact_email'],
    'support_email'     => $config['support_email'],
    'rfq_email'         => $config['rfq_email'],
    'phone'             => $config['phone'],
    'address'           => $config['address'],
    'social'            => $config['social'],
    'pagination_per_page' => $config['pagination_per_page'],
    'admin_pagination'    => $config['admin_pagination'],
    'rfq_rate_limit'      => $config['rfq_rate_limit'],
    'global_rate_limit'   => $config['global_rate_limit'],
    'auth_secret'         => $config['auth_secret'],
    'session_lifetime'    => $config['session_lifetime'],
    'mailer'              => [
        'from_email'     => $config['from_email'],
        'from_name'      => $config['from_name'],
        'reply_to'       => $config['reply_to'],
    ],
    'resend_api_key'      => $config['resend_api_key'],
    'resend_api_url'      => $config['resend_api_url'],
    'env'                 => ENVIRONMENT,
    'debug'               => ENVIRONMENT !== 'production',
];
