<?php
/** @var array|null $row */
/** @var array $columns */
/** @var string $form_url */
$is_create = empty($row);
?>
<form method="post" action="<?= vp_safe_html($form_url) ?>" class="space-y-4 max-w-3xl">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if (!$is_create): ?><input type="hidden" name="id" value="<?= $row['id'] ?>"><?php endif; ?>

    <?php foreach ($columns as $label => $col):
        $val = $row[$col] ?? '';
        $type = 'text';
        $skip = in_array($col, ['id', 'createdAt', 'updatedAt', 'views', 'lastLoginAt', 'lastNotifiedAt', 'statusUpdatedAt', 'version'], true);
        if ($skip) continue;
        if ($col === 'isActive' || $col === 'active')     $type = 'checkbox';
        elseif ($col === 'password' || $col === 'Password') $type = 'password';
        elseif (in_array($col, ['description','answer','content','message','requirements','benefits','summary','notes','internalNotes','coverLetter','shortDescription'], true)) $type = 'textarea';
        elseif ($col === 'slug' || $col === 'sku' || $col === 'name' || $col === 'title' || $col === 'email') $type = 'text';
        elseif (in_array($col, ['isActive','active','featured'], true)) $type = 'checkbox';
    ?>
        <div class="vp-form-row">
            <label><?= vp_safe_html($label) ?><?php if ($type !== 'checkbox' && in_array($col, ['name','title','sku','email','question','description','content','answer','message','requirements','slug'], true)): ?> *<?php endif; ?></label>
            <?php if ($type === 'textarea'): ?>
                <textarea class="vp-textarea" name="<?= $col ?>" rows="6"><?= vp_safe_html($val) ?></textarea>
            <?php elseif ($type === 'checkbox'): ?>
                <label class="inline-flex items-center gap-2"><input type="hidden" name="<?= $col ?>" value="0"><input type="checkbox" name="<?= $col ?>" value="1" <?= (int)$val ? 'checked' : '' ?>></label>
            <?php elseif ($col === 'email'): ?>
                <input class="vp-input" type="email" name="<?= $col ?>" value="<?= vp_safe_html($val) ?>" <?= $is_create ? 'required' : '' ?>>
            <?php else: ?>
                <input class="vp-input" type="<?= $type ?>" name="<?= $col ?>" value="<?= vp_safe_html($val) ?>" <?= $is_create && in_array($col, ['name','title','sku','email','question','description','content','answer','message','requirements','slug'], true) ? 'required' : '' ?>>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="flex items-center gap-2">
        <button class="vp-btn vp-btn-primary" type="submit"><?= $is_create ? 'Create' : 'Save' ?></button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url(str_replace('/save', '', $form_url)) ?>">Cancel</a>
    </div>
</form>
