<?php
/**
 * KTPE admin menu.
 *
 * @package TajirKendro_Price_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register KTPE admin menu.
 */
function ktpe_register_admin_menu() {

	add_submenu_page(
		'woocommerce',
		__( 'TajirKendro Price Editor', 'tajirkendro-price-editor' ),
		__( 'KTPE', 'tajirkendro-price-editor' ),
		'manage_woocommerce',
		'ktpe',
		'ktpe_render_admin_page'
	);

}

add_action( 'admin_menu', 'ktpe_register_admin_menu' );