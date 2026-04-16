<?php

if (!defined('ABSPATH')) {
    exit;
}

function github_chat_widget_admin_menu() {
    add_options_page(
        'Github Chat Settings',
        'Github Chat',
        'manage_options',
        'github-chat-widget',
        'github_chat_widget_admin_page'
    );
}
add_action('admin_menu', 'github_chat_widget_admin_menu');

function github_chat_widget_admin_assets($hook) {
    if ($hook !== 'settings_page_github-chat-widget') {
        return;
    }

    wp_enqueue_style(
        'github-chat-widget-admin-style',
        GITHUB_CHAT_WIDGET_URL . 'assets/admin.css',
        array(),
        GITHUB_CHAT_WIDGET_VERSION
    );
}
add_action('admin_enqueue_scripts', 'github_chat_widget_admin_assets');

function github_chat_widget_get_admin_email_rows($limit = 100) {
    global $wpdb;

    $limit = max(1, min(500, (int) $limit));
    $table = github_chat_widget_users_table_name();

    return $wpdb->get_results(
        $wpdb->prepare("SELECT id, email, created_at, last_seen_at FROM {$table} ORDER BY last_seen_at DESC LIMIT %d", $limit),
        ARRAY_A
    );
}

function github_chat_widget_get_admin_history_rows($limit = 100) {
    global $wpdb;

    $limit = max(1, min(500, (int) $limit));
    $table = github_chat_widget_history_table_name();

    return $wpdb->get_results(
        $wpdb->prepare("SELECT id, email, message_count, messages_json, updated_at FROM {$table} ORDER BY updated_at DESC LIMIT %d", $limit),
        ARRAY_A
    );
}

function github_chat_widget_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = github_chat_widget_get_settings();
    $email_rows = github_chat_widget_get_admin_email_rows(100);
    $history_rows = github_chat_widget_get_admin_history_rows(100);
    $css_class_reference = ".github-chat-widget-root\n.github-chat-widget-panel\n.github-chat-widget-header\n.github-chat-widget-title\n.github-chat-widget-change-email\n.github-chat-widget-email-gate\n.github-chat-widget-email-title\n.github-chat-widget-email-form\n.github-chat-widget-email-input\n.github-chat-widget-email-submit\n.github-chat-widget-email-error\n.github-chat-widget-messages\n.github-chat-widget-row.is-user\n.github-chat-widget-row.is-ai\n.github-chat-widget-bubble\n.github-chat-widget-actions\n.github-chat-widget-nav-button\n.github-chat-widget-form\n.github-chat-widget-input\n.github-chat-widget-send\n.github-chat-widget-launcher";
    ?>
    <div class="wrap github-chat-widget-admin-wrap">
        <h1>Github Chat Settings</h1>
        <p class="github-chat-widget-admin-subtitle">Configure API, appearance, behavior, and route actions from one place.</p>

        <form method="post" action="options.php">
            <?php settings_fields('github_chat_widget_group'); ?>

            <div class="github-chat-widget-admin-grid">
                <section class="github-chat-widget-admin-card">
                    <h2>Core</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="github_chat_widget_title">Chat Title</label></th>
                            <td><input id="github_chat_widget_title" type="text" class="regular-text" name="github_chat_widget_settings[chat_title]" value="<?php echo esc_attr($settings['chat_title']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_api_key">API Key</label></th>
                            <td>
                                <input id="github_chat_widget_api_key" type="password" class="regular-text" name="github_chat_widget_settings[api_key]" value="" autocomplete="new-password" />
                                <p class="description">Leave empty to keep existing key.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_model">Model</label></th>
                            <td><input id="github_chat_widget_model" type="text" class="regular-text" name="github_chat_widget_settings[model]" value="<?php echo esc_attr($settings['model']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_base_url">Base URL</label></th>
                            <td><input id="github_chat_widget_base_url" type="url" class="regular-text code" name="github_chat_widget_settings[base_url]" value="<?php echo esc_attr($settings['base_url']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_temperature">Temperature</label></th>
                            <td><input id="github_chat_widget_temperature" type="number" step="0.1" min="0" max="2" name="github_chat_widget_settings[temperature]" value="<?php echo esc_attr($settings['temperature']); ?>" /></td>
                        </tr>
                    </table>
                </section>

                <section class="github-chat-widget-admin-card">
                    <h2>Texts</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="github_chat_widget_welcome_text">Welcome Text</label></th>
                            <td><input id="github_chat_widget_welcome_text" type="text" class="regular-text" name="github_chat_widget_settings[welcome_text]" value="<?php echo esc_attr($settings['welcome_text']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_thinking_text">Thinking Text</label></th>
                            <td><input id="github_chat_widget_thinking_text" type="text" class="regular-text" name="github_chat_widget_settings[thinking_text]" value="<?php echo esc_attr($settings['thinking_text']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_input_placeholder">Input Placeholder</label></th>
                            <td><input id="github_chat_widget_input_placeholder" type="text" class="regular-text" name="github_chat_widget_settings[input_placeholder]" value="<?php echo esc_attr($settings['input_placeholder']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_send_button_text">Send Button Text</label></th>
                            <td><input id="github_chat_widget_send_button_text" type="text" class="regular-text" name="github_chat_widget_settings[send_button_text]" value="<?php echo esc_attr($settings['send_button_text']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_launcher_label">Launcher Label (ARIA)</label></th>
                            <td><input id="github_chat_widget_launcher_label" type="text" class="regular-text" name="github_chat_widget_settings[launcher_aria_label]" value="<?php echo esc_attr($settings['launcher_aria_label']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_launcher_icon">Launcher Icon</label></th>
                            <td><input id="github_chat_widget_launcher_icon" type="text" class="regular-text" name="github_chat_widget_settings[launcher_icon]" value="<?php echo esc_attr($settings['launcher_icon']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_launcher_image_url">Launcher Image URL</label></th>
                            <td>
                                <input id="github_chat_widget_launcher_image_url" type="url" class="regular-text code" name="github_chat_widget_settings[launcher_image_url]" value="<?php echo esc_attr($settings['launcher_image_url']); ?>" />
                                <p class="description">If set, this image replaces the icon and covers the full circle.</p>
                            </td>
                        </tr>
                    </table>
                </section>

                <section class="github-chat-widget-admin-card">
                    <h2>Layout and Colors</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="github_chat_widget_position">Chat Position</label></th>
                            <td>
                                <select id="github_chat_widget_position" name="github_chat_widget_settings[chat_position]">
                                    <option value="bottom-right" <?php selected($settings['chat_position'], 'bottom-right'); ?>>Bottom Right</option>
                                    <option value="bottom-left" <?php selected($settings['chat_position'], 'bottom-left'); ?>>Bottom Left</option>
                                    <option value="top-right" <?php selected($settings['chat_position'], 'top-right'); ?>>Top Right</option>
                                    <option value="top-left" <?php selected($settings['chat_position'], 'top-left'); ?>>Top Left</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_offset_x">Horizontal Offset (px)</label></th>
                            <td><input id="github_chat_widget_offset_x" type="number" min="0" max="200" name="github_chat_widget_settings[offset_x]" value="<?php echo esc_attr($settings['offset_x']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_offset_y">Vertical Offset (px)</label></th>
                            <td><input id="github_chat_widget_offset_y" type="number" min="0" max="200" name="github_chat_widget_settings[offset_y]" value="<?php echo esc_attr($settings['offset_y']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_panel_width">Panel Width (px)</label></th>
                            <td><input id="github_chat_widget_panel_width" type="number" min="280" max="700" name="github_chat_widget_settings[panel_width]" value="<?php echo esc_attr($settings['panel_width']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_panel_height">Panel Height (px)</label></th>
                            <td><input id="github_chat_widget_panel_height" type="number" min="360" max="900" name="github_chat_widget_settings[panel_height]" value="<?php echo esc_attr($settings['panel_height']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_panel_bg">Panel Background</label></th>
                            <td><input id="github_chat_widget_panel_bg" type="color" name="github_chat_widget_settings[panel_bg_color]" value="<?php echo esc_attr($settings['panel_bg_color']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_accent">Accent Color</label></th>
                            <td><input id="github_chat_widget_accent" type="color" name="github_chat_widget_settings[accent_color]" value="<?php echo esc_attr($settings['accent_color']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_request_text_color">User Message Text Color</label></th>
                            <td><input id="github_chat_widget_request_text_color" type="color" name="github_chat_widget_settings[request_text_color]" value="<?php echo esc_attr($settings['request_text_color']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_response_text_color">Response Message Text Color</label></th>
                            <td><input id="github_chat_widget_response_text_color" type="color" name="github_chat_widget_settings[response_text_color]" value="<?php echo esc_attr($settings['response_text_color']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_title_font_size">Title Font Size</label></th>
                            <td>
                                <input id="github_chat_widget_title_font_size" type="text" class="regular-text code" name="github_chat_widget_settings[title_font_size]" value="<?php echo esc_attr($settings['title_font_size']); ?>" />
                                <p class="description">Use a CSS clamp() value. Example: clamp(0.95rem, 0.9rem + 0.2vw, 1.05rem)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_body_font_size">Body Font Size</label></th>
                            <td><input id="github_chat_widget_body_font_size" type="text" class="regular-text code" name="github_chat_widget_settings[body_font_size]" value="<?php echo esc_attr($settings['body_font_size']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_input_font_size">Input Font Size</label></th>
                            <td><input id="github_chat_widget_input_font_size" type="text" class="regular-text code" name="github_chat_widget_settings[input_font_size]" value="<?php echo esc_attr($settings['input_font_size']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_button_font_size">Button Font Size</label></th>
                            <td><input id="github_chat_widget_button_font_size" type="text" class="regular-text code" name="github_chat_widget_settings[button_font_size]" value="<?php echo esc_attr($settings['button_font_size']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_launcher_border_color">Launcher Border Color</label></th>
                            <td><input id="github_chat_widget_launcher_border_color" type="color" name="github_chat_widget_settings[launcher_border_color]" value="<?php echo esc_attr($settings['launcher_border_color']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_launcher_border_width">Launcher Border Width (px)</label></th>
                            <td><input id="github_chat_widget_launcher_border_width" type="number" min="0" max="8" name="github_chat_widget_settings[launcher_border_width]" value="<?php echo esc_attr($settings['launcher_border_width']); ?>" /></td>
                        </tr>
                    </table>
                </section>

                <section class="github-chat-widget-admin-card-full">
                    <h2>Buttons and Routes</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">Auto Inject Widget</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="github_chat_widget_settings[auto_inject]" value="1" <?php checked(!empty($settings['auto_inject'])); ?> />
                                    Show floating chat widget on all frontend pages.
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Enable Section Buttons</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="github_chat_widget_settings[enable_ui_buttons]" value="1" <?php checked(!empty($settings['enable_ui_buttons'])); ?> />
                                    Allow AI responses to include section/page navigation buttons.
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Dynamic System Info</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="github_chat_widget_settings[enable_dynamic_system_info]" value="1" <?php checked(!empty($settings['enable_dynamic_system_info'])); ?> />
                                    Enable two-step context flow: select related pages/posts first, then answer using fetched content.
                                </label>
                                <p class="description">If disabled, chat uses the current single system prompt flow.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_section_targets">Section/Page Targets</label></th>
                            <td>
                                <input id="github_chat_widget_section_targets" type="text" class="regular-text" name="github_chat_widget_settings[section_targets]" value="<?php echo esc_attr($settings['section_targets']); ?>" />
                                <p class="description">Comma-separated values. Example: experience,projects,skills,contact</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_default_button_label">Default Button Label</label></th>
                            <td><input id="github_chat_widget_default_button_label" type="text" class="regular-text" name="github_chat_widget_settings[default_button_label]" value="<?php echo esc_attr($settings['default_button_label']); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="github_chat_widget_button_routes">Button Routes</label></th>
                            <td>
                                <textarea id="github_chat_widget_button_routes" class="large-text code" rows="8" name="github_chat_widget_settings[button_routes]"><?php echo esc_textarea($settings['button_routes']); ?></textarea>
                                <p class="description">One route per line. Format: key|Button Label|URL or key|URL. Example: contact|Contact Me|/contact</p>
                            </td>
                        </tr>
                    </table>
                </section>

                <section class="github-chat-widget-admin-card github-chat-widget-admin-card-full">
                    <h2>System Prompt</h2>
                    <textarea id="github_chat_widget_system_prompt" class="large-text code" rows="14" name="github_chat_widget_settings[system_prompt]"><?php echo esc_textarea($settings['system_prompt']); ?></textarea>
                    <p class="description">Use strict JSON output format to preserve UI actions.</p>
                </section>

                <section class="github-chat-widget-admin-card github-chat-widget-admin-card-full">
                    <h2>Advanced CSS</h2>
                    <textarea id="github_chat_widget_advanced_css" class="large-text code github-chat-widget-admin-code-area" rows="12" name="github_chat_widget_settings[advanced_css]"><?php echo esc_textarea($settings['advanced_css']); ?></textarea>
                    <p class="description">Custom CSS is loaded on the frontend widget after the default stylesheet. Target the classes below.</p>
                    <label class="github-chat-widget-admin-label" for="github_chat_widget_css_classes">Available CSS Classes</label>
                    <textarea id="github_chat_widget_css_classes" class="large-text code github-chat-widget-admin-code-area" rows="12" readonly><?php echo esc_textarea($css_class_reference); ?></textarea>
                </section>
            </div>

            <?php submit_button('Save Settings'); ?>
        </form>

        <div class="github-chat-widget-admin-help">
            <h2>Usage</h2>
            <p>Shortcode: <code>[github_chat_widget]</code> when Auto Inject is disabled.</p>
            <p>Route keys should match likely user intents, such as <code>contact</code> or <code>projects</code>.</p>
            <p>Advanced CSS is intended for frontend widget styling, including the email input, chat input, send button, and navigation button states.</p>
        </div>

        <div class="github-chat-widget-admin-help">
            <h2>Submitted Emails</h2>
            <div class="github-chat-widget-admin-table-wrap">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($email_rows)) : ?>
                            <?php foreach ($email_rows as $row) : ?>
                                <tr>
                                    <td><?php echo esc_html((string) $row['id']); ?></td>
                                    <td><?php echo esc_html((string) $row['email']); ?></td>
                                    <td><?php echo esc_html((string) $row['created_at']); ?></td>
                                    <td><?php echo esc_html((string) $row['last_seen_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4">No emails captured yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="github-chat-widget-admin-help">
            <h2>Chat History JSON</h2>
            <div class="github-chat-widget-admin-table-wrap">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Messages</th>
                            <th>Updated</th>
                            <th>JSON</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($history_rows)) : ?>
                            <?php foreach ($history_rows as $row) : ?>
                                <tr>
                                    <td><?php echo esc_html((string) $row['id']); ?></td>
                                    <td><?php echo esc_html((string) $row['email']); ?></td>
                                    <td><?php echo esc_html((string) $row['message_count']); ?></td>
                                    <td><?php echo esc_html((string) $row['updated_at']); ?></td>
                                    <td>
                                        <textarea class="github-chat-widget-admin-json" readonly rows="5"><?php echo esc_textarea((string) $row['messages_json']); ?></textarea>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5">No chat history stored yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
