<?php /** @var array $contact */ ?>
<?php $this->load->view('partials/photo_writeup_hero', [
    'hero_image'         => IMG_URL . 'contact-engineer.jpg',
    'hero_alt'           => 'Industrial engineer discussing a customer project',
    'hero_title_html'    => vp_inline_text('contact_hero_title', 'Contact us', 'h1', 'text-4xl lg:text-5xl font-extrabold'),
    'hero_subtitle_html' => vp_inline_text('contact_hero_subtitle', 'Sales, service, careers and general enquiries - we respond within 1 business day.', 'p', 'mt-3 max-w-2xl text-lg'),
]); ?>
<section class="container mx-auto px-4 py-12 grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 vp-card vp-card-pad">
        <form method="post" action="<?= base_url('contact/submit') ?>" class="space-y-4">
            <input type="hidden" name="<?= $csrf_token_name ?>" value="<?= $csrf_token ?>">
            <div class="vp-grid-2">
                <div class="vp-form-row"><label>Name *</label><input class="vp-input" name="name" required value="<?= vp_safe_html($this->input->post('name')) ?>"></div>
                <div class="vp-form-row"><label>Email *</label><input class="vp-input" type="email" name="email" required value="<?= vp_safe_html($this->input->post('email')) ?>"></div>
            </div>
            <div class="vp-grid-2">
                <div class="vp-form-row"><label>Company</label><input class="vp-input" name="company" value="<?= vp_safe_html($this->input->post('company')) ?>"></div>
                <div class="vp-form-row"><label>Phone</label><input class="vp-input" name="phone" value="<?= vp_safe_html($this->input->post('phone')) ?>"></div>
            </div>
            <div class="vp-grid-2">
                <div class="vp-form-row"><label>Department</label>
                    <select class="vp-select" name="department">
                        <option value="">General</option>
                        <option>Sales</option>
                        <option>Engineering</option>
                        <option>Service / Spares</option>
                        <option>Careers</option>
                    </select>
                </div>
                <div class="vp-form-row"><label>Subject *</label><input class="vp-input" name="subject" required value="<?= vp_safe_html($this->input->post('subject')) ?>"></div>
            </div>
            <div class="vp-form-row"><label>Message *</label><textarea class="vp-textarea" name="message" rows="6" required><?= vp_safe_html($this->input->post('message')) ?></textarea></div>
            <button class="vp-btn vp-btn-primary" type="submit"><i class="ri-send-plane-line"></i> Send message</button>
        </form>
    </div>
    <aside class="space-y-4">
        <div class="vp-card overflow-hidden">
            <img src="<?= IMG_URL ?>contact-engineer.jpg" alt="Industrial engineer discussing a customer project" class="w-full aspect-[4/3] object-cover" loading="lazy" decoding="async">
            <div class="p-5"><p class="text-sm text-ink-800">Talk directly with an engineer who understands your process and specifications.</p></div>
        </div>
        <div class="vp-card vp-card-pad">
            <h3 class="font-bold mb-2">Headquarters</h3>
            <p class="text-sm text-ink-800"><?= vp_safe_html($contact['address'] ?? '') ?></p>
        </div>
        <div class="vp-card vp-card-pad">
            <h3 class="font-bold mb-2">Sales</h3>
            <p class="text-sm"><a class="text-brand-600" href="mailto:<?= vp_safe_html($contact['email'] ?? '') ?>"><?= vp_safe_html($contact['email'] ?? '') ?></a></p>
            <p class="text-sm"><?= vp_safe_html($contact['phone'] ?? '') ?></p>
        </div>
        <div class="vp-card vp-card-pad">
            <h3 class="font-bold mb-2">RFQ</h3>
            <p class="text-sm text-ink-800">Use the <a class="text-brand-600 hover:underline" href="<?= base_url('rfq') ?>">Request a Quote</a> form for project enquiries.</p>
        </div>
    </aside>
</section>

<?php
$map_address = vp_map_query($contact['address'] ?? '');
$map_search  = vp_maps_search_url($map_address);
$map_embed   = vp_map_embed_url($map_address);
?>
<section class="container mx-auto px-4 pb-12">
    <div class="vp-card overflow-hidden">
        <div class="vp-card-pad border-b">
            <h2 class="text-2xl font-bold mb-2">Find us</h2>
            <?php if ($map_address): ?>
                <p class="text-sm mb-3"><i class="ri-map-pin-line"></i> <?= vp_safe_html($map_address) ?></p>
            <?php endif; ?>
            <a class="text-sm font-semibold text-brand-600 hover:underline" href="<?= vp_safe_html($map_search) ?>" target="_blank" rel="noopener">
                Open in Google Maps <i class="ri-external-link-line"></i>
            </a>
        </div>
        <div id="vp-contact-map" class="vp-contact-map"
             data-address="<?= vp_safe_html($map_address) ?>"
             data-maps-url="<?= vp_safe_html($map_search) ?>"
             role="region" aria-label="Map of our location"></div>
        <noscript>
            <iframe
                src="<?= vp_safe_html($map_embed) ?>"
                width="100%" height="420" style="border:0; display:block;"
                allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Map of our location"></iframe>
        </noscript>
    </div>
</section>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= JS_URL ?>contact-map.js?v=<?= VP_ASSET_VERSION ?>"></script>
