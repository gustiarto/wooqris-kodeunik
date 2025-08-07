<?php
if (!defined('ABSPATH')) {
    exit;
}

// Plugin version
define('QRIS_VERSION', '1.5.0');

// Plugin paths
define('QRIS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QRIS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Database
define('QRIS_DB_VERSION', '1.0');
define('QRIS_TABLE_NAME', 'qris_pending_payments');

// API endpoints
define('QRIS_API_NAMESPACE', 'qris-webhook/v1');
define('QRIS_NOTIFY_ENDPOINT', '/notify');
define('QRIS_HEARTBEAT_ENDPOINT', '/heartbeat');

// Timeouts and limits
define('QRIS_DEFAULT_TIMEOUT', 300); // 5 minutes
define('QRIS_MAX_ATTEMPTS', 3);
define('QRIS_RATE_LIMIT', 60); // requests per minute
define('QRIS_MIN_AMOUNT', 1000); // Rp. 1.000
define('QRIS_MAX_AMOUNT', 10000000); // Rp. 10.000.000

// Debug
define('QRIS_DEBUG', false);
define('QRIS_LOG_FILE', WP_CONTENT_DIR . '/qris-debug.log');
