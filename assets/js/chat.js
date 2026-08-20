// Vortex Precision — AI chat widget (public site).
// External file only: the production CSP blocks inline scripts.
(function () {
    'use strict';

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Turn bare http(s) URLs into clickable links, then escape the rest.
    function linkify(text) {
        var escaped = escapeHtml(text);
        return escaped.replace(/(https?:\/\/[^\s<]+)/g, function (url) {
            return '<a href="' + url + '" target="_blank" rel="noopener nofollow">' + url + '</a>';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('vp-chat');
        if (!root) return;

        var launcher = document.getElementById('vp-chat-launcher');
        var minimize = document.getElementById('vp-chat-minimize');
        var panel = document.getElementById('vp-chat-panel');
        var messages = document.getElementById('vp-chat-messages');
        var quickWrap = document.getElementById('vp-chat-quick');
        var form = document.getElementById('vp-chat-form');
        var input = document.getElementById('vp-chat-input');

        var endpoint = root.getAttribute('data-endpoint') || 'chat/message';
        var welcome = root.getAttribute('data-welcome') || 'Hi! How can I help you today?';
        var csrfName = root.getAttribute('data-csrf-name') || 'csrf_token';
        var csrf = root.getAttribute('data-csrf') || '';
        var quick = [];
        try { quick = JSON.parse(root.getAttribute('data-quick') || '[]'); } catch (e) { quick = []; }

        var initialized = false;
        var busy = false;

        function open() {
            root.classList.add('is-open');
            panel.hidden = false;
            launcher.setAttribute('aria-expanded', 'true');
            if (!initialized) {
                initialized = true;
                addMessage('bot', welcome);
            }
            if (input) input.focus();
        }

        function close() {
            root.classList.remove('is-open');
            panel.hidden = true;
            launcher.setAttribute('aria-expanded', 'false');
        }

        function addMessage(who, text) {
            var wrap = document.createElement('div');
            wrap.className = 'vp-chat__msg vp-chat__msg--' + who;

            var bubble = document.createElement('div');
            bubble.className = 'vp-chat__bubble';
            bubble.innerHTML = linkify(text);
            wrap.appendChild(bubble);
            messages.appendChild(wrap);
            messages.scrollTop = messages.scrollHeight;
            return wrap;
        }

        function addTyping() {
            var wrap = document.createElement('div');
            wrap.className = 'vp-chat__msg vp-chat__msg--bot vp-chat__typing-wrap';
            wrap.innerHTML = '<div class="vp-chat__bubble vp-chat__typing"><span></span><span></span><span></span></div>';
            messages.appendChild(wrap);
            messages.scrollTop = messages.scrollHeight;
            return wrap;
        }

        var tokenUrl = root.getAttribute('data-token-endpoint') || '';

        // Ask the server for a fresh CSRF token. Used to recover automatically
        // when a rotated token/cookie got out of sync (proxies and CDNs can
        // strip the rotated Set-Cookie), instead of breaking the conversation.
        function refreshToken() {
            if (!tokenUrl) return Promise.resolve(false);
            return fetch(tokenUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && data.csrf_token) { csrf = data.csrf_token; return true; }
                    return false;
                })
                .catch(function () { return false; });
        }

        function postMessage(text) {
            var body = new URLSearchParams();
            body.set(csrfName, csrf);
            body.set('message', text);

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: body.toString(),
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(function (res) {
                return res.text().then(function (raw) {
                    var data = null;
                    try { data = JSON.parse(raw); } catch (e) { data = null; }
                    return { status: res.status, data: data };
                });
            });
        }

        function send(text) {
            text = (text || '').trim();
            if (!text || busy) return;

            busy = true;
            addMessage('user', text);
            input.value = '';
            autoGrow();

            var typing = addTyping();

            var finish = function (reply) {
                typing.remove();
                addMessage('bot', reply);
                busy = false;
            };

            postMessage(text)
                .then(function (r) {
                    // Keep the token in sync for the next message.
                    if (r.data && r.data.csrf_token) csrf = r.data.csrf_token;

                    if (r.data && r.data.reply) {
                        finish(r.data.reply);
                        return;
                    }

                    // No usable JSON (expired token, 403/419/500 HTML page…):
                    // refresh the token and try exactly once more.
                    return refreshToken().then(function () {
                        return postMessage(text).then(function (r2) {
                            if (r2.data && r2.data.csrf_token) csrf = r2.data.csrf_token;
                            if (r2.data && r2.data.reply) {
                                finish(r2.data.reply);
                            } else {
                                finish('Sorry, I could not reach the assistant just now. Please try again in a moment, or contact our team directly.');
                            }
                        });
                    });
                })
                .catch(function () {
                    // Network error: one silent retry, then a friendly message.
                    return refreshToken()
                        .then(function () { return postMessage(text); })
                        .then(function (r2) {
                            if (r2 && r2.data && r2.data.csrf_token) csrf = r2.data.csrf_token;
                            if (r2 && r2.data && r2.data.reply) {
                                finish(r2.data.reply);
                            } else {
                                finish('Sorry, something went wrong. Please try again or contact our team directly.');
                            }
                        })
                        .catch(function () {
                            finish('Sorry, something went wrong. Please try again or contact our team directly.');
                        });
                })
                .finally(function () {
                    busy = false;
                });
        }

        function autoGrow() {
            if (!input) return;
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        }

        if (launcher) {
            launcher.addEventListener('click', function () {
                root.classList.contains('is-open') ? close() : open();
            });
        }
        if (minimize) minimize.addEventListener('click', close);

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                send(input.value);
            });
        }
        if (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    send(input.value);
                }
            });
            input.addEventListener('input', autoGrow);
        }
        if (quickWrap) {
            quickWrap.addEventListener('click', function (e) {
                var chip = e.target.closest('.vp-chat__chip');
                if (!chip) return;
                var q = chip.getAttribute('data-question');
                if (q) { open(); send(q); }
            });
        }
    });
})();
