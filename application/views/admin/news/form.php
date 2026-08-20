<?php /** @var array|null $row */ $is_create = empty($row); ?>
<form method="post" action="<?= base_url('admin/news/save') ?>" class="space-y-4 max-w-3xl">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if (!$is_create): ?><input type="hidden" name="id" value="<?= $row['id'] ?>"><?php endif; ?>
    <div class="vp-form-row"><label>Title *</label><input class="vp-input" name="title" required value="<?= vp_safe_html($row['title'] ?? '') ?>"></div>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Slug</label><input class="vp-input" name="slug" value="<?= vp_safe_html($row['slug'] ?? '') ?>" placeholder="auto from title"></div>
        <div class="vp-form-row"><label>Category</label><input class="vp-input" name="category" value="<?= vp_safe_html($row['category'] ?? '') ?>"></div>
    </div>
    <div class="vp-form-row"><label>Summary</label><textarea class="vp-textarea" name="summary" rows="2"><?= vp_safe_html($row['summary'] ?? '') ?></textarea></div>
    <div class="vp-form-row"><label>Content</label><textarea class="vp-textarea" name="content" rows="10"><?= vp_safe_html($row['content'] ?? '') ?></textarea></div>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Image URL</label><input class="vp-input" name="image" value="<?= vp_safe_html($row['image'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Published at</label><input class="vp-input" name="publishedAt" data-flatpickr value="<?= vp_safe_html($row['publishedAt'] ?? '') ?>"></div>
    </div>
    <div class="vp-form-row">
        <label class="inline-flex items-center gap-2"><input type="hidden" name="isActive" value="0"><input type="checkbox" name="isActive" value="1" <?= (!$is_create || !empty($row['isActive'])) ? 'checked' : '' ?>> Active</label>
    </div>
    <div class="flex items-center gap-2">
        <button class="vp-btn vp-btn-primary" type="submit"><?= $is_create ? 'Create' : 'Save' ?></button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/news') ?>">Cancel</a>
    </div>
</form>
