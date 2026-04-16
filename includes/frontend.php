<?php

if (!defined('ABSPATH')) {
    exit;
}

function github_chat_widget_register_assets() {
    wp_register_style(
        'github-chat-widget-style',
        GITHUB_CHAT_WIDGET_URL . 'assets/chat.css',
        array(),
        GITHUB_CHAT_WIDGET_VERSION
    );

    wp_register_script(
        'github-chat-widget-script',
        GITHUB_CHAT_WIDGET_URL . 'assets/chat.js',
        array(),
        GITHUB_CHAT_WIDGET_VERSION,
        true
    );

    $settings = github_chat_widget_get_settings();
    $advanced_css = github_chat_widget_sanitize_custom_css($settings['advanced_css']);

    if ($advanced_css !== '') {
        wp_add_inline_style('github-chat-widget-style', $advanced_css);
    }

    $allowed_sections = array_filter(array_map('trim', explode(',', (string) $settings['section_targets'])));
    $button_routes = github_chat_widget_parse_button_routes($settings['button_routes']);
    $button_route_map = array();

    foreach ($button_routes as $route) {
        $button_route_map[$route['key']] = array(
            'label' => $route['label'],
            'url' => $route['url'],
        );
    }

    wp_localize_script('github-chat-widget-script', 'GithubChatWidgetConfig', array(
        'restUrl' => esc_url_raw(rest_url('github-chat-widget/v1/chat')),
        'sessionUrl' => esc_url_raw(rest_url('github-chat-widget/v1/session')),
        'chatTitle' => sanitize_text_field($settings['chat_title']),
        'welcomeText' => sanitize_text_field($settings['welcome_text']),
        'thinkingText' => sanitize_text_field($settings['thinking_text']),
        'inputPlaceholder' => sanitize_text_field($settings['input_placeholder']),
        'sendButtonText' => sanitize_text_field($settings['send_button_text']),
        'emailTitle' => 'Enter your email to start chat',
        'emailPlaceholder' => 'you@example.com',
        'emailButtonText' => 'Start Chat',
        'changeEmailText' => 'Change Email',
        'launcherAriaLabel' => sanitize_text_field($settings['launcher_aria_label']),
        'launcherIcon' => sanitize_text_field($settings['launcher_icon']),
        'launcherImageUrl' => esc_url_raw($settings['launcher_image_url']),
        'launcherBorderColor' => github_chat_widget_sanitize_hex_color($settings['launcher_border_color'], '#0f172a'),
        'launcherBorderWidth' => (int) $settings['launcher_border_width'],
        'chatPosition' => github_chat_widget_sanitize_position($settings['chat_position']),
        'offsetX' => (int) $settings['offset_x'],
        'offsetY' => (int) $settings['offset_y'],
        'panelWidth' => (int) $settings['panel_width'],
        'panelHeight' => (int) $settings['panel_height'],
        'panelBgColor' => github_chat_widget_sanitize_hex_color($settings['panel_bg_color'], '#111827'),
        'accentColor' => github_chat_widget_sanitize_hex_color($settings['accent_color'], '#10b981'),
        'requestTextColor' => github_chat_widget_sanitize_hex_color($settings['request_text_color'], '#ffffff'),
        'responseTextColor' => github_chat_widget_sanitize_hex_color($settings['response_text_color'], '#d1d5db'),
        'titleFontSize' => github_chat_widget_sanitize_clamp_value($settings['title_font_size'], 'clamp(0.95rem, 0.9rem + 0.2vw, 1.05rem)'),
        'bodyFontSize' => github_chat_widget_sanitize_clamp_value($settings['body_font_size'], 'clamp(0.875rem, 0.84rem + 0.15vw, 0.95rem)'),
        'inputFontSize' => github_chat_widget_sanitize_clamp_value($settings['input_font_size'], 'clamp(0.875rem, 0.84rem + 0.15vw, 1rem)'),
        'buttonFontSize' => github_chat_widget_sanitize_clamp_value($settings['button_font_size'], 'clamp(0.75rem, 0.72rem + 0.15vw, 0.875rem)'),
        'enableUiButtons' => !empty($settings['enable_ui_buttons']),
        'defaultButtonLabel' => sanitize_text_field($settings['default_button_label']),
        'allowedSections' => array_values($allowed_sections),
        'buttonRouteMap' => $button_route_map,
    ));
}
add_action('wp_enqueue_scripts', 'github_chat_widget_register_assets');

function github_chat_widget_widget_markup() {
    ob_start();
    ?>
    <div id="github-chat-widget-widget" class="github-chat-widget-root" aria-live="polite"></div>
    <?php
    return ob_get_clean();
}

function github_chat_widget_enqueue_widget() {
    wp_enqueue_style('github-chat-widget-style');
    wp_enqueue_script('github-chat-widget-script');
}

function github_chat_widget_shortcode() {
    github_chat_widget_enqueue_widget();
    return github_chat_widget_widget_markup();
}
add_shortcode('github_chat_widget', 'github_chat_widget_shortcode');

function github_chat_widget_footer_injection() {
    $settings = github_chat_widget_get_settings();
    if (empty($settings['auto_inject'])) {
        return;
    }

    github_chat_widget_enqueue_widget();
    echo github_chat_widget_widget_markup();
}
add_action('wp_footer', 'github_chat_widget_footer_injection');
