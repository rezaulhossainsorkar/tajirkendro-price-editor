<?php
/**
 * TKPE admin page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Enqueue TKPE admin assets.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function tkpe_enqueue_admin_assets( $hook_suffix ) {

	if ( 'woocommerce_page_tkpe' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'tkpe-admin-products',
		TKPE_PLUGIN_URL . 'admin/assets/css/admin-products.css',
		array(),
		TKPE_VERSION
	);

	wp_enqueue_script(
	'tkpe-admin-products',
	TKPE_PLUGIN_URL . 'admin/assets/js/admin-products.js',
	array( 'jquery' ),
	TKPE_VERSION,
	true
	);

	wp_enqueue_script(
		'tkpe-admin-quick-edit',
		TKPE_PLUGIN_URL . 'admin/assets/js/admin-quick-edit.js',
		array(
			'jquery',
			'tkpe-admin-products',
		),
		TKPE_VERSION,
		true
	);

	wp_enqueue_script(
		'tkpe-admin-bulk-edit',
		TKPE_PLUGIN_URL . 'admin/assets/js/admin-bulk-edit.js',
		array(
			'jquery',
			'tkpe-admin-products',
		),
		TKPE_VERSION,
		true
	);

	wp_localize_script(
		'tkpe-admin-products',
		'tkpeAdmin',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'tkpe_admin_nonce' ),
		)
	);
}

add_action( 'admin_enqueue_scripts', 'tkpe_enqueue_admin_assets' );


/**
 * Render TKPE admin page.
 *
 * @return void
 */
function tkpe_render_admin_page() {

	require TKPE_PLUGIN_PATH . 'admin/views/header.php';

	require TKPE_PLUGIN_PATH . 'admin/views/search&filter.php';

	require TKPE_PLUGIN_PATH . 'admin/views/bulk-tab-table.php';

	require TKPE_PLUGIN_PATH . 'admin/views/quick-tab-table.php';

	require TKPE_PLUGIN_PATH . 'admin/views/footer.php';
}