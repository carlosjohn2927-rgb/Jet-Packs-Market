// Halyk Petroleum - public site JS
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        // ----- Mobile navigation toggle -----
        var t = document.getElementById('vp-mobile-toggle');
        var m = document.getElementById('vp-mobile-menu');
        if (t && m) {
            t.addEventListener('click', function () {
                var open = m.classList.toggle('hidden') === false;
                t.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        // ----- Header search panel toggle -----
        var searchBtn   = document.getElementById('jpm-search-toggle');
        var searchPanel = document.getElementById('jpm-search-panel');
        var searchInput = document.getElementById('jpm-search-input');
        if (searchBtn && searchPanel) {
            var openSearch = function () {
                searchPanel.classList.remove('hidden');
                searchBtn.setAttribute('aria-expanded', 'true');
                if (searchInput) {
                    setTimeout(function () { searchInput.focus(); }, 30);
                }
            };
            var closeSearch = function () {
                searchPanel.classList.add('hidden');
                searchBtn.setAttribute('aria-expanded', 'false');
            };
            var isOpen = function () { return !searchPanel.classList.contains('hidden'); };

            searchBtn.addEventListener('click', function () {
                if (isOpen()) { closeSearch(); searchBtn.focus(); }
                else {
                    // Close the mobile menu if it happens to be open.
                    if (m && !m.classList.contains('hidden')) {
                        m.classList.add('hidden');
                        if (t) t.setAttribute('aria-expanded', 'false');
                    }
                    openSearch();
                }
            });

            // Esc closes the search panel and returns focus to the button.
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && isOpen()) {
                    closeSearch();
                    searchBtn.focus();
                }
            });

            // "/" keyboard shortcut opens search (like most commerce sites).
            document.addEventListener('keydown', function (e) {
                if (e.key === '/' && document.activeElement &&
                    !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName)) {
                    e.preventDefault();
                    if (!isOpen()) openSearch();
                }
            });
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
            div.innerHTML = '<input class="vp-input col-span-6" name="item_name[]" placeholder="Part number or part name" required>'
                + '<input class="vp-input col-span-2" name="item_qty[]" type="number" min="1" value="1" required>'
                + '<input class="vp-input col-span-3" name="item_spec[]" placeholder="Specification / condition">'
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
