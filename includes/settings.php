<?php

if (!defined('ABSPATH')) {
    exit;
}

function github_chat_widget_get_settings() {
    $defaults = github_chat_widget_defaults();
    $saved = get_option('github_chat_widget_settings', array());

    if (!is_array($saved)) {
        $saved = array();
    }

    $settings = wp_parse_args($saved, $defaults);

    if (empty($saved['response_text_color']) && !empty($saved['text_color'])) {
        $settings['response_text_color'] = github_chat_widget_sanitize_hex_color($saved['text_color'], $defaults['response_text_color']);
    }

    return $settings;
}

function github_chat_widget_activate() {
    if (get_option('github_chat_widget_settings') === false) {
        add_option('github_chat_widget_settings', github_chat_widget_defaults());
    }

    github_chat_widget_create_tables();
}

function github_chat_widget_create_tables() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $users_table = github_chat_widget_users_table_name();
    $history_table = github_chat_widget_history_table_name();

    $users_sql = "CREATE TABLE {$users_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        last_seen_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) {$charset_collate};";

    $history_sql = "CREATE TABLE {$history_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        email VARCHAR(190) NOT NULL,
        messages_json LONGTEXT NOT NULL,
        message_count INT(11) UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY email (email),
        KEY user_id (user_id)
    ) {$charset_collate};";

    dbDelta($users_sql);
    dbDelta($history_sql);

    update_option('github_chat_widget_db_version', GITHUB_CHAT_WIDGET_DB_VERSION);
}

function github_chat_widget_maybe_upgrade_db() {
    $stored_version = get_option('github_chat_widget_db_version', '');
    if ((string) $stored_version === (string) GITHUB_CHAT_WIDGET_DB_VERSION) {
        return;
    }

    github_chat_widget_create_tables();
}
add_action('init', 'github_chat_widget_maybe_upgrade_db');

function github_chat_widget_register_settings() {
    register_setting('github_chat_widget_group', 'github_chat_widget_settings', 'github_chat_widget_sanitize_settings');
}
add_action('admin_init', 'github_chat_widget_register_settings');

function github_chat_widget_sanitize_settings($input) {
    $defaults = github_chat_widget_defaults();
    $current_settings = github_chat_widget_get_settings();

    $api_key = isset($input['api_key']) ? trim((string) $input['api_key']) : '';
    if ($api_key === '' && !empty($current_settings['api_key'])) {
        $api_key = (string) $current_settings['api_key'];
    } else {
        $api_key = sanitize_text_field($api_key);
    }

    return array(
        'chat_title' => isset($input['chat_title']) ? sanitize_text_field($input['chat_title']) : $defaults['chat_title'],
        'api_key' => $api_key,
        'model' => isset($input['model']) ? sanitize_text_field($input['model']) : $defaults['model'],
        'base_url' => isset($input['base_url'])
            ? github_chat_widget_sanitize_base_url($input['base_url'], $defaults['base_url'])
            : $defaults['base_url'],
        'temperature' => isset($input['temperature']) ? sanitize_text_field($input['temperature']) : $defaults['temperature'],
        'auto_inject' => isset($input['auto_inject']) ? '1' : '',
        'welcome_text' => isset($input['welcome_text']) ? sanitize_text_field($input['welcome_text']) : $defaults['welcome_text'],
        'thinking_text' => isset($input['thinking_text']) ? sanitize_text_field($input['thinking_text']) : $defaults['thinking_text'],
        'input_placeholder' => isset($input['input_placeholder']) ? sanitize_text_field($input['input_placeholder']) : $defaults['input_placeholder'],
        'send_button_text' => isset($input['send_button_text']) ? sanitize_text_field($input['send_button_text']) : $defaults['send_button_text'],
        'launcher_aria_label' => isset($input['launcher_aria_label']) ? sanitize_text_field($input['launcher_aria_label']) : $defaults['launcher_aria_label'],
        'launcher_icon' => isset($input['launcher_icon']) ? sanitize_text_field($input['launcher_icon']) : $defaults['launcher_icon'],
        'launcher_image_url' => isset($input['launcher_image_url']) ? esc_url_raw($input['launcher_image_url']) : $defaults['launcher_image_url'],
        'launcher_border_color' => isset($input['launcher_border_color']) ? github_chat_widget_sanitize_hex_color($input['launcher_border_color'], $defaults['launcher_border_color']) : $defaults['launcher_border_color'],
        'launcher_border_width' => isset($input['launcher_border_width']) ? github_chat_widget_sanitize_range_number($input['launcher_border_width'], $defaults['launcher_border_width'], 0, 8) : $defaults['launcher_border_width'],
        'chat_position' => isset($input['chat_position']) ? github_chat_widget_sanitize_position($input['chat_position']) : $defaults['chat_position'],
        'offset_x' => isset($input['offset_x']) ? github_chat_widget_sanitize_range_number($input['offset_x'], $defaults['offset_x'], 0, 200) : $defaults['offset_x'],
        'offset_y' => isset($input['offset_y']) ? github_chat_widget_sanitize_range_number($input['offset_y'], $defaults['offset_y'], 0, 200) : $defaults['offset_y'],
        'panel_width' => isset($input['panel_width']) ? github_chat_widget_sanitize_range_number($input['panel_width'], $defaults['panel_width'], 280, 700) : $defaults['panel_width'],
        'panel_height' => isset($input['panel_height']) ? github_chat_widget_sanitize_range_number($input['panel_height'], $defaults['panel_height'], 360, 900) : $defaults['panel_height'],
        'panel_bg_color' => isset($input['panel_bg_color']) ? github_chat_widget_sanitize_hex_color($input['panel_bg_color'], $defaults['panel_bg_color']) : $defaults['panel_bg_color'],
        'accent_color' => isset($input['accent_color']) ? github_chat_widget_sanitize_hex_color($input['accent_color'], $defaults['accent_color']) : $defaults['accent_color'],
        'request_text_color' => isset($input['request_text_color']) ? github_chat_widget_sanitize_hex_color($input['request_text_color'], $defaults['request_text_color']) : $defaults['request_text_color'],
        'response_text_color' => isset($input['response_text_color']) ? github_chat_widget_sanitize_hex_color($input['response_text_color'], $defaults['response_text_color']) : $defaults['response_text_color'],
        'title_font_size' => isset($input['title_font_size']) ? github_chat_widget_sanitize_clamp_value($input['title_font_size'], $defaults['title_font_size']) : $defaults['title_font_size'],
        'body_font_size' => isset($input['body_font_size']) ? github_chat_widget_sanitize_clamp_value($input['body_font_size'], $defaults['body_font_size']) : $defaults['body_font_size'],
        'input_font_size' => isset($input['input_font_size']) ? github_chat_widget_sanitize_clamp_value($input['input_font_size'], $defaults['input_font_size']) : $defaults['input_font_size'],
        'button_font_size' => isset($input['button_font_size']) ? github_chat_widget_sanitize_clamp_value($input['button_font_size'], $defaults['button_font_size']) : $defaults['button_font_size'],
        'enable_ui_buttons' => isset($input['enable_ui_buttons']) ? '1' : '',
        'enable_dynamic_system_info' => isset($input['enable_dynamic_system_info']) ? '1' : '',
        'section_targets' => isset($input['section_targets']) ? sanitize_text_field($input['section_targets']) : $defaults['section_targets'],
        'default_button_label' => isset($input['default_button_label']) ? sanitize_text_field($input['default_button_label']) : $defaults['default_button_label'],
        'button_routes' => isset($input['button_routes']) ? sanitize_textarea_field($input['button_routes']) : $defaults['button_routes'],
        'system_prompt' => isset($input['system_prompt']) ? sanitize_textarea_field($input['system_prompt']) : $defaults['system_prompt'],
        'advanced_css' => isset($input['advanced_css']) ? github_chat_widget_sanitize_custom_css($input['advanced_css']) : $defaults['advanced_css'],
    );
}
