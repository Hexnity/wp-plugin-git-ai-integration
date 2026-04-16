<?php
/**
 * Plugin Name: Github Chat Widget
 * Plugin URI: https://shashinthalk.cc
 * Description: Floating AI chat widget with UI action buttons and a WordPress REST endpoint.
 * Version: 1.3.2
 * Author: Nishan Shashintha
 * License: GPL2+
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GITHUB_CHAT_WIDGET_VERSION', '1.3.2');
define('GITHUB_CHAT_WIDGET_DB_VERSION', '1.0.0');
define('GITHUB_CHAT_WIDGET_SLUG', 'github-chat-widget');
define('GITHUB_CHAT_WIDGET_URL', plugin_dir_url(__FILE__));
define('GITHUB_CHAT_WIDGET_PATH', plugin_dir_path(__FILE__));
define('GITHUB_CHAT_WIDGET_UPDATE_SLUG', 'wp-plugin-git-ai-integration');
define('GITHUB_CHAT_WIDGET_REPOSITORY_URL', 'https://github.com/Hexnity/wp-plugin-git-ai-integration');

function github_chat_widget_init_update_checker()
{
    static $update_checker = null;

    if ($update_checker !== null) {
        return;
    }

    $puc_bootstrap = GITHUB_CHAT_WIDGET_PATH . 'libraries/plugin-update-checker/plugin-update-checker.php';

    if (!file_exists($puc_bootstrap)) {
        return;
    }

    require_once $puc_bootstrap;

    if (!class_exists('\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
        return;
    }

    $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        GITHUB_CHAT_WIDGET_REPOSITORY_URL,
        __FILE__,
        GITHUB_CHAT_WIDGET_UPDATE_SLUG
    );

    $update_checker->setBranch('main');
    $update_checker->setCheckPeriod(12);
}

require_once GITHUB_CHAT_WIDGET_PATH . 'includes/helpers.php';
require_once GITHUB_CHAT_WIDGET_PATH . 'includes/settings.php';
require_once GITHUB_CHAT_WIDGET_PATH . 'includes/frontend.php';
require_once GITHUB_CHAT_WIDGET_PATH . 'includes/rest.php';
require_once GITHUB_CHAT_WIDGET_PATH . 'includes/admin.php';

add_action('plugins_loaded', 'github_chat_widget_init_update_checker');
register_activation_hook(__FILE__, 'github_chat_widget_activate');
