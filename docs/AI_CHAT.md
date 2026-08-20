# AI Chat Assistant

The site ships with a floating **AI chat assistant** that is available to every
visitor — logged in or not — in the bottom-right corner of every public page.

By default it runs **100% locally** and needs **no external service or API
key**. It answers from the site's own content:

- **FAQs** (`faqs` table)
- **Products** (matched by name / description / SKU)
- **Industries** and **categories**
- **Contact details**, delivery/lead times, careers, and company info

When it can't find a confident answer it gives a helpful fallback and points
the visitor to a human (email / phone / contact form).

## Turning it on / off

The whole feature is driven by settings in the **`CHAT`** group
(`Admin → Settings`, or `install/seed.sql`):

| Key                    | Type   | Purpose                                        |
|------------------------|--------|------------------------------------------------|
| `chat_enabled`         | BOOL   | Master switch for the widget (default on)      |
| `chat_title`           | STRING | Header title shown in the widget               |
| `chat_bot_name`        | STRING | Assistant's name used in greetings             |
| `chat_avatar`          | STRING | Assistant avatar image (default `/assets/img/chat-bot-avatar.png`) |
| `chat_welcome`         | TEXT   | First message a new visitor sees               |
| `chat_quick_replies`   | JSON   | Suggested questions as tappable chips          |
| `chat_rate_limit_per_hour` | INT | Max messages per IP per hour (default 60)      |

## Using a real LLM (optional)

To answer with an actual large-language model instead of the local knowledge
base, set `chat_ai_provider` to `openai` (or `custom`) and provide an API key.
The endpoint is OpenAI-compatible, so it works with OpenAI, Azure OpenAI, or
any compatible gateway.

Either configure the settings in **Admin → Settings**, or set environment
variables (env wins when both are present):

```bash
VP_AI_API_KEY=sk-xxxx
VP_AI_API_URL=https://api.openai.com/v1/chat/completions
VP_AI_MODEL=gpt-4o-mini
```

| Key                 | Default                                      |
|---------------------|----------------------------------------------|
| `chat_ai_provider`  | `local`                                      |
| `chat_ai_api_key`   | *(empty — local mode)*                       |
| `chat_ai_api_url`   | `https://api.openai.com/v1/chat/completions` |
| `chat_ai_model`     | `gpt-4o-mini`                                |
| `chat_ai_system_prompt` | *(built-in default)*                     |

If the remote LLM call fails for any reason, the assistant automatically falls
back to the local knowledge base, so the chat never appears "broken".

## How it works under the hood

- Widget markup: `app/application/views/partials/chat_widget.php`
- Front-end:      `app/assets/js/chat.js`, `app/assets/css/chat.css`
- Endpoint:       `POST /chat/message` → `app/application/controllers/Chat.php`
- Brain:          `app/application/libraries/Vp_assistant.php`
- Settings:       `vp_chat_config()` in `app/application/helpers/app_helper.php`

The endpoint is CSRF-protected and rate-limited per IP. The CSRF token is
rotated on every message (the response returns the fresh token for the next
request).
