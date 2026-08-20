<?php
/**
 * AI chat widget (public). Floats in the bottom-right corner and is available
 * to every visitor — logged in or not.
 *
 * Configuration lives in the settings table under group "CHAT"
 * (see admin -> Settings, or seed data). Toggle with the "chat_enabled" flag.
 */
$chat = $chat ?? vp_chat_config();
$title = $chat['title'] ?? 'Assistant';
$bot   = $chat['bot_name'] ?? 'Assistant';
$avatar = $chat['avatar'] ?? IMG_URL . 'chat-bot-avatar.png';
$welcome = $chat['welcome'] ?? 'Hi! How can I help you today?';
$quick  = $chat['quick_replies'] ?? [];
$csrfName = $csrf_token_name ?? 'csrf_token';
$csrfVal  = $csrf_token ?? '';
?>
<div id="vp-chat" class="vp-chat"
     data-endpoint="<?= vp_safe_html(base_url('chat/message')) ?>"
     data-token-endpoint="<?= vp_safe_html(base_url('chat/token')) ?>"
     data-title="<?= vp_safe_html($title) ?>"
     data-bot="<?= vp_safe_html($bot) ?>"
     data-welcome="<?= vp_safe_html($welcome) ?>"
     data-csrf-name="<?= vp_safe_html($csrfName) ?>"
     data-csrf="<?= vp_safe_html($csrfVal) ?>"
     data-quick="<?= vp_safe_html(json_encode(array_values($quick), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
     aria-live="polite">

    <button type="button" class="vp-chat__launcher" id="vp-chat-launcher" aria-label="Open chat" aria-expanded="false">
        <img src="<?= vp_safe_html($avatar) ?>" alt="" class="vp-chat__launcher-avatar">
        <i class="ri-close-line vp-chat__launcher-icon vp-chat__launcher--close"></i>
    </button>

    <div class="vp-chat__panel" id="vp-chat-panel" role="dialog" aria-label="<?= vp_safe_html($title) ?>" hidden>
        <header class="vp-chat__header">
            <div class="vp-chat__avatar" aria-hidden="true">
                <img src="<?= vp_safe_html($avatar) ?>" alt="" width="38" height="38" decoding="async">
            </div>
            <div class="vp-chat__meta">
                <div class="vp-chat__title"><?= vp_safe_html($title) ?></div>
                <div class="vp-chat__status"><span class="vp-chat__dot"></span> Online</div>
            </div>
            <button type="button" class="vp-chat__minimize" id="vp-chat-minimize" aria-label="Minimize chat">
                <i class="ri-subtract-line"></i>
            </button>
        </header>

        <div class="vp-chat__messages" id="vp-chat-messages" aria-live="polite" aria-atomic="false"></div>

        <?php if (!empty($quick)): ?>
        <div class="vp-chat__quick" id="vp-chat-quick">
            <?php foreach (array_values($quick) as $q): ?>
                <button type="button" class="vp-chat__chip" data-question="<?= vp_safe_html($q) ?>"><?= vp_safe_html($q) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form class="vp-chat__form" id="vp-chat-form" autocomplete="off">
            <textarea class="vp-chat__input" id="vp-chat-input" rows="1" placeholder="Type your message…" aria-label="Message"></textarea>
            <button type="submit" class="vp-chat__send" aria-label="Send message">
                <i class="ri-send-plane-fill"></i>
            </button>
        </form>
    </div>
</div>
