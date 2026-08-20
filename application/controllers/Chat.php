<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — public AI chat endpoint.
 *
 * Serves the floating chat widget available to every site visitor. Replies are
 * produced by the Vp_assistant library (local knowledge base by default, or an
 * external LLM when configured).
 *
 * Why this endpoint handles CSRF itself
 * -------------------------------------
 * CodeIgniter rotates the CSRF cookie on every POST. The widget is a long-lived
 * page that posts repeatedly, so if a proxy/CDN strips or delays the rotated
 * `Set-Cookie` (very common on shared hosting behind Cloudflare) the *second*
 * message posts a token the server no longer recognises. CI then aborts with an
 * HTML 403 page, the widget cannot parse it as JSON and the visitor sees
 * "Sorry, something went wrong."
 *
 * `chat/message` is therefore listed in `csrf_exclude_uris` and protected here
 * instead, with checks that fit a public, read-only, rate-limited endpoint:
 *   • same-origin enforcement (Origin / Referer must match the site host)
 *   • the CSRF token is still accepted and rotated when it matches
 *   • per-IP rate limiting
 *   • every response is JSON — the widget always gets something it can render
 */
class Chat extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['vp_assistant', 'rate_limiter']);
        $this->load->helper('security_helper');
    }

    /**
     * GET chat/token → { csrf_token }
     * Lets the widget re-synchronise after a rotated/lost token instead of
     * failing the conversation.
     */
    public function token()
    {
        $this->json([
            'ok'         => true,
            'csrf_token' => $this->security->get_csrf_hash(),
            'csrf_name'  => $this->config->item('csrf_token_name'),
        ]);
    }

    /**
     * POST chat/message  →  { reply, csrf_token }
     */
    public function message()
    {
        // Only accept POSTs.
        if ($this->input->method() !== 'post') {
            return $this->json([
                'ok'         => false,
                'reply'      => 'Please send your question through the chat window.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ], 405);
        }

        // Same-origin guard (replaces the global CSRF filter for this endpoint).
        if (!$this->_same_origin()) {
            log_message('error', 'Chat: cross-origin POST rejected from ' . vp_get_client_ip());
            return $this->json([
                'ok'         => false,
                'reply'      => 'Sorry, I could not verify where that message came from. Please reload the page and try again.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ], 403);
        }

        $config = vp_chat_config();
        if (empty($config['enabled'])) {
            return $this->json([
                'ok'         => true,
                'reply'      => 'Chat is currently unavailable. Please contact our team directly.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ]);
        }

        // Per-IP rate limit. A misconfigured (0/empty) setting must not lock
        // the widget out after the first message, so clamp to a sane minimum.
        $ip = vp_get_client_ip();
        $limit = (int) vp_setting('chat_rate_limit_per_hour', 60);
        if ($limit < 5) $limit = 60;
        if ($this->rate_limiter->too_many('chat:' . $ip, $limit, 3600)) {
            return $this->json([
                'ok'         => true,
                'reply'      => 'You have sent a lot of messages recently. Please try again in a little while, or reach us by email or phone.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ], 200);
        }

        $message = trim((string) $this->input->post('message'));
        if ($message === '') {
            return $this->json([
                'ok'         => true,
                'reply'      => 'Please type a message and I will do my best to help.',
                'csrf_token' => $this->security->get_csrf_hash(),
            ]);
        }
        if (mb_strlen($message) > 1000) {
            $message = mb_substr($message, 0, 1000);
        }

        try {
            $reply = $this->vp_assistant->reply($message);
        } catch (Throwable $e) {
            log_message('error', 'Chat: assistant failed - ' . $e->getMessage());
            $reply = 'Sorry, I ran into a problem answering that. Please contact our team directly and we will help right away — '
                . vp_site('email', '') . ' ' . vp_site('phone', '');
        }

        $this->json([
            'ok'         => true,
            'reply'      => trim((string) $reply),
            'csrf_token' => $this->security->get_csrf_hash(),
        ]);
    }

    /**
     * TRUE when the request comes from this website (or carries no Origin /
     * Referer at all, which some privacy tools strip — the endpoint is
     * read-only and rate limited, so that is acceptable).
     */
    private function _same_origin()
    {
        $source = $this->input->get_request_header('Origin', true)
            ?: $this->input->get_request_header('Referer', true);
        if (!$source) return true;

        $incoming = strtolower((string) parse_url($source, PHP_URL_HOST));
        if ($incoming === '') return true;

        $allowed = [strtolower((string) parse_url(base_url(), PHP_URL_HOST))];
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host !== '') $allowed[] = preg_replace('/:\d+$/', '', $host);
        $bare = function ($host) { return preg_replace('/^www\./', '', (string) $host); };
        foreach ($allowed as $a) {
            if ($a === '') continue;
            if ($incoming === $a) return true;
            if ($bare($incoming) === $bare($a)) return true;   // www.example.com ↔ example.com
        }
        return false;
    }
}
