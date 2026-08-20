<?php /** @var string $quoteNumber */ ?>
<section class="container mx-auto px-4 py-20 text-center">
    <div class="max-w-xl mx-auto">
        <div class="w-16 h-16 rounded-full bg-green-100 text-green-700 flex items-center justify-center mx-auto text-3xl"><i class="ri-check-line"></i></div>
        <h1 class="text-3xl font-extrabold mt-4">Thank you</h1>
        <p class="text-ink-800 mt-3">We have received your Request for Quote. Your reference number is:</p>
        <div class="text-3xl font-extrabold text-brand-600 mt-2 tracking-wider"><?= vp_safe_html($quoteNumber) ?></div>
        <p class="text-ink-800 mt-3">A confirmation has been emailed to you. Our engineering team will respond within 2 business days.</p>
        <div class="mt-6 flex justify-center gap-2">
            <a href="<?= base_url() ?>" class="vp-btn vp-btn-primary">Back to home</a>
            <a href="<?= base_url('products') ?>" class="vp-btn vp-btn-secondary">Browse products</a>
        </div>
    </div>
</section>
