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
define( 'KTPE_VERSION', '1.0.0' );
define( 'KTPE_PLUGIN_FILE', __FILE__ );
define( 'KTPE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'KTPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


/**
 * Load plugin files.
 */
function ktpe_load_files() {

	require_once KTPE_PLUGIN_PATH . 'includes/admin/admin-menu.php';
	require_once KTPE_PLUGIN_PATH . 'includes/admin/admin-page.php';

}

ktpe_load_files();


/**
 * Initialize KTPE.
 */
function ktpe_init() {

	// KTPE initialization will go here.

}

add_action( 'plugins_loaded', 'ktpe_init' );