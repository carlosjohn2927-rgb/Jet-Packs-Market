<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — admin form widgets.
 *
 * Small HTML builders shared by every dashboard editor so the CMS forms stay
 * consistent (and so image fields always go through the media library).
 */

if (!function_exists('vp_field_id')) {
    function vp_field_id($name)
    {
        return 'f_' . preg_replace('/[^a-z0-9_]+/i', '_', (string) $name);
    }
}

if (!function_exists('vp_media_field')) {
    /**
     * Image/file field with live preview, media-library picker and clear button.
     */
    function vp_media_field($name, $value = '', $label = '', $help = '')
    {
        $id  = vp_field_id($name);
        $val = (string) $value;
        ob_start(); ?>
        <div class="space-y-2">
            <?php if ($label !== ''): ?>
                <label class="vp-label" for="<?= $id ?>"><?= vp_safe_html($label) ?></label>
            <?php endif; ?>
            <div class="flex items-start gap-3">
                <div class="w-24 h-24 rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                    <img data-vp-preview-for="<?= $id ?>" src="<?= vp_safe_html($val ?: '') ?>"
                         alt="" class="w-full h-full object-contain <?= $val === '' ? 'hidden' : '' ?>">
                    <?php if ($val === ''): ?><i class="ri-image-line text-2xl text-gray-400"></i><?php endif; ?>
                </div>
                <div class="flex-1 min-w-0 space-y-2">
                    <input class="vp-input" type="text" id="<?= $id ?>" name="<?= vp_safe_html($name) ?>"
                           value="<?= vp_safe_html($val) ?>" placeholder="/assets/uploads/… or https://…">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="vp-btn vp-btn-secondary text-xs" data-vp-media-target="<?= $id ?>">
                            <i class="ri-folder-image-line"></i> Choose from media
                        </button>
                        <button type="button" class="vp-btn vp-btn-secondary text-xs" data-vp-media-clear="<?= $id ?>">
                            <i class="ri-delete-bin-line"></i> Remove
                        </button>
                    </div>
                    <?php if ($help !== ''): ?><p class="text-xs text-ink-800/60"><?= vp_safe_html($help) ?></p><?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('vp_text_field')) {
    function vp_text_field($name, $value = '', $label = '', $attrs = [])
    {
        $id = vp_field_id($name);
        $type = $attrs['type'] ?? 'text';
        $ph   = $attrs['placeholder'] ?? '';
        $help = $attrs['help'] ?? '';
        ob_start(); ?>
        <div>
            <?php if ($label !== ''): ?><label class="vp-label" for="<?= $id ?>"><?= vp_safe_html($label) ?></label><?php endif; ?>
            <input class="vp-input" type="<?= vp_safe_html($type) ?>" id="<?= $id ?>" name="<?= vp_safe_html($name) ?>"
                   value="<?= vp_safe_html((string) $value) ?>" placeholder="<?= vp_safe_html($ph) ?>"
                   <?= !empty($attrs['required']) ? 'required' : '' ?>>
            <?php if ($help !== ''): ?><p class="text-xs text-ink-800/60 mt-1"><?= vp_safe_html($help) ?></p><?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('vp_textarea_field')) {
    function vp_textarea_field($name, $value = '', $label = '', $rows = 4, $help = '')
    {
        $id = vp_field_id($name);
        ob_start(); ?>
        <div>
            <?php if ($label !== ''): ?><label class="vp-label" for="<?= $id ?>"><?= vp_safe_html($label) ?></label><?php endif; ?>
            <textarea class="vp-input" id="<?= $id ?>" name="<?= vp_safe_html($name) ?>" rows="<?= (int) $rows ?>"><?= vp_safe_html((string) $value) ?></textarea>
            <?php if ($help !== ''): ?><p class="text-xs text-ink-800/60 mt-1"><?= vp_safe_html($help) ?></p><?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('vp_toggle_field')) {
    /** Checkbox rendered as a switch, with a hidden 0 so "off" is submitted. */
    function vp_toggle_field($name, $checked = false, $label = '', $help = '')
    {
        $id = vp_field_id($name);
        ob_start(); ?>
        <label class="flex items-start gap-3 cursor-pointer select-none" for="<?= $id ?>">
            <input type="hidden" name="<?= vp_safe_html($name) ?>" value="0">
            <input type="checkbox" id="<?= $id ?>" name="<?= vp_safe_html($name) ?>" value="1"
                   class="mt-1 w-4 h-4 rounded border-gray-300 text-brand-600" <?= $checked ? 'checked' : '' ?>>
            <span>
                <span class="font-medium text-sm text-ink-900"><?= vp_safe_html($label) ?></span>
                <?php if ($help !== ''): ?><span class="block text-xs text-ink-800/60"><?= vp_safe_html($help) ?></span><?php endif; ?>
            </span>
        </label>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('vp_select_field')) {
    function vp_select_field($name, $options, $value = '', $label = '', $help = '')
    {
        $id = vp_field_id($name);
        ob_start(); ?>
        <div>
            <?php if ($label !== ''): ?><label class="vp-label" for="<?= $id ?>"><?= vp_safe_html($label) ?></label><?php endif; ?>
            <select class="vp-input" id="<?= $id ?>" name="<?= vp_safe_html($name) ?>">
                <?php foreach ($options as $k => $v): ?>
                    <option value="<?= vp_safe_html((string) $k) ?>" <?= ((string) $k === (string) $value) ? 'selected' : '' ?>><?= vp_safe_html((string) $v) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($help !== ''): ?><p class="text-xs text-ink-800/60 mt-1"><?= vp_safe_html($help) ?></p><?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('vp_color_field')) {
    /**
     * Colour picker + hex text field, kept in sync by assets/js/admin.js.
     */
    function vp_color_field($name, $value = '', $label = '', $help = '', $css_var = '')
    {
        $id  = vp_field_id($name);
        $val = function_exists('vp_sanitize_hex_color')
            ? vp_sanitize_hex_color($value, '#000000')
            : (string) $value;
        $var = $css_var !== '' ? ' data-vp-theme-var="' . vp_safe_html($css_var) . '"' : '';
        ob_start(); ?>
        <div data-vp-color>
            <?php if ($label !== ''): ?><label class="vp-label" for="<?= $id ?>"><?= vp_safe_html($label) ?></label><?php endif; ?>
            <div class="flex items-center gap-2">
                <input type="color" value="<?= vp_safe_html($val) ?>"
                       class="h-10 w-12 rounded-lg border cursor-pointer p-0 bg-transparent" aria-label="<?= vp_safe_html($label) ?> picker"
                       data-vp-color-picker<?= $var ?>>
                <input class="vp-input font-mono" type="text" id="<?= $id ?>" name="<?= vp_safe_html($name) ?>"
                       value="<?= vp_safe_html($val) ?>" maxlength="7" pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$"
                       placeholder="#000000" data-vp-color-text<?= $var ?>>
            </div>
            <?php if ($help !== ''): ?><p class="text-xs text-ink-800/60 mt-1"><?= vp_safe_html($help) ?></p><?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('vp_admin_card_open')) {
    function vp_admin_card_open($title = '', $subtitle = '', $icon = '')
    {
        ob_start(); ?>
        <section class="bg-white border rounded-2xl shadow-sm">
            <?php if ($title !== ''): ?>
            <header class="px-5 py-4 border-b flex items-center gap-3">
                <?php if ($icon !== ''): ?><i class="<?= vp_safe_html($icon) ?> text-xl text-brand-600"></i><?php endif; ?>
                <div>
                    <h2 class="font-bold text-ink-900"><?= vp_safe_html($title) ?></h2>
                    <?php if ($subtitle !== ''): ?><p class="text-xs text-ink-800/60"><?= vp_safe_html($subtitle) ?></p><?php endif; ?>
                </div>
            </header>
            <?php endif; ?>
            <div class="p-5 space-y-4">
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('vp_admin_card_close')) {
    function vp_admin_card_close()
    {
        return '</div></section>';
    }
}
