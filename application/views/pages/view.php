<?php
/**
 * Public CMS page — content managed in Dashboard → Website → Pages.
 * @var array $page
 */
$wide = ($page['template'] ?? 'default') === 'wide';
?>
<?php if (!empty($is_draft)): ?>
    <div class="bg-amber-100 border-b border-amber-300 text-amber-900 text-sm">
        <div class="container mx-auto px-4 py-2">
            <i class="ri-draft-line"></i> You are previewing an unpublished page (staff only).
            <?php if (vp_can('pages.manage')): ?>
                <a class="underline font-semibold ml-1" href="<?= base_url('admin/pages/edit/' . $page['id']) ?>">Edit page</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($page['featuredImage'])): ?>
    <section class="relative bg-ink-900 min-h-[280px] flex items-end">
        <img src="<?= vp_safe_html(vp_asset_url($page['featuredImage'])) ?>" alt="<?= vp_safe_html($page['title']) ?>"
             class="absolute inset-0 w-full h-full object-cover" decoding="async">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative container mx-auto px-4 py-12">
            <div class="vp-writeup-band vp-writeup-overlay max-w-2xl rounded-2xl p-6 md:p-8">
                <h1 class="text-4xl lg:text-5xl font-extrabold"><?= vp_safe_html($page['title']) ?></h1>
                <?php if (!empty($page['excerpt'])): ?>
                    <p class="text-lg mt-3 max-w-2xl"><?= vp_safe_html($page['excerpt']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php else: ?>
    <section class="vp-writeup-band bg-gray-50 border-b">
        <div class="container mx-auto px-4 py-12">
            <h1 class="text-3xl lg:text-4xl font-extrabold text-ink-900"><?= vp_safe_html($page['title']) ?></h1>
            <?php if (!empty($page['excerpt'])): ?>
                <p class="text-ink-800 mt-3 max-w-2xl"><?= vp_safe_html($page['excerpt']) ?></p>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<article class="container mx-auto px-4 py-12">
    <div class="vp-prose prose max-w-none <?= $wide ? '' : 'lg:max-w-3xl lg:mx-auto' ?>">
        <?= $page['content'] ?>
    </div>

    <?php if (!empty($sections)): ?>
        </article>
        <?php $this->load->view('partials/cms_sections', ['cms_sections' => $sections, 'cms_blocks' => $blocks ?? []]); ?>
        <article class="container mx-auto px-4 py-4">
    <?php endif; ?>
</article>
