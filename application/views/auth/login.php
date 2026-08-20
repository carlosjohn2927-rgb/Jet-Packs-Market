<section class="relative bg-ink-900 text-white overflow-hidden min-h-[300px] flex items-end">
    <img src="<?= IMG_URL ?>customer-portal.jpg" alt="Secure industrial customer portal" class="absolute inset-0 w-full h-full object-cover object-center" fetchpriority="high" decoding="async">
    <div class="absolute inset-0 bg-gradient-to-r from-ink-900 via-ink-900/90 to-ink-900/20"></div>
    <div class="container mx-auto px-4 py-12 relative">
        <h1 class="text-3xl font-extrabold"><?= vp_safe_html($page_title ?: "Sign in") ?></h1>
        <p class="text-white mt-1">Sign in to access quotes, downloads and personalised content.</p>
    </div>
</section>
<section class="container mx-auto px-4 py-12">
    <div class="max-w-md mx-auto vp-card vp-card-pad">
        <form method="post" action="<?= base_url('login') ?>" class="space-y-4">
            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
            <?php if (!empty($next)): ?><input type="hidden" name="next" value="<?= vp_safe_html($next) ?>"><?php endif; ?>

            <div>
                <label for="email">Email</label>
                <input class="vp-input" type="email" id="email" name="email" required value="<?= vp_safe_html($this->input->post('email')) ?>">
                <?= form_error('email', '<div class="vp-error">', '</div>') ?>
            </div>
            <div>
                <label for="password">Password</label>
                <input class="vp-input" type="password" id="password" name="password" required>
                <?= form_error('password', '<div class="vp-error">', '</div>') ?>
            </div>
            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-ink-900">
                    <input type="checkbox" name="remember" value="1"> Remember me
                </label>
                <a class="text-sm text-brand-600 hover:underline" href="<?= base_url('forgot') ?>">Forgot password?</a>
            </div>
            <button type="submit" class="vp-btn vp-btn-primary w-full justify-center">Sign in</button>
        </form>
        <p class="text-sm text-ink-800 mt-4 text-center">
            No account yet? <a class="text-brand-600 hover:underline" href="<?= base_url('register') ?>">Create one</a>
        </p>
        <p class="text-xs text-ink-800 mt-4 text-center">
            Admin? <a class="hover:underline" href="<?= base_url('admin/login') ?>">Sign in to admin</a>
        </p>
    </div>
</section>
