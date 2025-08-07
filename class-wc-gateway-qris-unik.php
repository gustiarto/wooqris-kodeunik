<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Keluar jika diakses langsung
}

/**
 * WC_Gateway_QRIS_Unik Class.
 */
class WC_Gateway_QRIS_Unik extends WC_Payment_Gateway {

    /**
     * Constructor untuk gateway.
     */
    public function __construct() {
        $this->id                 = 'qris_kode_unik';
        $this->icon               = apply_filters(
            'woocommerce_qris_kode_unik_icon',
            plugins_url('assets/images/qris-logo.png', __FILE__)
        );
        $this->has_fields         = false;
        $this->method_title       = __( 'QRIS (Kode Unik)', 'qris-kode-unik' );
        $this->method_description = __( 'Menerima pembayaran melalui QRIS dengan validasi nominal unik, webhook, dan monitor heartbeat.', 'qris-kode-unik' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->instructions = $this->get_option( 'instructions' );
        $this->qris_string  = $this->get_option( 'qris_string' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'thankyou_page' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'wp_ajax_qris_regenerate_api_key', array( $this, 'ajax_regenerate_api_key' ) );
        add_action( 'admin_init', array( $this, 'handle_macro_download' ) );
    }

    /**
     * Muat skrip khusus untuk halaman admin.
     * CATATAN: Pastikan Anda sudah membuat folder /assets/js/ di dalam plugin
     * dan menempatkan file jsQR.min.js di dalamnya.
     */
    public function admin_scripts($hook) {
        if ($hook !== 'woocommerce_page_wc-settings' || !isset($_GET['section']) || $_GET['section'] !== $this->id) {
            return;
        }

        // Muat skrip dari file lokal, bukan CDN.
        wp_enqueue_script(
            'jsqr',
            plugin_dir_url(__FILE__) . 'assets/js/jsQR.min.js',
            array(),
            '1.4.0',
            true
        );

        // Kirim data dan string terjemahan ke JavaScript
        $localized_data = array(
            'ajax_url'          => admin_url('admin-ajax.php'),
            'nonce'             => wp_create_nonce('qris_regenerate_api_key'),
            'scan_success'      => __('Konten QRIS berhasil di-scan.', 'qris-kode-unik'),
            'scan_fail'         => __('Tidak dapat menemukan QR Code pada gambar.', 'qris-kode-unik'),
            'copied'            => __('Tersalin!', 'qris-kode-unik'),
            'copy_fail'         => __('Gagal menyalin.', 'qris-kode-unik'),
            'regenerate_confirm' => __('Yakin ingin membuat API Key baru? Kunci yang lama akan segera tidak valid.', 'qris-kode-unik'),
            'regenerate_success' => __('API Key baru telah dibuat.', 'qris-kode-unik'),
            'regenerate_fail'   => __('Gagal membuat API Key baru:', 'qris-kode-unik'),
        );
        wp_localize_script('jsqr', 'qris_admin_params', $localized_data);

        $script = "
            document.addEventListener('DOMContentLoaded', function() {
                const params = window.qris_admin_params;
                const fileInput = document.getElementById('qris_scanner_upload');
                const textArea = document.getElementById('woocommerce_qris_kode_unik_qris_string');
                if (fileInput && textArea) {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d', { willReadFrequently: true });
                    fileInput.addEventListener('change', function(e) {
                        const file = e.target.files[0]; if (!file) return;
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const img = new Image();
                            img.onload = function() {
                                canvas.width = img.width; canvas.height = img.height;
                                ctx.drawImage(img, 0, 0, img.width, img.height);
                                const imageData = ctx.getImageData(0, 0, img.width, img.height);
                                const code = jsQR(imageData.data, imageData.width, imageData.height);
                                if (code) { textArea.value = code.data; alert(params.scan_success); }
                                else { alert(params.scan_fail); }
                            };
                            img.src = event.target.result;
                        };
                        reader.readAsDataURL(file);
                    });
                }
                function copyToClipboard(elementId) { 
                    navigator.clipboard.writeText(document.getElementById(elementId).value).then(() => alert(params.copied), () => alert(params.copy_fail)); 
                }
                document.getElementById('qris_copy_webhook_url')?.addEventListener('click', () => copyToClipboard('qris_webhook_url_input'));
                document.getElementById('qris_copy_api_key')?.addEventListener('click', () => copyToClipboard('qris_api_key_input'));
                document.getElementById('qris_regenerate_api_key')?.addEventListener('click', function() {
                    if (!confirm(params.regenerate_confirm)) return;
                    const nonce = this.dataset.nonce;
                    const apiKeyInput = document.getElementById('qris_api_key_input');
                    const formData = new FormData();
                    formData.append('action', 'qris_regenerate_api_key');
                    formData.append('nonce', nonce);
                    fetch(params.ajax_url, { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) { apiKeyInput.value = data.data.new_key; alert(params.regenerate_success); }
                            else { alert(params.regenerate_fail + ' ' + data.data.message); }
                        });
                });
            });
        ";
        wp_add_inline_script('jsqr', $script);
    }

    /**
     * Inisialisasi form setting di admin.
     */
    public function init_form_fields() {
        if ( empty( $this->get_option( 'api_key' ) ) ) {
            $this->settings['api_key'] = 'qris_sk_' . wp_generate_password( 32, false );
        }
        $webhook_url = get_home_url(null, 'wp-json/qris-webhook/v1/notify');
        $api_key = $this->get_option('api_key');

        $this->form_fields = array(
            'enabled' => array('title' => __( 'Aktifkan/Nonaktifkan', 'qris-kode-unik' ), 'type' => 'checkbox', 'label' => __( 'Aktifkan QRIS Kode Unik', 'qris-kode-unik' ), 'default' => 'no'),
            'title' => array('title' => __( 'Judul', 'qris-kode-unik' ), 'type' => 'text', 'default' => __( 'QRIS (Scan Kode QR)', 'qris-kode-unik' )),
            'description' => array('title' => __( 'Deskripsi', 'qris-kode-unik' ), 'type' => 'textarea', 'default' => __( 'Bayar dengan QRIS. Nominal akan ditambahkan kode unik.', 'qris-kode-unik' )),
            'instructions' => array('title' => __( 'Instruksi', 'qris-kode-unik' ), 'type' => 'textarea', 'default' => __( 'Silakan selesaikan pembayaran Anda.', 'qris-kode-unik' )),
            'qris_details' => array('title' => __( 'Pengaturan QRIS', 'qris-kode-unik' ), 'type' => 'title', 'description' => __( '<strong>Upload Gambar QRIS:</strong><br><input type="file" id="qris_scanner_upload" accept="image/*" />', 'qris-kode-unik' )),
            'qris_string' => array('title' => __( 'Konten QRIS Statis', 'qris-kode-unik' ), 'type' => 'textarea', 'default' => '', 'css' => 'width:100%; height: 150px;'),
            'parsed_qris_info' => array('title' => __('Info Merchant (dari QRIS)'), 'type' => 'title', 'description' => $this->get_parsed_qris_info_html()),
            'webhook_settings' => array('title' => __('Pengaturan Webhook & Keamanan', 'qris-kode-unik'), 'type' => 'title'),
            'webhook_url_display' => array('title' => __('URL Webhook', 'qris-kode-unik'), 'type' => 'title', 'description' => '<input type="text" id="qris_webhook_url_input" value="' . esc_attr($webhook_url) . '" readonly style="width: 100%; max-width: 500px;"> <button type="button" class="button" id="qris_copy_webhook_url">Salin</button>'),
            'api_key_display' => array('title' => __('API Key', 'qris-kode-unik'), 'type' => 'title', 'description' => '<input type="text" id="qris_api_key_input" value="' . esc_attr($api_key) . '" readonly style="width: 100%; max-width: 500px;"> <button type="button" class="button" id="qris_copy_api_key">Salin</button> <button type="button" class="button-secondary" id="qris_regenerate_api_key" data-nonce="' . wp_create_nonce('qris-regenerate-api-key-nonce') . '">Buat Ulang</button>'),
            'api_key' => array('title' => __('API Key (Hidden)'), 'type' => 'hidden', 'default' => $api_key),
            'automation_instructions' => array(
                'title' => __('Petunjuk Otomasi Android & Heartbeat', 'qris-kode-unik'),
                'type' => 'title',
                'description' => $this->get_automation_instructions_html(),
            ),
            'heartbeat_logs' => array(
                'title' => __('10 Log Heartbeat Terakhir', 'qris-kode-unik'),
                'type' => 'title',
                'description' => $this->get_heartbeat_logs_html(),
            ),
            'display_settings' => array('title' => __('Pengaturan Tampilan Halaman Pembayaran', 'qris-kode-unik'), 'type' => 'title'),
            'payment_page_template' => array('title' => __('Template Halaman Pembayaran', 'qris-kode-unik'), 'type' => 'textarea', 'description' => __( 'Gunakan placeholder: {instructions}, {total_price}, {qrcode_container}, {countdown_timer}.', 'qris-kode-unik' ), 'css' => 'width:100%; height: 250px;', 'default' => '<div style="border: 2px dashed #ddd; padding: 20px; text-align: center; margin: 20px 0;"><h2>Detail Pembayaran QRIS</h2><p>{instructions}</p><h4>Total Pembayaran</h4><h3 style="font-size: 2em; color: #d63638; margin: 0;">{total_price}</h3><p><strong>Harap transfer sesuai nominal di atas.</strong></p><div id="qris-code-container" style="margin: 20px auto; max-width: 250px;">{qrcode_container}</div><h4>Waktu Tersisa</h4><div id="qris-countdown" style="font-size: 1.5em; font-weight: bold;">{countdown_timer}</div></div>'),
            'advanced_settings' => array('title' => __('Pengaturan Lanjutan (Timeout & AJAX)', 'qris-kode-unik'), 'type' => 'title'),
            'onhold_timeout' => array('title' => __('Waktu Timeout (Menit)'), 'type' => 'number', 'default' => '5'),
            'check_delay' => array('title' => __('Delay Pengecekan (Detik)'), 'type' => 'number', 'default' => '10'),
            'check_interval' => array('title' => __('Interval Pengecekan (Detik)'), 'type' => 'number', 'default' => '5'),
            'success_action' => array('title' => __('Aksi Jika Pembayaran Berhasil'), 'type' => 'select', 'options' => array('message' => __('Tampilkan Pesan'), 'redirect' => __('Redirect ke URL')), 'default' => 'message'),
            'success_message' => array('title' => __('Pesan Sukses'), 'type' => 'textarea', 'default' => __('Pembayaran Anda telah berhasil!', 'qris-kode-unik')),
            'success_redirect_url' => array('title' => __('URL Redirect Sukses'), 'type' => 'text', 'default' => ''),
            'timeout_action' => array('title' => __('Aksi Jika Waktu Habis'), 'type' => 'select', 'options' => array('message' => __('Tampilkan Pesan'), 'redirect' => __('Redirect ke URL')), 'default' => 'message'),
            'timeout_message' => array('title' => __('Pesan Waktu Habis'), 'type' => 'textarea', 'default' => __('Waktu pembayaran telah habis.', 'qris-kode-unik')),
            'timeout_redirect_url' => array('title' => __('URL Redirect Waktu Habis'), 'type' => 'text', 'default' => ''),
        );
    }
    
    /**
     * AJAX handler untuk membuat ulang API key.
     */
    public function ajax_regenerate_api_key() {
        check_ajax_referer( 'qris-regenerate-api-key-nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        $new_key = 'qris_sk_' . wp_generate_password( 32, false );
        $this->settings['api_key'] = $new_key;
        update_option( $this->get_option_key(), $this->settings );
        wp_send_json_success( array( 'new_key' => $new_key ) );
    }

    /**
     * Menangani unduhan file macro.
     */
    public function handle_macro_download() {
        if ( isset($_GET['action']) && $_GET['action'] == 'download_qris_macro' ) {
            if ( ! current_user_can('manage_woocommerce') ) {
                wp_die('Anda tidak memiliki izin untuk mengakses file ini.');
            }
            
            // Set header untuk download file
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="QRISNotify.macro"');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            $template_path = plugin_dir_path(__FILE__) . 'QRISNotify.macro';
            if ( ! file_exists($template_path) ) {
                wp_die('File template QRISNotify.macro tidak ditemukan di folder plugin.');
            }
            
            $template_content = file_get_contents($template_path);
            
            $webhook_url = get_home_url(null, 'wp-json/qris-webhook/v1/notify');
            $heartbeat_url = get_home_url(null, 'wp-json/qris-webhook/v1/heartbeat');
            $api_key = $this->get_option('api_key');
            $qris_string = $this->get_option('qris_string');

            $acquirer_id = '';
            if (!empty($qris_string)) {
                $qris_data = $this->parse_qris_string($qris_string);
                if (isset($qris_data['26'])) {
                    // $qris_data['26'] sekarang langsung berisi identifier merchant (misal: ID.CO.SPEEDCASH.MERCHANT)
                    $acquirer_id = $qris_data['26'];
                }
            }

            // Mapping QRIS merchant ID ke package name aplikasi
            $package_name_map = array(
                'ID.CO.SPEEDCASH.MERCHANT' => 'id.dana',
                'ID.CO.QRIS.WWW' => 'id.dana',
                'COM.GO-JEK.WWW' => 'com.gojek.app',
                'ID.CO.LINKAJA' => 'com.telkom.mwallet',
                'ID.CO.OVO' => 'id.ovo.app',
                'SHOPEEPAY' => 'com.shopee.id',
                // Tambahkan mapping lain sesuai kebutuhan
            );
            $acquirer_package_name = $package_name_map[$acquirer_name] ?? '';

            // Pastikan kita memiliki package name yang valid
            $acquirer_package_name = $package_name_map[$acquirer_id] ?? '';
            if (empty($acquirer_package_name)) {
                // Log untuk debugging
                error_log('QRIS Debug - Acquirer ID: ' . $acquirer_id);
                error_log('QRIS Debug - Available mappings: ' . print_r($package_name_map, true));
                
                // Gunakan default package name jika tidak ditemukan
                $acquirer_package_name = 'id.dana'; // Default ke DANA karena paling umum
            }

            $final_content = str_replace(
                array(
                    '%%WEBHOOK_URL%%', 
                    '%%API_KEY%%', 
                    '%%HEARTBEAT_URL%%', 
                    '%%ACQUIRER_PACKAGE_NAME%%'
                ),
                array(
                    $webhook_url, 
                    $api_key, 
                    $heartbeat_url, 
                    $acquirer_package_name
                ),
                $template_content
            );

            echo $final_content;
            exit;
        }
    }

    /**
     * Menghasilkan HTML untuk instruksi otomasi.
     */
    private function get_automation_instructions_html() {
        $download_link = add_query_arg('action', 'download_qris_macro', admin_url('admin.php'));
        return '
            <ol>
                <li>Install aplikasi <a href="https://play.google.com/store/apps/details?id=com.arlosoft.macrodroid" target="_blank">MacroDroid</a> di perangkat Android Anda.</li>
                <li>Pastikan aplikasi Acquirer (misal: m-banking) yang notifikasinya akan dibaca juga sudah terinstall.</li>
                <li>Unduh file macro yang sudah dikonfigurasi dengan mengklik tombol di bawah ini.</li>
                <li><a href="' . esc_url($download_link) . '" class="button button-primary">Unduh File QRISNotify.macro</a></li>
                <li>Buka MacroDroid, pilih menu "Templates" > ikon folder (pojok kanan atas) > "Import from local storage", lalu pilih file <strong>QRISNotify.macro</strong> yang baru saja Anda unduh.</li>
                <li>Aktifkan macro tersebut dan berikan semua izin yang diminta oleh MacroDroid (terutama akses Notifikasi dan Baterai).</li>
            </ol>
            <p>Macro ini akan secara otomatis membaca notifikasi pembayaran yang masuk, mengambil nominalnya, dan mengirimkannya ke server Anda. Ia juga akan mengirim sinyal heartbeat setiap 5 menit untuk memastikan koneksi tetap terjaga.</p>
        ';
    }

    /**
     * Menghasilkan HTML untuk tabel log heartbeat.
     */
    private function get_heartbeat_logs_html() {
        $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $this->id);
        $logs = get_option('qris_heartbeat_logs', array());
        
        // Tambahkan tombol ke settings page
        $html = '<div style="margin-bottom: 15px;">';
        $html .= '<a href="' . esc_url($settings_url) . '" class="button button-primary">';
        $html .= '<span class="dashicons dashicons-admin-generic" style="margin: 4px 5px 0 -2px;"></span> ';
        $html .= esc_html__('Pengaturan QRIS', 'qris-kode-unik');
        $html .= '</a>';
        $html .= '</div>';
        
        if (empty($logs)) {
            return $html . '<p>Belum ada log heartbeat yang diterima.</p>';
        }
        
        $html .= '<table class="widefat striped" style="width:100%;">';
        $html .= '<thead><tr><th>Waktu</th><th>Perangkat</th><th>Baterai</th><th>Charging</th><th>WiFi SSID</th><th>Sinyal WiFi</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($logs as $log) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html(human_time_diff(strtotime($log['timestamp']), current_time('timestamp'))) . ' lalu</td>';
            $html .= '<td>' . esc_html($log['device']) . '</td>';
            $html .= '<td>' . esc_html($log['battery']) . '</td>';
            $html .= '<td>' . esc_html($log['charging']) . '</td>';
            $html .= '<td>' . esc_html($log['wifi_ssid']) . '</td>';
            $html .= '<td>' . esc_html($log['wifi_rssi']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }
    
    public function process_payment( $order_id ) {
        global $wpdb;
        $order = wc_get_order( $order_id );
        $table_name = $wpdb->prefix . 'qris_pending_payments';
        $total = $order->get_total();
        $fee = 0;
        $fee_setting = $this->get_option( 'fee_amount' );
        if ( ! empty( $fee_setting ) ) {
            if ( strpos( $fee_setting, '%' ) !== false ) {
                $percentage = floatval( str_replace( '%', '', $fee_setting ) );
                $fee = ( $total * $percentage ) / 100;
            } else {
                $fee = floatval( $fee_setting );
            }
            $total += $fee;
        }
        $min = intval( $this->get_option( 'unique_code_min', 100 ) );
        $max = intval( $this->get_option( 'unique_code_max', 999 ) );
        $unique_total = 0;
        for ($i = 0; $i < 10; $i++) {
            $unique_code = rand($min, $max);
            $temp_total = floor($total) + $unique_code;
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE unique_amount = %f", $temp_total ) );
            if ($exists == 0) {
                $unique_total = $temp_total;
                break;
            }
        }
        if ($unique_total == 0) {
            wc_add_notice( 'Gagal membuat kode pembayaran unik. Silakan coba lagi.', 'error' );
            return;
        }
        $wpdb->insert( $table_name, array(
            'order_id'      => $order_id,
            'unique_amount' => $unique_total,
            'created_at'    => current_time( 'mysql' ),
        ));
        $order->update_meta_data( '_qris_total_unik', $unique_total );
        $order->update_meta_data( '_qris_kode_unik', $unique_code );
        if ($fee > 0) $order->update_meta_data( '_qris_fee', $fee );
        $order->save();
        $order->update_status( 'on-hold', __( 'Menunggu pembayaran QRIS.', 'qris-kode-unik' ) );
        wc_reduce_stock_levels( $order_id );
        WC()->cart->empty_cart();
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }

    public function thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );
        if ($order->get_payment_method() !== $this->id || !$order->has_status('on-hold')) {
            return;
        }
        $total_unik = $order->get_meta( '_qris_total_unik' );
        if ( empty( $total_unik ) ) return;
        $modified_qris_string = $this->modify_qris_string_for_amount( $this->qris_string, $total_unik );
        $template = $this->get_option('payment_page_template', $this->form_fields['payment_page_template']['default']);
        $placeholders = array(
            '{instructions}'      => wp_kses_post($this->instructions),
            '{total_price}'       => wp_kses_post(wc_price($total_unik)),
            '{qrcode_container}'  => '',
            '{countdown_timer}'   => '',
        );
        $output = str_replace(array_keys($placeholders), array_values($placeholders), $template);
        echo '<div id="qris-payment-details-wrapper">' . $output . '</div>';
        echo '<div id="qris-payment-result-message" style="display:none; border: 2px solid #4CAF50; padding: 20px; text-align: center; margin: 20px 0; background-color: #f1f9f1;"></div>';
        $this->payment_scripts( $order_id, $modified_qris_string );
    }

    /**
     * Muat skrip JS yang diperlukan di halaman pembayaran.
     * CATATAN: Pastikan Anda sudah membuat folder /assets/js/ di dalam plugin
     * dan menempatkan file qrcode.min.js di dalamnya.
     */
    public function payment_scripts( $order_id, $qris_data ) {
        // Muat skrip dari file lokal, bukan CDN.
        wp_enqueue_script(
            'qrcode-js',
            plugin_dir_url(__FILE__) . 'assets/js/qrcode.min.js',
            array(),
            '1.0.0',
            true
        );
        
        $timeout_minutes = (int) $this->get_option('onhold_timeout', 5);
        
        // Kirim data dan string terjemahan ke JavaScript
        $localized_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qris-payment-status-nonce'),
            'order_id' => $order_id,
            'qris_data' => $qris_data,
            'timeout_seconds' => $timeout_minutes * 60,
            'check_delay' => (int) $this->get_option('check_delay', 10) * 1000,
            'check_interval' => (int) $this->get_option('check_interval', 5) * 1000,
            'success_action' => $this->get_option('success_action', 'message'),
            'success_message' => $this->get_option('success_message'),
            'success_redirect_url' => $this->get_option('success_redirect_url'),
            'timeout_action' => $this->get_option('timeout_action', 'message'),
            'timeout_message' => $this->get_option('timeout_message'),
            'timeout_redirect_url' => $this->get_option('timeout_redirect_url'),
        );
        wp_localize_script('qrcode-js', 'qris_payment_params', $localized_data);

        $script = "
            document.addEventListener('DOMContentLoaded', function() {
                const params = window.qris_payment_params;
                const wrapper = document.getElementById('qris-payment-details-wrapper');
                const resultMessageContainer = document.getElementById('qris-payment-result-message');
                const countdownElement = document.getElementById('qris-countdown');
                let paymentChecker;
                let countdownTimer;

                function showResultMessage(message, isSuccess) {
                    wrapper.style.display = 'none';
                    resultMessageContainer.style.display = 'block';
                    resultMessageContainer.style.borderColor = isSuccess ? '#4CAF50' : '#f44336';
                    resultMessageContainer.style.backgroundColor = isSuccess ? '#f1f9f1' : '#fdecea';
                    resultMessageContainer.innerHTML = '<h4>' + message + '</h4>';
                }

                function checkStatus() {
                    const formData = new FormData();
                    formData.append('action', 'qris_check_payment_status');
                    formData.append('nonce', params.nonce);
                    formData.append('order_id', params.order_id);
                    fetch(params.ajax_url, { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data.status !== 'on-hold') {
                                clearInterval(paymentChecker);
                                clearInterval(countdownTimer);
                                if (params.success_action === 'redirect') { window.location.href = params.success_redirect_url; } 
                                else { showResultMessage(params.success_message, true); }
                            }
                        });
                }

                if (document.getElementById('qris-code-container')) { new QRCode(document.getElementById('qris-code-container'), { text: params.qris_data, width: 250, height: 250 }); }
                if (countdownElement) {
                    let timeLeft = params.timeout_seconds;
                    countdownTimer = setInterval(function() {
                        if (timeLeft <= 0) {
                            clearInterval(countdownTimer); clearInterval(paymentChecker);
                            if (params.timeout_action === 'redirect') { window.location.href = params.timeout_redirect_url; }
                            else { showResultMessage(params.timeout_message, false); }
                        } else {
                            const minutes = Math.floor(timeLeft / 60);
                            let seconds = timeLeft % 60;
                            seconds = seconds < 10 ? '0' + seconds : seconds;
                            countdownElement.innerHTML = minutes + ':' + seconds;
                        }
                        timeLeft -= 1;
                    }, 1000);
                }
                setTimeout(function() { checkStatus(); paymentChecker = setInterval(checkStatus, params.check_interval); }, params.check_delay);
            });
        ";
        wp_add_inline_script( 'qrcode-js', $script );
    }

    private function parse_qris_string($string) {
        $data = []; 
        $i = 0;
        
        while ($i < strlen($string)) {
            // Get tag
            $tag = substr($string, $i, 2);
            if (!$tag) break;
            
            // Get length
            $len = substr($string, $i + 2, 2);
            if (!$len) break;
            $len = intval($len);
            
            // Get value
            $value = substr($string, $i + 4, $len);
            if (strlen($value) !== $len) break;
            
            // Handle special tags
            if ($tag === '26') {
                // Parse merchant account information
                $merchant_data = $this->parse_qris_string($value);
                if (isset($merchant_data['00'])) {
                    // If we have a global unique identifier
                    $data[$tag] = $merchant_data['00'];
                } else {
                    // Fallback to raw value
                    $data[$tag] = $value;
                }
            } else {
                $data[$tag] = $value;
            }
            
            $i += 4 + $len;
        }
        
        return $data;
    }

    private function get_parsed_qris_info_html() {
        $qris_string = $this->get_option('qris_string');
        if (empty($qris_string)) return '<em>Masukkan konten QRIS untuk melihat info merchant.</em>';
        $qris_data = $this->parse_qris_string($qris_string);
        $html = '<ul style="list-style-type: disc; padding-left: 20px;">';
        $html .= '<li><strong>Nama Merchant:</strong> ' . esc_html($qris_data['59'] ?? 'Tidak ditemukan') . '</li>';
        $html .= '<li><strong>Kota Merchant:</strong> ' . esc_html($qris_data['60'] ?? 'Tidak ditemukan') . '</li>';
        if (isset($qris_data['26'])) {
            $acquirer_data = $this->parse_qris_string($qris_data['26']);
            $html .= '<li><strong>Nama Acquirer:</strong> ' . esc_html($acquirer_data['01'] ?? 'Tidak ditemukan') . '</li>';
        } else {
            $html .= '<li><strong>Nama Acquirer:</strong> Tidak ditemukan</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    private function modify_qris_string_for_amount( $qris_string, $amount ) {
        $amount_str = (string) number_format($amount, 2, '.', '');
        $qris_string = preg_replace('/54\d{2}.*?(\d{2})/', '$1', $qris_string, 1);
        $pos53 = strpos($qris_string, '5303360');
        if ($pos53 === false) return $qris_string;
        $len54 = str_pad(strlen($amount_str), 2, '0', STR_PAD_LEFT);
        $tag54 = '54' . $len54 . $amount_str;
        $start = substr($qris_string, 0, $pos53 + 7);
        $end = substr($qris_string, $pos53 + 7);
        return $start . $tag54 . $end;
    }
}
