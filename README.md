# Github Chat Widget

Floating AI chat widget plugin for WordPress with customizable UI, route buttons, dynamic content grounding, and email-based cross-device chat history.

## Features

- Floating frontend chat widget with launcher button.
- Configurable placement: bottom-right, bottom-left, top-right, top-left.
- Customizable panel size, offsets, colors, and launcher border.
- Customizable texts: title, welcome text, thinking text, placeholder, send label.
- Supports launcher icon or custom launcher image URL.
- WordPress shortcode support: `[github_chat_widget]`.
- Auto inject mode to display widget on all frontend pages.
- REST API backend for AI chat completion requests.
- API configuration from WP Admin: API key, model, base URL, temperature.
- System prompt editor with strict JSON output support.
- Optional UI action button in AI responses.
- Route map support: map route keys to labels and URLs.
- Link promotion: converts text links to clickable action button payloads.
- Optional dynamic website context mode:
- Selects relevant pages/posts from your site based on user intent.
- Fetches real page/post content and feeds it into the model.
- Uses route matching against the latest user message.
- Origin validation for REST requests.
- Basic IP rate limiting.
- Email-first chat session flow:
- User must submit email before starting chat.
- Submitted emails are stored in database.
- Chat history is stored as JSON by email.
- Returning with same email on another device restores previous chat.
- Admin tables for auditing captured emails and chat JSON history.

## Database Tables

On activation (or upgrade), the plugin creates:

- `wp_github_chat_widget_users`
- Stores unique user emails and timestamps.

- `wp_github_chat_widget_chat_history`
- Stores latest chat history JSON per email, message count, and timestamps.

Note: table prefix depends on your WordPress `$wpdb->prefix`.

## Installation

1. Copy plugin folder into `wp-content/plugins/git-ai-chat-widget`.
2. Activate **Github Chat Widget** from WordPress Admin.
3. Open **Settings > Github Chat**.
4. Add your API key and model endpoint settings.
5. Save settings.

## Usage

- Enable **Auto Inject Widget** to show chat globally.
- Or disable auto inject and place shortcode where needed:

```text
[github_chat_widget]
```

- On frontend, users must enter an email to start.
- Chat history is restored automatically for that email.

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
- Dynamic system info toggle.
- System prompt editor.
- Submitted Emails table.
- Chat History JSON table.

## Version

Current plugin version: `1.3.0`
