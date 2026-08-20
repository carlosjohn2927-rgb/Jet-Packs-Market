<section class="relative bg-ink-900 text-white overflow-hidden min-h-[300px] flex items-end">
    <img src="<?= IMG_URL ?>customer-portal.jpg" alt="Industrial equipment customer portal" class="absolute inset-0 w-full h-full object-cover object-center" fetchpriority="high" decoding="async">
    <div class="absolute inset-0 bg-gradient-to-r from-ink-900 via-ink-900/90 to-ink-900/20"></div>
    <div class="container mx-auto px-4 py-12 relative">
        <h1 class="text-3xl font-extrabold">Create your account</h1>
        <p class="text-white mt-1">Track your RFQs, download technical files, and get personalised recommendations.</p>
    </div>
</section>
<section class="container mx-auto px-4 py-12">
    <div class="max-w-xl mx-auto vp-card vp-card-pad">
        <form method="post" action="<?= base_url('register') ?>" class="space-y-4">
            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
            <div class="vp-grid-2">
                <div>
                    <label for="firstName">First name</label>
                    <input class="vp-input" type="text" id="firstName" name="firstName" required value="<?= vp_safe_html($this->input->post('firstName')) ?>">
                </div>
                <div>
                    <label for="lastName">Last name</label>
                    <input class="vp-input" type="text" id="lastName" name="lastName" required value="<?= vp_safe_html($this->input->post('lastName')) ?>">
                </div>
            </div>
            <div>
                <label for="email">Email</label>
                <input class="vp-input" type="email" id="email" name="email" required value="<?= vp_safe_html($this->input->post('email')) ?>">
            </div>
            <div>
                <label for="password">Password</label>
                <input class="vp-input" type="password" id="password" name="password" required minlength="8">
                <p class="vp-help">At least 8 characters.</p>
            </div>
            <div class="vp-grid-2">
                <div>
                    <label for="company">Company (optional)</label>
                    <input class="vp-input" type="text" id="company" name="company" value="<?= vp_safe_html($this->input->post('company')) ?>">
                </div>
                <div>
                    <label for="phone">Phone (optional)</label>
                    <input class="vp-input" type="text" id="phone" name="phone" value="<?= vp_safe_html($this->input->post('phone')) ?>">
                </div>
            </div>
            <button type="submit" class="vp-btn vp-btn-primary w-full justify-center">Create account</button>
        </form>
        <p class="text-sm text-ink-800 mt-4 text-center">
            Already have an account? <a class="text-brand-600 hover:underline" href="<?= base_url('login') ?>">Sign in</a>
        </p>
    </div>
</section>
