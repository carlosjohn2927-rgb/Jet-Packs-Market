<?php
/**
 * Shared "coming soon / in progress" view used by stub controllers.
 * Receives: $cs_title, $cs_intro
 */
$cs_title = $cs_title ?? 'Coming soon';
$cs_intro = $cs_intro ?? 'This page is part of the conversion. It will be fully implemented in the next build steps.';
?>
<section class="vp-writeup-band bg-white border-b">
    <div class="container mx-auto px-4 py-16">
        <span class="text-xs font-semibold tracking-widest uppercase text-white bg-white/15 px-3 py-1 rounded-full">Coming soon</span>
        <h1 class="text-4xl font-extrabold mt-3"><?= vp_safe_html($cs_title) ?></h1>
        <p class="mt-3 max-w-2xl"><?= vp_safe_html($cs_intro) ?></p>
    </div>
</section>
<section class="container mx-auto px-4 py-16">
    <div class="vp-card vp-card-pad max-w-2xl">
        <h2 class="text-xl font-bold">What works today</h2>
        <p class="text-ink-800 mt-2">The site skeleton, layouts, CSS/JS, the public home page, the database schema, and the libraries (Auth, RBAC, Settings, Mailer, Audit, Rate_limiter, Upload) are all wired up.</p>
        <h3 class="font-bold mt-6">What is being built next</h3>
        <ol class="list-decimal pl-5 mt-2 text-ink-900 space-y-1">
            <li>Auth + Users + RBAC (login / register / admin login)</li>
            <li>Catalog: Products / Categories / Industries / Downloads (public + admin)</li>
            <li>CMS: Home / About / Services (settings-driven)</li>
            <li>RFQ (public form + admin list/status update/PDF/CSV with hardened state machine)</li>
            <li>Content: Blog / Careers / Contacts / FAQs / News / Testimonials / Partners</li>
            <li>Admin polish: Dashboard, Audit log, Notifications, Media library</li>
            <li>Docs + cleanup</li>
        </ol>
        <div class="mt-6 flex flex-wrap gap-2">
            <a href="<?= base_url() ?>" class="vp-btn vp-btn-primary">Back to home</a>
            <a href="<?= base_url('products') ?>" class="vp-btn vp-btn-secondary">Products</a>
            <a href="<?= base_url('rfq') ?>" class="vp-btn vp-btn-secondary">Request a Quote</a>
        </div>
    </div>
</section>
