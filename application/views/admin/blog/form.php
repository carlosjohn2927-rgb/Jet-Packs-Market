<?php /** @var array|null $row */ /** @var array $staff */ $is_create = empty($row);
$tags_csv = $row && $row['tags'] ? implode(', ', json_decode($row['tags'], true) ?: []) : ''; ?>
<form method="post" action="<?= base_url('admin/blog/save') ?>" class="space-y-4 max-w-4xl">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if (!$is_create): ?><input type="hidden" name="id" value="<?= $row['id'] ?>"><?php endif; ?>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Title *</label><input class="vp-input" name="title" required value="<?= vp_safe_html($row['title'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Slug</label><input class="vp-input" name="slug" value="<?= vp_safe_html($row['slug'] ?? '') ?>" placeholder="auto from title"></div>
    </div>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Category</label><input class="vp-input" name="category" value="<?= vp_safe_html($row['category'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Tags (comma)</label><input class="vp-input" name="tags_csv" value="<?= vp_safe_html($tags_csv) ?>" placeholder="valves, engineering, asme"></div>
    </div>
    <div class="vp-form-row"><label>Excerpt</label><textarea class="vp-textarea" name="excerpt" rows="2"><?= vp_safe_html($row['excerpt'] ?? '') ?></textarea></div>
    <div class="vp-form-row">
        <label>Content *</label>
        <textarea class="vp-textarea" name="content" rows="15"><?= vp_safe_html($row['content'] ?? '') ?></textarea>
        <p class="vp-help">HTML allowed. For richer editing, paste HTML from your editor of choice.</p>
    </div>
    <div class="vp-grid-3">
        <div class="vp-form-row"><label>Author</label>
            <select class="vp-select" name="authorId">
                <?php foreach ($staff as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($row['authorId'] ?? '') === $u['id'] ? 'selected' : '' ?>><?= vp_safe_html(trim($u['firstName'].' '.$u['lastName'])) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="vp-form-row"><label>Status</label>
            <select class="vp-select" name="status">
                <?php foreach (['DRAFT','PUBLISHED'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($row['status'] ?? 'DRAFT') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="vp-form-row"><label>Published at</label><input class="vp-input" name="publishedAt" data-flatpickr value="<?= vp_safe_html($row['publishedAt'] ?? '') ?>"></div>
    </div>
    <div class="vp-form-row"><label>Featured image URL</label><input class="vp-input" name="featuredImage" value="<?= vp_safe_html($row['featuredImage'] ?? '') ?>"></div>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Meta title</label><input class="vp-input" name="metaTitle" value="<?= vp_safe_html($row['metaTitle'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Meta description</label><input class="vp-input" name="metaDescription" value="<?= vp_safe_html($row['metaDescription'] ?? '') ?>"></div>
    </div>
    <div class="flex items-center gap-2">
        <button class="vp-btn vp-btn-primary" type="submit"><?= $is_create ? 'Create' : 'Save' ?></button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/blog') ?>">Cancel</a>
    </div>
</form>
