<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the setup wizard for the QRIS Kode Unik plugin.
 */
class QRIS_Admin_Setup_Wizard {

    /**
     * Hook in tabs.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_qris_save_wizard_settings', array( $this, 'save_wizard_settings' ) );
    }

    /**
     * Add admin menus/screens.
     */
    public function admin_menus() {
        // Register a hidden admin page for the setup wizard.
        add_dashboard_page(
            __( 'QRIS Setup', 'qris-kode-unik' ), // Page title
            '',                                     // Menu title (hidden)
            'manage_woocommerce',                   // Capability
            'qris-setup',                           // Menu slug
            array( $this, 'setup_wizard_page' )     // Callback function to render the page
        );
    }

    /**
     * Enqueue scripts and styles for the setup wizard.
     */
    public function enqueue_scripts() {
        // PERBAIKAN FINAL: Gunakan get_current_screen() untuk identifikasi halaman yang andal.
        if ( ! function_exists('get_current_screen') ) {
            return;
        }
        
        $screen = get_current_screen();

        // Periksa apakah ID layar adalah ID halaman wizard kita.
        if ( ! is_object($screen) || 'dashboard_page_qris-setup' !== $screen->id ) {
            return;
        }

        // Remove all admin notices to provide a clean interface
        remove_all_actions( 'admin_notices' );
        remove_all_actions( 'all_admin_notices' );

        // Enqueue styles and scripts
        // Anda perlu membuat file CSS ini di /assets/css/
        wp_enqueue_style( 'qris-setup-wizard', plugins_url( 'assets/css/setup-wizard.css', __FILE__ ), array( 'dashicons', 'install' ), '1.5.0' );
        
        // Muat pustaka JS dari file lokal
        wp_enqueue_script( 'jsqr', plugins_url( 'assets/js/jsQR.min.js', __FILE__ ), array(), '1.4.0', true );
        wp_enqueue_script( 'qris-setup-wizard-js', plugins_url( 'assets/js/qris-setup-wizard.js', __FILE__ ), array( 'jquery', 'jsqr' ), '1.5.0', true );

        // Localize script with data
        $localized_data = array(
            'ajax_url'          => admin_url('admin-ajax.php'),
            'nonce'             => wp_create_nonce('qris-wizard-nonce'),
            'scan_success'      => __('Konten QRIS berhasil di-scan.', 'qris-kode-unik'),
            'scan_fail'         => __('Tidak dapat menemukan QR Code pada gambar.', 'qris-kode-unik'),
            'saving'            => __('Menyimpan...', 'qris-kode-unik'),
            'error_qris_empty'  => __('Harap isi atau scan konten QRIS Anda sebelum melanjutkan.', 'qris-kode-unik'),
            'dashboard_url'     => admin_url(),
        );
        wp_localize_script('qris-setup-wizard-js', 'qris_wizard_params', $localized_data);
    }

    /**
     * Render the setup wizard page.
     */
    public function setup_wizard_page() {
        // Konten wizard
        ?>
        <div class="qris-setup-wizard-container">
            <div class="qris-setup-header">
                <svg id="adbaf254-4c4b-487b-85d3-058a15f799b8" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2135 1178.5"><title>woo-qris</title><path d="M658.3,826.7c44.2,0,79.8-21.9,106.5-72.2l59.6-111.4v94.5c0,55.7,36,89.1,91.7,89.1,43.7,0,76-19.2,107.1-72.2l137.1-231.6c30.1-50.8,8.8-89-57.3-89-35.5,0-58.5,11.5-79.2,50.2L929.2,661.7V503.8c0-47-22.4-69.9-63.9-69.9-32.7,0-59,14.2-79.2,53.5l-89,174.3V505.4c0-50.2-20.8-71.5-71-71.5H523.4c-38.8,0-58.5,18-58.5,51.3s20.8,52.5,58.5,52.5h42V737.1C565.4,793.3,603.1,826.7,658.3,826.7Z" transform="translate(-464.9 -433.9)" style="fill:#873eff"/><path d="M1360.8,433.9c-112,0-197.7,83.6-197.7,196.6s86.3,196.2,197.7,196.2,196.7-83.6,197.2-196.2S1472.3,433.9,1360.8,433.9Zm0,272c-42,0-71-31.7-71-75.4s29-75.9,71-75.9,71,32.2,71,75.9S1403.4,705.9,1360.8,705.9Z" transform="translate(-464.9 -433.9)" style="fill:#873eff;fill-rule:evenodd"/><path d="M1585.9,630.5c0-113,85.7-196.6,197.2-196.6s197.2,84.1,197.2,196.6-85.8,196.2-197.2,196.2S1585.9,743.6,1585.9,630.5Zm126.7,0c0,43.7,27.9,75.4,70.5,75.4s71-31.7,71-75.4-29-75.9-71-75.9S1712.6,586.8,1712.6,630.5Z" transform="translate(-464.9 -433.9)" style="fill:#873eff;fill-rule:evenodd"/><g id="a53c9bff-83bf-48f8-bcec-5975cae31ec0" data-name="g108"><path id="f6fb0cb6-db1d-4754-b5be-c761ba4f143b" data-name="path114" d="M1594.9,941.4h385.5v231.2H1818.7l161.7,148.1H1843.1l-158.7-148.1v148.1h-89.5V1085.5h282.7v-56.2H1594.9Z" transform="translate(-464.9 -433.9)"/><path id="e6c350a6-693d-4629-8c89-e8c281931ddf" data-name="path116" d="M2020.5,1320.7h94.2V940.6h-94.2Z" transform="translate(-464.9 -433.9)"/><path id="b24191f3-13cc-46e6-9834-e228c9c62d37" data-name="path118" d="M1266,1228.9V941.4h-73.8c-13.4,0-23.6,10-23.6,22.3,0,67.9-.8,267.5-.8,333.8,0,12.4,10.2,23.2,22.8,23.2h219v-91.8Z" transform="translate(-464.9 -433.9)"/><path id="b506f395-0fa2-4679-982c-4e0da2f4e8f6" data-name="path120" d="M1458.3,1415.5h96.6V1225.1h-96.6Z" transform="translate(-464.9 -433.9)"/><path id="a128d7ba-2f97-4923-8b55-64782f728daa" data-name="path122" d="M1313.1,941.4v94.8h145.2V1178h96.6V964.5c0-12.4-10.2-22.4-22.8-23.1Z" transform="translate(-464.9 -433.9)"/><path id="a07f3f0d-f77f-4d2f-b4ae-d6f0d4118899" data-name="path124" d="M1313.1,1082.4v96.4h95.8v-96.4Zm67.5,67.1h-39.3V1111h39.3Z" transform="translate(-464.9 -433.9)"/><path id="fed9a1d9-e2e4-494f-a579-5795d99a31be" data-name="path126" d="M2577.1,1225.1v141.8c0,12.4-11,22.4-23.5,22.4H2409.1v23.1h168.8a22.4,22.4,0,0,0,22-22.3v-165Z" transform="translate(-464.9 -433.9)"/><path id="a7f7d3de-07f4-4c57-be35-ca810a083637" data-name="path128" d="M1128.6,1035.4V893.5c0-12.3,9.4-21.5,22-21.5H1295a.8.8,0,0,0,.8-.8V849.6a.8.8,0,0,0-.8-.8H1128.6c-13.4,0-23.6,10.1-23.6,23.2v163.4a.8.8,0,0,0,.8.8h22A.8.8,0,0,0,1128.6,1035.4Z" transform="translate(-464.9 -433.9)"/><path id="e231b55b-7388-4320-b886-91a9ebaa2d2e" data-name="path130" d="M2536.3,1035.4v-94H2155.5v235.9h253.6v47H2155.5v94.8h380.8V1083.2H2282.7v-47.8Z" transform="translate(-464.9 -433.9)"/></g><path d="M2087.2,611.7h251.1a20.8,20.8,0,0,1,20.9,20.9V833.2l48-48a20.9,20.9,0,0,1,29.6,29.6l-83.7,83.7a.1.1,0,0,1-.1.1l-1.4,1.3-.8.6-.9.6-.9.6-.8.5-1,.5-.9.4-1,.3-.9.4-1,.2-1,.3h-1.2l-.8.2a29.4,29.4,0,0,1-4.2,0l-.8-.2h-1.2l-1-.3-1-.2-.9-.4-1-.3-.9-.4-1-.5-.8-.5-.9-.6-.9-.6-.8-.6-1.4-1.3a.1.1,0,0,1-.1-.1l-83.7-83.7a20.9,20.9,0,0,1,29.6-29.6h0l48,48V653.5H2087.2a20.9,20.9,0,1,1,0-41.8Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M1051,1151.7H799.9a21,21,0,0,1-21-21V930.1l-48,48a20.9,20.9,0,0,1-29.5-29.6l83.7-83.7h.1a8.6,8.6,0,0,1,1.4-1.3l.8-.6.8-.6,1-.6.8-.5,1-.4.9-.5.9-.3,1-.3,1-.3,1-.2,1.1-.2h.9a14.1,14.1,0,0,1,4.1,0h.9l1.2.2,1,.2.9.3,1,.3,1,.3.9.5.9.4.8.5,1,.6.8.6.8.6,1.5,1.3h.1l83.7,83.7a20.9,20.9,0,0,1-29.6,29.6h0l-48-48v179.7H1051a21,21,0,0,1,0,41.9Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M594.9,1490.7l-17.4,119.5H552.4l-17.2-50-3.5-12.1-3.9,12.8-16.6,49.3H486.8l-17.2-119.5h20.2l10,81.2L502,1590l5.1-15.9,17.3-53.6h14.9l18.7,52.9,5.4,15.9,1.7-16.8,9.3-81.8Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M658.9,1507.9H623.6v-17.2h56.3v102.2h35.5v17.3H619.6v-17.3h39.3Zm7.3-67.3a16.4,16.4,0,0,1,11.6,4.8,16.7,16.7,0,0,1,3.4,5.2,15.9,15.9,0,0,1,0,12.6,17.2,17.2,0,0,1-3.4,5.3,15.4,15.4,0,0,1-5.2,3.5,16.9,16.9,0,0,1-6.4,1.2,17.5,17.5,0,0,1-6.5-1.2,15,15,0,0,1-5.1-3.5,17.5,17.5,0,0,1-3.5-5.3,17.1,17.1,0,0,1,0-12.6,17,17,0,0,1,3.5-5.2,15,15,0,0,1,5.1-3.5A15.7,15.7,0,0,1,666.2,1440.6Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M848.5,1608.5a97.6,97.6,0,0,1-14.5,2.6c-5,.5-10.1.8-15.3.8-15,0-26.1-3.4-33.5-10.2s-11.1-17.2-11.1-31.2v-62.4H740.7v-17.4h33.4v-32.8l20.7-5.4v38.2h53.7v17.4H794.8v60.7q0,12.9,6.9,19.2c4.5,4.3,11.2,6.4,20.1,6.4a95.4,95.4,0,0,0,12.5-.9,114.6,114.6,0,0,0,14.2-2.8Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M982.2,1610.2H961.5v-76.3c0-9.2-1.7-16.1-5.1-20.6s-8.4-6.9-14.9-6.9a25.4,25.4,0,0,0-7.8,1.2,24.6,24.6,0,0,0-7.5,3.9,75.1,75.1,0,0,0-8.6,7.5c-3.1,3.2-6.6,7.2-10.7,11.9v79.3H886.2V1442.1h20.7v48.6l-.7,18.8a81.6,81.6,0,0,1,9.6-9.8,54.7,54.7,0,0,1,9.4-6.5,38.2,38.2,0,0,1,9.6-3.5,41.7,41.7,0,0,1,10.1-1.1c11.9,0,21.1,3.6,27.6,10.9s9.7,18.2,9.7,32.8Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M1268,1610.2h-23.1l-10.9-33.9h-64.7l-10.9,33.9h-22.1l51.7-155.5h29Zm-40-52.8-26.3-83.2-26.3,83.2Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M1384.2,1610.2h-18.5l-.8-19.3a82.8,82.8,0,0,1-10.1,10.3,45.8,45.8,0,0,1-9.6,6.6,33.8,33.8,0,0,1-9.6,3.5,47.7,47.7,0,0,1-10.2,1c-12.3,0-21.5-3.6-27.8-10.8s-9.4-18.1-9.4-32.7v-78.1h20.7v76.4c0,18.4,6.9,27.5,20.7,27.5a26.5,26.5,0,0,0,7.4-1.1,31,31,0,0,0,7.7-3.9,61.5,61.5,0,0,0,8.5-7.6c3.1-3.2,6.5-7.2,10.3-12v-79.3h20.7Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M1518.5,1608.5a99,99,0,0,1-14.6,2.6,151.6,151.6,0,0,1-15.2.8q-22.5,0-33.6-10.2c-7.3-6.8-11-17.2-11-31.2v-62.4h-33.5v-17.4h33.5v-32.8l20.7-5.4v38.2h53.7v17.4h-53.7v60.7c0,8.6,2.3,15,6.8,19.2s11.3,6.4,20.2,6.4a95.4,95.4,0,0,0,12.5-.9,118.3,118.3,0,0,0,14.2-2.8Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M1660.2,1549.5a79.3,79.3,0,0,1-3.9,25.5,57.8,57.8,0,0,1-11.3,19.9,53,53,0,0,1-18,12.9,61.3,61.3,0,0,1-24.1,4.5,64.1,64.1,0,0,1-23-3.9,45.2,45.2,0,0,1-17.2-11.8,50.4,50.4,0,0,1-10.9-19.1c-2.5-7.6-3.7-16.4-3.7-26.3a78.3,78.3,0,0,1,3.9-25.4,56.6,56.6,0,0,1,11.3-19.8,49.7,49.7,0,0,1,18-12.8,59.1,59.1,0,0,1,24-4.6,61.3,61.3,0,0,1,23,4,45.2,45.2,0,0,1,17.3,11.6,52.4,52.4,0,0,1,10.8,19.1A84,84,0,0,1,1660.2,1549.5Zm-21.2,1a70,70,0,0,0-2.4-19.4,40.7,40.7,0,0,0-7-13.8,28.9,28.9,0,0,0-11-8.3,35.7,35.7,0,0,0-14.5-2.8,33,33,0,0,0-15.9,3.6,31.5,31.5,0,0,0-10.8,9.7,42.9,42.9,0,0,0-6.2,14.1,71.8,71.8,0,0,0-1.9,16.9,71,71,0,0,0,2.4,19.4,39,39,0,0,0,7,13.8,28.9,28.9,0,0,0,10.9,8.4,37.1,37.1,0,0,0,14.5,2.8,32.8,32.8,0,0,0,15.9-3.7,30.5,30.5,0,0,0,10.8-9.7,39.3,39.3,0,0,0,6.2-14.1A71.1,71.1,0,0,0,1639,1550.5Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M1789.5,1604.4a102.1,102.1,0,0,1-39.1,7.7q-33,0-50.6-19.7t-17.7-58.2c0-12.5,1.6-23.8,4.9-33.8a72.9,72.9,0,0,1,13.9-25.7,60.8,60.8,0,0,1,21.9-16.2,69.8,69.8,0,0,1,28.8-5.7,105.8,105.8,0,0,1,20.1,1.8,78.6,78.6,0,0,1,17.8,5.7v20.8a75.6,75.6,0,0,0-17.5-7.1,76.5,76.5,0,0,0-19.7-2.4,45.6,45.6,0,0,0-19.8,4.1,40.6,40.6,0,0,0-15.1,11.9,55.6,55.6,0,0,0-9.5,19,91.6,91.6,0,0,0-3.3,25.7c0,20.1,4.1,35.3,12.2,45.5s20.2,15.4,36,15.4a79.9,79.9,0,0,0,19.1-2.3,90.6,90.6,0,0,0,17.6-6.5Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M1928.2,1549.5a76.6,76.6,0,0,1-4,25.5,54.7,54.7,0,0,1-11.3,19.9,51.7,51.7,0,0,1-17.9,12.9,61.3,61.3,0,0,1-24.1,4.5,64.1,64.1,0,0,1-23-3.9,45.2,45.2,0,0,1-17.2-11.8,50.4,50.4,0,0,1-10.9-19.1c-2.5-7.6-3.7-16.4-3.7-26.3a78.3,78.3,0,0,1,3.9-25.4,56.6,56.6,0,0,1,11.3-19.8,49.7,49.7,0,0,1,18-12.8,58.7,58.7,0,0,1,24-4.6,61.3,61.3,0,0,1,23,4,45.2,45.2,0,0,1,17.3,11.6,52.4,52.4,0,0,1,10.8,19.1A84,84,0,0,1,1928.2,1549.5Zm-21.2,1a66.2,66.2,0,0,0-2.5-19.4,38.9,38.9,0,0,0-6.9-13.8,28.9,28.9,0,0,0-11-8.3,35.7,35.7,0,0,0-14.5-2.8,32.8,32.8,0,0,0-15.9,3.6,31.5,31.5,0,0,0-10.8,9.7,42.9,42.9,0,0,0-6.2,14.1,71.8,71.8,0,0,0-1.9,16.9,71,71,0,0,0,2.4,19.4,37.4,37.4,0,0,0,7,13.8,28.9,28.9,0,0,0,10.9,8.4,37.1,37.1,0,0,0,14.5,2.8,32.8,32.8,0,0,0,15.9-3.7,30.5,30.5,0,0,0,10.8-9.7,39.3,39.3,0,0,0,6.2-14.1A71.1,71.1,0,0,0,1907,1550.5Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M1958.2,1490.7h18.4l.8,19.3a83,83,0,0,1,10.2-10.3,58.4,58.4,0,0,1,9.5-6.6,37.2,37.2,0,0,1,9.6-3.5,48.1,48.1,0,0,1,10.1-1c12.3,0,21.6,3.6,27.9,10.9s9.5,18.2,9.5,32.8v77.9h-20.7v-76.3c0-9.3-1.8-16.3-5.3-20.7s-8.6-6.8-15.5-6.8a23.6,23.6,0,0,0-7.5,1.2,24.6,24.6,0,0,0-7.5,3.9,60.9,60.9,0,0,0-8.5,7.5c-3.1,3.2-6.5,7.2-10.3,11.9v79.3h-20.7Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M2198.2,1461.3a138.2,138.2,0,0,0-28.1-3.4c-18.6,0-28,9.7-28,29.3v20.9h52.4v17.3h-52.4v84.8h-21v-84.8h-38.5v-17.3h38.5v-19.8q0-47.7,49.7-47.7a147.9,147.9,0,0,1,27.4,2.9Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M2266.8,1507.9h-35.3v-17.2h56.3v102.2h35.6v17.3h-95.8v-17.3h39.2Zm7.3-67.3a16.4,16.4,0,0,1,11.6,4.8,17,17,0,0,1,3.5,5.2,17.1,17.1,0,0,1,0,12.6,17.5,17.5,0,0,1-3.5,5.3,15.4,15.4,0,0,1-5.2,3.5,17.7,17.7,0,0,1-12.8,0,15.5,15.5,0,0,1-8.6-8.8,15.9,15.9,0,0,1,0-12.6,15.1,15.1,0,0,1,3.4-5.2,16.4,16.4,0,0,1,11.6-4.8Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M2365,1490.7h18.9l.6,22c7.1-8.4,14.1-14.6,20.9-18.4a42.7,42.7,0,0,1,20.8-5.7c12.4,0,21.7,4,28.1,12s9.4,19.9,8.9,35.7h-20.9c.2-10.5-1.3-18.1-4.6-22.8s-8.1-7.1-14.5-7.1a24.8,24.8,0,0,0-8.4,1.5,34.6,34.6,0,0,0-8.7,4.8,84.1,84.1,0,0,0-9.4,8.4,143.2,143.2,0,0,0-10.7,12.3v76.8h-21Z" transform="translate(-464.9 -433.9)" style="fill:#666"/><path d="M2579.3,1610.2v-85.8a78.6,78.6,0,0,0-.4-9.2,22.1,22.1,0,0,0-1.3-5.6,5.7,5.7,0,0,0-2.2-2.9,6.4,6.4,0,0,0-3.4-.9,7.1,7.1,0,0,0-4.4,1.5,19.6,19.6,0,0,0-4.4,4.6c-1.5,2.2-3.2,5-5.1,8.5s-4.1,7.9-6.6,13v76.8h-18.9v-83.5c0-4.4-.2-7.9-.4-10.6a24.8,24.8,0,0,0-1.4-6.3,5.1,5.1,0,0,0-2.3-3.1,6.2,6.2,0,0,0-3.4-.9,7.5,7.5,0,0,0-4.1,1.2,21.5,21.5,0,0,0-4.2,4.3,74.1,74.1,0,0,0-5.2,8.5c-1.9,3.5-4.1,8.1-6.8,13.6v76.8h-19V1490.7h15.8l1,22.8a88.1,88.1,0,0,1,6-11.6,35.5,35.5,0,0,1,6-7.7,21.6,21.6,0,0,1,6.6-4.2,19.4,19.4,0,0,1,7.8-1.4c6.4,0,11.3,2.1,14.6,6.3s5,10.7,5,19.5c1.9-4.1,3.8-7.8,5.6-11a44.6,44.6,0,0,1,5.9-8.1,22.5,22.5,0,0,1,7-5,21.9,21.9,0,0,1,8.8-1.7c15,0,22.5,11.5,22.5,34.6v87Z" transform="translate(-464.9 -433.9)" style="fill:#666"/></svg>
                <h1><?php esc_html_e( 'Selamat Datang di QRIS Kode Unik', 'qris-kode-unik' ); ?></h1>
                <p><?php esc_html_e( 'Panduan ini akan membantu Anda mengkonfigurasi metode pembayaran dalam beberapa langkah mudah.', 'qris-kode-unik' ); ?></p>
                <ol class="qris-setup-steps">
                    <li data-step="1" class="active"><?php esc_html_e( 'Selamat Datang', 'qris-kode-unik' ); ?></li>
                    <li data-step="2"><?php esc_html_e( 'Setup QRIS', 'qris-kode-unik' ); ?></li>
                    <li data-step="3"><?php esc_html_e( 'Hubungkan Android', 'qris-kode-unik' ); ?></li>
                    <li data-step="4"><?php esc_html_e( 'Selesai!', 'qris-kode-unik' ); ?></li>
                </ol>
            </div>

            <div class="qris-setup-content">
                <!-- Step 1: Welcome -->
                <div class="qris-setup-step active" data-step="1">
                    <h2><?php esc_html_e( 'Terima kasih telah menginstall!', 'qris-kode-unik' ); ?></h2>
                    <p><?php esc_html_e( 'Dalam 2 menit ke depan, toko Anda akan siap menerima pembayaran QRIS otomatis. Klik "Mulai" untuk melanjutkan.', 'qris-kode-unik' ); ?></p>
                </div>

                <!-- Step 2: QRIS Setup -->
                <div class="qris-setup-step" data-step="2">
                    <h2><?php esc_html_e( 'Setup QRIS Statis Anda', 'qris-kode-unik' ); ?></h2>
                    <p><?php esc_html_e( 'Tempel konten teks dari QRIS statis Anda, atau unggah gambarnya untuk kami scan secara otomatis.', 'qris-kode-unik' ); ?></p>
                    <div class="qris-setup-form">
                        <textarea id="qris_string_input" placeholder="<?php esc_attr_e( 'Tempel konten QRIS di sini...', 'qris-kode-unik' ); ?>"></textarea>
                        <p class="qris-setup-or"><?php esc_html_e( 'ATAU', 'qris-kode-unik' ); ?></p>
                        <label for="qris_scanner_upload" class="button button-secondary"><?php esc_html_e( 'Upload & Scan Gambar QRIS Statis', 'qris-kode-unik' ); ?></label>
                        <input type="file" id="qris_scanner_upload" accept="image/*" style="display: none;">
                    </div>
                </div>

                <!-- Step 3: Connect Android -->
                <div class="qris-setup-step" data-step="3">
                    <h2><?php esc_html_e( 'Hubungkan Perangkat Android Anda', 'qris-kode-unik' ); ?></h2>
                    <p><?php esc_html_e( 'Ikuti langkah berikut untuk mengotomatiskan konfirmasi pembayaran:', 'qris-kode-unik' ); ?></p>
                    <a href="<?php echo esc_url( add_query_arg('action', 'download_qris_macro', admin_url('admin.php')) ); ?>" class="button button-primary"><?php esc_html_e( 'Unduh File QRISNotify.macro', 'qris-kode-unik' ); ?></a>
                    <ol>
                        <li><?php esc_html_e( 'Unduh file macro yang sudah dikonfigurasi untuk toko Anda di bawah ini.', 'qris-kode-unik' ); ?></li>
                        <li><?php printf( wp_kses_post( __( 'Install aplikasi <a href="%s" target="_blank">MacroDroid</a> di perangkat Android Anda.', 'qris-kode-unik' ) ), 'https://play.google.com/store/apps/details?id=com.arlosoft.macrodroid' ); ?></li>
                        <li><?php esc_html_e( 'Buka MacroDroid, impor file tersebut, dan aktifkan macronya dan izinkan prizinan yang diperlukan.', 'qris-kode-unik' ); ?></li>
                    </ol>
                </div>

                <!-- Step 4: Finish -->
                <div class="qris-setup-step" data-step="4">
                    <h2><?php esc_html_e( 'Setup Selesai!', 'qris-kode-unik' ); ?></h2>
                    <p><?php esc_html_e( 'Selamat! Toko Anda sekarang siap menerima pembayaran otomatis melalui QRIS Kode Unik.', 'qris-kode-unik' ); ?></p>
                    <p><?php esc_html_e( 'Anda bisa mengubah pengaturan lebih lanjut kapan saja melalui menu pengaturan WooCommerce.', 'qris-kode-unik' ); ?></p>
                    <a href="<?php echo esc_url( admin_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Kembali ke Dashboard', 'qris-kode-unik' ); ?></a>
                </div>
            </div>

            <div class="qris-setup-footer">
                <a href="<?php echo esc_url( admin_url() ); ?>" class="button button-secondary qris-skip-wizard"><?php esc_html_e( 'Lewati Setup', 'qris-kode-unik' ); ?></a>
                <button class="button button-primary qris-wizard-next-step"><?php esc_html_e( 'Mulai', 'qris-kode-unik' ); ?></button>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler to save wizard settings.
     */
    public function save_wizard_settings() {
        check_ajax_referer('qris-wizard-nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Forbidden'), 403);
        }

        $qris_string = isset($_POST['qris_string']) ? sanitize_textarea_field(wp_unslash($_POST['qris_string'])) : '';
        if (empty($qris_string)) {
            wp_send_json_error(array('message' => 'QRIS string cannot be empty.'));
        }

        $settings = get_option('woocommerce_qris_kode_unik_settings', array());
        $settings['qris_string'] = $qris_string;
        
        // Enable the gateway automatically
        $settings['enabled'] = 'yes';
        
        update_option('woocommerce_qris_kode_unik_settings', $settings);

        wp_send_json_success();
    }
}
