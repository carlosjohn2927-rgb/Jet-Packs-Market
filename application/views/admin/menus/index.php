<?php
/** @var string $menu */
/** @var array $locations */
/** @var array $items */
/** @var array $pages */
$edit_id = $this->input->get('edit');
$editing = null;
foreach ($items as $it) { if ($it['id'] === $edit_id) $editing = $it; }
?>
<div class="max-w-6xl space-y-5">

    <div class="bg-white border rounded-2xl p-4 flex flex-wrap items-center gap-2">
        <?php foreach ($locations as $key => $label): ?>
            <a class="vp-tab <?= $key === $menu ? 'vp-tab-active' : '' ?>" href="<?= base_url('admin/menus/index/' . $key) ?>"><?= vp_safe_html($label) ?></a>
        <?php endforeach; ?>
        <a class="vp-btn vp-btn-secondary ml-auto" href="<?= base_url() ?>" target="_blank" rel="noopener"><i class="ri-external-link-line"></i> View website</a>
    </div>

    <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-xl px-4 py-3 text-sm flex gap-2">
        <i class="ri-information-line text-lg"></i>
        <span>The website logo always links to the homepage and is not part of the menu — manage it under <a class="underline font-semibold" href="<?= base_url('admin/appearance') ?>">Logo &amp; branding</a>.</span>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Items -->
        <section class="lg:col-span-2 bg-white border rounded-2xl">
            <header class="px-5 py-4 border-b">
                <h2 class="font-bold text-ink-900"><?= vp_safe_html($locations[$menu]) ?></h2>
                <p class="text-xs text-ink-800/60">Order here is the order visitors see.</p>
            </header>
            <div class="divide-y">
                <?php foreach ($items as $i => $it): ?>
                    <div class="px-5 py-3 flex flex-wrap items-center gap-3">
                        <i class="<?= vp_safe_html($it['icon'] ?: 'ri-links-line') ?> text-ink-800/50"></i>
                        <div class="min-w-0">
                            <div class="font-semibold text-sm text-ink-900">
                                <?= vp_safe_html($it['label']) ?>
                                <?php if (empty($it['isActive'])): ?><span class="vp-pill bg-gray-200 text-gray-700 ml-1">hidden</span><?php endif; ?>
                                <?php if ($it['target'] === '_blank'): ?><span class="vp-pill bg-gray-100 text-gray-600 ml-1">new tab</span><?php endif; ?>
                            </div>
                            <div class="text-xs text-ink-800/60 truncate">
                                <?= vp_safe_html($it['type']) ?> · <?= vp_safe_html($it['type'] === 'PAGE' ? ('page: ' . ($it['pageId'] ?? '')) : ($it['url'] ?? '')) ?>
                            </div>
                        </div>
                        <div class="ml-auto flex items-center gap-1">
                            <form method="post" action="<?= base_url('admin/menus/move/' . $it['id'] . '/up') ?>">
                                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                                <button class="p-2 rounded hover:bg-gray-100 <?= $i === 0 ? 'opacity-30 pointer-events-none' : '' ?>"><i class="ri-arrow-up-line"></i></button>
                            </form>
                            <form method="post" action="<?= base_url('admin/menus/move/' . $it['id'] . '/down') ?>">
                                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                                <button class="p-2 rounded hover:bg-gray-100 <?= $i === count($items) - 1 ? 'opacity-30 pointer-events-none' : '' ?>"><i class="ri-arrow-down-line"></i></button>
                            </form>
                            <form method="post" action="<?= base_url('admin/menus/toggle/' . $it['id']) ?>">
                                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                                <button class="vp-btn vp-btn-secondary vp-btn-sm"><i class="<?= empty($it['isActive']) ? 'ri-eye-off-line' : 'ri-eye-line' ?>"></i></button>
                            </form>
                            <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('admin/menus/index/' . $menu . '?edit=' . $it['id']) ?>"><i class="ri-edit-line"></i></a>
                            <form method="post" action="<?= base_url('admin/menus/delete/' . $it['id']) ?>" data-confirm="Delete this menu item?">
                                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                                <button class="vp-btn vp-btn-secondary vp-btn-sm text-red-600"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <p class="p-8 text-center text-sm text-ink-800/60">No items in this menu yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Add / edit -->
        <form method="post" action="<?= base_url('admin/menus/save') ?>" class="space-y-4">
            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
            <input type="hidden" name="menu" value="<?= vp_safe_html($menu) ?>">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= vp_safe_html($editing['id']) ?>"><?php endif; ?>

            <?= vp_admin_card_open($editing ? 'Edit menu item' : 'Add menu item', '', 'ri-add-line') ?>
                <?= vp_text_field('label', $editing['label'] ?? '', 'Label', ['required' => true]) ?>
                <?= vp_select_field('type', [
                        'INTERNAL' => 'Internal path (e.g. products)',
                        'PAGE'     => 'CMS page',
                        'EXTERNAL' => 'External URL',
                    ], $editing['type'] ?? 'INTERNAL', 'Link type') ?>
                <?= vp_text_field('url', $editing['url'] ?? '', 'URL / path', ['help' => 'For internal links use a path such as products. For external links use the full https:// URL.']) ?>
                <?php
                $page_options = ['' => '— select a page —'];
                foreach ($pages as $p) $page_options[$p['id']] = $p['title'] . ' (/' . $p['slug'] . ')';
                echo vp_select_field('pageId', $page_options, $editing['pageId'] ?? '', 'CMS page (when link type is “CMS page”)');
                ?>
                <?= vp_select_field('target', ['_self' => 'Same tab', '_blank' => 'New tab'], $editing['target'] ?? '_self', 'Open in') ?>
                <?= vp_text_field('icon', $editing['icon'] ?? '', 'Icon class (optional)', ['placeholder' => 'ri-home-line']) ?>
                <?= vp_toggle_field('isActive', !$editing || !empty($editing['isActive']), 'Visible in the menu') ?>
                <div class="flex gap-2 pt-2">
                    <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-save-3-line"></i> <?= $editing ? 'Save item' : 'Add item' ?></button>
                    <?php if ($editing): ?><a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/menus/index/' . $menu) ?>">Cancel</a><?php endif; ?>
                </div>
            <?= vp_admin_card_close() ?>
        </form>
    </div>
</div>
