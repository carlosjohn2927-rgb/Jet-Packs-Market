<?php
/** @var string $key   @var array $field  @var mixed $value */
$label = $field[0] ?? $key;
$type  = $field[1] ?? 'text';
$value = (string) ($value ?? '');
?>
<div class="vp-form-row">
    <label for="<?= vp_safe_html($key) ?>"><?= vp_safe_html($label) ?></label>

    <?php if ($type === 'textarea'): ?>
        <textarea class="vp-textarea" id="<?= vp_safe_html($key) ?>" name="<?= vp_safe_html($key) ?>" rows="3"><?= vp_safe_html($value) ?></textarea>

    <?php elseif ($type === 'robots'): ?>
        <select class="vp-select" id="<?= vp_safe_html($key) ?>" name="<?= vp_safe_html($key) ?>">
            <?php foreach (['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'] as $opt): ?>
                <option value="<?= vp_safe_html($opt) ?>" <?= $value === $opt ? 'selected' : '' ?>><?= vp_safe_html($opt) ?></option>
            <?php endforeach; ?>
        </select>

    <?php elseif ($type === 'bool'): ?>
        <select class="vp-select" id="<?= vp_safe_html($key) ?>" name="<?= vp_safe_html($key) ?>">
            <option value="1" <?= $value === '1' ? 'selected' : '' ?>>On</option>
            <option value="0" <?= $value === '0' ? 'selected' : '' ?>>Off</option>
        </select>

    <?php else: ?>
        <input class="vp-input" id="<?= vp_safe_html($key) ?>" name="<?= vp_safe_html($key) ?>" value="<?= vp_safe_html($value) ?>">
    <?php endif; ?>
</div>
