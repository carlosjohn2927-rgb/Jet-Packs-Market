<?php /** @var array|null $row */ $is_create = empty($row); ?>
<form method="post" action="<?= base_url('admin/careers/save') ?>" class="space-y-4 max-w-3xl">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if (!$is_create): ?><input type="hidden" name="id" value="<?= $row['id'] ?>"><?php endif; ?>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Title *</label><input class="vp-input" name="title" required value="<?= vp_safe_html($row['title'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Slug</label><input class="vp-input" name="slug" value="<?= vp_safe_html($row['slug'] ?? '') ?>" placeholder="auto from title"></div>
    </div>
    <div class="vp-grid-3">
        <div class="vp-form-row"><label>Department *</label><input class="vp-input" name="department" required value="<?= vp_safe_html($row['department'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Location *</label><input class="vp-input" name="location" required value="<?= vp_safe_html($row['location'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Type</label>
            <select class="vp-select" name="type">
                <?php foreach (['Full-time','Part-time','Contract','Internship'] as $t): ?>
                    <option <?= ($row['type'] ?? 'Full-time') === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Experience</label><input class="vp-input" name="experience" value="<?= vp_safe_html($row['experience'] ?? '') ?>" placeholder="5+ years"></div>
        <div class="vp-form-row"><label>Salary</label><input class="vp-input" name="salary" value="<?= vp_safe_html($row['salary'] ?? '') ?>" placeholder="Competitive"></div>
    </div>
    <div class="vp-form-row"><label>Description *</label><textarea class="vp-textarea" name="description" rows="6" required><?= vp_safe_html($row['description'] ?? '') ?></textarea></div>
    <div class="vp-form-row"><label>Requirements *</label><textarea class="vp-textarea" name="requirements" rows="6" required><?= vp_safe_html($row['requirements'] ?? '') ?></textarea></div>
    <div class="vp-form-row"><label>Benefits</label><textarea class="vp-textarea" name="benefits" rows="3"><?= vp_safe_html($row['benefits'] ?? '') ?></textarea></div>
    <div class="vp-grid-2">
        <div class="vp-form-row"><label>Posted at</label><input class="vp-input" name="postedAt" data-flatpickr value="<?= vp_safe_html($row['postedAt'] ?? '') ?>"></div>
        <div class="vp-form-row"><label>Closing at</label><input class="vp-input" name="closingAt" data-flatpickr value="<?= vp_safe_html($row['closingAt'] ?? '') ?>"></div>
    </div>
    <div class="vp-form-row">
        <label class="inline-flex items-center gap-2"><input type="hidden" name="isActive" value="0"><input type="checkbox" name="isActive" value="1" <?= (!$is_create || !empty($row['isActive'])) ? 'checked' : '' ?>> Active</label>
    </div>
    <div class="flex items-center gap-2">
        <button class="vp-btn vp-btn-primary" type="submit"><?= $is_create ? 'Create' : 'Save' ?></button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/careers') ?>">Cancel</a>
    </div>
</form>
