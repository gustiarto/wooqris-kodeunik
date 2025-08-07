<?php
/**
 * Plugin Name:       WooCommerce QRIS Kode Unik
 * Plugin URI:        https://example.com/
 * Description:       Metode pembayaran QRIS dengan validasi kode unik, webhook, dan monitor heartbeat.
 * Version:           1.5.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       qris-kode-unik
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 *
 * @package QRIS_Kode_Unik
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
require_once plugin_dir_path(__FILE__) . 'includes/bootstrap.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-qris-admin-menu.php';

// Initialize admin menu
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        QRIS_Admin_Menu::init();
    }
});

/**
 * Deklarasi kompatibilitas HPOS secara resmi.
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Fungsi utama yang akan dijalankan setelah semua plugin dimuat.
 */
function qris_kode_unik_init() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    // Load required files
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-qris-validator.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-qris-logger.php';
    require_once plugin_dir_path( __FILE__ ) . 'class-wc-gateway-qris-unik.php';
    require_once plugin_dir_path( __FILE__ ) . 'class-qris-admin-setup-wizard.php';

    // Initialize logger
    QRIS_Logger::init();
    
    add_filter( 'woocommerce_payment_gateways', 'add_qris_kode_unik_gateway' );
    
    // Inisialisasi kelas wizard
    new QRIS_Admin_Setup_Wizard();
}
add_action( 'plugins_loaded', 'qris_kode_unik_init' );

/**
 * Menambahkan kelas gateway ke daftar gateway WooCommerce.
 */
function add_qris_kode_unik_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_QRIS_Unik';
    return $gateways;
}

/**
 * Membuat tabel, menjadwalkan cron, dan mengatur redirect ke wizard saat aktivasi.
 */
function qris_kode_unik_activate() {
    // Set transient untuk redirect ke setup wizard
    set_transient( '_qris_setup_wizard_redirect', true, 30 );

    global $wpdb;
    $table_name = $wpdb->prefix . 'qris_pending_payments';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Gunakan BIGINT untuk ID untuk menghindari overflow
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        unique_amount decimal(15, 2) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_amount (unique_amount)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    // Tambahkan pengecekan error
    $result = dbDelta( $sql );
    if ( is_wp_error( $result ) ) {
        error_log( 'QRIS Kode Unik - Error creating table: ' . $result->get_error_message() );
        return;
    }
    
    // Simpan versi database
    $installed_ver = get_option( "qris_db_version" );
    if ( $installed_ver != '1.0' ) {
        update_option( "qris_db_version", '1.0' );
    }

    // Tambahkan interval kustom
    add_filter( 'cron_schedules', function( $schedules ) {
        $schedules['minutely'] = array(
            'interval' => 60,
            'display'  => __( 'Every Minute', 'qris-kode-unik' )
        );
        $schedules['fifteen_minutes'] = array(
            'interval' => 900,
            'display'  => __( 'Every 15 Minutes', 'qris-kode-unik' )
        );
        return $schedules;
    });

    if ( ! wp_next_scheduled( 'qris_check_expired_payments_hook' ) ) {
        wp_schedule_event( time(), 'minutely', 'qris_check_expired_payments_hook' );
    }
    if ( ! wp_next_scheduled( 'qris_check_heartbeat_status_hook' ) ) {
        wp_schedule_event( time(), 'fifteen_minutes', 'qris_check_heartbeat_status_hook' );
    }
}
register_activation_hook( __FILE__, 'qris_kode_unik_activate' );

/**
 * Hapus semua cron job saat plugin dinonaktifkan.
 */
function qris_kode_unik_deactivate() {
    wp_clear_scheduled_hook( 'qris_check_expired_payments_hook' );
    wp_clear_scheduled_hook( 'qris_check_heartbeat_status_hook' );
}
register_deactivation_hook( __FILE__, 'qris_kode_unik_deactivate' );


/**
 * Redirect ke halaman setup wizard setelah aktivasi.
 */
function qris_setup_wizard_redirect() {
    if ( get_transient( '_qris_setup_wizard_redirect' ) ) {
        delete_transient( '_qris_setup_wizard_redirect' );
        // Pastikan tidak redirect saat melakukan bulk activate
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
            return;
        }
        // Redirect ke halaman wizard
        wp_safe_redirect( admin_url( 'index.php?page=qris-setup' ) );
        exit;
    }
}
add_action( 'admin_init', 'qris_setup_wizard_redirect' );


/**
 * Menampilkan banner notifikasi untuk rating.
 */
function qris_rate_plugin_notice() {
    // Hanya tampilkan jika user bisa manage woocommerce dan belum dismiss
    if ( ! current_user_can( 'manage_woocommerce' ) || get_user_meta( get_current_user_id(), '_qris_rate_notice_dismissed' ) ) {
        return;
    }
    // Hanya tampilkan jika qris string sudah diisi (plugin sudah dikonfigurasi)
    $settings = get_option('woocommerce_qris_kode_unik_settings', array());
    if ( empty($settings['qris_string']) ) {
        return;
    }
    ?>
    <div class="notice notice-info is-dismissible" id="qris-rate-notice">
        <p>
            <?php printf(
                /* translators: %s: Plugin Name. */
                esc_html__( 'Terima kasih telah menggunakan %s! Jika Anda merasa plugin ini bermanfaat, mohon pertimbangkan untuk memberikan rating ★★★★★ untuk mendukung pengembangan kami.', 'qris-kode-unik' ),
                '<strong>QRIS Kode Unik for WooCommerce</strong>'
            ); ?>
            <a href="https://wordpress.org/support/plugin/qris-kode-unik/reviews/?filter=5" class="button-primary" target="_blank" style="margin-left: 10px;"><?php esc_html_e( 'Beri Rating Sekarang', 'qris-kode-unik' ); ?></a>
        </p>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('#qris-rate-notice')?.addEventListener('click', function(event) {
                if (event.target.classList.contains('notice-dismiss')) {
                    const formData = new FormData();
                    formData.append('action', 'qris_dismiss_rate_notice');
                    formData.append('nonce', '<?php echo wp_create_nonce("qris_dismiss_rate_notice_nonce"); ?>');
                    fetch('<?php echo admin_url("admin-ajax.php"); ?>', { method: 'POST', body: formData });
                }
            });
        });
    </script>
    <?php
}
add_action( 'admin_notices', 'qris_rate_plugin_notice' );

/**
 * AJAX handler untuk dismiss notifikasi rating.
 */
function qris_dismiss_rate_notice() {
    check_ajax_referer( 'qris_dismiss_rate_notice_nonce', 'nonce' );
    update_user_meta( get_current_user_id(), '_qris_rate_notice_dismissed', true );
    wp_die();
}
add_action( 'wp_ajax_qris_dismiss_rate_notice', 'qris_dismiss_rate_notice' );

/**
 * Fungsi cron job untuk membersihkan pembayaran kedaluwarsa.
 */
function qris_check_expired_payments() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'qris_pending_payments';
    $settings = get_option( 'woocommerce_qris_kode_unik_settings', array() );
    $timeout_minutes = isset( $settings['onhold_timeout'] ) ? intval( $settings['onhold_timeout'] ) : 5;
    $timeout_seconds = $timeout_minutes * 60;

    $expired_payments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE created_at < %s", date( 'Y-m-d H:i:s', time() - $timeout_seconds ) ) );

    foreach ( $expired_payments as $payment ) {
        $order = wc_get_order( $payment->order_id );
        if ( $order && $order->has_status( 'on-hold' ) ) {
            $order->update_status( 'cancelled', __( 'Pembayaran tidak diterima dalam waktu yang ditentukan.', 'qris-kode-unik' ) );
        }
        $wpdb->delete( $table_name, array( 'id' => $payment->id ) );
    }
}
add_action( 'qris_check_expired_payments_hook', 'qris_check_expired_payments' );

/**
 * Fungsi cron job untuk memeriksa status heartbeat.
 */
function qris_check_heartbeat_status() {
    $last_heartbeat = get_option( 'qris_last_heartbeat_timestamp', 0 );
    $email_sent = get_option( 'qris_heartbeat_email_sent', false );

    // Jika tidak ada heartbeat selama lebih dari 20 menit dan email belum dikirim
    if ( ( time() - $last_heartbeat > 1200 ) && ! $email_sent ) {
        $admin_email = get_option( 'admin_email' );
        $subject = '[' . get_bloginfo( 'name' ) . '] Peringatan: Koneksi Heartbeat Terputus';
        $message = 'Sistem tidak menerima sinyal heartbeat dari aplikasi Android Anda selama lebih dari 20 menit. Harap periksa koneksi dan aplikasi di perangkat Anda.';
        wp_mail( $admin_email, $subject, $message );
        update_option( 'qris_heartbeat_email_sent', true );
    }
}
add_action( 'qris_check_heartbeat_status_hook', 'qris_check_heartbeat_status' );

/**
 * Membuat endpoint REST API.
 */
add_action( 'rest_api_init', function () {
    // Endpoint untuk notifikasi pembayaran
    register_rest_route( 'qris-webhook/v1', '/notify', array(
        'methods' => 'POST',
        'callback' => 'handle_qris_webhook',
        'permission_callback' => 'qris_webhook_permission_check',
    ) );
    // Endpoint untuk heartbeat
    register_rest_route( 'qris-webhook/v1', '/heartbeat', array(
        'methods' => 'POST',
        'callback' => 'handle_qris_heartbeat',
        'permission_callback' => 'qris_webhook_permission_check',
    ) );
} );

/**
 * Memeriksa izin untuk semua webhook di namespace ini.
 */
function qris_webhook_permission_check( $request ) {
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'qris_rate_limit_' . md5($ip);
    $rate_count = get_transient($transient_key);
    
    if (false !== $rate_count) {
        if ($rate_count > 60) { // Maksimal 60 request per menit
            return new WP_Error(
                'rest_forbidden',
                __('Terlalu banyak request. Silakan coba lagi nanti.', 'qris-kode-unik'),
                array('status' => 429)
            );
        }
        set_transient($transient_key, $rate_count + 1, 60);
    } else {
        set_transient($transient_key, 1, 60);
    }

    $settings = get_option( 'woocommerce_qris_kode_unik_settings', array() );
    $api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : '';

    if ( empty( $api_key ) ) {
        return new WP_Error( 'rest_forbidden', __( 'API Key belum diatur.', 'qris-kode-unik' ), array( 'status' => 403 ) );
    }
    $sent_key = $request->get_header( 'x-api-key' );
    if ( ! $sent_key ) {
        return new WP_Error( 'rest_forbidden', __( 'Header X-API-KEY tidak ditemukan.', 'qris-kode-unik' ), array( 'status' => 401 ) );
    }
    if ( ! hash_equals( $api_key, $sent_key ) ) {
        return new WP_Error( 'rest_forbidden', __( 'API Key tidak valid.', 'qris-kode-unik' ), array( 'status' => 403 ) );
    }
    return true;
}

/**
 * Menangani logika saat webhook pembayaran dipanggil.
 */
function handle_qris_webhook( $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'qris_pending_payments';
    $params = $request->get_json_params();

    if ( ! isset( $params['amount'] ) || ! is_numeric( $params['amount'] ) ) {
        return new WP_Error( 'bad_request', 'Parameter "amount" tidak valid.', array( 'status' => 400 ) );
    }

    $paid_amount = floatval( $params['amount'] );
    $payment_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE unique_amount = %f", $paid_amount ) );

    if ( $payment_data ) {
        $order = wc_get_order( $payment_data->order_id );
        if ( $order && $order->has_status( 'on-hold' ) ) {
            $order->payment_complete();
            $order->add_order_note( __( 'Pembayaran QRIS berhasil diverifikasi via webhook.', 'qris-kode-unik' ) );
            $wpdb->delete( $table_name, array( 'id' => $payment_data->id ) );
            return new WP_REST_Response( array( 'status' => 'success', 'message' => 'Order ' . $order->get_id() . ' berhasil diproses.' ), 200 );
        }
    }
    return new WP_Error( 'not_found', 'Pembayaran dengan nominal tersebut tidak ditemukan.', array( 'status' => 404 ) );
}

/**
 * Menangani logika saat webhook heartbeat dipanggil.
 */
function handle_qris_heartbeat( $request ) {
    $params = $request->get_json_params();
    $logs = get_option( 'qris_heartbeat_logs', array() );

    $new_log = array(
        'timestamp' => current_time('mysql'),
        'device'    => sanitize_text_field( $params['device'] ?? 'N/A' ),
        'battery'   => sanitize_text_field( $params['battery'] ?? 'N/A' ),
        'charging'  => sanitize_text_field( $params['charging'] ?? 'N/A' ),
        'wifi_ssid' => sanitize_text_field( $params['wifi_ssid'] ?? 'N/A' ),
        'wifi_rssi' => sanitize_text_field( $params['wifi_rssi'] ?? 'N/A' ),
    );

    $last_log_comparable = isset($logs[0]) ? $logs[0] : null;
    if ($last_log_comparable) unset($last_log_comparable['timestamp']);
    $new_log_comparable = $new_log;
    unset($new_log_comparable['timestamp']);

    if ( ! empty($logs) && json_encode($last_log_comparable) === json_encode($new_log_comparable) ) {
        $logs[0]['timestamp'] = $new_log['timestamp'];
    } else {
        array_unshift( $logs, $new_log );
    }

    $logs = array_slice( $logs, 0, 10 );

    update_option( 'qris_heartbeat_logs', $logs );
    update_option( 'qris_last_heartbeat_timestamp', time() );
    update_option( 'qris_heartbeat_email_sent', false );

    return new WP_REST_Response( array( 'status' => 'received' ), 200 );
}

/**
 * Endpoint AJAX untuk memeriksa status pembayaran dari sisi pelanggan.
 */
function qris_ajax_check_payment_status() {
    check_ajax_referer( 'qris-payment-status-nonce', 'nonce' );
    $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
    if ( ! $order_id ) wp_send_json_error();
    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error();
    wp_send_json_success( array( 'status' => $order->get_status() ) );
}
add_action( 'wp_ajax_qris_check_payment_status', 'qris_ajax_check_payment_status' );
add_action( 'wp_ajax_nopriv_qris_check_payment_status', 'qris_ajax_check_payment_status' );

/**
 * Menambahkan widget ke dashboard WordPress.
 */
function qris_add_dashboard_widgets() {
    wp_add_dashboard_widget(
        'qris_heartbeat_dashboard_widget',
        'Status Heartbeat QRIS',
        'qris_heartbeat_dashboard_widget_display'
    );
}
add_action( 'wp_dashboard_setup', 'qris_add_dashboard_widgets' );

/**
 * Menampilkan konten widget dashboard.
 */
function qris_heartbeat_dashboard_widget_display() {
    $logs = get_option('qris_heartbeat_logs', array());
    if (empty($logs)) {
        echo '<p>Belum ada sinyal heartbeat yang diterima.</p>';
        return;
    }
    $latest = $logs[0];
    $last_time = strtotime($latest['timestamp']);
    $time_diff = human_time_diff($last_time, current_time('timestamp')) . ' yang lalu';

    echo '<strong>Sinyal Terakhir Diterima:</strong> ' . esc_html($time_diff) . ' (' . esc_html($latest['timestamp']) . ')<br>';
    echo '<strong>Perangkat:</strong> ' . esc_html($latest['device']) . '<br>';
    echo '<strong>Baterai:</strong> ' . esc_html($latest['battery']) . ' (' . esc_html($latest['charging']) . ')<br>';
    echo '<strong>WiFi:</strong> ' . esc_html($latest['wifi_ssid']) . ' (' . esc_html($latest['wifi_rssi']) . ')<br>';
}
