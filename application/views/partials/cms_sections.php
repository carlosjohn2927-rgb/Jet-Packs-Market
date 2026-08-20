<?php
/**
 * Render CMS page-builder sections anywhere on the public site.
 * @var array $cms_sections
 * @var array $cms_blocks
 */
if (empty($cms_sections)) return;
$types = array_keys(vp_section_types());
$blocks = $cms_blocks ?? [];
foreach ($cms_sections as $section) {
    $view = in_array($section['type'], $types, true) ? $section['type'] : 'richtext';
    $file = APPPATH . 'views/partials/sections/' . $view . '.php';
    if (!is_file($file)) $view = 'richtext';
    echo vp_inline_section_open($section);
    $this->load->view('partials/sections/' . $view, array_merge(get_defined_vars(), [
        'section' => $section,
        'blocks'  => $blocks,
    ]));
    echo vp_inline_section_close();
}
