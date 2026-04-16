# Github Chat Widget

Floating AI chat widget plugin for WordPress with customizable UI, persistent email sessions, dynamic website-content grounding, and action buttons.

## Version

Current plugin version: `1.3.7`

## Features

- Floating frontend chat widget with launcher button.
- Configurable placement: bottom-right, bottom-left, top-right, top-left.
- Customizable panel size, offsets, colors, font sizes, and launcher border.
- Optional custom launcher image.
- WordPress shortcode support: `[github_chat_widget]`.
- Auto inject mode to display widget on all frontend pages.
- Model/API configuration from WP Admin (API key, model, endpoint, temperature).
- Live model dropdown from GitHub Models catalog.
- Usage/quota indicator in admin (based on response rate-limit headers).
- Dynamic 2-step website grounding flow:
- Step 1: Select relevant pages/posts from sitemap-style catalog using user message.
- Step 2: Fetch full content of selected pages/posts and answer from that context.
- Optional AI action button routing to configured URLs.
- Chat history admin table with "View Chat" modal UX.
- Email-first sessions with browser state persistence:
- Email and recent messages stay available after refresh.
- State resets only when user clicks "Change Email".

## Security

- REST origin validation with same-host checks for `Origin` and `Referer`.
- Basic IP-based rate limiting for session/chat requests.
- Strict sanitization for settings, model IDs, colors, clamp values, and custom CSS.
- Request message normalization and length limits before provider calls.
- Same-host URL validation for AI-driven navigation targets (mitigates open redirects).

## Database Tables

On activation or DB upgrade:

- `wp_github_chat_widget_users`
- Stores unique user emails and timestamps.

- `wp_github_chat_widget_chat_history`
- Stores latest chat history JSON per email, message count, and timestamps.

Note: table prefix depends on `$wpdb->prefix`.

## Installation

1. Copy plugin folder into `wp-content/plugins/git-ai-chat-widget`.
2. Activate **Github Chat Widget** in WordPress admin.
3. Go to **Settings > Github Chat**.
4. Configure API key/model/base URL.
5. Save settings.

## Usage

- Enable **Auto Inject Widget** to show chat globally.
- Or disable auto inject and place shortcode where needed:

```text
[github_chat_widget]
```

- On frontend, users must enter an email to start.
- After refresh, the same browser keeps email/session state.
- Use **Change Email** to reset and start a new session identity.

## REST Endpoints

Namespace: `github-chat-widget/v1`

- `POST /session`
- Body:

```json
{
  "email": "user@example.com"
}
```

- Response includes normalized stored messages for that email.

- `POST /chat`
- Body:

```json
{
  "email": "user@example.com",
  "messages": [
    { "role": "user", "content": "Hello" },
    { "role": "ai", "content": "Hi there" }
  ]
}
```

- Response:

```json
{
  "output": "..."
}
```

## Admin Area

Settings page path: **Settings > Github Chat**

Includes:

- Core model/API configuration.
- Text configuration.
- Layout and color controls.
- Route and button controls.
- Dynamic website content controls.
- System prompt editor.
- Submitted emails table.
- Chat history table with popup conversation viewer.
