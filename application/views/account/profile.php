<?php /** @var array $user */ ?>

<section class="container mx-auto px-4 py-10">
    <h1 class="text-3xl font-extrabold text-ink-900">My profile</h1>
    <p class="text-ink-800 mt-1">Update your contact details and password.</p>

    <div class="grid lg:grid-cols-4 gap-6 mt-8">
        <?= $this->load->view('account/_nav', get_defined_vars(), TRUE) ?>

        <div class="lg:col-span-3">
            <form method="post" action="<?= base_url('account/profile') ?>" class="space-y-6">
                <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">

                <div class="vp-card vp-card-pad">
                    <h2 class="font-bold text-lg mb-4">Contact details</h2>
                    <div class="vp-grid-2">
                        <div class="vp-form-row"><label>First name *</label><input class="vp-input" name="firstName" required value="<?= vp_safe_html($user['firstName'] ?? '') ?>"></div>
                        <div class="vp-form-row"><label>Last name *</label><input class="vp-input" name="lastName" required value="<?= vp_safe_html($user['lastName'] ?? '') ?>"></div>
                        <div class="vp-form-row"><label>Email</label><input class="vp-input" value="<?= vp_safe_html($user['email'] ?? '') ?>" disabled></div>
                        <div class="vp-form-row"><label>Company</label><input class="vp-input" name="company" value="<?= vp_safe_html($user['company'] ?? '') ?>"></div>
                        <div class="vp-form-row lg:col-span-2"><label>Phone</label><input class="vp-input" name="phone" value="<?= vp_safe_html($user['phone'] ?? '') ?>"></div>
                    </div>
                </div>

                <div class="vp-card vp-card-pad">
                    <h2 class="font-bold text-lg mb-4">Change password</h2>
                    <p class="text-sm text-ink-800 mb-3">Leave blank to keep your current password.</p>
                    <div class="vp-grid-2">
                        <div class="vp-form-row"><label>Current password</label><input class="vp-input" type="password" name="current_password" autocomplete="current-password"></div>
                        <div class="vp-form-row"><label>New password</label><input class="vp-input" type="password" name="new_password" minlength="8" autocomplete="new-password"></div>
                        <div class="vp-form-row lg:col-span-2"><label>Confirm new password</label><input class="vp-input" type="password" name="new_password_confirm" autocomplete="new-password"></div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="vp-btn vp-btn-primary"><i class="ri-save-line"></i> Save changes</button>
                </div>
            </form>
        </div>
    </div>
</section>
