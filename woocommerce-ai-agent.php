<?php
/**
 * Plugin Name: WooCommerce AI Agent
 * Description: A local demo assistant for WooCommerce product search, order tracking, store policies, and authorized store reports. No AI API key required.
 * Version: 0.1.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: miamen16
 * Text Domain: wc-ai-agent
 */
if (!defined('ABSPATH')) { exit; }

define('WCAIA_VERSION', '0.1.0');
define('WCAIA_FILE', __FILE__);
define('WCAIA_PATH', plugin_dir_path(__FILE__));
define('WCAIA_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            if (current_user_can('activate_plugins')) {
                echo '<div class="notice notice-warning"><p>' . esc_html__('WooCommerce AI Agent requires WooCommerce to be installed and active.', 'wc-ai-agent') . '</p></div>';
            }
        });
        return;
    }
    require_once WCAIA_PATH . 'includes/class-wcaia-demo.php';
    require_once WCAIA_PATH . 'includes/class-wcaia-rest.php';
    WCAIA_Demo::init();
    WCAIA_REST::init();
});
