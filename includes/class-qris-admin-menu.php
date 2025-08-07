<?php
/**
 * Class untuk menangani menu admin
 */
class QRIS_Admin_Menu {
    /**
     * Initialize the admin menu
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'), 4);
        add_filter('plugin_action_links_qris-kode-unik/qris-kode-unik.php', array(__CLASS__, 'add_settings_link'));
    }

    /**
     * Tambahkan menu di admin
     */
    public static function add_admin_menu() {
        $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=qris_kode_unik');
        
        add_menu_page(
            __('QRIS Settings', 'qris-kode-unik'),
            __('QRIS', 'qris-kode-unik'),
            'manage_woocommerce',
            $settings_url,
            '',
            'dashicons-qrcode',
            4
        );
    }

    /**
     * Tambahkan link settings di halaman plugins
     */
    public static function add_settings_link($links) {
        $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=qris_kode_unik');
        $settings_link = sprintf('<a href="%s">%s</a>', $settings_url, __('Settings', 'qris-kode-unik'));
        array_unshift($links, $settings_link);
        return $links;
    }
}
