<?php /** @var array $job */ ?>
<section class="bg-white border-b">
    <div class="container mx-auto px-4 py-6">
        <nav class="text-xs text-ink-800">
            <a class="hover:text-brand-600" href="<?= base_url() ?>">Home</a>
            <span class="mx-1">/</span>
            <a class="hover:text-brand-600" href="<?= base_url('careers') ?>">Careers</a>
            <span class="mx-1">/</span>
            <span class="text-ink-900"><?= vp_safe_html($job['title']) ?></span>
        </nav>
    </div>
</section>
<section class="container mx-auto px-4 py-10 grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <img src="<?= IMG_URL ?>careers-team.jpg" alt="Our engineering and fabrication team" class="w-full aspect-[16/7] object-cover rounded-2xl mb-7" loading="eager" decoding="async">
        <h1 class="text-3xl font-extrabold text-ink-900"><?= vp_safe_html($job['title']) ?></h1>
        <p class="text-sm text-ink-800 mt-1"><?= vp_safe_html($job['department']) ?> &middot; <?= vp_safe_html($job['location']) ?> &middot; <?= vp_safe_html($job['type']) ?> <?= $job['experience'] ? ' &middot; ' . vp_safe_html($job['experience']) : '' ?></p>
        <div class="vp-prose mt-6">
            <h2>Role</h2>
            <?= nl2br(vp_safe_html($job['description'])) ?>
            <h2>Requirements</h2>
            <?= nl2br(vp_safe_html($job['requirements'])) ?>
            <?php if (!empty($job['benefits'])): ?>
                <h2>Benefits</h2>
                <?= nl2br(vp_safe_html($job['benefits'])) ?>
            <?php endif; ?>
        </div>
    </div>
    <aside>
        <div class="vp-card vp-card-pad sticky top-24">
            <h3 class="font-bold mb-3">Apply</h3>
            <form method="post" action="<?= base_url('careers/apply/' . $job['slug']) ?>" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                <div class="vp-form-row"><label>Name *</label><input class="vp-input" name="name" required></div>
                <div class="vp-form-row"><label>Email *</label><input class="vp-input" type="email" name="email" required></div>
                <div class="vp-form-row"><label>Phone</label><input class="vp-input" name="phone"></div>
                <div class="vp-form-row"><label>LinkedIn</label><input class="vp-input" name="linkedin" placeholder="https://linkedin.com/in/..."></div>
                <div class="vp-form-row"><label>Cover letter</label><textarea class="vp-textarea" name="coverLetter" rows="4"></textarea></div>
                <div class="vp-form-row"><label>Resume (PDF, DOC) *</label><input class="vp-input" type="file" name="resume" accept=".pdf,.doc,.docx" required></div>
                <button class="vp-btn vp-btn-primary w-full justify-center" type="submit">Submit application</button>
            </form>
        </div>
    </aside>
</section>
