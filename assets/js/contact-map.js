/**
 * Contact page map — Leaflet + OpenStreetMap (no iframe, so it is not
 * blocked by the site Content-Security-Policy).
 *
 * Expects #vp-contact-map with data-address and data-maps-url.
 */
(function () {
    var el = document.getElementById('vp-contact-map');
    if (!el) return;

    var address = (el.getAttribute('data-address') || '').trim() || 'Houston, TX';
    var mapsUrl = el.getAttribute('data-maps-url') || ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(address));
    var fallback = { lat: 29.7604, lon: -95.3698, zoom: 12 };

    function linkHtml() {
        return '<a class="text-brand-600 font-semibold" href="' + mapsUrl + '" target="_blank" rel="noopener">Open in Google Maps</a>';
    }

    function showIframe() {
        var iframe = document.createElement('iframe');
        iframe.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(address) + '&hl=en&z=15&output=embed';
        iframe.width = '100%';
        iframe.height = '420';
        iframe.style.border = '0';
        iframe.style.display = 'block';
        iframe.title = 'Map of our location';
        iframe.loading = 'lazy';
        iframe.referrerPolicy = 'no-referrer-when-downgrade';
        iframe.setAttribute('allowfullscreen', '');
        el.innerHTML = '';
        el.appendChild(iframe);
    }

    function showFallback(message) {
        showIframe();
        if (!el.querySelector('iframe')) {
            el.innerHTML = '<div style="height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:24px;text-align:center;font-size:14px;">'
                + '<p>' + (message || 'Map preview is unavailable.') + '</p>'
                + '<p>' + linkHtml() + '</p>'
                + '</div>';
        }
    }

    if (typeof L === 'undefined') {
        showFallback('Interactive map could not be loaded.');
        return;
    }

    L.Icon.Default.imagePath = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/';

    var map = L.map(el, { scrollWheelZoom: false }).setView([fallback.lat, fallback.lon], fallback.zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    function place(lat, lon, zoom) {
        map.setView([lat, lon], zoom);
        L.marker([lat, lon]).addTo(map).bindPopup(address).openPopup();
        setTimeout(function () { map.invalidateSize(); }, 200);
    }

    var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(address);
    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(function (res) { return res.ok ? res.json() : []; })
        .then(function (results) {
            if (results && results[0]) {
                place(parseFloat(results[0].lat), parseFloat(results[0].lon), 15);
            } else {
                place(fallback.lat, fallback.lon, fallback.zoom);
            }
        })
        .catch(function () {
            place(fallback.lat, fallback.lon, fallback.zoom);
        });
})();
