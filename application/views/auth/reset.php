<section class="container mx-auto px-4 py-12">
    <div class="max-w-md mx-auto vp-card overflow-hidden">
        <img src="<?= IMG_URL ?>customer-portal.jpg" alt="Secure customer account access" class="w-full h-40 object-cover" loading="eager" decoding="async">
        <div class="p-6">
            <h1 class="text-2xl font-bold">Choose a new password</h1>
            <p class="text-sm text-ink-800 mt-2">Enter a new password of at least 8 characters for your account.</p>
            <form method="post" action="<?= base_url('reset/' . ($token ?? '')) ?>" class="mt-4 space-y-4">
                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
                <div>
                    <label for="password">New password</label>
                    <input class="vp-input" type="password" id="password" name="password" required minlength="8" autofocus>
                    <?= form_error('password', '<div class="vp-error">', '</div>') ?>
                </div>
                <div>
                    <label for="password2">Confirm new password</label>
                    <input class="vp-input" type="password" id="password2" name="password2" required minlength="8">
                    <?= form_error('password2', '<div class="vp-error">', '</div>') ?>
                </div>
                <button class="vp-btn vp-btn-primary w-full justify-center" type="submit">Reset password</button>
            </form>
            <p class="text-sm text-ink-800 mt-4 text-center">
                <a class="text-brand-600 hover:underline" href="<?= base_url('login') ?>">Back to sign in</a>
            </p>
        </div>
    </div>
</section>
