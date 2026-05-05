=== Site AI Chat Widget ===
Contributors: nishanshashintha
Tags: chat, ai, widget, assistant, customer-support
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Floating AI chat widget with persistent email sessions, configurable UI, and optional page-aware responses.

== Description ==
Site AI Chat Widget adds a floating chat interface to your WordPress site.

== Compliance ==
= Guideline 1: GPL compatibility =
This plugin is licensed under GPLv2 or later.
All plugin code and bundled assets included in this package are intended to be GPL-compatible.

= Guideline 2: Developer responsibility and third-party services =
The plugin author is responsible for all shipped files and behavior.
Before release, all included code/assets and third-party service usage should be reviewed for licensing and terms compliance.
This plugin documents all external services it uses in the External Services section.

= Guideline 3: Stable version distributed via WordPress.org =
The stable version is identified by Stable tag in this readme and by the plugin header version.
Users should install/update from WordPress.org releases (SVN/tagged directory versions) for production use.

= Guideline 4: Human-readable code and source access =
The plugin ships readable source code and does not rely on obfuscated or packed code.
No proprietary build output is required to run the plugin.

= Guideline 5: No trialware restrictions in plugin code =
The plugin does not lock local features behind a paid upgrade or trial countdown.
Paid third-party AI usage may apply according to the external provider's own plan and terms, but plugin code paths are fully included in this distribution.

= Guideline 6: Software as a Service disclosure =
This plugin works as a SaaS integration for AI chat functionality.
The external services used, what data is transmitted, and where to review provider terms are documented in the External Services section.

= Guideline 7: No tracking without consent =
External API requests are disabled by default until the site administrator enables External Service Consent in plugin settings.
When disabled, the plugin does not send chat messages or model catalog requests to third-party AI endpoints.

= Guideline 8: No executable code from third-party systems =
The plugin does not download or execute remote JavaScript/PHP code, does not install plugins/themes from third-party servers, and does not use iframes for admin pages.
All plugin JS/CSS assets are bundled locally.
Outbound network communication is limited to documented HTTPS API endpoints used for SaaS chat features.

= Guideline 9: No illegal, dishonest, or abusive behavior =
The plugin is designed as a site assistant tool and does not include traffic manipulation, review manipulation, black-hat SEO behavior, crypto-mining, or botnet-style resource abuse features.

= Guideline 10: No external credits/links embedded on public site by default =
The plugin does not inject mandatory "powered by" links or third-party credit links on the public site.
Action-button URLs are restricted to same-site destinations.

= Guideline 11: No admin dashboard hijacking =
The plugin does not add persistent global ads, dashboard widgets, or unrelated admin nags.
Configuration UX is contained within the plugin settings page.

= Guideline 12: Public readme must not spam =
This readme uses concise, user-focused descriptions, includes no affiliate links, and keeps tags within WordPress.org limits.
No competitor-tag stuffing or keyword stuffing is used.

= Guideline 13: WordPress default libraries =
The plugin does not bundle prohibited duplicate copies of WordPress core libraries.
Frontend/admin assets are plugin-local and rely on WordPress loading behavior.

= Guideline 14: Avoid frequent SVN commit churn =
Release workflow should batch related changes into a small number of descriptive SVN commits.
SVN should be treated as a release repository.

= Guideline 15: Version numbers increment each release =
Plugin header version and Stable tag are kept aligned for each release so users receive update notifications.

= Guideline 16: Complete plugin at submission time =
This package includes runnable source code, assets, and required metadata for review.

= Guideline 17: Respect trademarks and project names =
Public branding avoids implying affiliation with third-party trademark owners.
Original branding is used in the plugin display name to reduce confusion.

= Guideline 18: Directory maintenance and safety enforcement =
This plugin acknowledges WordPress.org Plugin Directory governance rights, including emergency actions to protect users.
The maintainer commits to responding to review feedback, security requests, and guideline updates in a timely manner.

Features include:
- Floating widget or shortcode-based placement.
- Configurable labels, colors, sizing, and position.
- Optional email-based session continuity.
- Optional button routing to site sections/pages.
- Optional dynamic page/post context to improve answers.

Use shortcode:
[github_chat_widget]

== External Services ==
This plugin connects to external AI services only to generate chat responses and fetch model metadata.

1) GitHub Models Inference API
- Service: GitHub Models (Microsoft/GitHub)
- Endpoint: https://models.inference.ai.azure.com/chat/completions
- Trigger: When a visitor sends a chat message.
- Data sent: Configured model ID, conversation messages, system prompt, and optional context generated from your site content.
- Data received: AI response text and optional rate limit headers.
- Terms: https://docs.github.com/en/site-policy/privacy-policies/github-privacy-statement

2) GitHub Models Catalog API
- Service: GitHub Models Catalog
- Endpoint: https://models.github.ai/catalog/models
- Trigger: When an administrator opens the plugin settings page.
- Data sent: Standard HTTPS request metadata.
- Data received: Available model catalog data for admin dropdown display.
- Terms: https://docs.github.com/en/site-policy/privacy-policies/github-privacy-statement

== Installation ==
1. Upload the plugin to /wp-content/plugins/git-ai-chat-widget/.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to Settings > Site AI Chat.
4. Add your API key and model settings.
5. Save changes.

== Source Code and Build Notes ==
- Source code is included directly in this distributed plugin package.
- JavaScript and CSS files are shipped in readable form.
- No build step is required for normal installation and execution.

== Frequently Asked Questions ==
= Does this plugin place external promotional links on my site? =
No. The plugin does not output third-party promotional links by default.

= Can the chat button navigate off-site? =
No. Navigation URLs are restricted to same-site URLs.

= Is this plugin GPL compatible? =
Yes. This plugin is licensed under GPLv2 or later.

= Does this plugin track users without consent? =
No. Third-party API communication is opt-in via the External Service Consent setting.

= Does this plugin load remote executable scripts? =
No. Frontend/admin JS and CSS are bundled locally and remote calls are API requests only.

= Does this plugin include affiliate links in readme content? =
No. This readme does not include affiliate or cloaked referral links.

== Changelog ==
= 1.3.8 =
- Added compliance coverage for WordPress.org Guidelines 12-18.
- Updated public plugin branding for clearer trademark-safe naming.

= 1.3.7 =
- Hardened URL validation for chat action buttons (same-site only).
- Added REST nonce support for endpoint requests.
- Removed custom update checker for WordPress.org compatibility.
- Added WordPress.org readme with external services disclosure.

== Upgrade Notice ==
= 1.3.8 =
Readme policy compliance updates and release metadata alignment for submission.
