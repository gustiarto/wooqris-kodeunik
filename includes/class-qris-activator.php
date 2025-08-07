<?php
/**
 * Class untuk menangani aktivasi plugin
 */
class QRIS_Activator {
    /**
     * Aktivasi plugin
     */
    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Buat tabel pembayaran
        $table_name = $wpdb->prefix . 'qris_pending_payments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            unique_amount decimal(15, 2) NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int NOT NULL DEFAULT 0,
            last_check timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_amount (unique_amount),
            KEY order_id (order_id),
            KEY status_created (status, created_at)
        ) $charset_collate;";

        dbDelta($sql);

        // Set versi database
        add_option('qris_db_version', QRIS_PLUGIN_VERSION);

        // Buat schedule events
        if (!wp_next_scheduled('qris_check_expired_payments')) {
            wp_schedule_event(time(), 'every_minute', 'qris_check_expired_payments');
        }

        if (!wp_next_scheduled('qris_check_heartbeat')) {
            wp_schedule_event(time(), 'fifteen_minutes', 'qris_check_heartbeat');
        }

        // Tambahkan interval kustom
        add_filter('cron_schedules', function($schedules) {
            $schedules['every_minute'] = array(
                'interval' => 60,
                'display' => __('Every Minute', 'qris-kode-unik')
            );
            $schedules['fifteen_minutes'] = array(
                'interval' => 900,
                'display' => __('Every 15 Minutes', 'qris-kode-unik')
            );
            return $schedules;
        });

        // Buat direktori upload jika belum ada
        $upload_dir = wp_upload_dir();
        $qris_upload_dir = $upload_dir['basedir'] . '/qris-logs';
        if (!file_exists($qris_upload_dir)) {
            wp_mkdir_p($qris_upload_dir);
        }

        // Redirect ke wizard setup
        set_transient('_qris_activation_redirect', true, 30);
    }
}
