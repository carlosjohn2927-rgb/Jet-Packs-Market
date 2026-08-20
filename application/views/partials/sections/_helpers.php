<?php
/**
 * Shared helper for section partials: turns a stored link into a URL.
 * (Internal paths are resolved against base_url, full URLs pass through.)
 */
if (!function_exists('vp_section_link')) {
    function vp_section_link($url)
    {
        $url = trim((string) $url);
        if ($url === '') return '';
        if (preg_match('~^(https?:)?//~i', $url) || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) return $url;
        return base_url(ltrim($url, '/'));
    }
}

if (!function_exists('vp_section_style_attr')) {
    /**
     * Inline colours chosen in the page builder (empty = theme default).
     */
    function vp_section_style_attr($section)
    {
        $s = function_exists('vp_section_settings') ? vp_section_settings($section) : [];
        $css = [];
        foreach (['bg_color' => 'background-color', 'text_color' => 'color'] as $key => $prop) {
            $raw = trim((string) ($s[$key] ?? ''));
            if ($raw === '') continue;
            $hex = vp_sanitize_hex_color($raw, '');
            if ($hex === '' || $hex === '#000000' && strtolower($raw) !== '#000000' && strtolower($raw) !== '#000') {
                if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $raw)) continue;
                $hex = vp_sanitize_hex_color($raw, $raw);
            }
            if ($hex !== '') $css[] = $prop . ':' . $hex;
        }
        $heading = trim((string) ($s['heading_color'] ?? ''));
        if ($heading !== '' && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $heading)) {
            $css[] = '--vp-heading:' . vp_sanitize_hex_color($heading, $heading);
        }
        return $css ? ' style="' . implode(';', $css) . '"' : '';
    }
}
