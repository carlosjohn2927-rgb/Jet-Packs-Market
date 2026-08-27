<?php
/**
 * Generic create/edit form driven by a normalised field list.
 *
 * @var array|null $row
 * @var array      $columns  legacy list columns (used when $fields is absent)
 * @var array      $fields   [field => ['label','type','options','required','help']]
 * @var string     $form_url
 */
$is_create = empty($row);
$fields = isset($fields) && $fields ? $fields : [];
if (empty($fields)) {
    // Backwards-compatible fallback: build fields from list columns.
    foreach (($columns ?? []) as $label => $col) {
        $fields[$col] = ['field' => $col, 'label' => $label];
    }
}
?>
<form method="post" action="<?= vp_safe_html($form_url) ?>" class="space-y-4 max-w-3xl">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if (!$is_create): ?><input type="hidden" name="id" value="<?= vp_safe_html($row['id'] ?? '') ?>"><?php endif; ?>

    <?php foreach ($fields as $col => $cfg):
        $cfg  = is_array($cfg) ? $cfg : ['field' => $cfg, 'label' => ucfirst($cfg)];
        $type = $cfg['type'] ?? 'text';
        $val  = $row[$col] ?? ($cfg['default'] ?? '');
        $req  = !empty($cfg['required']);
        $skip = in_array($col, ['id', 'createdAt', 'updatedAt'], true);
        if ($skip) continue;
        if ($type === 'auto') $type = 'text';
    ?>
        <div class="vp-form-row">
            <label for="f_<?= vp_safe_html($col) ?>">
                <?= vp_safe_html($cfg['label'] ?? ucfirst($col)) ?>
                <?php if ($req && $type !== 'checkbox'): ?> *<?php endif; ?>
            </label>

            <?php if ($type === 'textarea'): ?>
                <textarea class="vp-textarea" id="f_<?= vp_safe_html($col) ?>" name="<?= vp_safe_html($col) ?>" rows="<?= (int) ($cfg['rows'] ?? 5) ?>" <?= $req ? 'required' : '' ?>><?= vp_safe_html($val) ?></textarea>

            <?php elseif ($type === 'checkbox'): ?>
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="<?= vp_safe_html($col) ?>" value="0">
                    <input type="checkbox" id="f_<?= vp_safe_html($col) ?>" name="<?= vp_safe_html($col) ?>" value="1" <?= (int) $val ? 'checked' : '' ?>>
                    <?= vp_safe_html($cfg['check_label'] ?? 'Enabled') ?>
                </label>

            <?php elseif ($type === 'select'): ?>
                <select class="vp-select" id="f_<?= vp_safe_html($col) ?>" name="<?= vp_safe_html($col) ?>" <?= $req ? 'required' : '' ?>>
                    <?php foreach (($cfg['options'] ?? []) as $optVal => $optLabel): ?>
                        <option value="<?= vp_safe_html($optVal) ?>" <?= ((string) $val === (string) $optVal) ? 'selected' : '' ?>><?= vp_safe_html($optLabel) ?></option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($type === 'number'): ?>
                <input class="vp-input" type="number" step="<?= vp_safe_html($cfg['step'] ?? 'any') ?>" id="f_<?= vp_safe_html($col) ?>" name="<?= vp_safe_html($col) ?>" value="<?= vp_safe_html($val) ?>" <?= $req ? 'required' : '' ?>>

            <?php elseif ($type === 'image'): ?>
                <?php if (!empty($val)): ?>
                    <div class="mb-2 flex items-center gap-3">
                        <img src="<?= vp_safe_html($val) ?>" alt="" class="h-16 w-16 rounded border object-cover" loading="lazy">
                        <span class="text-xs text-gray-500 break-all"><?= vp_safe_html($val) ?></span>
                    </div>
                <?php endif; ?>
                <input class="vp-input" type="text" id="f_<?= vp_safe_html($col) ?>" name="<?= vp_safe_html($col) ?>" value="<?= vp_safe_html($val) ?>" placeholder="/assets/img/... or uploaded URL">

            <?php elseif ($type === 'email'): ?>
                <input class="vp-input" type="email" id="f_<?= vp_safe_html($col) ?>" name="<?= vp_safe_html($col) ?>" value="<?= vp_safe_html($val) ?>" <?= $req ? 'required' : '' ?>>

            <?php else: ?>
                <input class="vp-input" type="text" id="f_<?= vp_safe_html($col) ?>" name="<?= vp_safe_html($col) ?>" value="<?= vp_safe_html($val) ?>" <?= $req ? 'required' : '' ?>>
            <?php endif; ?>

            <?php if (!empty($cfg['help'])): ?>
                <p class="vp-help"><?= vp_safe_html($cfg['help']) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="flex items-center gap-2">
        <button class="vp-btn vp-btn-primary" type="submit"><?= $is_create ? 'Create' : 'Save' ?></button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url(str_replace('/save', '', $form_url)) ?>">Cancel</a>
    </div>
</form>
