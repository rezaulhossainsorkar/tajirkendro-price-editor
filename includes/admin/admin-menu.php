<?php
/**
 * TKPE admin menu.
 *
 * @package TajirKendro_Price_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register TKPE admin menu.
 */
function tkpe_register_admin_menu() {

	add_submenu_page(
		'woocommerce',
		__( 'TajirKendro Price Editor', 'tajirkendro-price-editor' ),
		__( 'TKPE', 'tajirkendro-price-editor' ),
		'manage_woocommerce',
		'tkpe',
		'tkpe_render_admin_page'
	);

}

add_action( 'admin_menu', 'tkpe_register_admin_menu' );