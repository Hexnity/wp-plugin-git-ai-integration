# Site AI Chat Widget

Floating AI chat widget plugin for WordPress with customizable UI, persistent email sessions, dynamic website-content grounding, and action buttons.

## Version

Current plugin version: `1.3.8`

## WordPress.org Compliance Notes

- Guideline 1 (GPL): Plugin is licensed as GPLv2 or later.
- Guideline 2 (Developer responsibility): External services are documented, and shipped files should be license-reviewed before release.
- Guideline 3 (Stable release): `readme.txt` stable tag and plugin header version must match the SVN release version.
- Guideline 4 (Human-readable code): Plugin is shipped with readable PHP/JS/CSS and no obfuscated code.
- Guideline 5 (No trialware): Plugin code does not hide local features behind trial windows or paywalls.
- Guideline 6 (SaaS): External AI services are documented with endpoints and terms links.
- Guideline 7 (Consent for external requests): Third-party API calls are gated by an explicit admin consent toggle.
- Guideline 8 (No remote executable code): Plugin bundles local JS/CSS and limits outbound requests to documented API endpoints.
- Guideline 9 (No illegal/dishonest behavior): Plugin does not include abusive traffic/review manipulation or resource abuse behavior.
- Guideline 10 (No forced external credits): Plugin does not inject mandatory third-party credit links on public pages.
- Guideline 11 (No admin hijacking): Plugin UI is limited to its settings page without persistent dashboard ads/nags.
- Guideline 12 (No readme spam): Public readme avoids affiliate links, keyword stuffing, and competitor-tag spam.
- Guideline 13 (Use core libraries): Plugin does not ship duplicate prohibited copies of WordPress core libraries.
- Guideline 14 (SVN release hygiene): Recommend batching release-ready changes into descriptive, non-frequent commits.
- Guideline 15 (Version increments): Plugin header and stable tag are kept aligned per release.
- Guideline 16 (Complete submission): Package includes executable plugin code and required metadata.
- Guideline 17 (Trademark respect): Public branding uses original naming to avoid false affiliation.
- Guideline 18 (Directory governance): Maintainer acknowledges WordPress.org safety and directory-maintenance authority.

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
