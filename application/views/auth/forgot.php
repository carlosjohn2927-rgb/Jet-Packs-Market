<section class="container mx-auto px-4 py-12">
    <div class="max-w-md mx-auto vp-card overflow-hidden">
        <img src="<?= IMG_URL ?>customer-portal.jpg" alt="Secure customer account access" class="w-full h-40 object-cover" loading="eager" decoding="async">
        <div class="p-6">
            <h1 class="text-2xl font-bold">Reset your password</h1>
            <p class="text-sm text-ink-800 mt-2">Enter your account email and we will send you a link to choose a new password.</p>
            <form method="post" action="<?= base_url('forgot') ?>" class="mt-4 space-y-4">
                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                <div>
                    <label for="email">Email</label>
                    <input class="vp-input" type="email" id="email" name="email" required autofocus value="<?= vp_safe_html($this->input->post('email')) ?>">
                    <?= form_error('email', '<div class="vp-error">', '</div>') ?>
                </div>
                <button class="vp-btn vp-btn-primary w-full justify-center" type="submit">Send reset link</button>
            </form>
            <p class="text-sm text-ink-800 mt-4 text-center">
                <a class="text-brand-600 hover:underline" href="<?= base_url('login') ?>">Back to sign in</a>
            </p>
        </div>
    </div>
</section>
