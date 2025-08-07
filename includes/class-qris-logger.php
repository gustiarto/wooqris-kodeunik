<?php
if (!defined('ABSPATH')) {
    exit;
}

class QRIS_Logger {
    private static $log_enabled = false;
    private static $log_file = '';

    /**
     * Inisialisasi logger
     */
    public static function init() {
        $settings = get_option('woocommerce_qris_kode_unik_settings', array());
        self::$log_enabled = isset($settings['debug_mode']) && $settings['debug_mode'] === 'yes';
        
        if (self::$log_enabled) {
            $upload_dir = wp_upload_dir();
            self::$log_file = $upload_dir['basedir'] . '/qris-debug.log';
        }
    }

    /**
     * Log pesan debug
     */
    public static function debug($message, $context = array()) {
        if (!self::$log_enabled) {
            return;
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $formatted_context = empty($context) ? '' : ' ' . json_encode($context);
        $log_message = "[{$timestamp}] DEBUG: {$message}{$formatted_context}\n";

        error_log($log_message, 3, self::$log_file);
    }

    /**
     * Log pesan error
     */
    public static function error($message, $context = array()) {
        if (!self::$log_enabled) {
            return;
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $formatted_context = empty($context) ? '' : ' ' . json_encode($context);
        $log_message = "[{$timestamp}] ERROR: {$message}{$formatted_context}\n";

        error_log($log_message, 3, self::$log_file);
    }

    /**
     * Bersihkan log file jika terlalu besar
     */
    public static function cleanup_logs() {
        if (!self::$log_enabled || !file_exists(self::$log_file)) {
            return;
        }

        $max_size = 5 * 1024 * 1024; // 5MB
        $file_size = filesize(self::$log_file);

        if ($file_size > $max_size) {
            $contents = file_get_contents(self::$log_file);
            $contents = substr($contents, $file_size - $max_size);
            file_put_contents(self::$log_file, $contents);
        }
    }
}
