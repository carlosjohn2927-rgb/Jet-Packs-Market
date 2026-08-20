<?php
/** Newsletter sign-up. @var array $section */
$this->load->view('partials/sections/_helpers');
?>
<section class="bg-gray-50 border-y"<?= vp_section_style_attr($section) ?>>
    <div class="container mx-auto px-4 py-14 text-center max-w-2xl">
        <?php if (!empty($section['title'])): ?><h2 class="text-2xl font-extrabold text-ink-900"><?= vp_safe_html($section['title']) ?></h2><?php endif; ?>
        <?php if (!empty($section['subtitle'])): ?><p class="text-ink-800 mt-2"><?= vp_safe_html($section['subtitle']) ?></p><?php endif; ?>
        <form method="post" action="<?= base_url('contact/submit') ?>" class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
            <input type="hidden" name="subject" value="Newsletter subscription">
            <input type="hidden" name="message" value="Please add me to the newsletter.">
            <input type="hidden" name="name" value="Newsletter subscriber">
            <input class="vp-input sm:w-80" type="email" name="email" required placeholder="you@company.com">
            <button class="vp-btn vp-btn-primary justify-center" type="submit"><?= vp_safe_html($section['buttonText'] ?: 'Subscribe') ?></button>
        </form>
        <?php if (!empty($section['body'])): ?><div class="text-xs text-ink-800/70 mt-3"><?= $section['body'] ?></div><?php endif; ?>
    </div>
</section>
