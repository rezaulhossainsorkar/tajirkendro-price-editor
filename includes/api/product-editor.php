<?php
/**
 * TKPE product editor API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Prepare variation data for the Quick Edit editor.
 *
 * @param WC_Product_Variation $variation Variation object.
 * @return array
 */
function tkpe_prepare_variation_editor_data( $variation ) {

	$attributes = array();

	foreach ( $variation->get_attributes() as $attribute_name => $attribute_value ) {

		$taxonomy = str_replace( 'attribute_', '', $attribute_name );

		$label = wc_attribute_label( $taxonomy );

		$value = $attribute_value;

		if ( taxonomy_exists( $taxonomy ) && $attribute_value ) {

			$term = get_term_by(
				'slug',
				$attribute_value,
				$taxonomy
			);

			if ( $term && ! is_wp_error( $term ) ) {
				$value = $term->name;
			}
		}

		$attributes[] = array(
			'name'  => $label,
			'value' => $value,
		);
	}

	return array(
		'id'             => $variation->get_id(),
		'attributes'     => $attributes,
		'regular_price'  => $variation->get_regular_price(),
		'sale_price'     => $variation->get_sale_price(),
		'price'          => $variation->get_price(),
		'stock_status'   => $variation->get_stock_status(),
		'stock_quantity' => $variation->get_stock_quantity(),
		'manage_stock'   => $variation->managing_stock(),
		'sku'            => $variation->get_sku(),
	);
}


/**
 * Prepare complete product editor data.
 *
 * This is intentionally separate from tkpe_prepare_products().
 *
 * The normal product table should remain lightweight.
 * Variation data is loaded only when the user opens Edit.
 *
 * @param WC_Product $product Product object.
 * @return array
 */
function tkpe_prepare_product_editor_data( $product ) {

	$data = array(
		'id'            => $product->get_id(),
		'name'          => $product->get_name(),
		'type'          => $product->get_type(),
		'sku'           => $product->get_sku(),
		'status'        => $product->get_status(),
		'regular_price' => $product->get_regular_price(),
		'sale_price'    => $product->get_sale_price(),
		'price'         => $product->get_price(),
	);

	/*
	 * Variable products require their variations.
	 */
	if ( $product->is_type( 'variable' ) ) {

		$variations = $product->get_children();

		$data['variations'] = array();

		foreach ( $variations as $variation_id ) {

			$variation = wc_get_product( $variation_id );

			if ( ! $variation ) {
				continue;
			}

			$data['variations'][] = tkpe_prepare_variation_editor_data(
				$variation
			);
		}
	}

	return $data;
}


/**
 * AJAX: Get product editor data.
 *
 * @return void
 */
function tkpe_ajax_get_product_editor() {

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

	wp_send_json_success(
		array(
			'product' => tkpe_prepare_product_editor_data( $product ),
		)
	);
}

add_action(
	'wp_ajax_tkpe_get_product_editor',
	'tkpe_ajax_get_product_editor'
);