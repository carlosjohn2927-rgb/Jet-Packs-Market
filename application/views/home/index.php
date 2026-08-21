<?php
/**
 * Public homepage — rendered entirely from the CMS `page_sections` rows.
 * @var array $sections
 * @var array $blocks   catalogue data required by the active sections
 */
$types = array_keys(vp_section_types());
?>
<?php foreach ($sections as $section): ?>
    <?php
    echo vp_inline_section_open($section);
    $view = in_array($section['type'], $types, true) ? $section['type'] : 'richtext';
    $this->load->view('partials/sections/' . $view, array_merge(get_defined_vars(), ['section' => $section]));
    echo vp_inline_section_close();
    ?>
<?php endforeach; ?>

<?php if (empty($sections)): ?>
    <section class="container mx-auto px-4 py-20 text-center">
        <h1 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html(vp_site('name')) ?></h1>
        <p class="text-ink-800 mt-3">This homepage has no sections yet.</p>
        <?php if (vp_can('homepage.manage')): ?>
            <a class="vp-btn vp-btn-primary mt-6 inline-flex" href="<?= base_url('admin/homepage') ?>">Add homepage sections</a>
        <?php endif; ?>
    </section>
<?php endif; ?>
