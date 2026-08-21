// Vortex Precision - public site JS
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var t = document.getElementById('vp-mobile-toggle');
        var m = document.getElementById('vp-mobile-menu');
        if (t && m) {
            t.addEventListener('click', function () { m.classList.toggle('hidden'); });
        }
        // Auto-dismiss flash messages after 6 seconds
        document.querySelectorAll('.container .bg-blue-50, .container .bg-green-50, .container .bg-red-50').forEach(function (el) {
            setTimeout(function () { el.style.opacity = '0'; el.style.transition = 'opacity .4s'; setTimeout(function(){ el.remove(); }, 500); }, 6000);
        });
    });
})();

// RFQ page: "+ Add line item" / per-row delete.
// External file - the production CSP blocks inline scripts.
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var items = document.getElementById('vp-items');
        var addBtn = document.getElementById('vp-item-add');
        if (!items || !addBtn) return;

        addBtn.addEventListener('click', function () {
            var div = document.createElement('div');
            div.className = 'vp-item-row grid grid-cols-12 gap-2';
            div.innerHTML = '<input class="vp-input col-span-6" name="item_name[]" placeholder="Product or service" required>'
                + '<input class="vp-input col-span-2" name="item_qty[]" type="number" min="1" value="1" required>'
                + '<input class="vp-input col-span-3" name="item_spec[]" placeholder="Specifications">'
                + '<button type="button" class="vp-btn vp-btn-secondary col-span-1 vp-item-del" aria-label="Remove line">×</button>';
            items.appendChild(div);
        });
        items.addEventListener('click', function (e) {
            if (e.target.classList.contains('vp-item-del')) {
                if (items.querySelectorAll('.vp-item-row').length > 1) {
                    e.target.closest('.vp-item-row').remove();
                }
            }
        });
    });
})();
