<?php /** @var array $rows */ ?>
<div class="space-y-5">
    <div class="flex flex-wrap items-center gap-3">
        <form method="get" class="flex items-center gap-2">
            <input class="vp-input" type="search" name="q" value="<?= vp_safe_html($search) ?>" placeholder="Search files…">
            <select class="vp-select w-auto" name="folder">
                <option value="">All folders</option>
                <?php foreach ($folders as $f): ?>
                    <option value="<?= vp_safe_html($f) ?>" <?= $folder === $f ? 'selected' : '' ?>><?= vp_safe_html($f) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="vp-btn vp-btn-secondary" type="submit">Filter</button>
        </form>
        <span class="text-sm text-ink-800/60"><?= (int) $total ?> file(s)</span>
    </div>

    <form method="post" action="<?= base_url('admin/media/upload') ?>" enctype="multipart/form-data"
          class="bg-white border rounded-2xl p-5 flex flex-wrap items-end gap-4">
        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
        <div>
            <label class="vp-label">File</label>
            <input type="file" name="file" required class="text-sm">
        </div>
        <div>
            <label class="vp-label">Folder</label>
            <input class="vp-input" type="text" name="folder" value="<?= vp_safe_html($folder ?: 'general') ?>">
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="vp-label">Alt text (accessibility)</label>
            <input class="vp-input" type="text" name="alt" placeholder="Describe the image">
        </div>
        <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-upload-2-line"></i> Upload</button>
    </form>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
        <?php foreach ($rows as $m): $isImg = strpos((string) $m['mimeType'], 'image/') === 0; ?>
            <div class="bg-white border rounded-xl overflow-hidden flex flex-col">
                <div class="h-32 bg-gray-50 flex items-center justify-center overflow-hidden">
                    <?php if ($isImg): ?>
                        <img src="<?= vp_safe_html($m['url']) ?>" alt="<?= vp_safe_html($m['alt'] ?? '') ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="ri-file-3-line text-4xl text-gray-400"></i>
                    <?php endif; ?>
                </div>
                <div class="p-3 text-xs flex-1 flex flex-col gap-2">
                    <div class="font-semibold truncate" title="<?= vp_safe_html($m['originalName']) ?>"><?= vp_safe_html($m['originalName']) ?></div>
                    <div class="text-ink-800/50"><?= vp_format_bytes((int) $m['size']) ?> · <?= vp_safe_html($m['folder']) ?></div>
                    <?php if (!empty($m['in_use'])): ?>
                        <span class="vp-pill bg-amber-100 text-amber-800">in use (logo/SEO)</span>
                    <?php endif; ?>

                    <div class="mt-auto flex flex-wrap gap-1">
                        <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-copy="<?= vp_safe_html(base_url(ltrim($m['url'], '/'))) ?>"><i class="ri-file-copy-line"></i> URL</button>
                        <a class="vp-btn vp-btn-secondary vp-btn-sm" href="<?= vp_safe_html($m['url']) ?>" target="_blank" rel="noopener"><i class="ri-eye-line"></i></a>
                        <button type="button" class="vp-btn vp-btn-secondary vp-btn-sm" data-vp-toggle="#media-<?= vp_safe_html($m['id']) ?>"><i class="ri-edit-line"></i></button>
                    </div>

                    <div id="media-<?= vp_safe_html($m['id']) ?>" class="hidden space-y-2 pt-2 border-t">
                        <form method="post" action="<?= base_url('admin/media/update/' . $m['id']) ?>" class="space-y-2">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <input class="vp-input text-xs" type="text" name="originalName" value="<?= vp_safe_html($m['originalName']) ?>" placeholder="File name">
                            <input class="vp-input text-xs" type="text" name="alt" value="<?= vp_safe_html($m['alt'] ?? '') ?>" placeholder="Alt text">
                            <button class="vp-btn vp-btn-primary vp-btn-sm w-full justify-center" type="submit">Save details</button>
                        </form>
                        <form method="post" action="<?= base_url('admin/media/replace/' . $m['id']) ?>" enctype="multipart/form-data" class="space-y-2">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <input type="file" name="file" required class="text-[11px] w-full">
                            <button class="vp-btn vp-btn-secondary vp-btn-sm w-full justify-center" type="submit">Replace file</button>
                        </form>
                        <form method="post" action="<?= base_url('admin/media/delete/' . $m['id']) ?>" data-confirm="Delete this file permanently?">
                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                            <button class="vp-btn vp-btn-secondary vp-btn-sm w-full justify-center text-red-600" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <p class="col-span-full text-center text-sm text-ink-800/60 py-10">No files yet — upload one above.</p>
        <?php endif; ?>
    </div>

    <div class="flex justify-center"><?= vp_pagination_links($total_pages, $page, $base_url) ?></div>
</div>

<script nonce="<?= vp_safe_html($csp_nonce ?? '') ?>">
document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-vp-toggle]');
    if (!t) return;
    var el = document.querySelector(t.getAttribute('data-vp-toggle'));
    if (el) el.classList.toggle('hidden');
});
</script>
