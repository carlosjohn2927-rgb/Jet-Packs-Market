<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - security helpers.
 */

if (!function_exists('vp_hmac_sign')) {
    /**
     * HMAC-SHA256 sign a payload using the auth secret.
     */
    function vp_hmac_sign($payload, $key = null)
    {
        $CI =& get_instance();
        $key = $key ?: $CI->config->item('auth_secret');
        if (!$key) $key = $CI->config->item('encryption_key');
        return hash_hmac('sha256', $payload, $key);
    }
}

if (!function_exists('vp_hmac_verify')) {
    /**
     * Constant-time HMAC verification.
     */
    function vp_hmac_verify($payload, $sig, $key = null)
    {
        $expected = vp_hmac_sign($payload, $key);
        return hash_equals($expected, (string) $sig);
    }
}

if (!function_exists('vp_random_token')) {
    /**
     * Cryptographically secure random token (URL-safe).
     */
    function vp_random_token($bytes = 32)
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }
}

if (!function_exists('vp_clean')) {
    /**
     * XSS-clean a string for safe display.
     */
    function vp_clean($value)
    {
        if (is_array($value)) {
            return array_map('vp_clean', $value);
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('vp_get_client_ip')) {
    /**
     * Best-effort client IP, trusting X-Forwarded-For only if behind a known proxy.
     * On shared hosting, REMOTE_ADDR is the safer choice.
     */
    function vp_get_client_ip()
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

if (!function_exists('vp_sanitize_html')) {
    /**
     * Sanitise administrator-authored HTML before it is stored.
     *
     * Content editors legitimately need formatting markup (headings, lists,
     * links, images, tables), but never scripting: <script>, <iframe>,
     * <object>, inline event handlers and javascript: URLs are stripped.
     */
    function vp_sanitize_html($html)
    {
        $html = (string) $html;
        if (trim($html) === '') return '';

        // Remove dangerous elements together with their content.
        $html = preg_replace('#<\s*(script|style|iframe|object|embed|form|base|meta|link)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        // …and any self-closing/unclosed variants.
        $html = preg_replace('#<\s*/?\s*(script|style|iframe|object|embed|base|meta|link)\b[^>]*>#i', '', $html);
        // Inline event handlers: onclick="…", onerror='…', onload=…
        $html = preg_replace('#\son[a-z]+\s*=\s*"[^"]*"#i', '', $html);
        $html = preg_replace("#\son[a-z]+\s*=\s*'[^']*'#i", '', $html);
        $html = preg_replace('#\son[a-z]+\s*=\s*[^\s>]+#i', '', $html);
        // javascript:/vbscript:/data: URLs in href/src attributes.
        $html = preg_replace('#(href|src|xlink:href)\s*=\s*(["\']?)\s*(javascript|vbscript|data)\s*:#i', '$1=$2#', $html);

        return trim($html);
    }
}
