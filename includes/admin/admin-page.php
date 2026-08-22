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
	'tkpe-admin',
	TKPE_PLUGIN_URL . 'admin/assets/css/admin.css',
	array(),
	TKPE_VERSION
	);

	wp_enqueue_style(
		'tkpe-admin-controls',
		TKPE_PLUGIN_URL . 'admin/assets/css/admin-controls.css',
		array( 'tkpe-admin' ),
		TKPE_VERSION
	);

	wp_enqueue_style(
		'tkpe-admin-tabs',
		TKPE_PLUGIN_URL . 'admin/assets/css/admin-tabs.css',
		array( 'tkpe-admin' ),
		TKPE_VERSION
	);

	wp_enqueue_style(
		'tkpe-admin-table',
		TKPE_PLUGIN_URL . 'admin/assets/css/admin-table.css',
		array( 'tkpe-admin' ),
		TKPE_VERSION
	);

	wp_enqueue_style(
		'tkpe-admin-bulk-edit',
		TKPE_PLUGIN_URL . 'admin/assets/css/admin-bulk-edit.css',
		array( 'tkpe-admin' ),
		TKPE_VERSION
	);

	wp_enqueue_style(
		'tkpe-admin-quick-edit',
		TKPE_PLUGIN_URL . 'admin/assets/css/admin-quick-edit.css',
		array( 'tkpe-admin' ),
		TKPE_VERSION
	);

	wp_enqueue_style(
		'tkpe-admin-responsive',
		TKPE_PLUGIN_URL . 'admin/assets/css/admin-responsive.css',
		array(
			'tkpe-admin-controls',
			'tkpe-admin-tabs',
			'tkpe-admin-table',
			'tkpe-admin-bulk-edit',
			'tkpe-admin-quick-edit',
		),
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