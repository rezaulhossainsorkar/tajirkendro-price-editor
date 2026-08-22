<?php
/**
 * TKPE product deleter API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * AJAX: Delete product.
 *
 * @return void
 */
function tkpe_ajax_delete_product() {

	tkpe_verify_ajax_request();

	$product_id = isset( $_POST['product_id'] )
		? absint( $_POST['product_id'] )
		: 0;

	if ( ! $product_id ) {

		wp_send_json_error(
			array(
				'message' => __( 'Invalid product.', 'tajirkendro-price-editor' ),
			),
			400
		);
	}

	$product = wc_get_product( $product_id );

	if ( ! $product ) {

		wp_send_json_error(
			array(
				'message' => __( 'Product not found.', 'tajirkendro-price-editor' ),
			),
			404
		);
	}

	$deleted = $product->delete( true );

	if ( ! $deleted ) {

		wp_send_json_error(
			array(
				'message' => __( 'The product could not be deleted.', 'tajirkendro-price-editor' ),
			),
			500
		);
	}

	wp_send_json_success(
		array(
			'product_id' => $product_id,
			'message'     => __( 'Product deleted successfully.', 'tajirkendro-price-editor' ),
		)
	);
}

add_action(
	'wp_ajax_tkpe_delete_product',
	'tkpe_ajax_delete_product'
);