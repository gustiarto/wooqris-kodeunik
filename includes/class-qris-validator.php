<?php
if (!defined('ABSPATH')) {
    exit;
}

class QRIS_Validator {
    /**
     * Validasi string QRIS
     */
    public static function validate_qris_string($qris_string) {
        if (empty($qris_string)) {
            return new WP_Error('invalid_qris', __('String QRIS tidak boleh kosong.', 'qris-kode-unik'));
        }

        // Validasi format dasar QRIS
        if (!preg_match('/^00020101/', $qris_string)) {
            return new WP_Error('invalid_qris', __('Format QRIS tidak valid.', 'qris-kode-unik'));
        }

        // Validasi panjang string
        if (strlen($qris_string) < 30 || strlen($qris_string) > 512) {
            return new WP_Error('invalid_qris', __('Panjang string QRIS tidak valid.', 'qris-kode-unik'));
        }

        // Validasi merchant ID (tag 26)
        if (!preg_match('/26\d{2}/', $qris_string)) {
            return new WP_Error('invalid_qris', __('Merchant ID tidak ditemukan dalam string QRIS.', 'qris-kode-unik'));
        }

        return true;
    }

    /**
     * Validasi nominal pembayaran
     */
    public static function validate_amount($amount) {
        if (!is_numeric($amount)) {
            return new WP_Error('invalid_amount', __('Nominal harus berupa angka.', 'qris-kode-unik'));
        }

        if ($amount <= 0) {
            return new WP_Error('invalid_amount', __('Nominal harus lebih besar dari 0.', 'qris-kode-unik'));
        }

        if ($amount >= 100000000) {
            return new WP_Error('invalid_amount', __('Nominal terlalu besar.', 'qris-kode-unik'));
        }

        return true;
    }

    /**
     * Generasi kode unik
     */
    public static function generate_unique_code($base_amount) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qris_pending_payments';
        $max_attempts = 10;
        $attempt = 0;

        do {
            $unique_cents = mt_rand(1, 999);
            $unique_amount = $base_amount + ($unique_cents / 100);
            
            // Cek apakah nominal sudah digunakan
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE unique_amount = %f",
                $unique_amount
            ));
            
            $attempt++;
            
            if (!$exists) {
                return $unique_amount;
            }
        } while ($attempt < $max_attempts);

        return new WP_Error('no_unique_code', __('Tidak dapat menghasilkan kode unik. Silakan coba lagi.', 'qris-kode-unik'));
    }

    /**
     * Sanitasi input
     */
    public static function sanitize_qris_input($input) {
        return preg_replace('/[^A-Za-z0-9]/', '', $input);
    }
}
