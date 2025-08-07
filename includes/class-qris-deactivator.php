<?php
/**
 * Class untuk menangani deaktivasi plugin
 */
class QRIS_Deactivator {
    /**
     * Deaktivasi plugin
     */
    public static function deactivate() {
        // Hapus schedule events
        wp_clear_scheduled_hook('qris_check_expired_payments');
        wp_clear_scheduled_hook('qris_check_heartbeat');

        // Hapus transient
        delete_transient('_qris_activation_redirect');

        // Hapus opsi versi (opsional, uncomment jika ingin menghapus)
        // delete_option('qris_db_version');

        // Bersihkan file log yang tidak diperlukan
        $upload_dir = wp_upload_dir();
        $qris_upload_dir = $upload_dir['basedir'] . '/qris-logs';
        if (file_exists($qris_upload_dir)) {
            $files = glob($qris_upload_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($qris_upload_dir);
        }

        // Log deaktivasi
        QRIS_Logger::info('Plugin dinonaktifkan', array(
            'timestamp' => current_time('mysql'),
            'version' => QRIS_PLUGIN_VERSION
        ));
    }
}
