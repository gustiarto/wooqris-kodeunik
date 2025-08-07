<?php
/**
 * QRIS Kode Unik Bootstrap
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('QRIS_PLUGIN_VERSION', '1.5.0');
define('QRIS_PLUGIN_FILE', __FILE__);
define('QRIS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QRIS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QRIS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'QRIS_';
    $base_dir = QRIS_PLUGIN_PATH . 'includes/';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
function qris_kode_unik_initialize() {
    // Check if WooCommerce is active
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo esc_html__('QRIS Kode Unik membutuhkan WooCommerce untuk berfungsi. Silakan install dan aktifkan WooCommerce terlebih dahulu.', 'qris-kode-unik');
            echo '</p></div>';
        });
        return;
    }

    // Load translations
    load_plugin_textdomain('qris-kode-unik', false, dirname(QRIS_PLUGIN_BASENAME) . '/languages');

    // Initialize components
    QRIS_Logger::init();
    QRIS_Gateway::init();
    QRIS_Admin::init();
    QRIS_API::init();
}
add_action('plugins_loaded', 'qris_kode_unik_initialize');

// Activation hook
register_activation_hook(QRIS_PLUGIN_FILE, array('QRIS_Activator', 'activate'));

// Deactivation hook
register_deactivation_hook(QRIS_PLUGIN_FILE, array('QRIS_Deactivator', 'deactivate'));
