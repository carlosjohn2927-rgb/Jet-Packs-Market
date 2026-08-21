// Vortex Precision — front-end inline page editor.
// Runs only when the inline-editor markup (config block + panels) is present,
// i.e. an Admin/Super Admin has turned live editing on for the current page.
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var cfgEl = document.getElementById('vp-inline-config');
        if (!cfgEl) return;

        var config = {};
        try { config = JSON.parse(cfgEl.textContent); } catch (e) { config = {}; }

        var sectionPanel = document.getElementById('vp-inline-panel');
        var textPanel = document.getElementById('vp-text-panel');
        var themePanel = document.getElementById('vp-theme-panel');
        var sectionForm = document.getElementById('vp-inline-form');
        var textForm = document.getElementById('vp-text-form');
        var themeForm = document.getElementById('vp-theme-form');

        function normalizeHex(v) {
            v = String(v || '').trim();
            if (/^#[0-9a-fA-F]{6}$/.test(v)) return v;
            if (/^#[0-9a-fA-F]{3}$/.test(v)) {
                return ('#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3]).toLowerCase();
            }
            return '';
        }

        function openPanel(panel, anchor) {
            if (!panel) return;
            closePanels();
            panel.hidden = false;
            if (anchor) {
                var r = anchor.getBoundingClientRect();
                var top = Math.max(8, Math.min(r.top, window.innerHeight - 280));
                panel.style.top = top + 'px';
            }
        }

        function closePanels() {
            if (sectionPanel) sectionPanel.hidden = true;
            if (textPanel) textPanel.hidden = true;
            if (themePanel) themePanel.hidden = true;
        }

        function sectionData(id) {
            var el = document.querySelector('[data-vp-section-data="' + id + '"]');
            if (!el) return null;
            try { return JSON.parse(el.textContent); } catch (e) { return null; }
        }

        function postForm(url, form, onOk) {
            var fd = new FormData(form);
            var submit = form.querySelector('button[type="submit"]');
            var old = submit ? submit.innerHTML : '';
            if (submit) {
                submit.disabled = true;
                submit.innerHTML = '<i class="ri-loader-4-line vp-spin"></i> Saving…';
            }
            fetch(url, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d && d.ok) {
                        if (onOk) onOk(d);
                    } else {
                        if (submit) {
                            submit.disabled = false;
                            submit.innerHTML = old;
                        }
                        window.alert(d && d.error ? d.error : 'Could not save. Please try again.');
                    }
                })
                .catch(function () {
                    if (submit) {
                        submit.disabled = false;
                        submit.innerHTML = old;
                    }
                    window.alert('Could not save. Please try again.');
                });
        }

        document.addEventListener('click', function (e) {
            var closeBtn = e.target.closest('[data-vp-inline-close]');
            if (closeBtn) {
                e.preventDefault();
                closePanels();
                return;
            }

            var themeBtn = e.target.closest('[data-vp-theme-panel]');
            if (themeBtn) {
                e.preventDefault();
                openPanel(themePanel, themeBtn);
                return;
            }

            var editBtn = e.target.closest('[data-vp-inline-edit]');
            if (editBtn) {
                e.preventDefault();
                var id = editBtn.getAttribute('data-vp-inline-edit');
                var d = sectionData(id);
                if (!d || !sectionForm) return;

                sectionForm.querySelector('[name="id"]').value = d.id || '';
                sectionForm.querySelector('[name="title"]').value = d.title || '';
                sectionForm.querySelector('[name="subtitle"]').value = d.subtitle || '';
                sectionForm.querySelector('[name="body"]').value = d.body || '';
                sectionForm.querySelector('[name="buttonText"]').value = d.buttonText || '';
                sectionForm.querySelector('[name="buttonUrl"]').value = d.buttonUrl || '';

                var colors = d.colors || {};
                sectionForm.querySelector('[name="text_color"]').value = normalizeHex(colors.text) || '#000000';
                sectionForm.querySelector('[name="bg_color"]').value = normalizeHex(colors.bg) || '#ffffff';
                sectionForm.querySelector('[name="heading_color"]').value = normalizeHex(colors.heading) || '#000000';

                openPanel(sectionPanel, editBtn);
                return;
            }

            var editable = e.target.closest('[data-vp-editable]');
            if (editable && textForm) {
                e.preventDefault();
                textForm.querySelector('[name="key"]').value = editable.getAttribute('data-vp-setting') || '';
                textForm.querySelector('[name="value"]').value = editable.textContent;
                openPanel(textPanel, editable);
            }
        });

        if (sectionForm) {
            sectionForm.addEventListener('submit', function (e) {
                e.preventDefault();
                postForm(config.sectionSave, sectionForm, function () {
                    window.location.reload();
                });
            });
        }

        if (textForm) {
            textForm.addEventListener('submit', function (e) {
                e.preventDefault();
                postForm(config.settingSave, textForm, function () {
                    window.location.reload();
                });
            });
        }

        if (themeForm) {
            themeForm.addEventListener('submit', function (e) {
                e.preventDefault();
                postForm(config.themeSave, themeForm, function () {
                    window.location.reload();
                });
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closePanels();
        });
    });
})();
