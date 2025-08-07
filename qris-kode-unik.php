<?php
/**
 * QRIS Unique Code for WooCommerce
 *
 * @package           QRIS_Kode_Unik
 * @author            Gustiarto
 * @copyright         2025 Gustiarto
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       QRIS Unique Code for WooCommerce
 * Plugin URI:        https://github.com/gustiarto/wooqris-kodeunik
 * Description:       Metode pembayaran QRIS dengan validasi kode unik, webhook, dan monitor heartbeat untuk WooCommerce.
 * Version:           1.5.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Gustiarto
 * Author URI:        https://github.com/gustiarto
 * Text Domain:       qris-kode-unik
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/gustiarto/wooqris-kodeunik
 *
 * WC requires at least: 3.0
 * WC tested up to:      8.0
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die('Direct access is not allowed.');
}

// Define plugin constants
define('QRIS_VERSION', '1.5.0');
define('QRIS_FILE', __FILE__);
define('QRIS_PATH', plugin_dir_path(__FILE__));
define('QRIS_URL', plugin_dir_url(__FILE__));
define('QRIS_BASENAME', plugin_basename(__FILE__));

// Require the plugin bootstrap file
require_once QRIS_PATH . 'includes/bootstrap.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, function() {
    require_once QRIS_PATH . 'includes/class-qris-activator.php';
    if (class_exists('QRIS_Activator')) {
        QRIS_Activator::activate();
    }
});

register_deactivation_hook(__FILE__, function() {
    require_once QRIS_PATH . 'includes/class-qris-deactivator.php';
    if (class_exists('QRIS_Deactivator')) {
        QRIS_Deactivator::deactivate();
    }
});

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
