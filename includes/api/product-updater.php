<?php
/**
 * TKPE product updater API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Calculate a new price.
 *
 * @param string $current_price Current price.
 * @param string $method        Pricing method.
 * @param string $value         Pricing value.
 * @return string|WP_Error
 */
function tkpe_calculate_price( $current_price, $method, $value ) {

	$current_price = (float) $current_price;
	$value         = (float) $value;

	switch ( $method ) {

		case 'set':
			$new_price = $value;
			break;

		case 'increase_percentage':
			$new_price = $current_price + ( $current_price * $value / 100 );
			break;

		case 'decrease_percentage':
			$new_price = $current_price - ( $current_price * $value / 100 );
			break;

		case 'increase_fixed':
			$new_price = $current_price + $value;
			break;

		case 'decrease_fixed':
			$new_price = $current_price - $value;
			break;

		default:
			return new WP_Error(
				'invalid_pricing_method',
				__( 'Invalid pricing method.', 'tajirkendro-price-editor' )
			);
	}

	if ( $new_price < 0 ) {
		$new_price = 0;
	}

	return wc_format_decimal( $new_price );
}


/**
 * Update one product price.
 *
 * @param WC_Product $product Product.
 * @param array      $pricing Pricing configuration.
 * @return true|WP_Error
 */
function tkpe_update_product_price( $product, $pricing ) {

	$regular_method = isset( $pricing['regular']['method'] )
		? sanitize_key( $pricing['regular']['method'] )
		: '';

	$regular_value = isset( $pricing['regular']['value'] )
		? wc_format_decimal( $pricing['regular']['value'] )
		: '';

	$sale_method = isset( $pricing['sale']['method'] )
		? sanitize_key( $pricing['sale']['method'] )
		: '';

	$sale_value = isset( $pricing['sale']['value'] )
		? wc_format_decimal( $pricing['sale']['value'] )
		: '';

	/*
	 * Regular price.
	 */
	if ( '' !== $regular_method ) {

		$current_regular = $product->get_regular_price();

		$result = tkpe_calculate_price(
			$current_regular,
			$regular_method,
			$regular_value
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$product->set_regular_price( $result );
	}

	/*
	 * Sale price.
	 */
	if ( '' !== $sale_method ) {

		$current_sale = $product->get_sale_price();

		/*
		 * If there is no existing sale price, use the
		 * regular price as the calculation base.
		 */
		if ( '' === $current_sale ) {
			$current_sale = $product->get_regular_price();
		}

		$result = tkpe_calculate_price(
			$current_sale,
			$sale_method,
			$sale_value
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$product->set_sale_price( $result );
	}

	/*
	 * If sale price is greater than regular price,
	 * WooCommerce pricing becomes problematic.
	 *
	 * Leave validation to the final product state.
	 */
	if (
		'' !== $product->get_sale_price() &&
		'' !== $product->get_regular_price() &&
		(float) $product->get_sale_price() > (float) $product->get_regular_price()
	) {

		return new WP_Error(
			'invalid_sale_price',
			__( 'Sale price cannot be greater than regular price.', 'tajirkendro-price-editor' )
		);
	}

	$product->save();

	return true;
}


/**
 * Update a variable product's variations.
 *
 * @param WC_Product_Variable $product Product.
 * @param array               $variations Variation configurations.
 * @return true|WP_Error
 */
function tkpe_update_variable_product( $product, $variations ) {

	if ( ! is_array( $variations ) ) {

		return new WP_Error(
			'invalid_variations',
			__( 'Invalid variation data.', 'tajirkendro-price-editor' )
		);
	}

	foreach ( $variations as $variation_data ) {

		$variation_id = isset( $variation_data['id'] )
			? absint( $variation_data['id'] )
			: 0;

		if ( ! $variation_id ) {
			continue;
		}

		$variation = wc_get_product( $variation_id );

		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			continue;
		}

		/*
		 * Make sure the variation actually belongs
		 * to the selected variable product.
		 */
		if ( (int) $variation->get_parent_id() !== (int) $product->get_id() ) {
			continue;
		}

		$pricing = isset( $variation_data['pricing'] )
			? $variation_data['pricing']
			: array();

		$result = tkpe_update_product_price(
			$variation,
			$pricing
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}
	}

	/*
	 * Recalculate parent variable product prices.
	 */
	$product->save();

	return true;
}


/**
 * AJAX: Update product.
 *
 * @return void
 */
function tkpe_ajax_update_product() {

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

	/*
	 * Simple/non-variable product.
	 */
	if ( ! $product->is_type( 'variable' ) ) {

		$pricing = isset( $_POST['pricing'] )
			? wp_unslash( $_POST['pricing'] )
			: array();

		if ( ! is_array( $pricing ) ) {
			$pricing = array();
		}

		$result = tkpe_update_product_price(
			$product,
			$pricing
		);

		if ( is_wp_error( $result ) ) {

			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				),
				400
			);
		}
	}

	/*
	 * Variable product.
	 */
	if ( $product->is_type( 'variable' ) ) {

		$variations = isset( $_POST['variations'] )
			? wp_unslash( $_POST['variations'] )
			: array();

		if ( ! is_array( $variations ) ) {
			$variations = array();
		}

		$result = tkpe_update_variable_product(
			$product,
			$variations
		);

		if ( is_wp_error( $result ) ) {

			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				),
				400
			);
		}
	}

	/*
	 * Return the updated lightweight product data.
	 */
	wp_send_json_success(
		array(
			'message' => __( 'Product updated successfully.', 'tajirkendro-price-editor' ),
			'product' => tkpe_prepare_products(
				array( $product )
			),
		)
	);
}

add_action(
	'wp_ajax_tkpe_update_product',
	'tkpe_ajax_update_product'
);