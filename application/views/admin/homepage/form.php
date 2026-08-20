<?php
/** @var array|null $row */
/** @var string $type */
/** @var string $pageKey */
/** @var array  $types */
$s        = $row ?? [];
$settings = vp_section_settings($row ?? []);
$items    = $settings['items'] ?? [];
$badges   = $settings['badges'] ?? [];
$editing  = !empty($row);
$def      = $types[$type] ?? ['Section', 'ri-layout-line', ''];

$has_heading  = true;
$has_body     = in_array($type, ['richtext', 'hero', 'cta', 'banner', 'newsletter', 'faq', 'services', 'image', 'video', 'file', 'gallery'], true);
$has_image    = in_array($type, ['hero', 'banner', 'richtext', 'services', 'image', 'video'], true);
$has_buttons  = in_array($type, ['hero', 'cta', 'banner', 'products', 'richtext', 'newsletter', 'file'], true);
$has_limit    = in_array($type, ['products', 'categories', 'industries', 'testimonials', 'partners', 'faq'], true);
$has_items    = in_array($type, ['stats', 'services'], true);
$has_badges   = ($type === 'hero');
$has_video    = ($type === 'video');
$has_file     = ($type === 'file');
$has_gallery  = ($type === 'gallery');
$gallery      = $settings['gallery'] ?? [];
?>
<form method="post" action="<?= base_url('admin/homepage/save') ?>" class="max-w-5xl space-y-6">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <input type="hidden" name="type" value="<?= vp_safe_html($type) ?>">
    <input type="hidden" name="pageKey" value="<?= vp_safe_html($pageKey) ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= vp_safe_html($row['id']) ?>"><?php endif; ?>

    <div class="bg-white border rounded-2xl p-5 flex flex-wrap items-center gap-3">
        <div class="w-11 h-11 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center"><i class="<?= vp_safe_html($def[1]) ?> text-xl"></i></div>
        <div>
            <div class="font-bold text-ink-900"><?= vp_safe_html($def[0]) ?></div>
            <div class="text-xs text-ink-800/60"><?= vp_safe_html($def[2]) ?></div>
        </div>
        <div class="ml-auto flex gap-2">
            <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/homepage/index/' . $pageKey) ?>">Back</a>
            <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save section</button>
        </div>
    </div>

    <?= vp_admin_card_open('Content', 'What visitors read in this block', 'ri-text') ?>
        <div class="grid md:grid-cols-2 gap-4">
            <?= vp_text_field('name', $s['name'] ?? $def[0], 'Internal name', ['help' => 'Only shown in the dashboard.']) ?>
            <?= vp_toggle_field('isActive', !isset($s['isActive']) || !empty($s['isActive']), 'Visible on the website') ?>
        </div>

        <?php if ($has_badges): ?>
            <?= vp_text_field('eyebrow', $settings['eyebrow'] ?? '', 'Eyebrow / kicker text') ?>
        <?php endif; ?>

        <?php if ($has_heading): ?>
            <?= vp_text_field('title', $s['title'] ?? '', 'Heading') ?>
            <?= vp_textarea_field('subtitle', $s['subtitle'] ?? '', 'Sub-heading / intro text', 3) ?>
        <?php endif; ?>

        <?php if ($has_body): ?>
            <?= vp_textarea_field('body', $s['body'] ?? '', 'Body content (HTML allowed)', 10,
                'Formatting HTML is kept; scripts and iframes are removed automatically.') ?>
        <?php endif; ?>
    <?= vp_admin_card_close() ?>

    <?php if ($has_image): ?>
        <?= vp_admin_card_open('Image', 'Pick from the media library or paste a URL', 'ri-image-line') ?>
            <?= vp_media_field('image', $s['image'] ?? '', 'Section image') ?>
        <?= vp_admin_card_close() ?>
    <?php endif; ?>

    <?php if ($has_buttons): ?>
        <?= vp_admin_card_open('Buttons', 'Leave a label empty to hide that button', 'ri-cursor-line') ?>
            <div class="grid md:grid-cols-2 gap-4">
                <?= vp_text_field('buttonText', $s['buttonText'] ?? '', 'Primary button label') ?>
                <?= vp_text_field('buttonUrl',  $s['buttonUrl']  ?? '', 'Primary button link', ['help' => 'Internal path (products) or full URL (https://…)']) ?>
                <?= vp_text_field('buttonText2', $s['buttonText2'] ?? '', 'Secondary button label') ?>
                <?= vp_text_field('buttonUrl2',  $s['buttonUrl2']  ?? '', 'Secondary button link') ?>
            </div>
        <?= vp_admin_card_close() ?>
    <?php endif; ?>

    <?php if ($has_limit): ?>
        <?= vp_admin_card_open('Options', '', 'ri-settings-3-line') ?>
            <?= vp_text_field('limit', $settings['limit'] ?? 6, 'How many items to show', ['type' => 'number']) ?>
        <?= vp_admin_card_close() ?>
    <?php endif; ?>

    <?php if ($has_badges): ?>
        <?= vp_admin_card_open('Trust badges', 'Short proof points shown under the hero text', 'ri-verified-badge-line') ?>
            <div id="vp-badges" class="space-y-2">
                <?php foreach (($badges ?: ['']) as $b): ?>
                    <div class="flex gap-2" data-vp-repeat-row>
                        <input class="vp-input" type="text" name="badges[]" value="<?= vp_safe_html($b) ?>" placeholder="e.g. ISO 9001:2015">
                        <button type="button" class="vp-btn vp-btn-secondary" data-vp-repeat-remove><i class="ri-delete-bin-line"></i></button>
                    </div>
                <?php endforeach; ?>
            </div>
            <template id="vp-badge-tpl">
                <div class="flex gap-2" data-vp-repeat-row>
                    <input class="vp-input" type="text" name="badges[]" value="" placeholder="e.g. ISO 9001:2015">
                    <button type="button" class="vp-btn vp-btn-secondary" data-vp-repeat-remove><i class="ri-delete-bin-line"></i></button>
                </div>
            </template>
            <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-repeat-add="vp-badges" data-vp-repeat-template="vp-badge-tpl"><i class="ri-add-line"></i> Add badge</button>
        <?= vp_admin_card_close() ?>
    <?php endif; ?>

    <?php if ($has_items): ?>
        <?= vp_admin_card_open($type === 'stats' ? 'Numbers' : 'Cards', 'Repeatable items rendered inside this section', 'ri-list-check-2') ?>
            <div id="vp-items" class="space-y-3">
                <?php foreach (($items ?: [[]]) as $it): ?>
                    <div class="grid md:grid-cols-12 gap-2 items-start border rounded-lg p-3" data-vp-repeat-row>
                        <?php if ($type === 'stats'): ?>
                            <input class="vp-input md:col-span-3" type="text" name="item_value[]" value="<?= vp_safe_html($it['value'] ?? '') ?>" placeholder="35+">
                            <input class="vp-input md:col-span-8" type="text" name="item_label[]" value="<?= vp_safe_html($it['label'] ?? '') ?>" placeholder="Years of experience">
                        <?php else: ?>
                            <input class="vp-input md:col-span-2" type="text" name="item_icon[]"  value="<?= vp_safe_html($it['icon'] ?? '') ?>" placeholder="ri-tools-line">
                            <input class="vp-input md:col-span-3" type="text" name="item_label[]" value="<?= vp_safe_html($it['label'] ?? '') ?>" placeholder="Card title">
                            <input class="vp-input md:col-span-4" type="text" name="item_text[]"  value="<?= vp_safe_html($it['text'] ?? '') ?>" placeholder="Short description">
                            <input class="vp-input md:col-span-2" type="text" name="item_url[]"   value="<?= vp_safe_html($it['url'] ?? '') ?>" placeholder="link (optional)">
                        <?php endif; ?>
                        <button type="button" class="vp-btn vp-btn-secondary md:col-span-1" data-vp-repeat-remove><i class="ri-delete-bin-line"></i></button>
                    </div>
                <?php endforeach; ?>
            </div>
            <template id="vp-item-tpl">
                <div class="grid md:grid-cols-12 gap-2 items-start border rounded-lg p-3" data-vp-repeat-row>
                    <?php if ($type === 'stats'): ?>
                        <input class="vp-input md:col-span-3" type="text" name="item_value[]" placeholder="35+">
                        <input class="vp-input md:col-span-8" type="text" name="item_label[]" placeholder="Years of experience">
                    <?php else: ?>
                        <input class="vp-input md:col-span-2" type="text" name="item_icon[]"  placeholder="ri-tools-line">
                        <input class="vp-input md:col-span-3" type="text" name="item_label[]" placeholder="Card title">
                        <input class="vp-input md:col-span-4" type="text" name="item_text[]"  placeholder="Short description">
                        <input class="vp-input md:col-span-2" type="text" name="item_url[]"   placeholder="link (optional)">
                    <?php endif; ?>
                    <button type="button" class="vp-btn vp-btn-secondary md:col-span-1" data-vp-repeat-remove><i class="ri-delete-bin-line"></i></button>
                </div>
            </template>
            <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-repeat-add="vp-items" data-vp-repeat-template="vp-item-tpl"><i class="ri-add-line"></i> Add item</button>
        <?= vp_admin_card_close() ?>
    <?php endif; ?>

    <div class="flex gap-3">
        <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> Save section</button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/homepage/index/' . $pageKey) ?>">Cancel</a>
        <?php if ($editing): ?>
            <a class="vp-btn vp-btn-secondary ml-auto" href="<?= base_url($pageKey === 'home' ? '' : $pageKey) ?>" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> View page</a>
        <?php endif; ?>
    </div>
</form>
