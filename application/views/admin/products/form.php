<?php
/** @var array|null $product */
/** @var array $industries */
/** @var array $categories */
/** @var array $all_products */
/** @var array $selected_industries */
/** @var array $selected_related */
/** @var string $certifications_csv */
/** @var array $specs_rows */
$is_create = empty($product);
$action = $is_create ? base_url('admin/products/save') : base_url('admin/products/save');
?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
    <?php if (!$is_create): ?><input type="hidden" name="id" value="<?= $product['id'] ?>"><?php endif; ?>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Basics</h2>
        <div class="vp-grid-2">
            <div class="vp-form-row">
                <label>Name *</label>
                <input class="vp-input" name="name" required value="<?= vp_safe_html($product['name'] ?? '') ?>">
            </div>
            <div class="vp-form-row">
                <label>SKU *</label>
                <input class="vp-input" name="sku" required value="<?= vp_safe_html($product['sku'] ?? '') ?>">
            </div>
        </div>
        <div class="vp-grid-2">
            <div class="vp-form-row">
                <label>Slug</label>
                <input class="vp-input" name="slug" value="<?= vp_safe_html($product['slug'] ?? '') ?>" placeholder="auto from name">
                <p class="vp-help">URL: /products/<span id="vp-slug-preview"><?= vp_safe_html($product['slug'] ?? 'your-slug') ?></span></p>
            </div>
            <div class="vp-form-row">
                <label>Category</label>
                <select class="vp-select" name="categoryId">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($product['categoryId'] ?? '') === $c['id'] ? 'selected' : '' ?>><?= vp_safe_html($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="vp-form-row">
            <label>Short description</label>
            <textarea class="vp-textarea" name="shortDescription" rows="2"><?= vp_safe_html($product['shortDescription'] ?? '') ?></textarea>
        </div>
        <div class="vp-form-row">
            <label>Description *</label>
            <textarea class="vp-textarea" name="description" rows="8" required><?= vp_safe_html($product['description'] ?? '') ?></textarea>
            <p class="vp-help">HTML allowed.</p>
        </div>
    </div>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Specs & ratings</h2>
        <div class="vp-grid-3">
            <div class="vp-form-row"><label>Material</label><input class="vp-input" name="material" value="<?= vp_safe_html($product['material'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Pressure</label><input class="vp-input" name="pressure" value="<?= vp_safe_html($product['pressure'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Temperature</label><input class="vp-input" name="temperature" value="<?= vp_safe_html($product['temperature'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Voltage</label><input class="vp-input" name="voltage" value="<?= vp_safe_html($product['voltage'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Dimensions</label><input class="vp-input" name="dimensions" value="<?= vp_safe_html($product['dimensions'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Weight</label><input class="vp-input" name="weight" value="<?= vp_safe_html($product['weight'] ?? '') ?>"></div>
        </div>
        <div class="vp-grid-2">
            <div class="vp-form-row"><label>Price (USD)</label><input class="vp-input" type="number" step="0.01" name="price" value="<?= vp_safe_html($product['price'] ?? '') ?>"></div>
            <div class="vp-form-row">
                <label>Availability</label>
                <select class="vp-select" name="availability">
                    <?php foreach (['IN_STOCK','MADE_TO_ORDER','DISCONTINUED'] as $a): ?>
                        <option value="<?= $a ?>" <?= ($product['availability'] ?? 'IN_STOCK') === $a ? 'selected' : '' ?>><?= str_replace('_',' ',$a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="vp-form-row">
            <label>Certifications (comma-separated)</label>
            <input class="vp-input" name="certifications_csv" value="<?= vp_safe_html($certifications_csv) ?>" placeholder="ASME B31.3, API 610, ISO 9001">
        </div>
    </div>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Images</h2>
        <div class="vp-form-row">
            <label>Add new images</label>
            <input class="vp-input" type="file" name="images[]" accept="image/*" multiple>
            <p class="vp-help">You can select multiple images at once. All will be saved as additional product photos. Auto-resized to max 1600px wide.</p>
        </div>
        <?php if (!$is_create && $product): ?>
            <?php
            $existing_images = $this->db->get_where('product_images', ['productId' => $product['id']])->result_array();
            ?>
            <?php if (!empty($existing_images)): ?>
                <div class="mt-4">
                    <div class="text-sm font-semibold mb-2">Current images</div>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                        <?php foreach ($existing_images as $img): ?>
                            <div class="relative group border rounded overflow-hidden aspect-square">
                                <img src="<?= base_url($img['url']) ?>" alt="" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1">
                                    <?php if (empty($img['isPrimary'])): ?>
                                        <form action="<?= base_url('admin/products/' . $product['id'] . '/images/' . $img['id'] . '/primary') ?>" method="post">
                                            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                                            <button class="vp-btn vp-btn-secondary vp-btn-sm text-xs" type="submit" title="Make primary">★</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="vp-pill bg-yellow-400 text-yellow-900 text-xs">Primary</span>
                                    <?php endif; ?>
                                    <form action="<?= base_url('admin/products/' . $product['id'] . '/images/' . $img['id'] . '/delete') ?>" method="post" data-confirm="Delete this image?">
                                        <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                                        <button class="vp-btn vp-btn-danger vp-btn-sm text-xs" type="submit" title="Delete">×</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="vp-help mt-2">Hover an image to set as primary or delete. Uploading new images adds them without removing existing ones.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Specifications (key/value pairs)</h2>
        <div id="vp-specs" class="space-y-2">
            <?php
            $rows = !empty($specs_rows) ? $specs_rows : [['key'=>'','value'=>'','unit'=>'']];
            foreach ($rows as $i => $s): ?>
            <div class="vp-spec-row grid grid-cols-12 gap-2">
                <input class="vp-input col-span-4" name="spec_key[]"   value="<?= vp_safe_html($s['key']) ?>"   placeholder="Key (e.g. Flow)">
                <input class="vp-input col-span-5" name="spec_value[]" value="<?= vp_safe_html($s['value']) ?>" placeholder="Value (e.g. 50)">
                <input class="vp-input col-span-2" name="spec_unit[]"  value="<?= vp_safe_html($s['unit'] ?? '') ?>" placeholder="Unit">
                <button type="button" class="vp-btn vp-btn-secondary col-span-1 vp-spec-del" title="Remove">×</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="vp-spec-add" class="vp-btn vp-btn-secondary mt-3 vp-btn-sm">+ Add specification</button>
    </div>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">Industries & related products</h2>
        <div class="vp-form-row">
            <label>Industries</label>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-2">
                <?php foreach ($industries as $i): ?>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="industries[]" value="<?= $i['id'] ?>" <?= in_array($i['id'], $selected_industries) ? 'checked' : '' ?>>
                        <?= vp_safe_html($i['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="vp-form-row">
            <label>Related products (max 12)</label>
            <select class="vp-select" name="related[]" multiple size="6">
                <?php foreach ($all_products as $ap): ?>
                    <option value="<?= $ap['id'] ?>" <?= in_array($ap['id'], $selected_related) ? 'selected' : '' ?>><?= vp_safe_html($ap['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="vp-card vp-card-pad">
        <h2 class="font-bold text-lg mb-4">SEO & status</h2>
        <div class="vp-grid-2">
            <div class="vp-form-row"><label>Meta title</label><input class="vp-input" name="metaTitle" value="<?= vp_safe_html($product['metaTitle'] ?? '') ?>"></div>
            <div class="vp-form-row"><label>Meta keywords (comma)</label><input class="vp-input" name="metaKeywords" value="<?= vp_safe_html(implode(', ', json_decode($product['metaKeywords'] ?? '[]', true) ?: [])) ?>"></div>
        </div>
        <div class="vp-form-row"><label>Meta description</label><textarea class="vp-textarea" name="metaDescription" rows="2"><?= vp_safe_html($product['metaDescription'] ?? '') ?></textarea></div>
        <div class="vp-grid-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="featured" value="1" <?= !empty($product['featured']) ? 'checked' : '' ?>> Featured on home page
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="isActive" value="0">
                <input type="checkbox" name="isActive" value="1" <?= (!$is_create || !empty($product['isActive'])) ? 'checked' : '' ?>> Active
            </label>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <button class="vp-btn vp-btn-primary" type="submit"><?= $is_create ? 'Create product' : 'Save changes' ?></button>
        <a class="vp-btn vp-btn-secondary" href="<?= base_url('admin/products') ?>">Cancel</a>
    </div>
</form>

<?php /* Slug preview + spec rows are wired up by assets/js/admin.js
         (inline scripts are blocked by the production CSP). */ ?>
