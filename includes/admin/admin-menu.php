<?php
/**
 * TKPE admin menu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register TKPE admin menu.
 *
 * @return void
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