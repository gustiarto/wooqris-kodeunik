<?php
/**
 * Class untuk menangani REST API
 */
class QRIS_API {
    /**
     * Inisialisasi API
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_endpoints'));
    }

    /**
     * Daftarkan endpoint API
     */
    public static function register_endpoints() {
        register_rest_route('qris/v1', '/notify', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_notification'),
            'permission_callback' => array(__CLASS__, 'verify_request'),
        ));

        register_rest_route('qris/v1', '/heartbeat', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_heartbeat'),
            'permission_callback' => array(__CLASS__, 'verify_request'),
        ));
    }

    /**
     * Verifikasi request API
     */
    public static function verify_request($request) {
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        $rate_key = 'qris_rate_' . md5($ip);
        $rate_count = (int) get_transient($rate_key);
        
        if ($rate_count > QRIS_RATE_LIMIT) {
            return new WP_Error(
                'too_many_requests',
                'Too many requests',
                array('status' => 429)
            );
        }
        
        set_transient($rate_key, $rate_count + 1, 60);

        // API key verification
        $api_key = $request->get_header('X-QRIS-Key');
        if (empty($api_key)) {
            return new WP_Error(
                'missing_key',
                'Missing API key',
                array('status' => 401)
            );
        }

        $settings = get_option('woocommerce_qris_kode_unik_settings', array());
        $valid_key = isset($settings['api_key']) ? $settings['api_key'] : '';

        if (!hash_equals($valid_key, $api_key)) {
            return new WP_Error(
                'invalid_key',
                'Invalid API key',
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Handle notifikasi pembayaran
     */
    public static function handle_notification($request) {
        try {
            $params = $request->get_json_params();
            
            if (!isset($params['amount']) || !is_numeric($params['amount'])) {
                throw new Exception('Invalid amount parameter');
            }

            $amount = floatval($params['amount']);
            $payment = QRIS_Payment::find_by_amount($amount);
            
            if (!$payment) {
                throw new Exception('Payment not found');
            }

            $order = wc_get_order($payment->order_id);
            if (!$order) {
                throw new Exception('Order not found');
            }

            if ($order->is_paid()) {
                throw new Exception('Order already paid');
            }

            $order->payment_complete();
            $order->add_order_note(
                sprintf(
                    __('Payment completed via QRIS. Amount: %s', 'qris-kode-unik'),
                    wc_price($amount)
                )
            );

            QRIS_Logger::info('Payment completed', array(
                'order_id' => $payment->order_id,
                'amount' => $amount
            ));

            return new WP_REST_Response(
                array('status' => 'success'),
                200
            );

        } catch (Exception $e) {
            QRIS_Logger::error('Payment notification failed', array(
                'error' => $e->getMessage(),
                'params' => $params ?? null
            ));

            return new WP_Error(
                'payment_failed',
                $e->getMessage(),
                array('status' => 400)
            );
        }
    }

    /**
     * Handle heartbeat
     */
    public static function handle_heartbeat($request) {
        $params = $request->get_json_params();
        
        update_option('qris_last_heartbeat', array(
            'timestamp' => current_time('mysql'),
            'device' => sanitize_text_field($params['device'] ?? ''),
            'status' => sanitize_text_field($params['status'] ?? '')
        ));

        return new WP_REST_Response(
            array('status' => 'success'),
            200
        );
    }
}
