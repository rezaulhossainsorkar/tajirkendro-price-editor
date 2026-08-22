<?php
/**
 * Plugin Name:       TajirKendro Price Editor
 * Plugin URI:        https://tajirkendro.com/
 * Description:       A simple product price editor for WooCommerce.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Requires Plugins:  woocommerce
 * Author:            Rezaul Hossain Sorkar
 * Author URI:        https://profiles.wordpress.org/rezaulhossainsorkar/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tajirkendro-price-editor
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Plugin constants.
 */
define( 'TKPE_VERSION', '1.0.0' );
define( 'TKPE_PLUGIN_FILE', __FILE__ );
define( 'TKPE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'TKPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


/**
 * Load plugin files.
 *
 * @return void
 */
function tkpe_load_files() {

	require_once TKPE_PLUGIN_PATH . 'includes/admin/admin-menu.php';
	require_once TKPE_PLUGIN_PATH . 'includes/admin/admin-page.php';

	require_once TKPE_PLUGIN_PATH . 'includes/api/products.php';
	require_once TKPE_PLUGIN_PATH . 'includes/api/bulk-pricing.php';
	require_once TKPE_PLUGIN_PATH . 'includes/api/quick-edit.php';
	require_once TKPE_PLUGIN_PATH . 'includes/api/product-editor.php';
	require_once TKPE_PLUGIN_PATH . 'includes/api/product-updater.php';
	require_once TKPE_PLUGIN_PATH . 'includes/api/product-deleter.php';

}

tkpe_load_files();


/**
 * Initialize TKPE.
 *
 * @return void
 */
function tkpe_init() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

}

add_action( 'plugins_loaded', 'tkpe_init' );