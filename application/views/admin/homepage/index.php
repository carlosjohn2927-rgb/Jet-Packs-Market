<?php
/** @var array $sections */
/** @var array $types */
/** @var string $pageKey */
/** @var array $page_keys */
$preview = $preview ?? ($pageKey === 'home' ? base_url() : base_url(strpos($pageKey, 'page:') === 0 ? substr($pageKey, 5) : $pageKey));
$pk_enc = rawurlencode($pageKey);
?>
<div class="max-w-6xl space-y-5">

    <div class="bg-white border rounded-2xl p-4 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            <?php foreach ($page_keys as $key => $label): ?>
                <a class="vp-tab <?= $key === $pageKey ? 'vp-tab-active' : '' ?>" href="<?= base_url('admin/homepage/index/' . rawurlencode($key)) ?>"><?= vp_safe_html($label) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <a class="vp-btn vp-btn-secondary" href="<?= vp_safe_html($preview) ?>" target="_blank" rel="noopener">
                <i class="ri-external-link-line"></i> Preview page
            </a>
        </div>
    </div>

    <p class="text-sm text-ink-800/70 px-1">
        Drag sections to reorder them. Edit text, colours, images, video and files on each block — changes go live on the public website.
    </p>

    <section class="bg-white border rounded-2xl">
        <header class="px-5 py-4 border-b flex items-center gap-3">
            <i class="ri-add-box-line text-xl text-brand-600"></i>
            <div>
                <h2 class="font-bold text-ink-900">Add a block</h2>
                <p class="text-xs text-ink-800/60">Drop a block onto the page, then edit its content, colour and media.</p>
            </div>
        </header>
        <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <?php foreach ($types as $key => $t): ?>
                <a href="<?= base_url('admin/homepage/create/' . $pk_enc . '?type=' . $key) ?>"
                   class="border rounded-xl px-4 py-3 hover:border-brand-400 hover:bg-brand-50/40 transition">
                    <div class="flex items-center gap-2 font-semibold text-sm text-ink-900"><i class="<?= vp_safe_html($t[1]) ?> text-brand-600"></i> <?= vp_safe_html($t[0]) ?></div>
                    <p class="text-[11px] text-ink-800/60 mt-1"><?= vp_safe_html($t[2]) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="bg-white border rounded-2xl">
        <header class="px-5 py-4 border-b flex items-center gap-3">
            <i class="ri-drag-move-2-line text-xl text-brand-600"></i>
            <div>
                <h2 class="font-bold text-ink-900">Page layout</h2>
                <p class="text-xs text-ink-800/60">Drag the handle to reorder. Hidden blocks are not shown to visitors.</p>
            </div>
            <span class="ml-auto text-xs text-ink-800/60"><?= count($sections) ?> block(s)</span>
        </header>

        <div id="vp-builder-list" class="divide-y" data-page-key="<?= vp_safe_html($pageKey) ?>">
            <?php if (empty($sections)): ?>
                <p class="p-8 text-center text-sm text-ink-800/60">No blocks yet — add one above.</p>
            <?php endif; ?>

            <?php foreach ($sections as $i => $s): $t = $types[$s['type']] ?? ['Section', 'ri-layout-line', '']; ?>
                <div class="vp-section-row px-5 py-4 flex flex-wrap items-center gap-4" draggable="true" data-id="<?= vp_safe_html($s['id']) ?>">
                    <button type="button" class="cursor-grab active:cursor-grabbing p-2 text-ink-800/40 hover:text-ink-900" title="Drag to reorder" aria-label="Drag to reorder">
                        <i class="ri-draggable text-xl"></i>
                    </button>
                    <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center">
                        <i class="<?= vp_safe_html($t[1]) ?> text-xl"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-ink-900 flex items-center gap-2">
                            <?= vp_safe_html($s['name'] ?: $t[0]) ?>
                            <?php if (!empty($s['isSystem'])): ?><span class="vp-pill bg-amber-100 text-amber-800">core</span><?php endif; ?>
                            <?php if (empty($s['isActive'])): ?><span class="vp-pill bg-gray-200 text-gray-700">hidden</span><?php endif; ?>
                        </div>
                        <div class="text-xs text-ink-800/60 truncate">
                            <?= vp_safe_html($t[0]) ?><?= $s['title'] ? ' · ' . vp_safe_html(vp_truncate($s['title'], 70)) : '' ?>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <form method="post" action="<?= base_url('admin/homepage/toggle/' . $s['id']) ?>">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <button class="vp-btn vp-btn-secondary vp-btn-sm" title="Show/hide on the website">
                                <i class="<?= empty($s['isActive']) ? 'ri-eye-off-line' : 'ri-eye-line' ?>"></i>
                                <?= empty($s['isActive']) ? 'Show' : 'Hide' ?>
                            </button>
                        </form>
                        <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= base_url('admin/homepage/edit/' . $s['id']) ?>"><i class="ri-edit-line"></i> Edit</a>
                        <form method="post" action="<?= base_url('admin/homepage/duplicate/' . $s['id']) ?>">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <button class="vp-btn vp-btn-secondary vp-btn-sm" title="Duplicate"><i class="ri-file-copy-line"></i></button>
                        </form>
                        <form method="post" action="<?= base_url('admin/homepage/delete/' . $s['id']) ?>" data-confirm="Delete this block from the page?">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <button class="vp-btn vp-btn-secondary vp-btn-sm text-red-600"><i class="ri-delete-bin-line"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
