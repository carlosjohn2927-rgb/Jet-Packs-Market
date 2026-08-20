<?php
/** @var array|null $row */
$editing = !empty($row);
?>
<form method="post" action="<?= base_url('admin/pages/save') ?>" class="max-w-6xl">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= vp_safe_html($row['id']) ?>"><?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <?= vp_admin_card_open('Page content', '', 'ri-pages-line') ?>
                <?= vp_text_field('title', $row['title'] ?? '', 'Page title', ['required' => true]) ?>
                <?= vp_text_field('slug', $row['slug'] ?? '', 'URL slug', ['help' => 'Leave empty to generate it from the title. The page is served at ' . base_url() . '<slug>']) ?>
                <?= vp_textarea_field('excerpt', $row['excerpt'] ?? '', 'Short summary', 2) ?>
                <?= vp_textarea_field('content', $row['content'] ?? '', 'Content (HTML)', 18,
                    'Headings, paragraphs, lists, links, tables and images are kept. Scripts and iframes are stripped for security.') ?>
            <?= vp_admin_card_close() ?>

            <?= vp_admin_card_open('Search engine (SEO)', 'Overrides the site defaults for this page', 'ri-search-eye-line') ?>
                <?= vp_text_field('metaTitle', $row['metaTitle'] ?? '', 'SEO title') ?>
                <?= vp_textarea_field('metaDescription', $row['metaDescription'] ?? '', 'SEO description', 3) ?>
            <?= vp_admin_card_close() ?>
        </div>

        <div class="space-y-6">
            <?= vp_admin_card_open('Publish', '', 'ri-send-plane-line') ?>
                <?= vp_select_field('status', ['DRAFT' => 'Draft', 'PUBLISHED' => 'Published'], $row['status'] ?? 'DRAFT', 'Status') ?>
                <?= vp_select_field('visibility', ['PUBLIC' => 'Public', 'PRIVATE' => 'Private (signed-in staff only)'], $row['visibility'] ?? 'PUBLIC', 'Visibility') ?>
                <?= vp_text_field('publishedAt', $row['publishedAt'] ?? '', 'Publish date', ['help' => 'YYYY-MM-DD HH:MM:SS — leave empty for “now”.']) ?>
                <?= vp_toggle_field('showInMenu', !empty($row['showInMenu']), 'Offer in navigation lists') ?>
                <?= vp_text_field('sortOrder', $row['sortOrder'] ?? 0, 'Sort order', ['type' => 'number']) ?>
                <div class="pt-2 flex flex-col gap-2">
                    <button class="vp-btn vp-btn-primary justify-center" type="submit"><i class="ri-save-3-line"></i> <?= $editing ? 'Save page' : 'Create page' ?></button>
                    <?php if ($editing): ?>
                        <a class="vp-btn vp-btn-secondary justify-center" href="<?= base_url('admin/homepage/index/' . rawurlencode('page:' . $row['slug'])) ?>"><i class="ri-layout-masonry-line"></i> Open page builder</a>
                        <a class="vp-btn vp-btn-secondary justify-center" href="<?= base_url($row['slug']) ?>" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> View page</a>
                    <?php endif; ?>
                    <a class="vp-btn vp-btn-secondary justify-center" href="<?= base_url('admin/pages') ?>">Back to pages</a>
                </div>
            <?= vp_admin_card_close() ?>

            <?= vp_admin_card_open('Featured image', '', 'ri-image-line') ?>
                <?= vp_media_field('featuredImage', $row['featuredImage'] ?? '', '') ?>
            <?= vp_admin_card_close() ?>

            <?= vp_admin_card_open('Template', '', 'ri-layout-line') ?>
                <?= vp_select_field('template', ['default' => 'Default (centred)', 'wide' => 'Wide', 'sidebar' => 'With sidebar'], $row['template'] ?? 'default', '') ?>
            <?= vp_admin_card_close() ?>
        </div>
    </div>
</form>
