<?php

if (!defined('ABSPATH')) {
    exit;
}

function github_chat_widget_register_rest_routes() {
    register_rest_route('github-chat-widget/v1', '/session', array(
        'methods' => 'POST',
        'callback' => 'github_chat_widget_rest_session_handler',
        'permission_callback' => function () {
            return true;
        },
    ));

    register_rest_route('github-chat-widget/v1', '/chat', array(
        'methods' => 'POST',
        'callback' => 'github_chat_widget_rest_handler',
        'permission_callback' => function () {
            return true;
        },
    ));
}
add_action('rest_api_init', 'github_chat_widget_register_rest_routes');

function github_chat_widget_validate_request_origin() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_ORIGIN'])) : '';
    if ($origin === '') {
        return true;
    }

    $origin_host = wp_parse_url($origin, PHP_URL_HOST);
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

    if (!empty($origin_host) && !empty($site_host) && strtolower((string) $origin_host) !== strtolower((string) $site_host)) {
        return false;
    }

    return true;
}

function github_chat_widget_rest_session_handler(WP_REST_Request $request) {
    if (!github_chat_widget_validate_request_origin()) {
        return new WP_REST_Response(array('error' => 'Invalid request origin.'), 403);
    }

    if (github_chat_widget_is_rate_limited()) {
        return new WP_REST_Response(array('error' => 'Rate limit exceeded. Please wait a minute.'), 429);
    }

    $email = github_chat_widget_validate_email($request->get_param('email'));
    if ($email === '') {
        return new WP_REST_Response(array('error' => 'A valid email is required.'), 400);
    }

    $user_id = github_chat_widget_find_or_create_email_user($email);
    if ($user_id <= 0) {
        return new WP_REST_Response(array('error' => 'Unable to initialize chat session.'), 500);
    }

    $messages = github_chat_widget_get_chat_history_by_email($email);

    return new WP_REST_Response(array(
        'email' => $email,
        'messages' => $messages,
    ), 200);
}

function github_chat_widget_rate_limit_key($ip) {
    return 'github_chat_widget_rl_' . md5((string) $ip);
}

function github_chat_widget_is_rate_limited() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $key = github_chat_widget_rate_limit_key($ip);
    $count = (int) get_transient($key);

    if ($count >= 30) {
        return true;
    }

    set_transient($key, $count + 1, MINUTE_IN_SECONDS);
    return false;
}

function github_chat_widget_normalize_messages($messages) {
    if (!is_array($messages)) {
        return array();
    }

    $normalized = array();

    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }

        $role = isset($message['role']) ? sanitize_text_field($message['role']) : '';
        $content = isset($message['content']) ? wp_strip_all_tags((string) $message['content']) : '';

        if ($role !== 'user' && $role !== 'ai') {
            continue;
        }

        if ($content === '') {
            continue;
        }

        $normalized[] = array(
            'role' => $role === 'ai' ? 'assistant' : 'user',
            'content' => $content,
        );
    }

    return $normalized;
}

function github_chat_widget_send_chat_completion($settings, $payload) {
    $base_url = trim((string) $settings['base_url']);
    $model_id = isset($payload['model']) ? github_chat_widget_normalize_model_id($payload['model']) : '';

    if ($model_id === '') {
        $model_id = github_chat_widget_normalize_model_id(isset($settings['model']) ? $settings['model'] : '');
    }

    if ($model_id === '') {
        $defaults = github_chat_widget_defaults();
        $model_id = github_chat_widget_normalize_model_id($defaults['model']);
    }

    if ($model_id !== '') {
        $payload['model'] = $model_id;
    }

    $request_args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . trim((string) $settings['api_key']),
        ),
        'timeout' => 45,
        'body' => wp_json_encode($payload),
    );

    $response = wp_remote_post(esc_url_raw($base_url), $request_args);
    if (is_wp_error($response)) {
        return array(
            'ok' => false,
            'status' => 500,
            'body' => '',
            'error' => $response->get_error_message(),
        );
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    $final_response = $response;

    if ($status_code === 404) {
        $alt_base_url = github_chat_widget_alternate_base_url($base_url);
        if ($alt_base_url !== '' && $alt_base_url !== $base_url) {
            $retry_response = wp_remote_post(esc_url_raw($alt_base_url), $request_args);
            if (!is_wp_error($retry_response)) {
                $retry_status = (int) wp_remote_retrieve_response_code($retry_response);
                if ($retry_status >= 200 && $retry_status < 300) {
                    $status_code = $retry_status;
                    $body = (string) wp_remote_retrieve_body($retry_response);
                    $final_response = $retry_response;
                }
            }
        }
    }

    if ($model_id !== '' && $status_code >= 200 && $status_code < 300) {
        github_chat_widget_persist_usage_data($model_id, $final_response);
    }

    if ($status_code < 200 || $status_code >= 300) {
        return array(
            'ok' => false,
            'status' => $status_code,
            'body' => $body,
            'error' => github_chat_widget_extract_error_message($body, $status_code),
        );
    }

    return array(
        'ok' => true,
        'status' => $status_code,
        'body' => $body,
        'error' => '',
    );
}

function github_chat_widget_extract_content_from_completion_body($body) {
    $decoded = json_decode((string) $body, true);
    if (!is_array($decoded)) {
        return '';
    }

    if (!empty($decoded['choices'][0]['message']['content'])) {
        return (string) $decoded['choices'][0]['message']['content'];
    }

    return '';
}

function github_chat_widget_rest_handler(WP_REST_Request $request) {
    if (!github_chat_widget_validate_request_origin()) {
        return new WP_REST_Response(array('error' => 'Invalid request origin.'), 403);
    }

    $email = github_chat_widget_validate_email($request->get_param('email'));
    if ($email === '') {
        return new WP_REST_Response(array('error' => 'A valid email is required before starting chat.'), 400);
    }

    $user_id = github_chat_widget_find_or_create_email_user($email);
    if ($user_id <= 0) {
        return new WP_REST_Response(array('error' => 'Unable to initialize chat session.'), 500);
    }

    if (github_chat_widget_is_rate_limited()) {
        return new WP_REST_Response(array('error' => 'Rate limit exceeded. Please wait a minute.'), 429);
    }

    $settings = github_chat_widget_get_settings();

    if (empty($settings['api_key'])) {
        return new WP_REST_Response(array('error' => 'API key is not configured in plugin settings.'), 500);
    }

    $messages = $request->get_param('messages');
    $normalized_messages = github_chat_widget_normalize_messages($messages);

    if (empty($normalized_messages)) {
        return new WP_REST_Response(array('error' => 'No valid messages provided.'), 400);
    }

    if (count($normalized_messages) > 20) {
        $normalized_messages = array_slice($normalized_messages, -20);
    }

    foreach ($normalized_messages as $message) {
        if (!empty($message['content']) && strlen((string) $message['content']) > 4000) {
            return new WP_REST_Response(array('error' => 'Message is too long. Maximum 4000 characters per message.'), 400);
        }
    }

    $temperature = is_numeric($settings['temperature']) ? (float) $settings['temperature'] : 0.7;

    $system_prompt = trim((string) $settings['system_prompt']);
    $section_targets = trim((string) $settings['section_targets']);
    $default_button_label = trim((string) $settings['default_button_label']);
    $enable_ui_buttons = !empty($settings['enable_ui_buttons']);
    $enable_dynamic_system_info = !empty($settings['enable_dynamic_system_info']);
    $button_routes = github_chat_widget_parse_button_routes($settings['button_routes']);
    $last_user_message = github_chat_widget_extract_last_user_message($normalized_messages);
    $matched_route = github_chat_widget_find_matching_route($last_user_message, $button_routes);

    if ($system_prompt === '') {
        $system_prompt = github_chat_widget_default_system_prompt();
    }

    if ($enable_ui_buttons) {
        if ($enable_dynamic_system_info) {
            $label_hint = $default_button_label !== '' ? '"' . $default_button_label . '"' : 'a descriptive label';
            $system_prompt .= "\n\nWhen dynamic website content context is provided, treat it as authoritative source data. If relevant facts are present, summarize 1-3 concrete points from that context in main_answer before suggesting navigation. Only say information is missing when the context truly does not contain the requested detail.";
            $system_prompt .= "\nWhen a page or post from the provided website content context is directly relevant to the user's question, set ui_action.show_button=true, set ui_action.target_url to the URL of that page or post from the context, and set ui_action.button_label to " . $label_hint . ". Do not invent URLs; only use URLs present in the context.";
        } elseif ($section_targets !== '') {
            $system_prompt .= "\n\nAllowed sections/pages: " . $section_targets . ".";
            if ($default_button_label !== '') {
                $system_prompt .= " Default button label: " . $default_button_label . ".";
            }
            if (!empty($button_routes)) {
                $system_prompt .= "\nConfigured button routes (use exact URLs when relevant):";
                foreach ($button_routes as $route) {
                    $system_prompt .= "\n- key: " . $route['key'] . "; label: " . $route['label'] . "; url: " . $route['url'];
                }
                $system_prompt .= "\nWhen returning ui_action, include target_url and route_key using one configured route when the intent matches.";
            }
        } else {
            $system_prompt .= "\n\nAlways return ui_action.show_button as false.";
        }
    } else {
        $system_prompt .= "\n\nAlways return ui_action.show_button as false.";
    }

    $dynamic_context = '';

    if ($enable_dynamic_system_info && $last_user_message !== '') {
        $catalog = github_chat_widget_build_content_catalog();
        if (!empty($catalog)) {
            $selector_payload = array(
                'model' => (string) $settings['model'],
                'temperature' => $temperature,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => github_chat_widget_dynamic_selector_system_prompt(),
                    ),
                    array(
                        'role' => 'user',
                        'content' => "User message:\n" . $last_user_message . "\n\nCatalog (JSON):\n" . wp_json_encode($catalog),
                    ),
                ),
            );

            $selector_response = github_chat_widget_send_chat_completion($settings, $selector_payload);
            if (!empty($selector_response['ok'])) {
                $selector_raw = github_chat_widget_extract_content_from_completion_body($selector_response['body']);
                $selected_items = github_chat_widget_parse_dynamic_selection($selector_raw, $catalog);
                if (empty($selected_items)) {
                    $selected_items = github_chat_widget_fallback_dynamic_selection($last_user_message, $catalog);
                }
                if (!empty($selected_items)) {
                    $dynamic_context = github_chat_widget_build_dynamic_context($selected_items);
                }
            }
        }
    }

    $final_messages = array(
        array(
            'role' => 'system',
            'content' => $system_prompt,
        ),
    );

    if ($dynamic_context !== '') {
        $final_messages[] = array(
            'role' => 'system',
            'content' => "Dynamic website content context:\n\n" . $dynamic_context . "\n\nUse this context to answer with concrete facts when relevant. If previous assistant replies said information is missing but this context contains it, trust this context and answer from it.",
        );
    }

    $final_messages = array_merge($final_messages, $normalized_messages);

    $payload = array(
        'model' => (string) $settings['model'],
        'temperature' => $temperature,
        'messages' => $final_messages,
    );

    $response = github_chat_widget_send_chat_completion($settings, $payload);
    if (empty($response['ok'])) {
        return new WP_REST_Response(array(
            'error' => (string) $response['error'],
            'provider_status' => (int) $response['status'],
        ), 500);
    }

    $raw_text = github_chat_widget_extract_content_from_completion_body($response['body']);
    if ($raw_text === '') {
        return new WP_REST_Response(array('error' => 'Invalid API response format.'), 500);
    }

    $final_payload = github_chat_widget_parse_payload($raw_text);

    if ($enable_ui_buttons && is_array($matched_route) && !$enable_dynamic_system_info) {
        $final_payload = github_chat_widget_merge_route_into_payload($final_payload, $matched_route, $default_button_label);
    }

    if ($enable_ui_buttons) {
        $final_payload = github_chat_widget_promote_text_link_to_button($final_payload, $default_button_label);
    }

    $history_to_store = github_chat_widget_normalize_chat_messages_for_storage($normalized_messages);
    $history_to_store[] = github_chat_widget_parse_assistant_payload_for_storage($final_payload);
    github_chat_widget_save_chat_history($email, $history_to_store);

    return new WP_REST_Response(array('output' => $final_payload), 200);
}
