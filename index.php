<?php
/**
 * Plugin Name: Site AI Chat Widget
 * Plugin URI: https://shashinthalk.cc
 * Description: Floating AI chat widget with persistent email sessions, grounded website answers, and secure WordPress REST endpoints.
 * Version: 1.3.8
 * Author: Nishan Shashintha
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: git-ai-chat-widget
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GITHUB_CHAT_WIDGET_VERSION', '1.3.8');
define('GITHUB_CHAT_WIDGET_DB_VERSION', '1.0.0');
define('GITHUB_CHAT_WIDGET_SLUG', 'github-chat-widget');
define('GITHUB_CHAT_WIDGET_URL', plugin_dir_url(__FILE__));
define('GITHUB_CHAT_WIDGET_PATH', plugin_dir_path(__FILE__));

require_once GITHUB_CHAT_WIDGET_PATH . 'includes/helpers.php';
require_once GITHUB_CHAT_WIDGET_PATH . 'includes/settings.php';
require_once GITHUB_CHAT_WIDGET_PATH . 'includes/frontend.php';
require_once GITHUB_CHAT_WIDGET_PATH . 'includes/rest.php';
require_once GITHUB_CHAT_WIDGET_PATH . 'includes/admin.php';

register_activation_hook(__FILE__, 'github_chat_widget_activate');
