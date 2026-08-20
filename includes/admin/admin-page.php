<?php
/**
 * KTPE admin page.
 *
 * @package TajirKendro_Price_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Enqueue KTPE admin assets.
 *
 * @param string $hook_suffix Current admin page hook suffix.
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
		array(),
		TKPE_VERSION,
		true
	);

	wp_localize_script(
		'tkpe-admin-products',
		'tkpeAdmin',
		array(
			'restUrl' => esc_url_raw( rest_url( 'tkpe/v1/products' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'i18n'    => array(
				'loading'       => __( 'Loading products…', 'tajirkendro-price-editor' ),
				'noProducts'    => __( 'No products found.', 'tajirkendro-price-editor' ),
				'error'         => __( 'Unable to load products. Please try again.', 'tajirkendro-price-editor' ),
				'noSalePrice'   => __( 'No sale price', 'tajirkendro-price-editor' ),
				'previous'      => __( 'Previous', 'tajirkendro-price-editor' ),
				'next'          => __( 'Next', 'tajirkendro-price-editor' ),
				'page'          => __( 'Page', 'tajirkendro-price-editor' ),
				'of'            => __( 'of', 'tajirkendro-price-editor' ),
				'products'      => __( 'products', 'tajirkendro-price-editor' ),
				'regular'       => __( 'Regular', 'tajirkendro-price-editor' ),
				'sale'          => __( 'Sale', 'tajirkendro-price-editor' ),
				'uncategorized' => __( 'Uncategorized', 'tajirkendro-price-editor' ),
			),
		)
	);

}

add_action( 'admin_enqueue_scripts', 'tkpe_enqueue_admin_assets' );


/**
 * Get product categories for the filter.
 *
 * @return WP_Term[]
 */
function tkpe_get_product_categories() {

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $categories ) ) {
		return array();
	}

	return $categories;

}


/**
 * Render the KTPE admin page.
 *
 * @return void
 */
function tkpe_render_admin_page() {

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to access this page.', 'tajirkendro-price-editor' )
		);
	}

	$categories = tkpe_get_product_categories();

	require TKPE_PLUGIN_PATH . 'admin/views/header.php';
	require TKPE_PLUGIN_PATH . 'admin/views/filters.php';
	require TKPE_PLUGIN_PATH . 'admin/views/product-table.php';
	require TKPE_PLUGIN_PATH . 'admin/views/footer.php';

}