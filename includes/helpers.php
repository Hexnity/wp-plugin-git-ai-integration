<?php

if (!defined('ABSPATH')) {
    exit;
}

function github_chat_widget_default_system_prompt() {
    return "### ROLE\n"
        . "You are Github Chat, a concise and helpful website assistant.\n\n"
        . "### BEHAVIOR\n"
        . "1. Answer with short, clear responses based only on the website/business context.\n"
        . "2. If information is missing, say so briefly instead of guessing.\n"
        . "3. Keep main_answer under 80 words.\n\n"
        . "### UI ACTION BUTTONS\n"
        . "When user intent matches known sections/pages, set ui_action.show_button=true and include matching sections.\n\n"
        . "### OUTPUT FORMAT (STRICT JSON)\n"
        . "Return ONLY a valid JSON object. No prose outside the JSON.\n"
        . "{\n"
        . "  \"out_of_scope\": boolean,\n"
        . "  \"main_answer\": \"string\",\n"
        . "  \"ui_action\": {\n"
        . "    \"show_button\": boolean,\n"
        . "    \"sections\": [\"string\"],\n"
        . "    \"generated_prompt\": \"string\",\n"
        . "    \"button_label\": \"string\",\n"
        . "    \"target_url\": \"string\",\n"
        . "    \"route_key\": \"string\"\n"
        . "  }\n"
        . "}";
}

function github_chat_widget_dynamic_selector_system_prompt() {
    return "You are a page selector. Given a user message and a catalog of WordPress pages/posts, return the IDs of the most relevant pages that contain information needed to answer the user's message.\n"
        . "Return ONLY a raw JSON object. No explanation, no markdown, no code blocks.\n"
        . "Output format:\n"
        . "{\"matches\": [{\"id\": 123, \"type\": \"page\"}, {\"id\": 456, \"type\": \"post\"}]}\n"
        . "Rules:\n"
        . "- Include max 5 items.\n"
        . "- type must be \"page\" or \"post\".\n"
        . "- IDs must exist in the provided catalog.\n"
        . "- Use name, slug, and content_preview to determine relevance.\n"
        . "- If the user asks about a person, biography, portfolio, skills, projects, or about the site owner, prefer pages whose name or slug contains 'about', 'home', 'portfolio', 'profile', 'bio', or whose content_preview mentions the person's name.\n"
        . "- When uncertain, include more pages rather than fewer.\n"
        . "- If no page is relevant, return {\"matches\": []}.";
}

function github_chat_widget_normalize_model_id($value) {
    $value = trim(sanitize_text_field((string) $value));

    if ($value === '') {
        return '';
    }

    if (strpos($value, '/') !== false) {
        $parts = explode('/', $value);
        $value = end($parts);
    }

    return trim((string) $value);
}

function github_chat_widget_defaults() {
    return array(
        'chat_title' => 'Github Chat',
        'api_key' => '',
        'model' => 'gpt-4o-mini',
        'base_url' => 'https://models.inference.ai.azure.com/chat/completions',
        'temperature' => '0.7',
        'auto_inject' => '1',
        'welcome_text' => 'Hi! How can I help you today?',
        'thinking_text' => 'Thinking...',
        'input_placeholder' => 'Ask anything...',
        'send_button_text' => 'Send',
        'launcher_aria_label' => 'Open chat',
        'launcher_icon' => '💬',
        'launcher_image_url' => '',
        'launcher_border_color' => '#0f172a',
        'launcher_border_width' => '2',
        'chat_position' => 'bottom-right',
        'offset_x' => '24',
        'offset_y' => '24',
        'panel_width' => '420',
        'panel_height' => '560',
        'panel_bg_color' => '#111827',
        'accent_color' => '#10b981',
        'request_text_color' => '#ffffff',
        'response_text_color' => '#d1d5db',
        'title_font_size' => 'clamp(0.95rem, 0.9rem + 0.2vw, 1.05rem)',
        'body_font_size' => 'clamp(0.875rem, 0.84rem + 0.15vw, 0.95rem)',
        'input_font_size' => 'clamp(0.875rem, 0.84rem + 0.15vw, 1rem)',
        'button_font_size' => 'clamp(0.75rem, 0.72rem + 0.15vw, 0.875rem)',
        'enable_ui_buttons' => '1',
        'enable_dynamic_system_info' => '',
        'section_targets' => 'experience,projects,skills,contact',
        'default_button_label' => 'Open Section',
        'button_routes' => "contact|Contact|/contact\nprojects|View Projects|/projects\nexperience|Experience|/experience",
        'system_prompt' => github_chat_widget_default_system_prompt(),
        'advanced_css' => '',
    );
}

function github_chat_widget_users_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'github_chat_widget_users';
}

function github_chat_widget_history_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'github_chat_widget_chat_history';
}

function github_chat_widget_validate_email($email) {
    $email = sanitize_email((string) $email);
    if ($email === '' || !is_email($email)) {
        return '';
    }

    return strtolower($email);
}

function github_chat_widget_find_or_create_email_user($email) {
    global $wpdb;

    $safe_email = github_chat_widget_validate_email($email);
    if ($safe_email === '') {
        return 0;
    }

    $users_table = github_chat_widget_users_table_name();
    $now = current_time('mysql');

    $existing_id = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM {$users_table} WHERE email = %s", $safe_email)
    );

    if ($existing_id > 0) {
        $wpdb->update(
            $users_table,
            array(
                'updated_at' => $now,
                'last_seen_at' => $now,
            ),
            array('id' => $existing_id),
            array('%s', '%s'),
            array('%d')
        );

        return $existing_id;
    }

    $inserted = $wpdb->insert(
        $users_table,
        array(
            'email' => $safe_email,
            'created_at' => $now,
            'updated_at' => $now,
            'last_seen_at' => $now,
        ),
        array('%s', '%s', '%s', '%s')
    );

    if (!$inserted) {
        return 0;
    }

    return (int) $wpdb->insert_id;
}

function github_chat_widget_normalize_chat_messages_for_storage($messages) {
    if (!is_array($messages)) {
        return array();
    }

    $normalized = array();
    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }

        $role = isset($message['role']) ? sanitize_key((string) $message['role']) : '';
        $content = isset($message['content']) ? wp_strip_all_tags((string) $message['content']) : '';

        if ($role === 'assistant') {
            $role = 'ai';
        }

        if ($role !== 'user' && $role !== 'ai') {
            continue;
        }

        if ($content === '') {
            continue;
        }

        $entry = array(
            'role' => $role,
            'content' => $content,
        );

        if (!empty($message['uiAction']) && is_array($message['uiAction'])) {
            $entry['uiAction'] = $message['uiAction'];
        }

        $normalized[] = $entry;
    }

    return array_slice($normalized, -100);
}

function github_chat_widget_parse_assistant_payload_for_storage($payload) {
    $content = trim((string) $payload);
    $ui_action = null;

    if (strpos($content, '|UI_DATA|') !== false) {
        $parts = explode('|UI_DATA|', $content, 2);
        $content = trim((string) $parts[0]);
        $decoded_ui = json_decode((string) $parts[1], true);
        if (is_array($decoded_ui)) {
            $ui_action = $decoded_ui;
        }
    }

    if ($content === '') {
        $content = 'Agent Offline.';
    }

    $entry = array(
        'role' => 'ai',
        'content' => $content,
    );

    if (is_array($ui_action)) {
        $entry['uiAction'] = $ui_action;
    }

    return $entry;
}

function github_chat_widget_get_chat_history_by_email($email) {
    global $wpdb;

    $safe_email = github_chat_widget_validate_email($email);
    if ($safe_email === '') {
        return array();
    }

    $history_table = github_chat_widget_history_table_name();
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT messages_json FROM {$history_table} WHERE email = %s", $safe_email),
        ARRAY_A
    );

    if (!is_array($row) || empty($row['messages_json'])) {
        return array();
    }

    $decoded = json_decode((string) $row['messages_json'], true);
    if (!is_array($decoded)) {
        return array();
    }

    return github_chat_widget_normalize_chat_messages_for_storage($decoded);
}

function github_chat_widget_save_chat_history($email, $messages) {
    global $wpdb;

    $safe_email = github_chat_widget_validate_email($email);
    if ($safe_email === '') {
        return false;
    }

    $normalized_messages = github_chat_widget_normalize_chat_messages_for_storage($messages);
    $user_id = github_chat_widget_find_or_create_email_user($safe_email);
    if ($user_id <= 0) {
        return false;
    }

    $history_table = github_chat_widget_history_table_name();
    $now = current_time('mysql');

    $existing_id = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM {$history_table} WHERE email = %s", $safe_email)
    );

    $data = array(
        'user_id' => $user_id,
        'email' => $safe_email,
        'messages_json' => wp_json_encode($normalized_messages),
        'message_count' => count($normalized_messages),
        'updated_at' => $now,
    );

    if ($existing_id > 0) {
        $updated = $wpdb->update(
            $history_table,
            $data,
            array('id' => $existing_id),
            array('%d', '%s', '%s', '%d', '%s'),
            array('%d')
        );

        return $updated !== false;
    }

    $data['created_at'] = $now;

    $inserted = $wpdb->insert(
        $history_table,
        $data,
        array('%d', '%s', '%s', '%d', '%s', '%s')
    );

    return (bool) $inserted;
}

function github_chat_widget_persist_usage_data($model_id, $response) {
    $model_id = github_chat_widget_normalize_model_id($model_id);
    if ($model_id === '' || is_wp_error($response)) {
        return;
    }

    $remaining = sanitize_text_field((string) wp_remote_retrieve_header($response, 'x-ratelimit-remaining-requests'));
    $limit     = sanitize_text_field((string) wp_remote_retrieve_header($response, 'x-ratelimit-limit-requests'));
    $reset     = sanitize_text_field((string) wp_remote_retrieve_header($response, 'x-ratelimit-reset'));
    $rem_tok   = sanitize_text_field((string) wp_remote_retrieve_header($response, 'x-ratelimit-remaining-tokens'));

    if ($remaining === '' && $limit === '') {
        return;
    }

    $usage = get_option('gh_models_usage_data', array());
    if (!is_array($usage)) {
        $usage = array();
    }

    $reset_ts = 0;
    if ($reset !== '') {
        if (is_numeric($reset)) {
            $reset_ts = (int) $reset;
        } else {
            $parsed = strtotime($reset);
            $reset_ts = ($parsed !== false) ? (int) $parsed : 0;
        }
    }

    $usage[$model_id] = array(
        'remaining'          => ($remaining !== '') ? (int) $remaining : null,
        'limit'              => ($limit !== '')     ? (int) $limit     : null,
        'reset'              => $reset_ts,
        'remaining_tokens'   => ($rem_tok !== '')   ? (int) $rem_tok   : null,
        'updated_at'         => time(),
    );

    update_option('gh_models_usage_data', $usage, false);
}

function github_chat_widget_sanitize_hex_color($value, $fallback) {
    $value = sanitize_hex_color((string) $value);
    return empty($value) ? $fallback : $value;
}

function github_chat_widget_sanitize_range_number($value, $fallback, $min, $max) {
    $number = (int) $value;
    if ($number < $min || $number > $max) {
        return (string) $fallback;
    }
    return (string) $number;
}

function github_chat_widget_sanitize_position($value) {
    $allowed = array('bottom-right', 'bottom-left', 'top-right', 'top-left');
    $value = sanitize_text_field((string) $value);
    if (!in_array($value, $allowed, true)) {
        return 'bottom-right';
    }
    return $value;
}

function github_chat_widget_sanitize_base_url($value, $fallback) {
    $url = esc_url_raw(trim((string) $value));
    if ($url === '' || stripos($url, 'https://') !== 0) {
        return $fallback;
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return $fallback;
    }

    if (!empty($parts['query']) || !empty($parts['fragment'])) {
        return $fallback;
    }

    return $url;
}

function github_chat_widget_sanitize_clamp_value($value, $fallback) {
    $value = trim((string) $value);

    if ($value === '') {
        return (string) $fallback;
    }

    if (strlen($value) > 100) {
        return (string) $fallback;
    }

    if (!preg_match('/^clamp\(\s*-?(?:\d+|\d*\.\d+)(?:px|rem|em|vw|vh|vmin|vmax|%)\s*,\s*-?(?:\d+|\d*\.\d+)(?:px|rem|em|vw|vh|vmin|vmax|%)\s*,\s*-?(?:\d+|\d*\.\d+)(?:px|rem|em|vw|vh|vmin|vmax|%)\s*\)$/i', $value)) {
        return (string) $fallback;
    }

    return $value;
}

function github_chat_widget_sanitize_custom_css($value) {
    $css = trim((string) $value);

    if ($css === '') {
        return '';
    }

    $css = wp_kses_no_null($css);
    $css = str_ireplace(array('<style', '</style', '<?', '?>'), '', $css);
    $css = preg_replace('/@import/i', '', $css);
    $css = preg_replace('/expression\s*\(/i', '', $css);
    $css = preg_replace('/javascript\s*:/i', '', $css);

    return trim((string) $css);
}

function github_chat_widget_parse_button_routes($value) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $value);
    $routes = array();

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        if (count($parts) < 2) {
            continue;
        }

        $key = sanitize_key($parts[0]);
        $label = '';
        $url_raw = '';

        if (count($parts) >= 3) {
            $label = sanitize_text_field($parts[1]);
            $url_raw = $parts[2];
        } else {
            $label = sanitize_text_field($parts[0]);
            $url_raw = $parts[1];
        }

        if ($url_raw !== '' && strpos($url_raw, '/') !== 0 && strpos($url_raw, 'http://') !== 0 && strpos($url_raw, 'https://') !== 0) {
            $url_raw = '/' . ltrim($url_raw, '/');
        }

        $url = esc_url_raw($url_raw);

        if ($key === '' || $url === '') {
            continue;
        }

        $routes[] = array(
            'key' => $key,
            'label' => $label,
            'url' => $url,
        );
    }

    return $routes;
}

function github_chat_widget_normalize_text($value) {
    $value = strtolower(wp_strip_all_tags((string) $value));
    $value = preg_replace('/[^a-z0-9\s\-_\/]/', ' ', $value);
    $value = preg_replace('/\s+/', ' ', (string) $value);
    return trim((string) $value);
}

function github_chat_widget_find_matching_route($text, $routes) {
    $normalized_text = github_chat_widget_normalize_text($text);
    if ($normalized_text === '' || !is_array($routes) || empty($routes)) {
        return null;
    }

    foreach ($routes as $route) {
        if (empty($route['key']) || empty($route['url'])) {
            continue;
        }

        $key = github_chat_widget_normalize_text($route['key']);
        $label = !empty($route['label']) ? github_chat_widget_normalize_text($route['label']) : '';

        if ($key !== '' && strpos($normalized_text, $key) !== false) {
            return $route;
        }

        if ($label !== '' && strpos($normalized_text, $label) !== false) {
            return $route;
        }
    }

    return null;
}

function github_chat_widget_extract_last_user_message($messages) {
    if (!is_array($messages) || empty($messages)) {
        return '';
    }

    for ($i = count($messages) - 1; $i >= 0; $i--) {
        if (!is_array($messages[$i])) {
            continue;
        }
        if (!isset($messages[$i]['role']) || $messages[$i]['role'] !== 'user') {
            continue;
        }
        if (empty($messages[$i]['content'])) {
            continue;
        }
        return (string) $messages[$i]['content'];
    }

    return '';
}

function github_chat_widget_build_ui_data_from_route($route, $default_button_label) {
    $label = $default_button_label !== '' ? $default_button_label : 'Open';
    if (!empty($route['label'])) {
        $label = (string) $route['label'];
    }

    return array(
        'show_button' => true,
        'sections' => !empty($route['key']) ? array((string) $route['key']) : array(),
        'generated_prompt' => '',
        'button_label' => $label,
        'target_url' => !empty($route['url']) ? (string) $route['url'] : '',
        'route_key' => !empty($route['key']) ? (string) $route['key'] : '',
    );
}

function github_chat_widget_merge_route_into_payload($payload, $route, $default_button_label) {
    if (!is_array($route) || empty($route['url'])) {
        return $payload;
    }

    $ui_data = github_chat_widget_build_ui_data_from_route($route, $default_button_label);
    $parts = explode('|UI_DATA|', (string) $payload, 2);
    $content = trim((string) $parts[0]);

    if (count($parts) === 2) {
        $existing_ui = json_decode($parts[1], true);
        if (is_array($existing_ui)) {
            if (empty($existing_ui['target_url'])) {
                $existing_ui['target_url'] = $ui_data['target_url'];
            }
            if (empty($existing_ui['button_label'])) {
                $existing_ui['button_label'] = $ui_data['button_label'];
            }
            if (empty($existing_ui['sections']) || !is_array($existing_ui['sections'])) {
                $existing_ui['sections'] = $ui_data['sections'];
            }
            if (empty($existing_ui['route_key'])) {
                $existing_ui['route_key'] = $ui_data['route_key'];
            }
            $existing_ui['show_button'] = true;
            return $content . '|UI_DATA|' . wp_json_encode($existing_ui);
        }
    }

    if ($content === '') {
        $content = 'You can use this link.';
    }

    return $content . '|UI_DATA|' . wp_json_encode($ui_data);
}

function github_chat_widget_parse_payload($raw) {
    $final_payload = trim((string) $raw);

    if ($final_payload === '') {
        return 'Agent Offline.';
    }

    $matches = array();
    preg_match('/\{[\s\S]*\}/', $final_payload, $matches);

    if (empty($matches[0])) {
        return $final_payload;
    }

    $json = json_decode($matches[0], true);
    if (!is_array($json)) {
        return $final_payload;
    }

    $main_answer = isset($json['main_answer']) ? trim((string) $json['main_answer']) : '';
    $ui_action = isset($json['ui_action']) && is_array($json['ui_action']) ? $json['ui_action'] : null;

    $built = $main_answer !== '' ? $main_answer : $final_payload;

    if ($ui_action && !empty($ui_action['show_button'])) {
        $target_url = '';
        if (isset($ui_action['target_url']) && is_string($ui_action['target_url'])) {
            $target_url = esc_url_raw($ui_action['target_url']);
        } elseif (isset($ui_action['url']) && is_string($ui_action['url'])) {
            $target_url = esc_url_raw($ui_action['url']);
        }

        $button_data = array(
            'show_button' => true,
            'sections' => isset($ui_action['sections']) && is_array($ui_action['sections']) ? array_values($ui_action['sections']) : array(),
            'generated_prompt' => isset($ui_action['generated_prompt']) ? (string) $ui_action['generated_prompt'] : '',
            'button_label' => isset($ui_action['button_label']) ? (string) $ui_action['button_label'] : '',
            'target_url' => $target_url,
            'route_key' => isset($ui_action['route_key']) ? sanitize_key((string) $ui_action['route_key']) : '',
        );
        $built .= '|UI_DATA|' . wp_json_encode($button_data);
    }

    return $built;
}

function github_chat_widget_promote_text_link_to_button($payload, $default_button_label) {
    $parts = explode('|UI_DATA|', (string) $payload, 2);
    $content = trim((string) $parts[0]);
    $ui_data = null;

    if (count($parts) === 2) {
        $decoded_ui = json_decode($parts[1], true);
        if (is_array($decoded_ui)) {
            $ui_data = $decoded_ui;
        }
    }

    $markdown_match = array();
    preg_match('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/i', $content, $markdown_match);

    $plain_url_match = array();
    preg_match('/(https?:\/\/[^\s]+)/i', $content, $plain_url_match);

    $target_url = '';
    $button_label = '';

    if (!empty($markdown_match[2])) {
        $target_url = esc_url_raw((string) $markdown_match[2]);
        $button_label = trim((string) $markdown_match[1]);
        $content = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/i', '$1', $content, 1);
    } elseif (!empty($plain_url_match[1])) {
        $target_url = esc_url_raw((string) $plain_url_match[1]);
        $content = trim((string) str_replace($plain_url_match[1], '', $content));
    }

    if ($target_url === '') {
        if (is_array($ui_data) && !empty($ui_data['show_button']) && !empty($ui_data['target_url'])) {
            $content = preg_replace('/https?:\/\/[^\s)]+/i', '', $content);
            $content = trim((string) preg_replace('/\s{2,}/', ' ', (string) $content));
            if ($content === '') {
                $content = 'Use the button below.';
            }
            return $content . '|UI_DATA|' . wp_json_encode($ui_data);
        }
        return $payload;
    }

    if ($button_label === '') {
        $button_label = $default_button_label !== '' ? $default_button_label : 'Open Page';
    }

    if (!is_array($ui_data)) {
        $ui_data = array(
            'show_button' => true,
            'sections' => array(),
            'generated_prompt' => '',
            'button_label' => $button_label,
            'target_url' => $target_url,
            'route_key' => '',
        );
    } else {
        $ui_data['show_button'] = true;
        if (empty($ui_data['target_url'])) {
            $ui_data['target_url'] = $target_url;
        }
        if (empty($ui_data['button_label'])) {
            $ui_data['button_label'] = $button_label;
        }
    }

    $content = trim((string) preg_replace('/\s{2,}/', ' ', (string) $content));
    if ($content === '') {
        $content = 'Use the button below.';
    }

    return $content . '|UI_DATA|' . wp_json_encode($ui_data);
}

function github_chat_widget_extract_error_message($body, $status_code) {
    $fallback = 'LLM request failed (HTTP ' . (int) $status_code . ').';
    if (!is_string($body) || trim($body) === '') {
        return $fallback;
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        return $fallback;
    }

    if (!empty($decoded['error']['message']) && is_string($decoded['error']['message'])) {
        return trim($decoded['error']['message']);
    }

    if (!empty($decoded['message']) && is_string($decoded['message'])) {
        return trim($decoded['message']);
    }

    if (!empty($decoded['error']) && is_string($decoded['error'])) {
        return trim($decoded['error']);
    }

    return $fallback;
}

function github_chat_widget_alternate_base_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    if (strpos($url, '/v1/chat/completions') !== false) {
        return str_replace('/v1/chat/completions', '/chat/completions', $url);
    }

    if (strpos($url, '/chat/completions') !== false) {
        return str_replace('/chat/completions', '/v1/chat/completions', $url);
    }

    return '';
}

function github_chat_widget_build_content_catalog() {
    $catalog = array();

    $post_types = array('page', 'post');
    foreach ($post_types as $post_type) {
        $ids = get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'numberposts' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
        ));

        foreach ($ids as $id) {
            $post = get_post((int) $id);
            if (!$post instanceof WP_Post) {
                continue;
            }

            $title = get_the_title($post);
            $permalink = get_permalink((int) $post->ID);
            // Render blocks/shortcodes first so Gutenberg block comments are gone before stripping tags
            $rendered_content = apply_filters('the_content', $post->post_content);
            $preview = trim((string) wp_strip_all_tags((string) $rendered_content));
            $preview = preg_replace('/\s+/', ' ', $preview);
            if (strlen($preview) > 500) {
                $preview = substr($preview, 0, 500);
            }
            $catalog[] = array(
                'id' => (int) $post->ID,
                'type' => $post_type,
                'name' => $title !== '' ? (string) $title : ('Untitled #' . (int) $post->ID),
                'slug' => (string) $post->post_name,
                'url' => $permalink ? esc_url_raw((string) $permalink) : '',
                'content_preview' => $preview,
            );
        }
    }

    return $catalog;
}

function github_chat_widget_parse_dynamic_selection($raw_text, $catalog) {
    if (!is_string($raw_text) || trim($raw_text) === '' || !is_array($catalog)) {
        return array();
    }

    $catalog_map = array();
    foreach ($catalog as $item) {
        if (!is_array($item) || empty($item['id']) || empty($item['type'])) {
            continue;
        }
        $id = (int) $item['id'];
        $type = $item['type'] === 'page' ? 'page' : ($item['type'] === 'post' ? 'post' : '');
        if ($id <= 0 || $type === '') {
            continue;
        }
        $catalog_map[$type . ':' . $id] = true;
    }

    $json = null;
    $matches = array();
    preg_match('/\{[\s\S]*\}/', $raw_text, $matches);
    if (!empty($matches[0])) {
        $json = json_decode($matches[0], true);
    }

    if (!is_array($json)) {
        return array();
    }

    $candidates = array();
    if (!empty($json['matches']) && is_array($json['matches'])) {
        $candidates = $json['matches'];
    } elseif (!empty($json['items']) && is_array($json['items'])) {
        $candidates = $json['items'];
    }

    $selected = array();
    foreach ($candidates as $candidate) {
        if (!is_array($candidate)) {
            continue;
        }

        $id = isset($candidate['id']) ? (int) $candidate['id'] : 0;
        $type = isset($candidate['type']) ? sanitize_key((string) $candidate['type']) : '';
        if ($type !== 'page' && $type !== 'post') {
            continue;
        }

        $key = $type . ':' . $id;
        if ($id <= 0 || empty($catalog_map[$key])) {
            continue;
        }

        $selected[$key] = array(
            'id' => $id,
            'type' => $type,
        );

        if (count($selected) >= 5) {
            break;
        }
    }

    return array_values($selected);
}

function github_chat_widget_fallback_dynamic_selection($user_message, $catalog) {
    $message = github_chat_widget_normalize_text($user_message);
    if ($message === '' || !is_array($catalog) || empty($catalog)) {
        return array();
    }

    $tokens = preg_split('/\s+/', $message);
    if (!is_array($tokens)) {
        $tokens = array();
    }

    $scored = array();
    foreach ($catalog as $item) {
        if (!is_array($item) || empty($item['id']) || empty($item['type'])) {
            continue;
        }

        $type = $item['type'] === 'page' ? 'page' : ($item['type'] === 'post' ? 'post' : '');
        $id = (int) $item['id'];
        if ($type === '' || $id <= 0) {
            continue;
        }

        $name = github_chat_widget_normalize_text(isset($item['name']) ? $item['name'] : '');
        $slug = github_chat_widget_normalize_text(isset($item['slug']) ? $item['slug'] : '');
        $preview = github_chat_widget_normalize_text(isset($item['content_preview']) ? $item['content_preview'] : '');

        $score = 0;
        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if (strlen($token) < 3) {
                continue;
            }
            if ($name !== '' && strpos($name, $token) !== false) {
                $score += 5;
            }
            if ($slug !== '' && strpos($slug, $token) !== false) {
                $score += 4;
            }
            if ($preview !== '' && strpos($preview, $token) !== false) {
                $score += 2;
            }
        }

        if ($score > 0) {
            $scored[] = array(
                'id' => $id,
                'type' => $type,
                'score' => $score,
            );
        }
    }

    if (empty($scored)) {
        return array();
    }

    usort($scored, function ($a, $b) {
        return (int) $b['score'] <=> (int) $a['score'];
    });

    $picked = array();
    foreach ($scored as $row) {
        $key = $row['type'] . ':' . (int) $row['id'];
        if (isset($picked[$key])) {
            continue;
        }

        $picked[$key] = array(
            'id' => (int) $row['id'],
            'type' => $row['type'],
        );

        if (count($picked) >= 3) {
            break;
        }
    }

    return array_values($picked);
}

function github_chat_widget_fetch_post_content_via_rest($type, $id) {
    $type = $type === 'page' ? 'page' : ($type === 'post' ? 'post' : '');
    $id = (int) $id;

    if ($type === '' || $id <= 0) {
        return null;
    }

    $endpoint = $type === 'page' ? '/wp/v2/pages/' : '/wp/v2/posts/';
    $request = new WP_REST_Request('GET', $endpoint . $id);
    $request->set_query_params(array('context' => 'view'));
    $response = rest_do_request($request);

    if (is_wp_error($response)) {
        return null;
    }

    if (!$response instanceof WP_REST_Response) {
        return null;
    }

    $status = (int) $response->get_status();
    if ($status < 200 || $status >= 300) {
        return null;
    }

    $data = $response->get_data();
    if (!is_array($data)) {
        return null;
    }

    $title_html = isset($data['title']['rendered']) ? (string) $data['title']['rendered'] : '';
    $title = html_entity_decode(wp_strip_all_tags($title_html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $content_html = isset($data['content']['rendered']) ? (string) $data['content']['rendered'] : '';
    $content = html_entity_decode(wp_strip_all_tags($content_html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return array(
        'id' => $id,
        'type' => $type,
        'slug' => isset($data['slug']) ? sanitize_title((string) $data['slug']) : '',
        'name' => $title,
        'url' => isset($data['link']) ? esc_url_raw((string) $data['link']) : '',
        'content' => trim($content),
    );
}

function github_chat_widget_build_dynamic_context($selected_items) {
    if (!is_array($selected_items) || empty($selected_items)) {
        return '';
    }

    $sections = array();
    foreach ($selected_items as $selected_item) {
        if (!is_array($selected_item) || empty($selected_item['id']) || empty($selected_item['type'])) {
            continue;
        }

        $item = github_chat_widget_fetch_post_content_via_rest($selected_item['type'], $selected_item['id']);
        if (!is_array($item)) {
            continue;
        }

        $content = (string) $item['content'];
        if (strlen($content) > 9000) {
            $content = substr($content, 0, 9000);
        }

        $sections[] = "Type: " . $item['type']
            . "\nID: " . (int) $item['id']
            . "\nName: " . $item['name']
            . "\nSlug: " . $item['slug']
            . "\nURL: " . (!empty($item['url']) ? $item['url'] : '')
            . "\nContent:\n" . $content;
    }

    if (empty($sections)) {
        return '';
    }

    return implode("\n\n---\n\n", $sections);
}
